<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

$output = [];
foreach ($tables as $t) {
    $tableName = $t->name;
    // Skip internal Laravel tables
    if (in_array($tableName, ['migrations', 'failed_jobs', 'personal_access_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'])) {
        continue;
    }
    
    $output[] = "### Table: {$tableName}";
    
    $columns = DB::select("PRAGMA table_info({$tableName})");
    $output[] = "Columns:";
    foreach ($columns as $c) {
        $pk = $c->pk ? " (PRIMARY KEY)" : "";
        $notnull = $c->notnull ? " NOT NULL" : "";
        $output[] = "- {$c->name} [{$c->type}]{$notnull}{$pk}";
    }
    
    // Get foreign keys
    $fks = DB::select("PRAGMA foreign_key_list({$tableName})");
    if (!empty($fks)) {
        $output[] = "Relations (Foreign Keys):";
        foreach ($fks as $fk) {
            $output[] = "- {$fk->from} -> {$fk->table}({$fk->to})";
        }
    }
    $output[] = "";
}

echo implode("\n", $output);
