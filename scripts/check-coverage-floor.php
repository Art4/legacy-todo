#!/usr/bin/env php
<?php
/**
 * Check that test coverage meets the committed floor.
 *
 * Usage: php scripts/check-coverage-floor.php <clover-xml-path> <floor-file-path>
 */

if ($argc !== 3) {
    fprintf(STDERR, "Usage: %s <clover-xml-path> <floor-file-path>\n", $argv[0]);
    exit(1);
}

$cloverPath = $argv[1];
$floorPath = $argv[2];

if (!file_exists($cloverPath)) {
    fprintf(STDERR, "Coverage report not found: %s\n", $cloverPath);
    exit(1);
}

if (!file_exists($floorPath)) {
    fprintf(STDERR, "Floor file not found: %s\n", $floorPath);
    exit(1);
}

$xml = simplexml_load_file($cloverPath);
if ($xml === false) {
    fprintf(STDERR, "Failed to parse coverage report: %s\n", $cloverPath);
    exit(1);
}

$projects = $xml->xpath('/coverage/project');
if (empty($projects)) {
    fprintf(STDERR, "No project element found in coverage report\n");
    exit(1);
}

$metrics = $projects[0]->metrics;
$totalElements = (int) $metrics['elements'];
$coveredElements = (int) $metrics['coveredelements'];

if ($totalElements === 0) {
    fprintf(STDERR, "No elements found in coverage report\n");
    exit(1);
}

$actualCoverage = round($coveredElements / $totalElements * 100, 1);
$floor = (float) trim(file_get_contents($floorPath));

printf("Coverage: %.1f%% (floor: %.1f%%)\n", $actualCoverage, $floor);

if ($actualCoverage < $floor) {
    fprintf(STDERR, "FAIL: coverage %.1f%% is below floor %.1f%%\n", $actualCoverage, $floor);
    exit(1);
}

printf("OK: coverage %.1f%% meets floor %.1f%%\n", $actualCoverage, $floor);
exit(0);
