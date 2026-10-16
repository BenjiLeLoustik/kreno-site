<?php

namespace App\Shared\Storage;

use App\Shared\Exception\StorageException;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Http\Request\UploadedFile;
use Throwable;

class PrivateStorage
{
    public function __construct(
        #[Autowire(param: 'kernel.root_path')]
        private string $rootPath,
    ) {
    }

    public function store(UploadedFile $file, string $directory, int $maxSize, array $mimeTypes): string
    {
        if (!$file->isValid()) {
            throw new StorageException('Le fichier n\'a pas pu être reçu.');
        }

        if ($file->getSize() > $maxSize) {
            throw new StorageException('Le fichier est trop volumineux.');
        }

        $extension = $mimeTypes[(string) $file->getMimeType()] ?? null;

        if ($extension === null) {
            throw new StorageException('Ce type de fichier n\'est pas accepté.');
        }

        $directory = trim($directory, '/');
        $name = sprintf('%s.%s', bin2hex(random_bytes(16)), $extension);

        try {
            $file->move($this->absolute($directory), $name);
        } catch (Throwable $exception) {
            throw new StorageException('Le fichier n\'a pas pu être enregistré.', 0, $exception);
        }

        return $directory . '/' . $name;
    }

    public function write(string $path, string $content): string
    {
        $absolute = $this->absolute($path);
        $directory = dirname($absolute);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new StorageException(sprintf('Le dossier "%s" n\'a pas pu être créé.', $directory));
        }

        if (file_put_contents($absolute, $content, LOCK_EX) === false) {
            throw new StorageException(sprintf('Le fichier "%s" n\'a pas pu être écrit.', $path));
        }

        return $path;
    }

    public function read(string $path): string
    {
        $content = $this->exists($path) ? file_get_contents($this->absolute($path)) : false;

        if ($content === false) {
            throw new StorageException(sprintf('Le fichier "%s" est introuvable.', $path));
        }

        return $content;
    }

    public function exists(?string $path): bool
    {
        return $path !== null && $path !== '' && is_file($this->absolute($path));
    }

    public function delete(?string $path): bool
    {
        return $this->exists($path) && unlink($this->absolute((string) $path));
    }

    public function absolute(string $path): string
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            throw new StorageException('Chemin de fichier invalide.');
        }

        return rtrim($this->rootPath, '/') . '/var/' . ltrim($path, '/');
    }
}