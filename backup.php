<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get database config from Laravel
$dbConfig = config('database.connections.mysql');
$dbHost = $dbConfig['host'];
$dbUser = $dbConfig['username'];
$dbPass = $dbConfig['password'];
$dbName = $dbConfig['database'];

// Set backup directory - now using storage/app/backup
$backupDir = storage_path('app/backup/');
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Backup filename with timestamp
$backupFile = $backupDir . 'backup-' . date('Y-m-d-His') . '.sql';

// Windows mysqldump detection
$mysqldump = null;
$possiblePaths = [
    'C:\\xampp\\mysql\\bin\\mysqldump.exe',
    'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe', // WAMP default
    'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
    'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
    'mysqldump.exe'
];

foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $mysqldump = $path;
        break;
    }
}

// Fallback to where command
if (!$mysqldump) {
    $output = [];
    exec('where mysqldump 2>&1', $output, $returnVar);
    if ($returnVar === 0 && !empty($output[0])) {
        $mysqldump = trim($output[0]);
    }
}

if (!$mysqldump) {
    die("Error: mysqldump not found. Please ensure MySQL is installed and in your PATH.\n");
}

// Build the command - using Windows syntax
$command = sprintf(
    '"%s" --host=%s --user=%s --password=%s %s -r %s',
    $mysqldump,
    escapeshellarg($dbHost),
    escapeshellarg($dbUser),
    escapeshellarg($dbPass),
    escapeshellarg($dbName),
    escapeshellarg($backupFile)
);

// Execute command
$output = [];
$returnVar = 0;
exec($command . ' 2>&1', $output, $returnVar);

if ($returnVar !== 0) {
    $errorOutput = array_filter($output, function ($line) {
        return !str_contains(strtolower($line), 'password');
    });
    die("Backup failed:\n" . implode("\n", $errorOutput) . "\n");
}

// Verify backup was created
if (!file_exists($backupFile)) {
    die("Error: Backup file was not created at: $backupFile\n");
}

echo "Backup successfully created: " . str_replace(storage_path(), 'storage', $backupFile) . "\n";
exit(0);
