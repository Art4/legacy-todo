<?php
/**
 * Smoke test proving the PHPUnit runner works on the PHP 5.6 target.
 *
 * This target's code is legacy (root-level procedural .php files, not PSR-4
 * classes), so tests that touch it belong in tests/Legacy/.
 */

class LegacySmokeTest extends PHPUnit_Framework_TestCase
{
    public function testPhpVersionIsSupportedByThisRunner()
    {
        $this->assertTrue(version_compare(PHP_VERSION, '5.6.0', '>='));
    }
}
