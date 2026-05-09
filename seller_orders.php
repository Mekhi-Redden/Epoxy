<?php include("includes/header.php");

if(!isset($_SESSION['user_id'])){
  header("Location: auth/login.php");
  exit;
}

$uid = $_SESSION['user_id'];

if($_POST){
  $conn->query("UPDATE orders SET status='{$_POST['status']}' WHERE id={$_POST['id']}");
}

$res = $conn->query("
SELECT orders.*, images.title 
FROM orders
JOIN images ON orders.image_id=images.id
WHERE images.user_id=$uid
");

while($row = $res->fetch_assoc()){
  echo "{$row['title']} - {$row['status']}";

  echo "<form method='POST'>
  <input type='hidden' name='id' value='{$row['id']}'>
  <select name='status'>
    <option>pending</option>
    <option>shipped</option>
    <option>completed</option>
  </select>
  <button>Update</button>
  </form>";
}
?>