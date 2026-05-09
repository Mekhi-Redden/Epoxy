<?php include("includes/header.php");

// protect page
if(!isset($_SESSION['user_id'])){
  header("Location: auth/login.php");
  exit;
}
?>

<button class="toggle" onclick="toggleTheme()">EPOXY</button>

<div class="section">
    <h1>My Cart</h1>

    <?php
    $userId = $_SESSION['user_id'];
    $res = $conn->query("
        SELECT c.id as cart_id, i.*
        FROM cart c
        JOIN images i ON c.image_id=i.id
        WHERE c.user_id=$userId
    ");

    $total = 0;
    ?>

    <div class="portfolio gallery">
    <?php while($row = $res->fetch_assoc()){
        $total += $row['price'];
    ?>
        <div class="img-wrap">
            <img src="images/<?php echo $row['filename']; ?>">
            
        </div>
        <p><?php echo $row['title']; ?></p>
            <p>$<?php echo $row['price']; ?></p>
            <a href="remove_cart.php?id=<?php echo $row['cart_id']; ?>">
                <button>Remove</button>
            </a>
    <?php } ?>
    </div>

    <h2>Total: $<?php echo $total; ?></h2>
    <a href="checkout.php"><button>Checkout</button></a>
</div>

<?php include("includes/footer.php"); ?>