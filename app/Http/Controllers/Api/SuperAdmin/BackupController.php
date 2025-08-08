<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    public function createBackup()
    {
        $backupScriptPath = base_path('backup.php');

        $output = [];
        $resultCode = 0;
        exec("php \"$backupScriptPath\"", $output, $resultCode);

        if ($resultCode === 0) {
            return response()->json([
                'status' => 'success',
                'message' => 'Backup created successfully.',
                // 'output' => implode("\n", $output)
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Backup failed with exit code ' . $resultCode,
                // 'output' => implode("\n", $output)
            ]);
        }
    }

    public function downloadLatestBackup()
    {
        try {
            $backupPath = storage_path('app/backup');
            $files = glob($backupPath . '/*');

            $latestBackup = collect($files)->sortByDesc(function ($file) {
                return filemtime($file);
            })->first();
            $date = Carbon::now()->format('Y-m-d_H-i-s');
            $fileName = "backup-{$date}.sql";
            Log::info($fileName);
            if ($latestBackup && file_exists($latestBackup)) {
                return response()->download($latestBackup)->deleteFileAfterSend(true);
                // return response()->download($latestBackup, $fileName,  [
                //     'Content-Type' => 'application/sql',
                //     'Cache-Control' => 'max-age=0',
                //     'Access-Control-Allow-Origin' => '*',
                //     'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                //     'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                // ])->deleteFileAfterSend(true);
            }

            return response()->json(['status' => 'error', 'message' => 'No backup found.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function downloadStorage()
    {
        try {
            // Create a zip archive of the storage directory
            $zip = new \ZipArchive();
            $fileName = 'storage_backup.zip';
            if ($zip->open(storage_path($fileName), \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(storage_path('app/public')), \RecursiveIteratorIterator::LEAVES_ONLY);
                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = 'storage/' . substr($filePath, strlen(storage_path('app/public')) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                $zip->close();

                return response()->download(storage_path($fileName))->deleteFileAfterSend(true);
                // return response()->download(storage_path($fileName), 'storage_backup.zip', [
                //     'Content-Type' => 'application/zip',
                //     // 'Content-Disposition' => 'attachment; filename="storage_backup.zip"',
                //     // 'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                //     // 'Pragma' => 'no-cache',
                //     'Cache-Control' => 'max-age=0',
                //     'Access-Control-Allow-Origin' => '*',
                //     'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                //     'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                // ]
                // )->deleteFileAfterSend(true);
            }

            return response()->json(['status' => 'error', 'message' => 'Failed to create zip archive.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function readFile()
    {
        $fullPath = base_path(request()->path);
        if (File::exists($fullPath))
            return response()->download($fullPath);
        return response()->json(['error' => 'File not found.'], 404);
    }

    public function writeFile()
    {
        $fullPath = base_path(request()->path);

        if (File::exists($fullPath)) {
            $newContent = request()->content;
            File::put($fullPath, $newContent);
            return response()->json(['success' => 'File updated successfully.'], 200);
        } else {
            return response()->json(['error' => 'File not found.'], 404);
        }
    }


    public function upload(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            if ($file->isValid()) {
                $destinationPath = base_path($request->path);

                if (!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $file->getClientOriginalName());

                return response()->json(['success' => 'File uploaded successfully.'], 200);
            } else {
                return response()->json(['error' => 'Invalid file.'], 400);
            }
        } else {
            return response()->json(['error' => 'No file provided.'], 400);
        }
    }
}
