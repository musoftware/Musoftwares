<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SchemaExportCommand extends Command
{
    protected $signature = 'schema:export {--raw : Output the raw base64 string without formatting} {--out= : File to write the base64 output to}';
    protected $description = 'Export the local database schema to a JSON structure for synchronization';

    public function handle()
    {
        $database = DB::connection()->getDatabaseName();

        $columns = DB::select('SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, COLUMN_KEY 
                               FROM information_schema.columns 
                               WHERE table_schema = ?', [$database]);

        $schema = [];
        foreach ($columns as $col) {
            $table = $col->TABLE_NAME;
            if (!isset($schema[$table])) {
                $schema[$table] = [];
            }
            $schema[$table][$col->COLUMN_NAME] = [
                'type' => $col->COLUMN_TYPE,
                'nullable' => $col->IS_NULLABLE,
                'default' => $col->COLUMN_DEFAULT,
                'extra' => $col->EXTRA,
                'key' => $col->COLUMN_KEY
            ];
        }

        $json = json_encode($schema);
        $base64 = base64_encode($json);

        if ($file = $this->option('out')) {
            file_put_contents($file, $base64);
            $this->info("Schema exported to {$file}");
        } elseif ($this->option('raw')) {
            $this->output->write($base64);
        } else {
            $this->info("Schema successfully exported.");
            $this->line($base64);
        }

        return 0;
    }
}
