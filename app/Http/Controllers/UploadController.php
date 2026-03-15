<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        Log::info('Upload request received', [
            'has_file' => $request->hasFile('file'),
            'files' => $request->files->keys(),
        ]);

        $maxMb = (int) env('MAX_UPLOAD_MB', 100);

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:' . ($maxMb * 1024),
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/quicktime',
            ],
        ]);

        $file = $request->file('file');
        Log::info('File validated', [
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        try {
            $disk = 'public'; // Use public disk so files are web-accessible
            $path = $file->store('chat', [
                'disk' => $disk,
                'visibility' => 'public',
            ]);

            Log::info('File stored successfully', [
                'path' => $path,
                'disk' => $disk,
            ]);

            $url = Storage::disk($disk)->url($path);
            Log::info('File URL generated', [
                'url' => $url,
                'path' => $path,
            ]);

            return response()->json([
                'fileUrl' => $url,
                'fileName' => $file->getClientOriginalName(),
                'fileMime' => $file->getMimeType(),
            ]);
        } catch (\Throwable $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'error' => 'File upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
