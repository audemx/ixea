<?php
// Corre desde contenedor.
// Generador de clases Enum/Constantes a partir de tablas de sistema.
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use Illuminate\Database\Capsule\Manager as Capsule;

function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rmdir($dir);
}

function sanitizeConstantName(string $string): string 
{
    // Limpia espacios y caracteres especiales a formato UPPER_SNAKE_CASE
    $clean = preg_replace('/[^a-zA-Z0-9_]/', '_', trim($string));
    return strtoupper($clean);
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

function tableNameToClassName($tableName) {
    $parts = explode('_', $tableName);
    $singularParts = array_map(function($part, $index) use ($parts) {
        return ($index === count($parts) - 1) ? singularize($part) : $part;
    }, $parts, array_keys($parts));
    
    return str_replace(' ', '', ucwords(implode(' ', $singularParts)));
}

try {
    // 0. Inicializar Eloquent con la configuración de la BD
    Connection::boot();

    // 1. Obtener la instancia PDO
    $pdo = Capsule::connection()->getPdo();
    
    // 2. Obtener el nombre de la base de datos conectada
    $dbName = Capsule::connection()->getDatabaseName();

    $enumsDir = __DIR__ . '/../classes/enums';

    deleteDirectory($enumsDir);
    mkdir($enumsDir, 0777, true);

    // 1. Obtener las tablas marcadas como enum en sys_tables
    $enumTables = Capsule::table('sys_tables')
        ->where('is_enum', 1)
        ->get(['name']);

    if ($enumTables->isEmpty()) {
        exit("⚠️ No se encontraron tablas marcadas con is_enum = 1 en 'sys_tables'.\n");
    }

    $generatedCount = 0;
    $nameColumn = 'name'; // Nombre de columna para usar como valor del Enum.
    foreach ($enumTables as $tableConfig) {
        $tableName  = $tableConfig->name;
        
        // Validar si la tabla existe en la base de datos
        $tableExists = Capsule::select("
            SELECT TABLE_NAME 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ", [$dbName, $tableName]);

        if (empty($tableExists)) {
            echo "⚠️ La tabla '{$tableName}' no existe en la BD. Omitiendo...\n";
            continue;
        }

        $className = tableNameToClassName($tableName);
        
        // Obtener los registros de la tabla de catálogo
        $rows = Capsule::table($tableName)->select('id', $nameColumn)->get();

        $code  = "<?php\n\n";
        $code .= "namespace App\Enums;\n\n";
        $code .= "/**\n";
        $code .= " * Clase autogenerada desde la tabla catálogo '{$tableName}'\n";
        $code .= " */\n";
        $code .= "class {$className}\n{\n";

        // Generar Constantes
        foreach ($rows as $row) {
            $id = $row->id;
            $nameValue = $row->{$nameColumn};
            $constName = sanitizeConstantName($nameValue);

            $code .= "    public const {$constName} = {$id};\n";
        }

        // Generar método helper all()
        $code .= "\n    /**\n     * Devuelve el catálogo completo en array [id => nombre]\n     */\n";
        $code .= "    public static function all(): array\n    {\n";
        $code .= "        return [\n";
        foreach ($rows as $row) {
            $id = $row->id;
            $nameValue = addslashes($row->{$nameColumn});
            $constName = sanitizeConstantName($nameValue);
            $code .= "            self::{$constName} => '{$nameValue}',\n";
        }
        $code .= "        ];\n";
        $code .= "    }\n";

        $code .= "}\n";

        file_put_contents("{$enumsDir}/{$className}.php", $code);
        echo "✅ Clase Enum generada: classes/{$className}.php\n";
        $generatedCount++;
    }

    echo "\n🎉 ¡Proceso finalizado! Se crearon {$generatedCount} clases en /classes.\n";

} catch (\Exception $e) {
    echo "❌ Error al generar las clases Enum: " . $e->getMessage() . "\n";
}