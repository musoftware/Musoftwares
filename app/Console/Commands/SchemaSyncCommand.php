<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SchemaSyncCommand extends Command
{
    protected $signature = 'schema:sync {payload? : The base64 encoded JSON schema} {--stdin : Read payload from standard input}';
    protected $description = 'Synchronize the remote database schema with the source schema by safely adding missing columns/tables.';

    public function handle()
    {
        if ($this->option('stdin')) {
            $payload = trim(file_get_contents('php://stdin'));
        } else {
            $payload = $this->argument('payload');
        }

        if (empty($payload)) {
            $this->error("No payload provided.");
            return 1;
        }

        $json = base64_decode($payload);
        if (!$json) {
            $this->error("Invalid base64 payload.");
            return 1;
        }

        $sourceSchema = json_decode($json, true);
        if (!$sourceSchema || !is_array($sourceSchema)) {
            $this->error("Failed to decode JSON schema.");
            return 1;
        }

        $database = DB::connection()->getDatabaseName();
        $this->info("Target database: " . $database);

        $columns = DB::select('SELECT TABLE_NAME, COLUMN_NAME 
                               FROM information_schema.columns 
                               WHERE table_schema = ?', [$database]);

        $targetSchema = [];
        foreach ($columns as $col) {
            $targetSchema[$col->TABLE_NAME][] = $col->COLUMN_NAME;
        }

        try {
            foreach ($sourceSchema as $tableName => $sourceColumns) {
                // If table is missing completely
                if (!isset($targetSchema[$tableName])) {
                    $this->warn("Table '{$tableName}' is entirely missing. Creating it...");
                    
                    $colDefs = [];
                    foreach ($sourceColumns as $colName => $colDef) {
                        $def = "`{$colName}` {$colDef['type']}";
                        if ($colDef['nullable'] === 'NO') {
                            $def .= " NOT NULL";
                        }
                        if ($colDef['default'] !== null) {
                            if (is_numeric($colDef['default']) || in_array(strtoupper($colDef['default']), ['CURRENT_TIMESTAMP', 'NULL', 'CURRENT_TIMESTAMP()'])) {
                                $def .= " DEFAULT {$colDef['default']}";
                            } else {
                                $defaultVal = $colDef['default'];
                                if (str_starts_with($defaultVal, "'") && str_ends_with($defaultVal, "'")) {
                                    $def .= " DEFAULT {$defaultVal}";
                                } else {
                                    $def .= " DEFAULT '{$defaultVal}'";
                                }
                            }
                        }
                        if (!empty($colDef['extra'])) {
                            $def .= " {$colDef['extra']}";
                        }
                        if ($colDef['key'] === 'PRI') {
                            $def .= " PRIMARY KEY";
                        }
                        $colDefs[] = $def;
                    }
                    
                    $createSql = "CREATE TABLE `{$tableName}` (\n  " . implode(",\n  ", $colDefs) . "\n)";
                    $this->line("Executing: " . $createSql);
                    DB::statement($createSql);
                    $this->info("Created table {$tableName}");
                    continue;
                }

                // Table exists, check for missing columns
                $existingCols = $targetSchema[$tableName];
                foreach ($sourceColumns as $colName => $colDef) {
                    if (!in_array($colName, $existingCols)) {
                        $this->warn("Missing column '{$colName}' in table '{$tableName}'. Adding it...");
                        
                        $def = "ALTER TABLE `{$tableName}` ADD COLUMN `{$colName}` {$colDef['type']}";
                        if ($colDef['nullable'] === 'NO') {
                            $def .= " NOT NULL";
                        }
                        if ($colDef['default'] !== null) {
                            if (is_numeric($colDef['default']) || in_array(strtoupper($colDef['default']), ['CURRENT_TIMESTAMP', 'NULL', 'CURRENT_TIMESTAMP()'])) {
                                $def .= " DEFAULT {$colDef['default']}";
                            } else {
                                $defaultVal = $colDef['default'];
                                if (str_starts_with($defaultVal, "'") && str_ends_with($defaultVal, "'")) {
                                    $def .= " DEFAULT {$defaultVal}";
                                } else {
                                    $def .= " DEFAULT '{$defaultVal}'";
                                }
                            }
                        }
                        if (!empty($colDef['extra'])) {
                            $def .= " {$colDef['extra']}";
                        }
                        
                        $this->line("Executing: " . $def);
                        DB::statement($def);
                        $this->info("Added column {$colName} to {$tableName}");
                    }
                }
            }
            $this->info("Schema sync completed successfully!");
        } catch (\Exception $e) {
            $this->error("Sync failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
