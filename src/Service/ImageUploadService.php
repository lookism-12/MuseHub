<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploadService
{
    private Cloudinary $cloudinary;

    public function __construct(
        string $cloudinaryCloudName,
        string $cloudinaryApiKey,
        string $cloudinaryApiSecret,
        private LoggerInterface $logger
    ) {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudinaryCloudName,
                'api_key' => $cloudinaryApiKey,
                'api_secret' => $cloudinaryApiSecret,
            ],
        ]);
    }

    /**
     * Upload a post image to Cloudinary
     * 
     * @param UploadedFile $file The uploaded file
     * @return string The secure URL of the uploaded image
     * @throws \Exception If upload fails
     */
    public function uploadPostImage(UploadedFile $file): string
    {
        $this->validateImage($file);

        try {
            $result = $this->cloudinary->uploadApi()->upload(
                $file->getPathname(),
                [
                    'folder' => 'musehub/posts',
                    'transformation' => [
                        'width' => 1200,
                        'crop' => 'limit',
                        'quality' => 'auto:good',
                        'fetch_format' => 'auto',
                    ],
                    'allowed_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                ]
            );

            $secureUrl = $result['secure_url'];

            $this->logger->info('Post image uploaded to Cloudinary', [
                'url' => $secureUrl,
                'public_id' => $result['public_id'],
                'format' => $result['format'],
                'size' => $result['bytes'],
            ]);

            return $secureUrl;
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload post image', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
            ]);

            throw new \Exception('Failed to upload image: ' . $e->getMessage());
        }
    }

    /**
     * Upload a user avatar to Cloudinary
     * 
     * @param UploadedFile $file The uploaded file
     * @return string The secure URL of the uploaded avatar
     * @throws \Exception If upload fails
     */
    public function uploadAvatar(UploadedFile $file): string
    {
        $this->validateImage($file);

        try {
            $result = $this->cloudinary->uploadApi()->upload(
                $file->getPathname(),
                [
                    'folder' => 'musehub/avatars',
                    'transformation' => [
                        'width' => 200,
                        'height' => 200,
                        'crop' => 'fill',
                        'gravity' => 'face',
                        'quality' => 'auto:good',
                        'fetch_format' => 'auto',
                    ],
                    'allowed_formats' => ['jpg', 'jpeg', 'png', 'webp'],
                ]
            );

            $secureUrl = $result['secure_url'];

            $this->logger->info('Avatar uploaded to Cloudinary', [
                'url' => $secureUrl,
                'public_id' => $result['public_id'],
                'format' => $result['format'],
                'size' => $result['bytes'],
            ]);

            return $secureUrl;
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload avatar', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
            ]);

            throw new \Exception('Failed to upload avatar: ' . $e->getMessage());
        }
    }

    /**
     * Delete an image from Cloudinary by public ID
     * 
     * @param string $publicId The Cloudinary public ID
     * @return bool True if deleted successfully
     */
    public function deleteImage(string $publicId): bool
    {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);

            $this->logger->info('Image deleted from Cloudinary', [
                'public_id' => $publicId,
                'result' => $result['result'],
            ]);

            return $result['result'] === 'ok';
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete image from Cloudinary', [
                'error' => $e->getMessage(),
                'public_id' => $publicId,
            ]);

            return false;
        }
    }

    /**
     * Extract public ID from Cloudinary URL
     * 
     * @param string $url The Cloudinary URL
     * @return string|null The public ID or null if not a valid Cloudinary URL
     */
    public function extractPublicId(string $url): ?string
    {
        // Example URL: https://res.cloudinary.com/cloud_name/image/upload/v1234567890/musehub/posts/abc123.jpg
        if (preg_match('#/upload/(?:v\d+/)?(.+)\.\w+$#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Validate uploaded image file
     * 
     * @throws \Exception If validation fails
     */
    private function validateImage(UploadedFile $file): void
    {
        // Check if file was uploaded successfully
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        // Check file size (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size of 10MB');
        }

        // Check MIME type
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed');
        }
    }
}
