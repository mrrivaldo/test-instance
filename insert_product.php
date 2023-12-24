<?php
// Enable error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files and libraries
require './vendor/autoload.php';
include "db.inc.php";
include "keyaws.php";

// Retrieve form data
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? '';
$image = $_FILES['image'] ?? '';

// Check if an image is uploaded
if (!empty($image['name'])) {
    // Get the temporary file path
    $tempFilePath = $image['tmp_name'];

    // Connect to RDS
    $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

    // Check the connection
    if (!$connection) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Insert product data into RDS without S3 URL
    $insertSql = "INSERT INTO products (name, description, price) VALUES (?, ?, ?)";
    $insertStmt = mysqli_prepare($connection, $insertSql);

    // Check for the prepared statement
    if ($insertStmt === false) {
        die("Error in preparing statement: " . mysqli_error($connection));
    }

    // Bind parameters to the prepared statement
    mysqli_stmt_bind_param($insertStmt, 'ssd', $name, $description, $price);

    // Execute the prepared statement
    $result = mysqli_stmt_execute($insertStmt);

    // Check for success
    if (!$result) {
        // Log the error or display a user-friendly message
        echo "Error: " . mysqli_error($connection);
    }

    // Get the auto-generated product_id
    $productId = mysqli_insert_id($connection);

    // Close the prepared statement
    mysqli_stmt_close($insertStmt);

    // Create S3 client using AWS credentials from keyaws.php
    $s3 = new Aws\S3\S3Client([
        'version' => 'latest',
        'region' => 'us-east-1', // Replace with your AWS region
        'credentials' => [
            'key' => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);

    // Upload image to S3
    $s3ImageKey = 'images/' . $productId . '_' . $image['name'];
    $s3->putObject([
        'Bucket' => 'wipe-web-s3',
        'Key' => $s3ImageKey,
        'Body' => fopen($tempFilePath, 'rb'),
        'ContentType' => mime_content_type($tempFilePath),
        'Metadata' => [
            'Product_id' => $productId,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            // Add more metadata fields as needed
        ],
    ]);

    // Get the S3 URL of the uploaded image
    $image_url = $s3->getObjectUrl('wipe-web-s3', $s3ImageKey);

    // Update RDS record with the S3 image URL
    $sqlUpdate = "UPDATE products SET image_url = ? WHERE product_id = ?";
    $stmtUpdate = mysqli_prepare($connection, $sqlUpdate);

    // Check for the prepared statement
    if ($stmtUpdate === false) {
        die("Error in preparing statement: " . mysqli_error($connection));
    }

    // Bind parameters to the prepared statement
    mysqli_stmt_bind_param($stmtUpdate, 'si', $image_url, $productId);

    // Execute the prepared statement
    mysqli_stmt_execute($stmtUpdate);

    // Close the update statement
    mysqli_stmt_close($stmtUpdate);

    // Close the database connection
    mysqli_close($connection);

    // Redirect to the product page on success
    header("Location: product.php");
} else {
    // No image uploaded, set image_url to an empty string
    $image_url = '';
    echo "Error: No image uploaded.";
}
?>
