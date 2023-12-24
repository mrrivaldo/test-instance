<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require './vendor/autoload.php';
include "db.inc.php";
include "keyaws.php"; // Include your AWS credentials

// Retrieve product ID
$id = $_GET['id'];

// Connect to RDS
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Fetch the image URL from RDS
$result = mysqli_query($connection, "SELECT image_url FROM products WHERE product_id=$id");
$row = mysqli_fetch_assoc($result);

// Check if the result is not empty
if ($row) {
    $imageUrl = $row['image_url'];

    // Create S3 client using AWS credentials
    $s3 = new Aws\S3\S3Client([
        'version' => 'latest',
        'region' => 'us-east-1', // Replace with your AWS region
        'credentials' => [
            'key' => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);

    // Extract S3 key from the image URL
    $parsedUrl = parse_url($imageUrl);
    $path = ltrim($parsedUrl['path'], '/');
    $s3Key = urldecode($path);

    // Delete the object from S3
    try {
        $s3->deleteObject([
            'Bucket' => 'wipe-web-s3',
            'Key' => $s3Key,
        ]);
    } catch (Aws\S3\Exception\S3Exception $e) {
        // Log or handle the error
        echo "Error deleting from S3: " . $e->getMessage();
    }

    // Delete product data from RDS
    $sql = "DELETE FROM products WHERE product_id=$id";
    $result = mysqli_query($connection, $sql);

    // Check for success
    if ($result) {
        header("Location: product.php"); // Redirect to the product page
    } else {
        // Log the error or display a user-friendly message
        echo "Error deleting from RDS: " . $sql . "<br>" . mysqli_error($connection);
    }
} else {
    // Handle the case where the record is not found in RDS
    echo "Product not found.";
}

// Close the database connection
mysqli_close($connection);
?>
