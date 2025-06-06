<?php
echo "<h1>Hello, World stupid SMART lol from PHP!</h1>";

// Database config
$host = 'mysql-container'; // Update this to match your container or Tailscale IP
$user = 'rasta';
$password = 'Burungnuri1212';
$database = 'bgbahasajerman';

$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}

// Query
$sql = "SELECT * FROM students WHERE StudentID = 5";
$result = $conn->query($sql);

// Display result
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<p><strong>Student Found:</strong></p>";
    echo "<ul>";
    foreach ($row as $key => $value) {
        echo "<li><strong>$key:</strong> $value</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No student found with ID 5.</p>";
}

$conn->close();
?>

