<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
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
        $disk = config('filesystems.default', 's3');
        $path = $file->store('chat', [
            'disk' => $disk,
            'visibility' => 'public',
        ]);

        return response()->json([
            'fileUrl' => Storage::disk($disk)->url($path),
            'fileName' => $file->getClientOriginalName(),
            'fileMime' => $file->getMimeType(),
        ]);
    }
}
