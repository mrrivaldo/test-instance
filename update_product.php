<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require './vendor/autoload.php';
include "db.inc.php";
include "keyaws.php"; // Include your AWS credentials

// Retrieve form data
$id = $_POST['id'];
$name = $_POST['name'];
$description = $_POST['description'];
$price = $_POST['price'];
$image = $_FILES['image'];
$existingImage = $_POST['existing_image'];

// Connect to RDS
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Create S3 client using AWS credentials
$s3 = new Aws\S3\S3Client([
    'version' => 'latest',
    'region' => 'us-east-1', // Replace with your AWS region
    'credentials' => [
        'key' => AWS_ACCESS_KEY_ID,
        'secret' => AWS_SECRET_ACCESS_KEY,
    ],
]);

// Check if a new image is uploaded
if (!empty($image['name'])) {
    // Process image upload logic and update S3
    $targetDir = "./images/";
    $targetFile = $targetDir . basename($image['name']);
    move_uploaded_file($image['tmp_name'], $targetFile);

    // Delete the previous image in S3
    $existingImageKey = 'images/' . basename($existingImage);
    $s3->deleteObject([
        'Bucket' => 'wipe-web-s3',
        'Key' => $existingImageKey,
    ]);

    // Upload the new image to S3
    $newImageKey = 'images/' . basename($targetFile);
    $s3->putObject([
        'Bucket' => 'wipe-web-s3',
        'Key' => $newImageKey,
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

    // Update product data in RDS with the new S3 image URL
    $newImageUrl = $s3->getObjectUrl('wipe-web-s3', $newImageKey);
    $sql = "UPDATE products SET name=?, description=?, price=?, image_url=? WHERE product_id=?";
    $stmt = mysqli_prepare($connection, $sql);

    // Bind parameters to the prepared statement
    mysqli_stmt_bind_param($stmt, 'ssdsd', $name, $description, $price, $newImageUrl, $id);

    // Execute the prepared statement
    $result = mysqli_stmt_execute($stmt);

    // Check for success
    if (!$result) {
        // Log the error or display a user-friendly message
        echo "Error: " . mysqli_error($connection);
    }

    // Close the prepared statement
    mysqli_stmt_close($stmt);
} else {
    // Update product data in RDS without updating the image URL
    $sql = "UPDATE products SET name=?, description=?, price=? WHERE product_id=?";
    $stmt = mysqli_prepare($connection, $sql);

    // Bind parameters to the prepared statement
    mysqli_stmt_bind_param($stmt, 'ssdd', $name, $description, $price, $id);

    // Execute the prepared statement
    $result = mysqli_stmt_execute($stmt);

    // Check for success
    if (!$result) {
        // Log the error or display a user-friendly message
        echo "Error: " . mysqli_error($connection);
    }

    // Close the prepared statement
    mysqli_stmt_close($stmt);

    // Keep the existing S3 image URL
    $newImageUrl = $existingImage;

    // Update metadata in S3 (if needed)
    $existingMetadata = $s3->headObject([
        'Bucket' => 'wipe-web-s3',
        'Key' => $existingImageKey,
    ])->get('Metadata');

    // Modify metadata as needed
    $existingMetadata['Product_id'] = $id;
    $existingMetadata['name'] = $name;
    $existingMetadata['description'] = $description;
    $existingMetadata['price'] = $price;

    // Update metadata in S3
    $s3->copyObject([
        'Bucket' => 'wipe-web-s3',
        'CopySource' => 'wipe-web-s3/' . $existingImageKey,
        'Key' => $existingImageKey,
        'Metadata' => $existingMetadata,
        'MetadataDirective' => 'REPLACE',
    ]);
}

// Close the database connection
mysqli_close($connection);

// Redirect to the product page on success
header("Location: product.php");
?>
