<?php

declare(strict_types=1);

/**
 * Deterministic canary for the #194 defect class: a module in src/ that the
 * real app can never load because Bootstrap's own require_once list (the
 * request-time loading path) doesn't reach it. Mirrors the divergence by
 * walking the actual filesystem, not Composer's PSR-4 autoloader.
 */
final class BootstrapRequireListTest extends PHPUnit\Framework\TestCase
{
    private const SRC_DIR = __DIR__ . '/../../src';

    /** @return list<string> */
    private function srcPhpFiles(): array
    {
        $files = glob(self::SRC_DIR . '/*.php');
        $files = $files !== false ? $files : [];
        sort($files);

        return $files;
    }

    /**
     * @return list<string> absolute paths named by `require_once __DIR__ . "/X.php"` lines
     */
    private function requireTargets(string $file): array
    {
        $contents = file_get_contents($file);
        if ($contents === false) {
            return [];
        }
        preg_match_all('~\brequire_once\s+__DIR__\s*\.\s*"/([A-Za-z0-9_]+\.php)"~', $contents, $matches);

        $targets = [];
        foreach ($matches[1] as $name) {
            $targets[] = self::SRC_DIR . '/' . $name;
        }

        return $targets;
    }

    /** @return list<string> */
    private function bootstrapDirectRequires(): array
    {
        return $this->requireTargets(self::SRC_DIR . '/Bootstrap.php');
    }

    /**
     * @param list<string> $roots
     * @return list<string>
     */
    private function transitiveClosure(array $roots): array
    {
        $reached = [];
        $queue = $roots;
        while ($queue !== []) {
            $file = array_shift($queue);
            if (isset($reached[$file])) {
                continue;
            }
            $reached[$file] = true;
            foreach ($this->requireTargets($file) as $target) {
                $queue[] = $target;
            }
        }
        ksort($reached);

        return array_keys($reached);
    }

    public function testEverySrcPhpFileIsReachableFromBootstrapRequireList(): void
    {
        $reached = $this->transitiveClosure($this->bootstrapDirectRequires());
        $missing = array_values(array_diff($this->srcPhpFiles(), $reached));

        $this->assertSame([], $missing, 'src/ files missing from Bootstrap require graph: ' . implode(', ', $missing));
    }

    public function testEverySrcRequireResolvesToExistingFile(): void
    {
        $dangling = [];
        foreach ($this->srcPhpFiles() as $file) {
            foreach ($this->requireTargets($file) as $target) {
                if (!is_file($target)) {
                    $dangling[] = basename($file) . ' -> ' . basename($target);
                }
            }
        }

        $this->assertSame([], $dangling, 'dangling requires: ' . implode(', ', $dangling));
    }

    public function testBootstrapRequireListNamesAllSiblingModules(): void
    {
        $siblings = array_map('basename', array_diff($this->srcPhpFiles(), [self::SRC_DIR . '/Bootstrap.php']));
        $required = array_map('basename', $this->bootstrapDirectRequires());
        sort($siblings);
        sort($required);

        $this->assertSame($siblings, $required);
    }
}
