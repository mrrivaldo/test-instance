<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require './vendor/autoload.php';
include "db.inc.php";
include "keyaws.php";

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

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
    // Process image upload logic

    // Delete the previous data in S3
    if (!empty($existingImage)) {
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1', // Replace with your AWS region
            'credentials' => [
                'key' => YOUR_AWS_ACCESS_KEY_ID,
                'secret' => YOUR_AWS_SECRET_ACCESS_KEY,
            ],
        ]);

        try {
            $s3->deleteObject([
                'Bucket' => 'wipe-web-s3',
                'Key' => 'images/' . basename($existingImage),
            ]);
        } catch (AwsException $e) {
            echo "Error deleting previous S3 data: " . $e->getMessage() . PHP_EOL;
        }
    }

    // Upload the new image to S3
    $targetDir = "./images/";
    $targetFile = $targetDir . basename($image['name']);
    move_uploaded_file($image['tmp_name'], $targetFile);

    $s3 = new S3Client([
        'version' => 'latest',
        'region' => 'us-east-1', // Replace with your AWS region
        'credentials' => [
            'key' => YOUR_AWS_ACCESS_KEY_ID,
            'secret' => YOUR_AWS_SECRET_ACCESS_KEY,
        ],
    ]);

    try {
        $s3->putObject([
            'Bucket' => 'wipe-web-s3',
            'Key' => 'images/' . basename($targetFile),
            'SourceFile' => $targetFile,
            'ContentType' => mime_content_type($targetFile),
            'Metadata' => [
                'Product_id' => $id,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                // Add more metadata fields as needed
            ],
        ]);
    } catch (AwsException $e) {
        echo "Error uploading new image to S3: " . $e->getMessage() . PHP_EOL;
    }

    // Update product data in RDS with the new S3 image URL
    $newImageUrl = $s3->getObjectUrl('wipe-web-s3', 'images/' . basename($targetFile));
    $sql = "UPDATE products SET name=?, description=?, price=?, image_url=? WHERE product_id=?";
} else {
    // Update product data in RDS without updating the image URL
    $sql = "UPDATE products SET name=?, description=?, price=? WHERE product_id=?";
}

// Use prepared statements to prevent SQL injection
$stmt = mysqli_prepare($connection, $sql);

if ($stmt) {
    // Bind parameters to the prepared statement
    if (!empty($image['name'])) {
        mysqli_stmt_bind_param($stmt, 'ssdsd', $name, $description, $price, $newImageUrl, $id);
    } else {
        mysqli_stmt_bind_param($stmt, 'ssdd', $name, $description, $price, $id);
    }

    // Execute the prepared statement
    $result = mysqli_stmt_execute($stmt);

    // Check for success
    if (!$result) {
        // Log the error or display a user-friendly message
        echo "Error updating product data in RDS: " . mysqli_error($connection);
    }

    // Close the prepared statement
    mysqli_stmt_close($stmt);
} else {
    echo "Error preparing SQL statement: " . mysqli_error($connection);
}

// Close the database connection
mysqli_close($connection);

// Redirect to the product page on success
header("Location: product.php");
?>
