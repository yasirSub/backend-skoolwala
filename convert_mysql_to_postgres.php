<?php
/**
 * MySQL to PostgreSQL Converter
 * Converts MySQL SQL dump to PostgreSQL format
 */

function convertMySQLToPostgreSQL($mysqlFile, $outputFile) {
    $content = file_get_contents($mysqlFile);
    
    // Remove MySQL-specific comments and commands
    $content = preg_replace('/^--.*$/m', '', $content);
    $content = preg_replace('/^\/\*.*?\*\/;?$/ms', '', $content);
    $content = preg_replace('/^SET.*$/m', '', $content);
    $content = preg_replace('/^LOCK TABLES.*$/m', '', $content);
    $content = preg_replace('/^UNLOCK TABLES.*$/m', '', $content);
    
    // Convert MySQL data types to PostgreSQL
    $conversions = [
        // Data types
        '/\bint\(\d+\)\s+/i' => 'INTEGER ',
        '/\bbigint\(\d+\)\s+/i' => 'BIGINT ',
        '/\bsmallint\(\d+\)\s+/i' => 'SMALLINT ',
        '/\btinyint\(\d+\)\s+/i' => 'SMALLINT ',
        '/\bmediumint\(\d+\)\s+/i' => 'INTEGER ',
        '/\bvarchar\(\d+\)\s+/i' => 'VARCHAR($1) ',
        '/\bchar\(\d+\)\s+/i' => 'CHAR($1) ',
        '/\btext\s+/i' => 'TEXT ',
        '/\blongtext\s+/i' => 'TEXT ',
        '/\bmediumtext\s+/i' => 'TEXT ',
        '/\btinytext\s+/i' => 'TEXT ',
        '/\bblob\s+/i' => 'BYTEA ',
        '/\blongblob\s+/i' => 'BYTEA ',
        '/\bmediumblob\s+/i' => 'BYTEA ',
        '/\btinyblob\s+/i' => 'BYTEA ',
        '/\bdatetime\s+/i' => 'TIMESTAMP ',
        '/\btimestamp\s+/i' => 'TIMESTAMP ',
        '/\bdate\s+/i' => 'DATE ',
        '/\btime\s+/i' => 'TIME ',
        '/\byear\(\d+\)\s+/i' => 'INTEGER ',
        '/\bdecimal\(\d+,\d+\)\s+/i' => 'DECIMAL($1,$2) ',
        '/\bfloat\(\d+,\d+\)\s+/i' => 'REAL ',
        '/\bdouble\(\d+,\d+\)\s+/i' => 'DOUBLE PRECISION ',
        '/\bbool\s+/i' => 'BOOLEAN ',
        '/\bboolean\s+/i' => 'BOOLEAN ',
        
        // Auto increment
        '/AUTO_INCREMENT/i' => 'SERIAL',
        
        // Default values
        '/DEFAULT CURRENT_TIMESTAMP/i' => 'DEFAULT CURRENT_TIMESTAMP',
        '/DEFAULT \'0000-00-00 00:00:00\'/i' => 'DEFAULT NULL',
        '/DEFAULT \'0000-00-00\'/i' => 'DEFAULT NULL',
        
        // Engine and charset
        '/ENGINE=\w+/i' => '',
        '/CHARSET=\w+/i' => '',
        '/COLLATE=\w+/i' => '',
        
        // Backticks
        '/`([^`]+)`/' => '"$1"',
        
        // Single quotes in strings
        "/'([^']*)'/" => "'$1'",
    ];
    
    foreach ($conversions as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // Remove empty lines and clean up
    $content = preg_replace('/^\s*$/m', '', $content);
    $content = preg_replace('/\n\s*\n/', "\n", $content);
    
    // Add PostgreSQL header
    $header = "-- PostgreSQL converted from MySQL\n";
    $header .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    file_put_contents($outputFile, $header . $content);
    
    return true;
}

// Usage example
if (isset($argv[1]) && isset($argv[2])) {
    $mysqlFile = $argv[1];
    $postgresFile = $argv[2];
    
    if (file_exists($mysqlFile)) {
        convertMySQLToPostgreSQL($mysqlFile, $postgresFile);
        echo "✅ Conversion complete! Output saved to: $postgresFile\n";
    } else {
        echo "❌ MySQL file not found: $mysqlFile\n";
    }
} else {
    echo "Usage: php convert_mysql_to_postgres.php input.sql output.sql\n";
}
