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

// Fetch the existing image URL from RDS
$existingImageUrl = '';
if ($existingImage) {
    $result = mysqli_query($connection, "SELECT image_url FROM products WHERE product_id=$id");
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        $existingImageUrl = $row['image_url'];
    }
}

// Check if a new image is uploaded
if (!empty($image['name'])) {
    // Process image upload logic (You can include your S3 upload code here)
    $targetDir = "./images/";
    $targetFile = $targetDir . basename($image['name']);
    move_uploaded_file($image['tmp_name'], $targetFile);

    // Delete the previous image in S3
    if ($existingImageUrl) {
        $parsedUrl = parse_url($existingImageUrl);
        $path = ltrim($parsedUrl['path'], '/');
        $s3Key = urldecode($path);

        // Create S3 client using AWS credentials
        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'us-east-1', // Replace with your AWS region
            'credentials' => [
                'key' => AWS_ACCESS_KEY_ID,
                'secret' => AWS_SECRET_ACCESS_KEY,
            ],
        ]);

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
    }

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
}

// Update product data in RDS with or without updating the image URL
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

// Close the database connection
mysqli_close($connection);

// Redirect to the product page on success
header("Location: product.php");
?>
