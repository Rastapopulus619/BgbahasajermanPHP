<?php
header("Content-Type: application/json; charset=UTF-8");

$templateKey = $_GET['template'] ?? 'reminders';
$templatePath = __DIR__ . '/../templates/' . $templateKey . '.txt';

if (!file_exists($templatePath)) {
    http_response_code(404);
    echo json_encode(["error" => "Template not found"]);
    exit;
}

$templateText = file_get_contents($templatePath);

// Extract placeholder keys like [NAME], [DATE], etc.
preg_match_all('/\[(.*?)\]/', $templateText, $matches);
$placeholders = array_unique($matches[1]);

// Build an empty placeholder map
$placeholderMap = [];
foreach ($placeholders as $key) {
    $placeholderMap[$key] = "";
}

echo json_encode([
    "text" => $templateText,
    "placeholders" => $placeholderMap
]);
