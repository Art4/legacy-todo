<?php

declare(strict_types=1);

require_once __DIR__ . '/../Fakes/RunCliHelper.php';

use Art4\LegacyTodo\Fakes\RunCliHelper;

final class RunCliHelperTest extends PHPUnit\Framework\TestCase
{
    public function testRunExecutesExpressionAndReturnsStdout(): void
    {
        $output = RunCliHelper::run('echo "hello world";', [], [], []);

        $this->assertSame('hello world', $output);
    }

    public function testRunCreatesFreshDatabaseAndAppInstance(): void
    {
        $output = RunCliHelper::run(
            'echo $app instanceof \Art4\LegacyTodo\Bootstrap ? "ok" : "fail";',
            [],
            [],
            [],
        );

        $this->assertSame('ok', $output);
    }
}
