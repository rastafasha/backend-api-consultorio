<?php

namespace App\Helpers;

use Validator;
use Illuminate\Support\Facades\Storage;

class Uploader2 {
    public static function uploadFile(string $key, string $path): string {
        $fileName = self::generateFileName($key);
        request()->file($key)->storeAs($path, $fileName);
        return $fileName;
}

    public static function removeFile(string $path, string $fileName) {
        Storage::delete(sprintf('%s/%s', $path, $fileName));
    }

    protected static function generateFileName(string $key): string {
        $fullName = request()->file($key)->getClientOriginalName();
        $extension = request()->file($key)->getClientOriginalExtension();
        return sprintf('%s-%s.%s', $fullName, now()->timestamp, $extension);
    }
}