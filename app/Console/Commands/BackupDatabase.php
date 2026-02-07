<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Carbon;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--compress : Compress the backup file with gzip}';

    protected $description = 'Backup the database to a SQL file';

    public function handle(): int
    {
        $dbConnection = config('database.default');
        $dbConfig = config("database.connections.{$dbConnection}");

        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $database = is_string($dbConfig['database']) ? basename($dbConfig['database']) : $dbConnection;
        $filename = "backup_{$database}_{$timestamp}.sql";
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $this->info("Starting database backup...");
        $this->info("Database: {$database}");
        $this->info("Connection: {$dbConnection}");

        $result = match ($dbConnection) {
            'mysql', 'mariadb' => $this->backupMysql($dbConfig, $filePath),
            'pgsql' => $this->backupPostgres($dbConfig, $filePath),
            'sqlite' => $this->backupSqlite($dbConfig, $filePath),
            default => $this->unsupportedDriver("Unsupported database driver: {$dbConnection}"),
        };

        if ($result === self::FAILURE) {
            return self::FAILURE;
        }

        if ($this->option('compress') && file_exists($filePath)) {
            $this->info("Compressing backup...");
            $gzPath = $filePath . '.gz';
            $fp = fopen($filePath, 'rb');
            $gz = gzopen($gzPath, 'wb9');

            while (! feof($fp)) {
                gzwrite($gz, fread($fp, 524288));
            }

            gzclose($gz);
            fclose($fp);
            unlink($filePath);
            $filePath = $gzPath;
            $filename .= '.gz';
        }

        $size = $this->formatBytes(filesize($filePath));
        $this->info("Backup completed successfully!");
        $this->info("File: {$filename}");
        $this->info("Size: {$size}");
        $this->info("Path: {$filePath}");

        $this->cleanOldBackups($backupDir);

        return self::SUCCESS;
    }

    protected function backupMysql(array $config, string $filePath): int
    {
        $command = [
            'mysqldump',
            '--host=' . ($config['host'] ?? '127.0.0.1'),
            '--port=' . ($config['port'] ?? '3306'),
            '--user=' . ($config['username'] ?? 'root'),
            '--skip-lock-tables',
            '--routines',
            '--triggers',
            '--result-file=' . $filePath,
            $config['database'],
        ];

        if (! empty($config['password'])) {
            $command[] = '--password=' . $config['password'];
        }

        $result = Process::run(implode(' ', $command));

        if ($result->failed()) {
            $this->error("MySQL backup failed: " . $result->errorOutput());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function backupPostgres(array $config, string $filePath): int
    {
        $env = [];
        if (! empty($config['password'])) {
            $env['PGPASSWORD'] = $config['password'];
        }

        $command = sprintf(
            'pg_dump --host=%s --port=%s --username=%s --no-password --format=plain --file=%s %s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? '5432',
            $config['username'] ?? 'postgres',
            $filePath,
            $config['database']
        );

        $result = Process::env($env)->run($command);

        if ($result->failed()) {
            $this->error("PostgreSQL backup failed: " . $result->errorOutput());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function backupSqlite(array $config, string $filePath): int
    {
        $dbPath = $config['database'];

        if (! file_exists($dbPath)) {
            $this->error("SQLite database file not found: {$dbPath}");
            return self::FAILURE;
        }

        copy($dbPath, $filePath);

        return self::SUCCESS;
    }

    protected function cleanOldBackups(string $backupDir, int $keepDays = 30): void
    {
        $files = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_*');
        $threshold = Carbon::now()->subDays($keepDays)->timestamp;
        $deleted = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("Cleaned up {$deleted} old backup(s) older than {$keepDays} days.");
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    protected function unsupportedDriver(string $message): int
    {
        $this->error($message);
        return self::FAILURE;
    }
}
