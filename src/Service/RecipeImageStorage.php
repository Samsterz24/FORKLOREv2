<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class RecipeImageStorage
{
    public const UPLOAD_SUBDIR = 'uploads/recipes';
    public const DEFAULT_IMAGE = 'default.svg';
    /** @var list<string> */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    /** @var list<string> */
    public const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    public function getUploadDir(): string
    {
        return $this->projectDir . '/public/' . self::UPLOAD_SUBDIR;
    }

    public function ensureUploadDir(): void
    {
        $dir = $this->getUploadDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    public function isAllowedExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::ALLOWED_EXTENSIONS, true);
    }

    public function validateUploadedImage(?UploadedFile $file): ?string
    {
        if (!$file) {
            return null;
        }
        if (!$file->isValid()) {
            return 'The uploaded image is invalid or incomplete.';
        }

        $originalName = (string) $file->getClientOriginalName();
        $ext = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === '' || !$this->isAllowedExtension($ext)) {
            return 'Please upload a valid image (JPG, PNG, or WebP).';
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = @finfo_file($finfo, $file->getPathname());
                @finfo_close($finfo);
                $mime = is_string($detected) ? strtolower($detected) : null;
            }
        }
        if ($mime === null) {
            try {
                $guessed = $file->getMimeType();
                $mime = is_string($guessed) ? strtolower($guessed) : null;
            } catch (\Throwable) {
                $mime = null;
            }
        }
        if ($mime !== null && !in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            return 'Please upload a valid image file (JPG, PNG, or WebP).';
        }

        return null;
    }

    public function store(UploadedFile $file): string
    {
        $this->ensureUploadDir();
        $originalName = (string) $file->getClientOriginalName();
        $ext = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if (!$this->isAllowedExtension($ext)) {
            $ext = 'jpg';
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $file->move($this->getUploadDir(), $filename);
        return $filename;
    }

    public function isManagedUploadedFile(?string $filename): bool
    {
        if ($filename === null || $filename === '') {
            return false;
        }
        if ($filename === self::DEFAULT_IMAGE || $filename === 'default.jpg' || $filename === 'default') {
            return false;
        }
        if (str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, '..')) {
            return false;
        }
        return (bool) preg_match('/^[A-Za-z0-9._-]+\.(jpg|jpeg|png|webp|svg)$/i', $filename);
    }

    public function deleteIfSafe(?string $filename): void
    {
        if (!$this->isManagedUploadedFile($filename) || $filename === null) {
            return;
        }
        $path = $this->getUploadDir() . '/' . $filename;
        $realDir = realpath($this->getUploadDir());
        $realPath = realpath($path);
        if ($realDir && $realPath && str_starts_with($realPath, $realDir) && is_file($realPath)) {
            @unlink($realPath);
        }
    }
}
