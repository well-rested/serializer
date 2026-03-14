<?php

declare(strict_types=1);

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
$rounded = (int) round($percentage);

$color = match (true) {
    $percentage >= 80 => 'brightgreen',
    $percentage >= 60 => 'yellow',
    $percentage >= 40 => 'orange',
    default           => 'red',
};

$badge = [
    'schemaVersion' => 1,
    'label'         => 'coverage',
    'message'       => "{$rounded}%",
    'color'         => $color,
];

file_put_contents(
    'build/coverage-badge.json',
    json_encode($badge, JSON_PRETTY_PRINT) . PHP_EOL,
);

echo sprintf("Badge generated: %d%% (%s)\n", $rounded, $color);
