<?php
include("includes/header.php");

if(!isset($_SESSION['user_id'])){
  header("Location: auth/login.php");
  exit;
}

$user = $_SESSION['user_id'];

$res = $conn->query("SELECT * FROM notifications WHERE user_id=$user ORDER BY id DESC");

while($row = $res->fetch_assoc()){
  echo "<p>{$row['message']}</p>";
}

$conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$user");

include("includes/footer.php");

