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
                        
                    ?>
                    <!-- Update your existing "Add" button with this -->
                    <a class="crud-button center-button" href="#" onclick="openModal()"><i class="fa-solid fa-plus"></i></a>

                    
                </div>
            </div>
        </div>
    </div>

    <!-- Add this modal code within the <body> tag, before the closing </body> tag -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <!-- Your form goes here -->
        <form method="post" action="insert_product.php" enctype="multipart/form-data" id="productForm">
            <!-- Other form fields -->
            <label for="name">Name:</label>
            <input type="text" name="name" required />

            <label for="description">Description:</label>
            <textarea name="description" required></textarea>

            <label for="price">Price:</label>
            <input type="text" name="price" required />

            <label for="image">Image:</label>
            <input type="file" name="image" required />

            <!-- Submit button -->
            <input type="submit" value="Add Product" />
        </form>
    </div>
</div>

     <!-- Close the database connection -->
    <?php mysqli_close($connection);?>

                        <!-- JavaScript for Delete confirmation -->
    <script>
        function confirmDelete(productId) {
            var confirmDelete = confirm("Are you sure you want to delete this product?");
            if (confirmDelete) {
                window.location.href = 'delete_product.php?id=' + productId;
            }
        }

        function openModal() {
        document.getElementById("myModal").style.display = "block";
        }

        function closeModal() {
        document.getElementById("myModal").style.display = "none";
        }
        

         // Optional: Close the modal if the user clicks outside of it
            window.onclick = function (event) {
                if (event.target === document.getElementById("myModal")) {
                    closeModal();
                }
            }
    </script>
</body>
</html>
