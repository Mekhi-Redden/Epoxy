<?php
include("includes/db.php");

// Protect page
if (!isset($_SESSION['user_id'])) {
  header("Location: auth/login.php");
  exit;
}

$userId = (int)$_SESSION['user_id'];
$imageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate image ID
if ($imageId <= 0) {
  header("Location: index.php");
  exit;
}

// Use prepared statement 
$stmt = $conn->prepare("
  INSERT IGNORE INTO favorites (user_id, image_id)
  VALUES (?, ?)
");

$stmt->bind_param("ii", $userId, $imageId);
$stmt->execute();

$stmt->close();

// Return user back 
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "index.php"));
exit;