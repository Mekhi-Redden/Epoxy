
<?php
include("includes/header.php");
?>

<button class="toggle" onclick="toggleTheme()">EPOXY</button>

<div class="section">
    <h1>Art Prices</h1>

    <?php
    $res = $conn->query("SELECT * FROM images ORDER BY price ASC");
    ?>

    <div class="portfolio gallery">
    <?php while($row = $res->fetch_assoc()){ ?>
        <div class="img-wrap">
            <img src="images/<?php echo $row['filename']; ?>">
            <span class="copyright">© <?php echo date("Y"); ?> EPOXY</span>
            <p><?php echo $row['title']; ?></p>
            <p>Price: $<?php echo $row['price']; ?></p>

            <a href="add_to_cart.php?id=<?php echo $row['id']; ?>">
                <button>Add to Cart</button>
            </a>

            <a href="favorite.php?id=<?php echo $row['id']; ?>">
                <button>❤️ Like</button>
            </a>
        </div>
    <?php } ?>
    </div>
</div>

<?php include("includes/footer.php"); ?>