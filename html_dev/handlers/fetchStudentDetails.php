<?php
/**
 * Fetch Student Details Handler
 * 
 * This is a legacy handler that fetches student details by name.
 * 
 * DEPRECATION NOTICE:
 * This handler is deprecated. Please use the new universal dataFetcher.php
 * with appropriate query configurations for better performance and maintainability.
 * 
 * @see dataFetcher.php for the new universal data fetching system
 */

require_once '../config/db.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$studentName = $_GET['name'] ?? '';

if (empty($studentName)) {
    echo json_encode(['error' => 'Student name is required']);
    exit;
}

try {
    // Use prepared statement for security
    $stmt = $conn->prepare("SELECT * FROM students WHERE Name = ? LIMIT 1");
    $stmt->bind_param("s", $studentName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        echo json_encode($student);
    } else {
        echo json_encode(['error' => 'Student not found']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
