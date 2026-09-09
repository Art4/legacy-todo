<?php

namespace Art4\LegacyTodo;

class Uploads
{
    /** @var string */
    private $dir;

    /** @var string[] */
    private const ALLOWED_EXTENSIONS = [
        'gif',
        'jpeg',
        'jpg',
        'pdf',
        'png',
        'txt',
        'webp',
    ];

    public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    /**
     * Persist an uploaded file into the uploads directory.
     *
     * @param array<string, mixed> $file a single $_FILES element
     * @return string|null the server-side filename stored, or null when nothing was stored
     */
    public function store(array $file): ?string
    {
        if (!$this->accepts($file)) {
            return null;
        }

        $ext = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $stored = sprintf('%s.%s', bin2hex(random_bytes(16)), $ext);
        if (!move_uploaded_file((string) $file['tmp_name'], $this->dir . DIRECTORY_SEPARATOR . $stored)) {
            return null;
        }

        return $stored;
    }

    private function accepts(array $file): bool
    {
        return $this->hasOkError($file)
            && $this->hasTmpFile($file)
            && $this->hasStorableName($file)
            && $this->hasAllowedExtension($file)
            && $this->hasSize($file);
    }

    private function hasOkError(array $file): bool
    {
        return ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    private function hasTmpFile(array $file): bool
    {
        $tmp = $file['tmp_name'] ?? '';
        return is_string($tmp) && $tmp !== '';
    }

    private function hasStorableName(array $file): bool
    {
        $name = $file['name'] ?? '';
        return is_string($name) && $name !== '' && !$this->rejectsName($name);
    }

    private function hasAllowedExtension(array $file): bool
    {
        $name = $file['name'] ?? '';
        $ext = strtolower((string) pathinfo((string) $name, PATHINFO_EXTENSION));

        return in_array($ext, self::ALLOWED_EXTENSIONS, true);
    }

    private function hasSize(array $file): bool
    {
        return (int) ($file['size'] ?? 0) > 0;
    }

    private function rejectsName(string $name): bool
    {
        if (strpbrk($name, "/\\") !== false) {
            return true;
        }

        if (strpos($name, '..') !== false) {
            return true;
        }

        return $name[0] === '.';
    }
}