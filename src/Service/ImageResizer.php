<?php

namespace App\Service;

class ImageResizer
{
    public function generateThumbnailPath(string $originalPath): string
    {
        $pathInfo = pathinfo($originalPath);
        $dir = $pathInfo['dirname'] ?? '';
        $filename = $pathInfo['filename'] ?? '';
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

        $thumbName = $filename . '_thumb' . $extension;

        if ($dir === '.' || $dir === '') {
            return $thumbName;
        }

        return rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $thumbName;
    }
}
