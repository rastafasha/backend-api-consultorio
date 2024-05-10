<?php

namespace App\Helpers;

use Illuminate\Support\Str;




class Uploader {
    public static function uploadFile(string $key, string $path): string {
        $fileName = self::generateFileName($key);
        request()->file($key)->move(public_path($path), $fileName);
        return $fileName;
    }

    public static function removeFile(string $path, string $fileName) {
        unlink(sprintf('%s/%s', public_path($path), $fileName));
    }

    protected static function generateFileName(string $key): string {
        $extension = request()->file($key)->getClientOriginalExtension();
        $fullName = request()->file($key)->getClientOriginalName();
        $pathFileName = trim(pathinfo($fullName, PATHINFO_FILENAME));
        $secureMaxName = substr(Str::slug($pathFileName), 0, 250);
        return sprintf('%s-%s.%s', $secureMaxName, now()->timestamp, $extension);
    }
    
}