<?php
/**
 * IXEA EROS - Generator: Eloquent Models
 * 
 * ⚠️ NOTA DE ARQUITECTURA / DEPLOYMENT:
 * Este script NO forma parte de la lógica en tiempo de ejecución del servidor web.
 * Es un script utilitario que se ejecuta localmente durante la fase de BUILD (build.php)
 * para inspeccionar la base de datos local y regenerar los modelos PHP en /models.
 * 
 * Por esta razón, gestiona su propia conexión directa a localhost (127.0.0.1)
 * en lugar de depender de la configuración del entorno Docker/Servidor.
 */

require_once __DIR__ . '/app/vendor/autoload.php';

use App\Database\Connection;
use Illuminate\Database\Capsule\Manager as Capsule;

Connection::boot();

/**
 * Elimina un directorio y todo su contenido de forma recursiva
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}

/**
 * Función helper para singularizar nombres de clases
 */
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

    // Reset total de la carpeta /models
    deleteDirectory($modelsDir);
    mkdir($modelsDir, 0777, true);

    // 1. Obtener todas las tablas de la base de datos
    $tables = Capsule::select("
        SELECT TABLE_NAME 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
    ", [$dbName]);

    $count = 0;

    foreach ($tables as $tableObj) {
        $tableName = $tableObj->TABLE_NAME;
        if ($tableName === 'migrations') continue;

        $className = tableNameToClassName($tableName);

        // 2. Comprobar si existen exactamente created_at y updated_at
        $columns = Capsule::select("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ", [$dbName, $tableName]);

        $columnNames = array_column($columns, 'COLUMN_NAME');
        $hasTimestamps = in_array('created_at', $columnNames) && in_array('updated_at', $columnNames);

        // 3. Obtener relaciones de Foreign Keys
        $foreignKeys = Capsule::select("
            SELECT 
                COLUMN_NAME, 
                REFERENCED_TABLE_NAME, 
                REFERENCED_COLUMN_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = ? 
              AND TABLE_NAME = ? 
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$dbName, $tableName]);

        // 4. Construir el archivo de la clase Eloquent
        $code  = "<?php\n\n";
        $code .= "namespace App\\Models;\n\n";
        $code .= "use Illuminate\\Database\\Eloquent\\Model;\n\n";
        $code .= "class {$className} extends Model\n{\n";
        $code .= "    protected \$table = '{$tableName}';\n";
        $code .= "    public \$timestamps = " . ($hasTimestamps ? 'true' : 'false') . ";\n";
        $code .= "    protected \$guarded = [];\n";

        // Generar relaciones BelongsTo automáticamente
        if (!empty($foreignKeys)) {
            $code .= "\n    // --- Relaciones BelongsTo ---\n";
            foreach ($foreignKeys as $fk) {
                $relatedClass = tableNameToClassName($fk->REFERENCED_TABLE_NAME);
                $methodName   = columnToMethodName($fk->COLUMN_NAME);
                $fkColumn     = $fk->COLUMN_NAME;

                $code .= "\n    public function {$methodName}()\n";
                $code .= "    {\n";
                $code .= "        return \$this->belongsTo({$relatedClass}::class, '{$fkColumn}');\n";
                $code .= "    }\n";
            }
        }

        $code .= "}\n";

        file_put_contents("{$modelsDir}/{$className}.php", $code);
        echo "✅ Generado: models/{$className}.php\n";
        $count++;
    }

    echo "\n🎉 ¡Listo! Se limpió la carpeta y se regeneraron {$count} modelos.\n";

} catch (\Exception $e) {
    echo "❌ Error al generar modelos: " . $e->getMessage() . "\n";
}