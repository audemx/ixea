<?php
/**
 * IXEA EROS - Generator: Eloquent Models (Con relaciones automáticas completas y sin duplicados)
 */

require_once __DIR__ . '/app/vendor/autoload.php';

use App\Database\Connection;
use Illuminate\Database\Capsule\Manager as Capsule;

Connection::boot();

function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rmdir($dir);
}

function singularize($word) {
    $rules = [
        '/(quiz)zes$/i' => '$1',
        '/(matr|vert|ind)ices$/i' => '$1ex',
        '/(alias|status)es$/i' => '$1',
        '/(octop|vir)uses$/i' => '$1us',
        '/(cris|ax|test)es$/i' => '$1is',
        '/(shoe)s$/i' => '$1',
        '/(o)es$/i' => '$1',
        '/(bus)es$/i' => '$1',
        '/([m|l])ice$/i' => '$1ouse',
        '/(x|ch|ss|sh)es$/i' => '$1',
        '/movies$/i' => 'movie',
        '/series$/i' => 'series',
        '/([^aeiouy]|qu)ies$/i' => '$1y',
        '/([lr])ves$/i' => '$1f',
        '/(tive)s$/i' => '$1',
        '/(hive)s$/i' => '$1',
        '/([^f])ves$/i' => '$1fe',
        '/(^analy)ses$/i' => '$1sis',
        '/((a)naly|(b)a|(d)iag|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '$1$2sis',
        '/([ti])a$/i' => '$1um',
        '/(n)ews$/i' => '$1ews',
        '/(s)tatus$/i' => '$1tatus',
        '/s$/i' => ''
    ];
    foreach ($rules as $pattern => $replacement) {
        if (preg_match($pattern, $word)) {
            return preg_replace($pattern, $replacement, $word);
        }
    }
    return $word;
}

function pluralize($word) {
    return $word . 's';
}

function columnToMethodName($columnName) {
    $name = preg_replace('/_id$/', '', $columnName);
    return str_replace(' ', '', lcfirst(ucwords(str_replace('_', ' ', $name))));
}

function tableNameToClassName($tableName) {
    $parts = explode('_', $tableName);
    $singularParts = array_map(function($part, $index) use ($parts) {
        return ($index === count($parts) - 1) ? singularize($part) : $part;
    }, $parts, array_keys($parts));
    
    return str_replace(' ', '', ucwords(implode(' ', $singularParts)));
}

