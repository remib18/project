<?php

namespace App\Service;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ImageUploadService
{
    private const UPLOAD_DIR = '/public/images/course/';
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const PLACEHOLDER_IMAGE = '/images/course/placeholder.webp';
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png'];
    private const MAX_WIDTH = 1200;
    private const MAX_HEIGHT = 800;

    public function __construct(
        private readonly string $projectDir,
        private readonly SluggerInterface $slugger,
        private readonly Filesystem $filesystem
    ) {
    }

    /**
     * Upload and convert image to WebP format
     *
     * @param UploadedFile $file The uploaded file
     * @param string $courseSlug The course slug for filename generation
     * @return string The path to the uploaded image
     * @throws \Exception
     */
    public function uploadCourseImage(UploadedFile $file, string $courseSlug): string
    {
        // Validate file
        $this->validateFile($file);

        // Create upload directory if it doesn't exist
        $uploadPath = $this->projectDir . self::UPLOAD_DIR;
        if (!$this->filesystem->exists($uploadPath)) {
            $this->filesystem->mkdir($uploadPath, 0755);
        }

        // Generate unique filename
        $randomNumber = random_int(1000, 9999);
        $safeFilename = $this->slugger->slug($courseSlug)->toString();
        $newFilename = sprintf('%s-%d.webp', $safeFilename, $randomNumber);
        $targetPath = $uploadPath . $newFilename;

        try {
            // Convert to WebP
            $imagine = new Imagine();
            $image = $imagine->open($file->getPathname());

            // Resize if needed
            $originalSize = $image->getSize();
            if ($originalSize->getWidth() > self::MAX_WIDTH || $originalSize->getHeight() > self::MAX_HEIGHT) {
                $image = $this->resizeImage($image, $originalSize);
            }

            // Save as WebP
            $image->save($targetPath, [
                'webp_quality' => 90,
                'quality' => 90
            ]);

            // Return the relative path from the public directory
            return '/images/course/' . $newFilename;
        } catch (\Exception $e) {
            if ($this->filesystem->exists($targetPath)) {
                $this->filesystem->remove($targetPath);
            }
            throw new \Exception('Failed to process image: ' . $e->getMessage());
        }
    }

    /**
     * Delete course image
     *
     * @param string $imagePath The image path to delete
     * @return void
     */
    public function deleteCourseImage(string $imagePath): void
    {
        // Don't delete placeholder
        if ($imagePath === self::PLACEHOLDER_IMAGE) {
            return;
        }

        $fullPath = $this->projectDir . '/public' . $imagePath;
        if ($this->filesystem->exists($fullPath)) {
            $this->filesystem->remove($fullPath);
        }
    }

    /**
     * Get placeholder image path
     *
     * @return string
     */
    public function getPlaceholderPath(): string
    {
        return self::PLACEHOLDER_IMAGE;
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception(sprintf('File size exceeds maximum limit of %d MB', self::MAX_FILE_SIZE / 1024 / 1024));
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \Exception('Invalid file type. Only JPEG and PNG images are allowed');
        }
    }

    /**
     * Resize image if it exceeds max dimensions
     *
     * @param ImageInterface $image
     * @param Box $originalSize
     * @return ImageInterface
     */
    private function resizeImage(ImageInterface $image, Box $originalSize): ImageInterface
    {
        $ratio = $originalSize->getWidth() / $originalSize->getHeight();

        if ($originalSize->getWidth() > self::MAX_WIDTH) {
            $newWidth = self::MAX_WIDTH;
            $newHeight = (int) ($newWidth / $ratio);
        } else {
            $newHeight = self::MAX_HEIGHT;
            $newWidth = (int) ($newHeight * $ratio);
        }

        $size = new Box($newWidth, $newHeight);
        return $image->resize($size);
    }
}