<?php
include("includes/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth/login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin.php?tab=artworks");
    exit;
}

$id = (int)$_GET['id'];

/*  GET FULL ROW (no guessing column names) */
$stmt = $conn->prepare("SELECT * FROM images WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if ($row) {

    /* STEP 2: FIND IMAGE COLUMN AUTOMATICALLY */
    $file = null;

    $possibleColumns = ['image', 'img', 'file', 'filename', 'image_path', 'url', 'path'];

    foreach ($possibleColumns as $col) {
        if (isset($row[$col]) && !empty($row[$col])) {
            $file = $row[$col];
            break;
        }
    }

    /* DELETE FILE IF FOUND */
    if ($file) {

        // adjust if your uploads folder is used
        $fullPath = $file;

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    /* DELETE DATABASE ROW */
    $del = $conn->prepare("DELETE FROM images WHERE id=?");
    $del->bind_param("i", $id);
    $del->execute();
}

header("Location: admin.php?tab=artworks");
exit;
?>