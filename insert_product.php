<?php
// Enable error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files and libraries
require './vendor/autoload.php';
include "db.inc.php"; 

// Retrieve form data
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? '';
$image = $_FILES['image'] ?? '';

// Connect to RDS
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Check the connection
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Upload image to S3
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

// Retrieve AWS credentials from a secure location (environment variables, configuration file, etc.)
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

// Generate a unique filename for the image in S3
$imagePathInS3 = 'images/' . uniqid() . '_' . basename($image['name']);

// Try uploading the image to S3
try {
    $s3->putObject([
        'Bucket' => 'wipe-webv2',
        'Key' => $imagePathInS3,
        'Body' => file_get_contents($image['tmp_name']),
        'ACL' => 'public-read',
    ]);
} catch (S3Exception $e) {
    // Log the error or display a user-friendly message
    echo "Error uploading image to S3: " . $e->getMessage();
    exit();
}

// Insert product data into RDS
$sql = "INSERT INTO products (name, description, image_url, price) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($connection, $sql);

// Bind parameters to the prepared statement
mysqli_stmt_bind_param($stmt, 'sssi', $name, $description, $imagePathInS3, $price);

// Execute the prepared statement
$result = mysqli_stmt_execute($stmt);

// Check for success
if ($result) {
    header("Location: product.php"); // Redirect to the product page
} else {
    // Log the error or display a user-friendly message
    echo "Error: " . mysqli_error($connection);
}

// Close the prepared statement and the database connection
mysqli_stmt_close($stmt);
mysqli_close($connection);
?>
