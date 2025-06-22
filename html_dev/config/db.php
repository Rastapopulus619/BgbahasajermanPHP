<?php
$host = 'mysql-container';
$user = 'rasta';
$password = 'Burungnuri1212';
$database = 'bgbahasajerman';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
