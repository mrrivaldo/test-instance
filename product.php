<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="./product.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=PT+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet" />
    <link rel="icon" href="./images/shoe-svgrepo-com.svg" />
    <script src="https://kit.fontawesome.com/fa2146ecfc.js" crossorigin="anonymous"></script>
    <title>Product</title>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <nav class="navbar">
            <div class="header">
                <div class="navbar-logo">
                    <h1>Wipe's Shoes <i class="fa-solid fa-shoe-prints"></i></h1>
                </div>
                <ul class="nav-menu">
                    <li><a class="nav-link" href="index.html">Home</a></li>
                    <li><a class="nav-link" href="product.php">Products</a></li>
                    <li><a class="nav-link" href="about.html">About Us</a></li>
                    <li><a class="nav-link" href="thanks.html">Thanks</a></li>
                </ul>
            </div>
        </nav>

        <!-- Display Products -->
        <div class="product">
            <div class="nike-product">
                <h2>Wipe's Collection</h2>
                <div class="nike-title">
                    <p>Shop</p>
                </div>

                <div class="carousel">
                    <?php  include "db.inc.php";?>
		
		<?php
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
                    // Create a connection to the database
                     /* Connect to MySQL and select the database. */
		  $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);
	
  			if (mysqli_connect_errno()) echo "Failed to connect to MySQL: " . mysqli_connect_error();

  			$database = mysqli_select_db($connection, DB_DATABASE);

                    // Fetch products from the database
                    $result = mysqli_query($connection,"SELECT * FROM products");

                    // Display products
                    while ($row = $result->fetch_assoc()) {
                        echo '<div class="carousel-card">';
                        echo '<div class="card-content">';
                        echo '<div class="card-image">';
                        echo '<img src="' . $row['image_url'] . '" alt="product-image" />';
                        echo '</div>';
                        echo '<div class="card-name">';
                        echo '<h3>' . $row['name'] . '</h3>';
                        echo '</div>';
                        echo '<div class="card-price">';
                        echo '<p>Rp ' . number_format($row['price'], 0, ',', '.') . '</p>';
                        echo '</div>';
                    
                        // Display buttons for CRUD operations
                        echo '<div class="crud-buttons">';
                        echo '<a class="crud-button" href="update_product.php?id=' . $row['product_id'] . '"><i class="fa-solid fa-pen"></i></a>';
                        echo '<a class="crud-button" href="#" onclick="confirmDelete(' . $row['product_id'] . ')"><i class="fa-solid fa-xmark"></i></a>';
                        echo '</div>';
                        
                        echo '</div>';
                        echo '</div>';
                    }
                        
                    
                    echo '<a class="crud-button center-button" href="insert_product.php"><i class="fa-solid fa-plus"></i></a>';
                    
                    // Close the database connection
                    mysqli_close($connection);
                    ?>

                </div>
            </div>
        </div>
    </div>

                        <!-- JavaScript for Delete confirmation -->
    <script>
        function confirmDelete(productId) {
            var confirmDelete = confirm("Are you sure you want to delete this product?");
            if (confirmDelete) {
                window.location.href = 'delete_product.php?id=' + productId;
            }
        }
    </script>
</body>
</html>
