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
$image = $_FILES['image'];
$existingImage = $_POST['existing_image'];

// Use Aws\S3\S3Client and Aws\S3\Exception\S3Exception outside of the if condition
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

// Connect to RDS
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Upload image to S3 only if a new image is provided
if (!empty($image['name'])) {
    $awsCredentials = [
        'key' => getenv('AWS_ACCESS_KEY_ID'),
        'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
    ];

    $s3Config = [
        'version' => 'latest',
        'region' => 'us-east-1',
        'credentials' => $awsCredentials,
    ];

    $s3 = new S3Client($s3Config);

    $imagePathInS3 = 'images/' . basename($image['name']);

    try {
        $s3->putObject([
            'Bucket' => 'wipe-webv2',
            'Key' => $imagePathInS3,
            'Body' => file_get_contents($image['tmp_name']),
            'ACL' => 'public-read',
        ]);

        // Use the new image path for updating
        $imagePathForUpdate = $imagePathInS3;
    } catch (S3Exception $e) {
        // Log the error or display a user-friendly message
        echo "Error uploading image to S3: " . $e->getMessage();
        exit();
    }
} else {
    // No new image, use the existing image path for updating
    $imagePathForUpdate = $existingImage;
}

// Update product data in RDS
$sql = "UPDATE products SET name='$name', description='$description', price=$price, image_url='$imagePathForUpdate' WHERE product_id=$id";
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
