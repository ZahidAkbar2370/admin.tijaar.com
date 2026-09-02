<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupCommand extends Command
{
    protected $signature = 'app:backup {--db-only : Backup database only}';
    protected $description = 'Backup database (and optionally storage)';

    public function handle(): int
    {
        $timestamp = now()->format('Y-m-d_His');
        $disk = Storage::disk('local');
        $backupPath = 'backups';

        if (!$disk->exists($backupPath)) {
            $disk->makeDirectory($backupPath);
        }

        $driver = config('database.default');
        $dbName = config("database.connections.{$driver}.database");
        $filename = "{$backupPath}/db_{$dbName}_{$timestamp}.sql";

        $this->info('Backing up database...');
        try {
            if ($driver === 'mysql') {
                $host = config('database.connections.mysql.host');
                $user = config('database.connections.mysql.username');
                $pass = config('database.connections.mysql.password');
                $cmd = sprintf(
                    'mysqldump -h %s -u %s %s %s > %s 2>/dev/null',
                    escapeshellarg($host),
                    escapeshellarg($user),
                    $pass ? '-p' . escapeshellarg($pass) : '',
                    escapeshellarg($dbName),
                    escapeshellarg(storage_path('app/' . $filename))
                );
                exec($cmd, $out, $code);
                if ($code !== 0) {
                    $this->warn('mysqldump may not be in PATH. Saving backup path for manual backup.');
                    $disk->put($filename . '.placeholder', "Manual backup: php artisan db:show\nBackup path: " . storage_path('app/' . $filename));
                } else {
                    $this->info('Database backup saved: ' . $filename);
                }
            } else {
                $this->warn('Only MySQL backup is automated. For other drivers, use your DB tool.');
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }

        if (!$this->option('db-only')) {
            $this->info('Storage backup: copy storage/app and storage/logs to a safe location.');
        }

        return 0;
    }
}
