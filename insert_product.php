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
        $image_url = $targetPath;

        // Connect to RDS
        $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

        // Check the connection
        if (!$connection) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // Insert product data into RDS
        $sql = "INSERT INTO products (name, description, image_url, price) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $sql);

        // Check for the prepared statement
        if ($stmt === false) {
            die("Error in preparing statement: " . mysqli_error($connection));
        }

        // Bind parameters to the prepared statement
        mysqli_stmt_bind_param($stmt, 'sssd', $name, $description, $image_url, $price);

        // Execute the prepared statement
        $result = mysqli_stmt_execute($stmt);

        // Check for success
        if (!$result) {
            // Log the error or display a user-friendly message
            echo "Error: " . mysqli_error($connection);
        }

        // Close the prepared statement and the database connection
        mysqli_stmt_close($stmt);
        mysqli_close($connection);

        // Create S3 client using AWS credentials from keyaws.php
        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region' => 'us-east-1', // Replace with your AWS region
            'credentials' => [
                'key' => AWS_ACCESS_KEY_ID,
                'secret' => AWS_SECRET_ACCESS_KEY,
            ],
        ]);

        // Upload product data to S3
        $productDataKey = 'product_data/' . $imageFileName . '.json';
        $productData = json_encode([
            'name' => $name,
            'description' => $description,
            'price' => $price,
        ]);

        $s3->putObject([
            'Bucket' => 'wipe-web-s3',
            'Key' => $productDataKey,
            'Body' => $productData,
            'ContentType' => 'application/json',
        ]);

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
