<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ValidationException;
use finfo;

class UploadService
{
    private const MAX_SIZE = 5 * 1024 * 1024;

    // SVG сознательно не разрешаем — может содержать исполняемый JS.
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(private readonly string $publicPath = __DIR__ . '/../../public/assets/uploads')
    {
    }

    public function storeImage(array $file, string $subdir): string
    {
        if (!isset($file['tmp_name'], $file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException(['file' => ['Ошибка загрузки файла.']]);
        }

        if (PHP_SAPI !== 'cli' && !is_uploaded_file($file['tmp_name'])) {
            throw new ValidationException(['file' => ['Недопустимый файл.']]);
        }

        if ((int) $file['size'] > self::MAX_SIZE) {
            throw new ValidationException(['file' => ['Файл слишком большой (максимум 5 МБ).']]);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if ($mime === false || !isset(self::ALLOWED_MIME[$mime])) {
            throw new ValidationException(['file' => ['Разрешены только изображения JPEG, PNG, WebP.']]);
        }

        $extension = self::ALLOWED_MIME[$mime];
        $filename = bin2hex(random_bytes(8)) . '.' . $extension;

        $subdir = trim($subdir, '/');
        $targetDir = $this->publicPath . '/' . $subdir;
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new ValidationException(['file' => ['Не удалось создать каталог для загрузки.']]);
        }

        $targetPath = $targetDir . '/' . $filename;
        if (!@move_uploaded_file($file['tmp_name'], $targetPath) && !@copy($file['tmp_name'], $targetPath)) {
            throw new ValidationException(['file' => ['Не удалось сохранить файл.']]);
        }

        return '/assets/uploads/' . $subdir . '/' . $filename;
    }

    public function delete(string $publicPath): void
    {
        $prefix = '/assets/uploads/';
        if (!str_starts_with($publicPath, $prefix)) {
            return;
        }

        $relative = substr($publicPath, strlen($prefix));
        $fullPath = $this->publicPath . '/' . $relative;

        $real = realpath($fullPath);
        $base = realpath($this->publicPath);

        if ($real === false || $base === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
            return;
        }

        @unlink($real);
    }
}
