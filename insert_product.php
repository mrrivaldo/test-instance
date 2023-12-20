<?php
// Enable error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files and libraries
require './vendor/autoload.php';
include "db.inc.php"; 

// Retrieve form data
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? '';
$image = $_FILES['image'] ?? '';

// Connect to RDS
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Check the connection
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// No need for S3-related code, remove the block

// Insert product data into RDS
$sql = "INSERT INTO products (name, description, image_url, price) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($connection, $sql);

// Bind parameters to the prepared statement
mysqli_stmt_bind_param($stmt, 'sssi', $name, $description, '', $price);

// Execute the prepared statement
$result = mysqli_stmt_execute($stmt);

// Check for success
if ($result) {
    header("Location: product.php"); // Redirect to the product page
} else {
    // Log the error or display a user-friendly message
    echo "Error: " . mysqli_error($connection);
}

// Close the prepared statement and the database connection
mysqli_stmt_close($stmt);
mysqli_close($connection);
?>
