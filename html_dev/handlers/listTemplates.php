<?php
// Return all .txt files from the templates directory as a list of names (without .txt)

header("Content-Type: application/json; charset=UTF-8");

$templateDir = __DIR__ . '/../templates/';
$files = glob($templateDir . '*.txt');

$templateNames = [];

foreach ($files as $file) {
    $name = basename($file, '.txt');
    $templateNames[] = $name;
}

echo json_encode($templateNames);
