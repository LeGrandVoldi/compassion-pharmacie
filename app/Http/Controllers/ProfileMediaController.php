<?php

namespace App\Http\Controllers;

use App\Models\MediaUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileMediaController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $userId   = session('id');
        $file = $request->file('profile_image');

        $relativeDir = 'uploads/profile-users';
        $absoluteDir = $this->webPublicPath($relativeDir);

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0775, true);
        }

        $existing = MediaUser::where('user_id', $userId)->first();

        if ($existing && !empty($existing->path)) {
            $oldFile = $this->webPublicPath($existing->path);

            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }

        $extension = $file->getClientOriginalExtension();
        $filename = 'user_' . $userId . '_' . time() . '_' . uniqid() . '.' . $extension;

        $file->move($absoluteDir, $filename);

        $relativePath = $relativeDir . '/' . $filename;
        $imageUrl = asset($relativePath);

        MediaUser::updateOrCreate(
            ['user_id' => $userId],
            [
                'path' => $relativePath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
            ]
        );

        session([
            'profileImage' => $imageUrl,
            'profile_image_path' => $relativePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Image de profil enregistrée avec succès.',
            'image_url' => $imageUrl,
        ]);
    }

    private function webPublicPath(string $path = ''): string
    {
        $base = public_path();
        $hostingerPath = dirname($base) . '/public_html';

        if (is_dir($hostingerPath)) {
            return rtrim($hostingerPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        }

        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}
