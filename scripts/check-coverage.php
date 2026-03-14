<?php

declare(strict_types=1);

$threshold = 60.0;
$cloverFile = 'build/coverage.xml';

if (!file_exists($cloverFile)) {
    echo "Coverage file not found: {$cloverFile}\n";
    exit(1);
}

$xml = simplexml_load_file($cloverFile);
$metrics = $xml->project->metrics;
$total = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];

if ($total === 0) {
    echo "No statements found in coverage report\n";
    exit(1);
}

$percentage = ($covered / $total) * 100;

echo sprintf("Line coverage: %.2f%% (%d/%d)\n", $percentage, $covered, $total);

if ($percentage < $threshold) {
    echo sprintf("FAIL: %.2f%% is below the %.0f%% threshold\n", $percentage, $threshold);
    exit(1);
}

echo sprintf("PASS: %.2f%% meets the %.0f%% threshold\n", $percentage, $threshold);
