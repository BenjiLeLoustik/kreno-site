<?php

namespace App\Shared\Storage;

use App\Shared\Exception\DirectoryNotCreatedException;
use App\Shared\Exception\FileNotSavedException;
use App\Shared\Exception\FileNotWrittenException;
use App\Shared\Exception\FileTooLargeException;
use App\Shared\Exception\FileTypeNotAcceptedException;
use App\Shared\Exception\InvalidFilePathException;
use App\Shared\Exception\StoredFileNotFoundException;
use App\Shared\Exception\UploadNotReceivedException;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Http\Request\UploadedFile;
use Throwable;

class PrivateStorage
{
    public const BASE_DIRECTORY = 'var';

    public const DIRECTORY_MODE = 0775;

    public function __construct(
        #[Autowire(param: 'kernel.root_path')]
        private string $rootPath,
    ) {
    }

    public function store(UploadedFile $file, string $directory, int $maxSize, array $mimeTypes): string
    {
        $extension = $this->extension($file, $maxSize, $mimeTypes);
        $directory = trim($directory, '/');
        $name = sprintf('%s.%s', bin2hex(random_bytes(16)), $extension);

        try {
            $file->move($this->absolute($directory), $name);
        } catch (Throwable $exception) {
            throw new FileNotSavedException($exception);
        }

        return $directory . '/' . $name;
    }

    public function write(string $path, string $content): string
    {
        $absolute = $this->absolute($path);

        $this->ensureDirectory(dirname($absolute));

        if (file_put_contents($absolute, $content, LOCK_EX) === false) {
            throw new FileNotWrittenException($path);
        }

        return $path;
    }

    public function read(string $path): string
    {
        if (!$this->exists($path)) {
            throw new StoredFileNotFoundException($path);
        }

        $content = file_get_contents($this->absolute($path));

        if ($content === false) {
            throw new StoredFileNotFoundException($path);
        }

        return $content;
    }

    public function exists(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        return is_file($this->absolute($path));
    }

    public function delete(?string $path): bool
    {
        if (!$this->exists($path)) {
            return false;
        }

        return unlink($this->absolute((string) $path));
    }

    public function absolute(string $path): string
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            throw new InvalidFilePathException();
        }

        return sprintf('%s/%s/%s', rtrim($this->rootPath, '/'), self::BASE_DIRECTORY, ltrim($path, '/'));
    }

    private function extension(UploadedFile $file, int $maxSize, array $mimeTypes): string
    {
        if (!$file->isValid()) {
            throw new UploadNotReceivedException();
        }

        if ($file->getSize() > $maxSize) {
            throw new FileTooLargeException();
        }

        $extension = $mimeTypes[(string) $file->getMimeType()] ?? null;

        if ($extension === null) {
            throw new FileTypeNotAcceptedException();
        }

        return $extension;
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, self::DIRECTORY_MODE, true) && !is_dir($directory)) {
            throw new DirectoryNotCreatedException($directory);
        }
    }
}