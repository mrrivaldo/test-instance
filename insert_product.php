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
    // Set the directory where you want to store the uploaded images
    $uploadDirectory = './images/';

    // Create a unique filename to avoid overwriting existing files
    $imageFileName = uniqid() . '_' . $image['name'];

    // Specify the path to move the uploaded file to
    $targetPath = $uploadDirectory . $imageFileName;

    // Move the uploaded file to the specified directory
    if (move_uploaded_file($image['tmp_name'], $targetPath)) {
        // File was successfully uploaded
        $image_url = '';  // Initialize image URL

        // Connect to RDS
        $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

        // Check the connection
        if (!$connection) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // Fetch the product ID from RDS based on other details
        $productId = 0; // Initialize product ID
        $selectSql = "SELECT product_id FROM products WHERE name = ? AND description = ? AND price = ?";
        $selectStmt = mysqli_prepare($connection, $selectSql);

        // Check for the prepared statement
        if ($selectStmt === false) {
            die("Error in preparing statement: " . mysqli_error($connection));
        }

        // Bind parameters to the prepared statement
        mysqli_stmt_bind_param($selectStmt, 'ssd', $name, $description, $price);

        // Execute the prepared statement
        mysqli_stmt_execute($selectStmt);

        // Bind the result variable
        mysqli_stmt_bind_result($selectStmt, $productId);

        // Fetch the result
        mysqli_stmt_fetch($selectStmt);

        // Close the select statement
        mysqli_stmt_close($selectStmt);

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
        $s3ImageKey = 'images/' . $imageFileName;
        $s3->putObject([
            'Bucket' => 'wipe-web-s3',
            'Key' => $s3ImageKey,
            'SourceFile' => $targetPath,
            'ContentType' => mime_content_type($targetPath),
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

        // Insert product data into RDS with S3 URL
        $insertSql = "INSERT INTO products (product_id, name, description, image_url, price) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = mysqli_prepare($connection, $insertSql);

        // Check for the prepared statement
        if ($insertStmt === false) {
            die("Error in preparing statement: " . mysqli_error($connection));
        }

        // Bind parameters to the prepared statement
        mysqli_stmt_bind_param($insertStmt, 'dsssd', $productId, $name, $description, $image_url, $price);

        // Execute the prepared statement
        $result = mysqli_stmt_execute($insertStmt);

        // Check for success
        if (!$result) {
            // Log the error or display a user-friendly message
            echo "Error: " . mysqli_error($connection);
        }

        // Close the prepared statement and the database connection
        mysqli_stmt_close($insertStmt);
        mysqli_close($connection);

        // Optionally, you can delete the local image file after processing
        unlink($targetPath);

        // Redirect to the product page on success
        header("Location: product.php");
    } else {
        // Failed to move the uploaded file
        echo "Error: Failed to move the uploaded file.";
    }
} else {
    // No image uploaded, set image_url to an empty string
    $image_url = '';
    echo "Error: No image uploaded.";
}
?>