try {
    $dbName = Capsule::connection()->getDatabaseName();
    $modelsDir = __DIR__ . '/app/db/models';

    deleteDirectory($modelsDir);
    mkdir($modelsDir, 0777, true);

    $tables = Capsule::select("
        SELECT TABLE_NAME 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
    ", [$dbName]);

    // 1. Mapeo global de todas las Foreign Keys
    $allForeignKeys = Capsule::select("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME, 
            REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = ? 
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ", [$dbName]);

    $fkByTable = [];
    $referencedByTable = [];
    foreach ($allForeignKeys as $fk) {
        $fkByTable[$fk->TABLE_NAME][] = $fk;
        $referencedByTable[$fk->REFERENCED_TABLE_NAME][] = $fk;
    }

    $pivotTables = [];
    foreach ($fkByTable as $tName => $fks) {
        if (count($fks) === 2) {
            $pivotTables[$tName] = $fks;
        }
    }

    $count = 0;

    foreach ($tables as $tableObj) {
        $tableName = $tableObj->TABLE_NAME;
        if ($tableName === 'migrations') continue;

        $className = tableNameToClassName($tableName);
        $isPivot = isset($pivotTables[$tableName]);

        $columns = Capsule::select("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ", [$dbName, $tableName]);

        $columnNames = array_column($columns, 'COLUMN_NAME');
        $hasTimestamps = in_array('created_at', $columnNames) && in_array('updated_at', $columnNames);

        $code  = "<?php\n\n";
        $code .= "namespace App\\Models;\n\n";
        $code .= "use Illuminate\\Database\\Eloquent\\Model;\n\n";
        $code .= "class {$className} extends Model\n{\n";
        $code .= "    protected \$table = '{$tableName}';\n";
        $code .= "    public \$timestamps = " . ($hasTimestamps ? 'true' : 'false') . ";\n";
        $code .= "    protected \$guarded = [];\n";

        $hasRelations = false;

        // A. Relaciones BelongsTo
        if (!empty($fkByTable[$tableName])) {
            $hasRelations = true;
            $code .= "\n    // --- Relaciones BelongsTo ---\n";
            foreach ($fkByTable[$tableName] as $fk) {
                $relatedClass = tableNameToClassName($fk->REFERENCED_TABLE_NAME);
                $methodName   = columnToMethodName($fk->COLUMN_NAME);
                $code .= "\n    public function {$methodName}()\n";
                $code .= "    {\n";
                $code .= "        return \$this->belongsTo({$relatedClass}::class, '{$fk->COLUMN_NAME}');\n";
                $code .= "    }\n";
            }
        }

        // B. Relaciones HasMany (Sin duplicación de nombres de métodos)
        if (!$isPivot && !empty($referencedByTable[$tableName])) {
            if (!$hasRelations) $code .= "\n";
            $hasRelations = true;
            $code .= "    // --- Relaciones HasMany ---\n";

            // Contar cuántas FKs apuntan desde la misma tabla hacia esta tabla
            $tableReferenceCounts = [];
            foreach ($referencedByTable[$tableName] as $ref) {
                if (isset($pivotTables[$ref->TABLE_NAME])) continue;
                $tableReferenceCounts[$ref->TABLE_NAME] = ($tableReferenceCounts[$ref->TABLE_NAME] ?? 0) + 1;
            }

            $usedMethods = [];

            foreach ($referencedByTable[$tableName] as $ref) {
                if (isset($pivotTables[$ref->TABLE_NAME])) continue;

                $relatedClass = tableNameToClassName($ref->TABLE_NAME);
                $baseMethodName = lcfirst(pluralize($relatedClass));

                // Si la tabla origen tiene más de 1 FK hacia esta tabla, desambiguamos con la columna
                if ($tableReferenceCounts[$ref->TABLE_NAME] > 1) {
                    $prefix = columnToMethodName($ref->COLUMN_NAME);
                    $methodName = $prefix . ucfirst($baseMethodName);
                } else {
                    $methodName = $baseMethodName;
                }

                // Evitar duplicados exactos si existen FKs idénticas
                if (in_array($methodName, $usedMethods)) {
                    continue;
                }
                $usedMethods[] = $methodName;

                $code .= "\n    public function {$methodName}()\n";
                $code .= "    {\n";
                $code .= "        return \$this->hasMany({$relatedClass}::class, '{$ref->COLUMN_NAME}');\n";
                $code .= "    }\n";
            }
        }

        // C. Relaciones BelongsToMany
        $belongsToManyRelations = [];
        foreach ($pivotTables as $pName => $pFks) {
            $isPart = false;
            $otherFk = null;
            $myFk = null;
            foreach ($pFks as $pTblFk) {
                if ($pTblFk->REFERENCED_TABLE_NAME === $tableName) {
                    $isPart = true;
                    $myFk = $pTblFk;
                } else {
                    $otherFk = $pTblFk;
                }
            }
            if ($isPart && $otherFk) {
                $relatedClass = tableNameToClassName($otherFk->REFERENCED_TABLE_NAME);
                $methodName   = lcfirst(pluralize($relatedClass));
                
                if (!isset($belongsToManyRelations[$methodName])) {
                    $belongsToManyRelations[$methodName] = [
                        'class' => $relatedClass,
                        'pivot' => $pName,
                        'foreignPivotKey' => $myFk->COLUMN_NAME,
                        'relatedPivotKey' => $otherFk->COLUMN_NAME
                    ];
                }
            }
        }

        if (!empty($belongsToManyRelations)) {
            if (!$hasRelations) $code .= "\n";
            $code .= "    // --- Relaciones BelongsToMany ---\n";
            foreach ($belongsToManyRelations as $mName => $rel) {
                $code .= "\n    public function {$mName}()\n";
                $code .= "    {\n";
                $code .= "        return \$this->belongsToMany({$rel['class']}::class, '{$rel['pivot']}', '{$rel['foreignPivotKey']}', '{$rel['relatedPivotKey']}');\n";
                $code .= "    }\n";
            }
        }

        $code .= "}\n";

        file_put_contents("{$modelsDir}/{$className}.php", $code);
        echo "✅ Generado: models/{$className}.php\n";
        $count++;
    }

    echo "\n🎉 ¡Listo! Se regeneraron {$count} modelos sin nombres de métodos duplicados.\n";

} catch (\Exception $e) {
    echo "❌ Error al generar modelos: " . $e->getMessage() . "\n";
}