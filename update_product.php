<?php
// Enable error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files and libraries
require './vendor/autoload.php';
include "db.inc.php";
include "keyaws.php";

// Retrieve form data
$id = $_POST['id'];
$name = $_POST['name'];
$description = $_POST['description'];
$price = $_POST['price'];
$image = $_FILES['image'];
$existingImage = $_POST['existing_image'];

// Connect to RDS
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Check if a new image is uploaded
if (!empty($image['name'])) {
    // Get the temporary file path
    $tempFilePath = $image['tmp_name'];

    // Update product data in RDS without S3 URL
    $updateSql = "UPDATE products SET name=?, description=?, price=? WHERE product_id=?";
    $updateStmt = mysqli_prepare($connection, $updateSql);

    // Check for the prepared statement
    if ($updateStmt === false) {
        die("Error in preparing statement: " . mysqli_error($connection));
    }

    // Bind parameters to the prepared statement
    mysqli_stmt_bind_param($updateStmt, 'ssdi', $name, $description, $price, $id);

    // Execute the prepared statement
    $result = mysqli_stmt_execute($updateStmt);

    // Check for success
    if (!$result) {
        // Log the error or display a user-friendly message
        echo "Error: " . mysqli_error($connection);
    }

    // Close the prepared statement
    mysqli_stmt_close($updateStmt);

    // Create S3 client using AWS credentials from keyaws.php
    $s3 = new Aws\S3\S3Client([
        'version' => 'latest',
        'region' => 'us-east-1', // Replace with your AWS region
        'credentials' => [
            'key' => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);

    // Extract S3 key from the existing image URL
    $parsedUrl = parse_url($existingImage);
    $path = ltrim($parsedUrl['path'], '/');
    $s3Key = urldecode($path);

    // Delete the previous object from S3
    try {
        $s3->deleteObject([
            'Bucket' => 'wipe-web-s3',
            'Key' => $s3Key,
        ]);
    } catch (Aws\S3\Exception\S3Exception $e) {
        // Log or handle the error
        echo "Error deleting from S3: " . $e->getMessage();
    }
    echo "Existing Image URL: " . $existingImage . PHP_EOL;
    echo "Parsed URL: " . print_r($parsedUrl, true) . PHP_EOL;
    echo "S3 Key: " . $s3Key . PHP_EOL;




    // Upload the new image to S3
    $s3ImageKey = 'images/' . $id . '_' . $image['name'];
    $s3->putObject([
        'Bucket' => 'wipe-web-s3',
        'Key' => $s3ImageKey,
        'Body' => fopen($tempFilePath, 'rb'),
        'ContentType' => mime_content_type($tempFilePath),
        'Metadata' => [
            'Product_id' => $id,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            // Add more metadata fields as needed
        ],
    ]);

    // Get the S3 URL of the uploaded image
    $newImageUrl = $s3->getObjectUrl('wipe-web-s3', $s3ImageKey);

    // Update RDS record with the new S3 image URL
    $updateImageUrlSql = "UPDATE products SET image_url=? WHERE product_id=?";
    $updateImageUrlStmt = mysqli_prepare($connection, $updateImageUrlSql);

    // Check for the prepared statement
    if ($updateImageUrlStmt === false) {
        die("Error in preparing statement: " . mysqli_error($connection));
    }

    // Bind parameters to the prepared statement
    mysqli_stmt_bind_param($updateImageUrlStmt, 'si', $newImageUrl, $id);

    // Execute the prepared statement
    mysqli_stmt_execute($updateImageUrlStmt);

    // Close the update statement
    mysqli_stmt_close($updateImageUrlStmt);

    // Close the database connection
    mysqli_close($connection);

    // Redirect to the product page on success
    header("Location: product.php");
} else {
    // No new image, update product data in RDS without changing the image URL
    $updateSql = "UPDATE products SET name=?, description=?, price=? WHERE product_id=?";
    $updateStmt = mysqli_prepare($connection, $updateSql);

    // Check for the prepared statement
    if ($updateStmt === false) {
        die("Error in preparing statement: " . mysqli_error($connection));
    }

    // Bind parameters to the prepared statement
    mysqli_stmt_bind_param($updateStmt, 'ssdi', $name, $description, $price, $id);

    // Execute the prepared statement
    $result = mysqli_stmt_execute($updateStmt);

    // Check for success
    if (!$result) {
        // Log the error or display a user-friendly message
        echo "Error: " . mysqli_error($connection);
    }

    // Close the prepared statement
    mysqli_stmt_close($updateStmt);

    // Close the database connection
    mysqli_close($connection);

    // Redirect to the product page on success
    header("Location: product.php");
}
?>
