<?php
require '/path/to/vendor/autoload.php';
include "db.inc.php";

// Retrieve product ID
$id = $_GET['id'];

// Connect to RDS
$connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Fetch the image URL from RDS
$result = mysqli_query($connection, "SELECT image_url FROM products WHERE product_id=$id");
$row = mysqli_fetch_assoc($result);
$imagePathInS3 = $row['image_url'];

// Delete the image from S3
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

try {
    $s3->deleteObject([
        'Bucket' => 'wipe-webv2',
        'Key' => $imagePathInS3,
    ]);
} catch (S3Exception $e) {
    // Log the error or display a user-friendly message
    echo "Error deleting image from S3: " . $e->getMessage();
    exit();
}

// Delete product data from RDS
$sql = "DELETE FROM products WHERE product_id=$id";
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
