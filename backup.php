<?php


$user = 'root';
$password = '';
$host = '127.0.0.1';
$database = 'menu';

$backupDir = __DIR__ . '/storage/app/backup';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$filename = $backupDir . '/backup-' . date('Y-m-d_H-i-s') . '.sql';

$command = "mysqldump --user=$user --password=$password --host=$host $database > $filename";
system($command, $resultCode);

if ($resultCode === 0) {
    echo "Backup created successfully";
} else {
    echo "Backup failed with exit code: $resultCode\n";
    echo "Output: " . implode("\n", $output);
}
