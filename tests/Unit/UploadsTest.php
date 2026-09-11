<?php

declare(strict_types=1);

use Art4\LegacyTodo\Uploads;

final class UploadsTest extends PHPUnit\Framework\TestCase
{
    /** @var string */
    private $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/legacy-todo-uploads-' . bin2hex(random_bytes(4));
        mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
    }

    public function testStoreReturnsNullForEmptyFilesArray(): void
    {
        $uploads = new Uploads($this->dir);

        $this->assertNull($uploads->store([]));
    }

    public function testStoreReturnsNullWhenUploadHasError(): void
    {
        $uploads = new Uploads($this->dir);

        $this->assertNull($uploads->store([
            'name' => 'photo.png',
            'tmp_name' => '/tmp/nope',
            'error' => UPLOAD_ERR_CANT_WRITE,
            'size' => 4,
        ]));
    }

    public function testStoreReturnsNullWhenNoFileUploaded(): void
    {
        $uploads = new Uploads($this->dir);

        $this->assertNull($uploads->store([
            'name' => 'photo.png',
            'tmp_name' => '/tmp/nope',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ]));
    }

    public function testStoreRejectsScriptableExtension(): void
    {
        $tmp = $this->tmpFile('<?php echo "x";');

        $stored = $this->runStore([
            'name' => 'shell.php',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => (int) filesize($tmp),
        ]);

        $this->assertNull($stored);
        $this->assertSame([], glob($this->dir . '/*') ?: []);
    }

    public function testStoreRejectsTraversalInClientName(): void
    {
        $tmp = $this->tmpFile('data');

        $stored = $this->runStore([
            'name' => '../../evil.png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => (int) filesize($tmp),
        ]);

        $this->assertNull($stored);
        $this->assertSame([], glob($this->dir . '/*') ?: []);
    }

    public function testStoreRejectsAbsoluteClientName(): void
    {
        $tmp = $this->tmpFile('data');

        $stored = $this->runStore([
            'name' => '/etc/evil.png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => (int) filesize($tmp),
        ]);

        $this->assertNull($stored);
        $this->assertSame([], glob($this->dir . '/*') ?: []);
    }

    public function testStoreRejectsZeroSizeUpload(): void
    {
        $tmp = $this->tmpFile('');

        $stored = $this->runStore([
            'name' => 'empty.png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => 0,
        ]);

        $this->assertNull($stored);
        $this->assertSame([], glob($this->dir . '/*') ?: []);
    }

    public function testStorePersistsValidUploadUnderGeneratedName(): void
    {
        $tmp = $this->tmpFile('data');
        $file = [
            'name' => 'photo.png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => (int) filesize($tmp),
        ];

        $stored = $this->runStore($file);

        $this->assertNotNull($stored);
        $this->assertStringEndsWith('.png', $stored);
        $this->assertStringNotContainsString('photo', $stored);
        $this->assertFileEquals($tmp, $this->dir . DIRECTORY_SEPARATOR . $stored);
        $this->assertSame([$stored], array_map('basename', glob($this->dir . '/*') ?: []));
    }

    public function testStoreReturnsNullWhenMoveFailsForNonUploadedFile(): void
    {
        $tmp = $this->tmpFile('data');
        $uploads = new Uploads($this->dir);

        $stored = $uploads->store([
            'name' => 'photo.png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => (int) filesize($tmp),
        ]);

        @unlink($tmp);

        $this->assertNull($stored);
    }

    public function testStoreRejectsNameContainingSlashInProcess(): void
    {
        $tmp = $this->tmpFile('data');
        $uploads = new Uploads($this->dir);

        $stored = $uploads->store([
            'name' => 'foo/bar.png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => (int) filesize($tmp),
        ]);

        @unlink($tmp);

        $this->assertNull($stored);
        $this->assertSame([], glob($this->dir . '/*') ?: []);
    }

    public function testStoreRejectsNameContainingDotDotInProcess(): void
    {
        $tmp = $this->tmpFile('data');
        $uploads = new Uploads($this->dir);

        $stored = $uploads->store([
            'name' => 'foo..bar.png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => (int) filesize($tmp),
        ]);

        @unlink($tmp);

        $this->assertNull($stored);
        $this->assertSame([], glob($this->dir . '/*') ?: []);
    }

    private function tmpFile(string $contents): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'legacy-todo-up-');
        file_put_contents($tmp, $contents);

        return $tmp;
    }

    /** @param array<string, mixed> $file */
    private function runStore(array $file): ?string
    {
        $script = 'namespace Art4\\LegacyTodo { function move_uploaded_file($source, $dest) { return copy($source, $dest); } }'
            . 'namespace {'
            . 'require ' . var_export(__DIR__ . '/../../vendor/autoload.php', true) . ';'
            . '$uploads = new Art4\\LegacyTodo\\Uploads(' . var_export($this->dir, true) . ');'
            . '$result = $uploads->store(' . var_export($file, true) . ');'
            . 'echo $result === null ? "__NULL__" : $result;'
            . '}';

        exec(PHP_BINARY . ' -r ' . escapeshellarg($script), $lines, $code);

        $this->assertSame(0, $code, implode("\n", $lines));

        $out = trim(implode("", $lines));

        return $out === '__NULL__' ? null : $out;
    }
}