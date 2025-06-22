<?php
$input = json_decode(file_get_contents("php://input"), true);
$html = $input['html'] ?? '';

if (!$html) {
    http_response_code(400);
    echo "Missing HTML content";
    exit;
}

// Send HTML to Playwright API from inside Docker
$ch = curl_init('http://playwright-api:3000/render');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'html' => $html,
    'width' => 1200,
    'height' => 800
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode !== 200 || strpos($contentType, 'image/png') === false) {
    http_response_code(500);
    echo "Playwright rendering failed";
    exit;
}

header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="Zahlungsbestaetigung.png"');
echo $response;
