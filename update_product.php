<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require './vendor/autoload.php';
include "db.inc.php";

// Retrieve form data
$id = $_POST['id'];
$name = $_POST['name'];
$description = $_POST['description'];
$price = $_POST['price'];

// Connect to RDS
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Update product data in RDS without updating the image
$sql = "UPDATE products SET name='$name', description='$description', price=$price WHERE product_id=$id";

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
