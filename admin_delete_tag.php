<?php
include("includes/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth/login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin.php?tab=tags");
    exit;
}

$id = (int)$_GET['id'];

/*  delete relationships first (IMPORTANT) */
$conn->query("DELETE FROM image_tags WHERE tag_id=$id");

/*  delete tag itself */
$conn->query("DELETE FROM tags WHERE id=$id");

header("Location: admin.php?tab=tags");
exit;
?>