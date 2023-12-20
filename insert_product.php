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

// Check if an image is uploaded
if (!empty($image['name'])) {
    // Read the contents of the uploaded file
    $imageContent = file_get_contents($image['tmp_name']);
} else {
    // No image uploaded, set imageContent to null
    $imageContent = null;
}

// Connect to RDS
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Check the connection
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Insert product data into RDS with image content as BLOB
$sql = "INSERT INTO products (name, description, image_content, price) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($connection, $sql);

// Check for the prepared statement
if ($stmt === false) {
    die("Error in preparing statement: " . mysqli_error($connection));
}

// Bind parameters to the prepared statement
mysqli_stmt_bind_param($stmt, 'sssd', $name, $description, $imageContent, $price);

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
