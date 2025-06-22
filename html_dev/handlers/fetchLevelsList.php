<?php
require_once '../config/db.php';

$term = $_GET['term'] ?? '';
$term = trim($term);
$suggestions = [];

$safeTerm = $conn->real_escape_string($term);

if ($term === '') {
    $query = "SELECT Level FROM levels"; // fetch full list
} else {
    $query = "SELECT Level FROM levels WHERE Level LIKE '%$safeTerm%'";
}

$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['Level'];
    }
}

header('Content-Type: application/json');
echo json_encode($suggestions);
?>
