<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function list()
    {
        $files = Storage::files('uploads');
        $fileNames = array_map(function ($file) {
            return basename($file);
        }, $files);

        return response()->json($fileNames);
    }
    public function uploadChunk(Request $request)
    {
        try {
            $this->validate($request, [
                'file' => 'required|file',
                'fileName' => 'required|string',
                'chunkNumber' => 'required|integer',
                'totalChunks' => 'required|integer',
            ]);

            $fileName = $request->input('fileName');
            $chunkNumber = $request->input('chunkNumber');
            $totalChunks = $request->input('totalChunks');
            $uniqueFileName = $this->createUniqueFileName($fileName);

            $tempDir = storage_path('app/temp_uploads/' . $fileName);

            // create temp dir for save chunks
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $chunkPath = $tempDir . '/chunk_' . $chunkNumber;
            file_put_contents($chunkPath, $request->file('file')->get());

            if ($chunkNumber + 1 == $totalChunks) {
                $this->joinChunks($uniqueFileName, $totalChunks);
            }

            return response()->json(['status' => 'success', 'message' => 'File uploaded successfully']);
        } catch (\Exception $e) {

          //Log::error('Upload chunk error:', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => 'Upload failed'], 500);
        }

    }

    private function joinChunks($fileName, $totalChunks)
    {
        $tempDir = storage_path('app/temp_uploads/' . $fileName);
        $finalPath = storage_path('app/uploads/' . $fileName);

        $outputFile = fopen($finalPath, 'w');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $tempDir . '/chunk_' . $i;
            $chunkContent = file_get_contents($chunkPath);
            fwrite($outputFile, $chunkContent);
            unlink($chunkPath);
        }

        fclose($outputFile);

        rmdir($tempDir);
    }

    private function createUniqueFileName($fileName)
    {
        $uploadDir = storage_path('app/uploads/');
        $filePath = $uploadDir . $fileName;

        // if fileName exist add an unique suffix
        if (file_exists($filePath)) {
            $fileInfo = pathinfo($fileName);
            $baseName = $fileInfo['filename'];
            $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
            $counter = 1;

            while (file_exists($uploadDir . $baseName . '(' . $counter . ')' . $extension)) {
                $counter++;
            }

            return $baseName . '(' . $counter . ')' . $extension;
        }

        return $fileName;
    }

}
