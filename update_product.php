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

// Check if a new image is uploaded
if (!empty($image['name'])) {
    // Process image upload logic (You can include your S3 upload code here)
    $tempFilePath = $image['tmp_name'];

    // Delete the previous S3 data
    if (!empty($existingImage)) {
        try {
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
            $s3->deleteObject([
                'Bucket' => 'wipe-web-s3',
                'Key' => $s3Key,
            ]);
        } catch (Aws\S3\Exception\S3Exception $e) {
            // Log or handle the error
            echo "Error deleting from S3: " . $e->getMessage();
        }
    }

    // Upload image to S3
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
    $image_url = $s3->getObjectUrl('wipe-web-s3', $s3ImageKey);

    // Update RDS record with the new S3 image URL
    $sqlUpdate = "UPDATE products SET name=?, description=?, price=?, image_url=? WHERE product_id=?";
    $stmtUpdate = mysqli_prepare($connection, $sqlUpdate);

    // Check for the prepared statement
    if ($stmtUpdate === false) {
        die("Error in preparing statement: " . mysqli_error($connection));
    }

    // Bind parameters to the prepared statement
    mysqli_stmt_bind_param($stmtUpdate, 'ssdsi', $name, $description, $price, $image_url, $id);

    // Execute the prepared statement
    mysqli_stmt_execute($stmtUpdate);

    // Close the update statement
    mysqli_stmt_close($stmtUpdate);
} else {
    // Update product data in RDS without updating the image
    $sql = "UPDATE products SET name=?, description=?, price=? WHERE product_id=?";
    $stmt = mysqli_prepare($connection, $sql);

    // Check for the prepared statement
    if ($stmt === false) {
        die("Error in preparing statement: " . mysqli_error($connection));
    }

    // Bind parameters to the prepared statement
    mysqli_stmt_bind_param($stmt, 'ssdi', $name, $description, $price, $id);

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Close the statement
    mysqli_stmt_close($stmt);
}

// Close the database connection
mysqli_close($connection);

// Redirect to the product page
header("Location: product.php");
?>
