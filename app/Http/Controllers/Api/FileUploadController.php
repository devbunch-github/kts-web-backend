<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function store(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded'], 422);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $type = $request->get('type', 'services'); // ✅ detect source

        $folder = match ($type) {
            'employees' => 'uploads/employees/images',
            default => (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])
                ? 'uploads/services/images'
                : 'uploads/services/files'),
        };

        $filename = Str::uuid() . '.' . $extension;
        $path = $file->storeAs($folder, $filename, 'public');
        $publicPath = asset('storage/' . $path);

        return response()->json([
            'success' => true,
            'path' => 'storage/' . $path,
            'url'  => $publicPath,
            'uploaded_by' => Auth::id(),
        ]);
    }

    public function destroy(Request $request)
    {
        $path = $request->input('path');

        if (!$path) {
            return response()->json(['success' => false, 'message' => 'No file path provided'], 422);
        }

        // Normalize path (remove leading "storage/" if present)
        $relativePath = str_replace('storage/', '', $path);

        // Delete file from public disk
        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'File not found or already deleted',
        ], 404);
    }

}
