<?php include("includes/db.php");

// FIX: require login
if(!isset($_SESSION['user_id'])){
  header("Location: auth/login.php");
  exit;
}

// Add item to cart (DB only)
$id = $_GET['id'];
$user = $_SESSION['user_id'];

$conn->query("INSERT INTO cart (user_id,image_id) VALUES ($user,$id)");

header("Location: cart.php");
exit;