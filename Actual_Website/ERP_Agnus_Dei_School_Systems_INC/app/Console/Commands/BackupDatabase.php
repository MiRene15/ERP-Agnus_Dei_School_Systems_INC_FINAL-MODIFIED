<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Create a database backup (MySQL via PDO or PostgreSQL via pg_dump)';

    public function handle(): int
    {
        $driver = DB::connection()->getDriverName();
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        try {
            $result = $driver === 'pgsql'
                ? $this->backupPgsql($backupDir)
                : $this->backupMysql($backupDir);

            $this->prune($backupDir);

            return $result;
        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function backupMysql(string $backupDir): int
    {
        $dbName = config('database.connections.mysql.database');
        $filename = 'backup_' . $dbName . '_' . now()->format('Y-m-d_His') . '.sql';
        $filepath = $backupDir . '/' . $filename;

        $this->info("Starting MySQL backup of database: {$dbName}");

        $pdo = DB::connection()->getPdo();
        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_NUM);
        $tables = array_column($tables, 0);

        $output = "-- Agnus Dei ERP Database Backup\n";
        $output .= "-- Date: " . now()->format('Y-m-d H:i:s') . "\n";
        $output .= "-- Database: {$dbName}\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        $totalRows = 0;

        foreach ($tables as $table) {
            $this->line("  Dumping table: {$table}");

            // CREATE TABLE
            $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
            $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $output .= $createTable[1] . ";\n\n";

            // Data
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_NUM);
            if (!empty($rows)) {
                // Get column count for batching
                $colCount = count($rows[0]);
                $placeholders = '(' . implode(',', array_fill(0, $colCount, '?')) . ')';

                // Get column names
                $cols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(\PDO::FETCH_NUM);
                $colNames = array_column($cols, 0);
                $colList = '`' . implode('`, `', $colNames) . '`';

                // Batch inserts (500 rows per statement)
                $batches = array_chunk($rows, 500);
                foreach ($batches as $batch) {
                    $values = [];
                    $bindings = [];
                    foreach ($batch as $row) {
                        $values[] = $placeholders;
                        foreach ($row as $val) {
                            $bindings[] = $val;
                        }
                    }
                    $output .= "INSERT INTO `{$table}` ({$colList}) VALUES " . implode(',\n', $values) . ";\n";
                    $totalRows += count($batch);
                }
                $output .= "\n";
            }
        }

        $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        file_put_contents($filepath, $output);

        $size = number_format(filesize($filepath) / 1024, 1);
        $this->info("Backup complete: {$filename} ({$size} KB, {$totalRows} rows across " . count($tables) . " tables)");

        return Command::SUCCESS;
    }

    protected function backupPgsql(string $backupDir): int
    {
        $config = config('database.connections.pgsql');
        $dbName = $config['database'];
        $filename = 'backup_' . $dbName . '_' . now()->format('Y-m-d_His') . '.sql';
        $filepath = $backupDir . '/' . $filename;

        $this->info("Starting PostgreSQL backup of database: {$dbName}");

        $command = sprintf(
            'pg_dump -h %s -p %s -U %s -d %s --no-owner --no-privileges --clean --if-exists > %s',
            escapeshellarg($config['host']),
            escapeshellarg((string) $config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($dbName),
            escapeshellarg($filepath)
        );

        $output = [];
        $exitCode = 0;
        $env = [
            'PGPASSWORD' => (string) ($config['password'] ?? ''),
            'PGSSLMODE' => (string) ($config['sslmode'] ?? 'prefer'),
        ];

        exec($command, $output, $exitCode, $env);

        if ($exitCode !== 0) {
            $this->error("pg_dump exited with code {$exitCode}: " . implode("\n", $output));
            return Command::FAILURE;
        }

        if (!file_exists($filepath)) {
            $this->error("pg_dump produced no file.");
            return Command::FAILURE;
        }

        $size = number_format(filesize($filepath) / 1024, 1);
        $this->info("Backup complete: {$filename} ({$size} KB)");

        return Command::SUCCESS;
    }

    protected function prune(string $backupDir): void
    {
        $backups = glob($backupDir . '/backup_*.sql');
        rsort($backups);
        if (count($backups) > 30) {
            $toDelete = array_slice($backups, 30);
            foreach ($toDelete as $old) {
                unlink($old);
            }
            $this->info("Pruned " . count($toDelete) . " old backup(s).");
        }
    }
}
