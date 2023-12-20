<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require './vendor/autoload.php';
include "db.inc.php";

// Retrieve product ID
$id = $_GET['id'];

// Connect to RDS
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Fetch the image URL from RDS
$result = mysqli_query($connection, "SELECT image_url FROM products WHERE product_id=$id");
$row = mysqli_fetch_assoc($result);
// No need to store image path if not used

// No need for S3-related code, remove the block

// Delete product data from RDS
$sql = "DELETE FROM products WHERE product_id=$id";
$result = mysqli_query($connection, $sql);

// Check for success
if ($result) {
    header("Location: product.php"); // Redirect to the product page
} else {
    // Log the error or display a user-friendly message
    echo "Error: " . $sql . "<br>" . mysqli_error($connection);
}

// Close the database connection
mysqli_close($connection);
?>
