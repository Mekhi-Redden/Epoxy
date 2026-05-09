<?php include("includes/header.php");

if(!isset($_SESSION['user_id'])){
  header("Location: auth/login.php");
  exit;
}

$user = $_SESSION['user_id'];

$res = $conn->query("
SELECT orders.*, images.title
FROM orders
JOIN images ON orders.image_id=images.id
WHERE orders.buyer_id=$user
");

while($row = $res->fetch_assoc()){
  echo "<p>{$row['title']} - {$row['status']} - $ {$row['amount']}</p>";
}

include("includes/footer.php");