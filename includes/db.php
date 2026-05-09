<?php
// ============================================================
// includes/db.php – database connection + session bootstrap
// ============================================================

// Connect to MySQL
$conn = new mysqli(
    "sql301.infinityfree.com",   // MySQL Hostname
    "if0_41869402",              // MySQL Username
    "Slicer101",                 // MySQL Password
    "if0_41869402_xxx"           // MySQL Database Name
);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session safely (only once)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load global helper functions (isSeller, isAdmin, requireLogin …)
require_once __DIR__ . "/functions.php";

// Predefined tag list used site-wide
define('PRESET_TAGS', [
    'Abstract', 'Portrait', 'Landscape', 'Digital Art', 'Photography',
    'Illustration', 'Watercolor', 'Oil Painting', 'Sketch', 'Anime',
    'Fantasy', 'Nature', 'Architecture', 'Street Art', 'Minimalist',
    'Surrealism', 'Pop Art', 'Black & White', 'Vintage', 'NFT',
    'Character Design', 'Concept Art', 'Fan Art', 'Typography', '3D Art'
]);
?>