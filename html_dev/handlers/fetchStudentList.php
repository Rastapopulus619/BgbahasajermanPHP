<?php
require_once '../config/db.php';

$term = $_GET['term'] ?? '';
$term = trim($term);
$suggestions = [];

$safeTerm = $conn->real_escape_string($term);

if ($term === '') {
    $query = "SELECT Name FROM students"; // fetch full list
} else {
    $query = "SELECT Name FROM students WHERE Name LIKE '%$safeTerm%'";
}

$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['Name'];
    }
}

header('Content-Type: application/json');
echo json_encode($suggestions);
?>
