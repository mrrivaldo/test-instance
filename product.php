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
                    <?php include "db.inc.php"; ?>

                    <?php
                    error_reporting(E_ALL);
                    ini_set('display_errors', 1);
                    // Create a connection to the database
                    $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

                    if (mysqli_connect_errno()) {
                        echo "Failed to connect to MySQL: " . mysqli_connect_error();
                    }

                    $database = mysqli_select_db($connection, DB_DATABASE);

                    // Fetch products from the database
                    $result = mysqli_query($connection, "SELECT * FROM products");

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
                        echo '<a class="crud-button" href="#" onclick="openUpdateModal(\'' . $row['product_id'] . '\', \'' . $row['name'] . '\', \'' . $row['description'] . '\', \'' . $row['price'] . '\')"><i class="fa-solid fa-pen"></i></a>';
                        echo '<a class="crud-button" href="#" onclick="confirmDelete(' . $row['product_id'] . ')"><i class="fa-solid fa-xmark"></i></a>';
                        echo '</div>';

                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <a class="crud-button center-button" href="#" onclick="openModal()"><i class="fa-solid fa-plus"></i></a>
    </div>

    <!-- UPDATE MODAL -->
        <div id="updateModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeUpdateModal()">&times;</span>
                <!-- Your update form goes here -->
                <form method="post" action="update_product.php" enctype="multipart/form-data" id="updateProductForm">
                    <!-- Other form fields -->
                    <input type="hidden" name="id" id="updateProductId" value="" />

                    <label for="updateName">Name:</label>
                    <input type="text" name="name" id="updateName" value="" required />

                    <label for="updateDescription">Description:</label>
                    <textarea name="description" id="updateDescription" required></textarea>

                    <label for="updatePrice">Price:</label>
                    <input type="text" name="price" id="updatePrice" value="" required />

                    <label for="updateImage">Image:</label>
                    <input type="file" name="image" id="updateImage" />

                    <!-- Hidden input to store the existing image URL -->
                    <input type="hidden" name="existing_image" id="updateExistingImage" value="" />

                    <!-- Submit button -->
                    <input type="submit" value="Update Product" />
                </form>
            </div>
        </div>


    <!-- ADD MODAL -->
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

   
   <!-- JavaScript -->
   <script>
     function openUpdateModal(productId, name, description, price) {
        // Set values for the input fields in the update form
        document.getElementById("updateProductId").value = productId;
        document.getElementById("updateName").value = name;
        document.getElementById("updateDescription").value = description;
        document.getElementById("updatePrice").value = price;

        // Display the update modal
        document.getElementById("updateModal").style.display = "block";
    }

    function closeUpdateModal() {
        // Close the update modal
        document.getElementById("updateModal").style.display = "none";
    }

    function confirmDelete(productId) {
        var confirmDelete = confirm("Are you sure you want to delete this product?");
        if (confirmDelete) {
            window.location.href = 'delete_product.php?id=' + productId;
        }
    }

    function openModal() {
        // Display the add product modal
        document.getElementById("myModal").style.display = "block";
    }

    function closeModal() {
        // Close the add product modal
        document.getElementById("myModal").style.display = "none";
    }

    // Optional: Close the modal if the user clicks outside of it
    window.onclick = function (event) {
        if (event.target === document.getElementById("myModal")) {
            closeModal();
        } else if (event.target === document.getElementById("updateModal")) {
            closeUpdateModal();
        }
    }

    // Add this function to close the modals when the page loads
    window.onload = function () {
        closeModal();
        closeUpdateModal();
    }
</script>


</body>

</html>