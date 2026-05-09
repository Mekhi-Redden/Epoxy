<?php
// Unread message count
$_navMsgCount = 0;
$_navNotifCount = 0;

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];

    $m = $conn->prepare("SELECT COUNT(*) AS c FROM messages WHERE receiver_id=? AND is_read=0");
    $m->bind_param("i", $uid);
    $m->execute();
    $_navMsgCount = $m->get_result()->fetch_assoc()['c'];

    $n = $conn->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id=? AND is_read=0");
    $n->bind_param("i", $uid);
    $n->execute();
    $_navNotifCount = $n->get_result()->fetch_assoc()['c'];
}
?>

<link rel="stylesheet" href="css/styles.css">

<style>
/* ================= NAVBAR ================= */
.nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 24px;
    background: rgb(0, 0, 0);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    position: sticky;
    top: 0;
    z-index: 999;
}

/* LEFT BRAND (EPOXY) */
.nav-brand a {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: 2px;
    color: white;
    text-decoration: none;
    padding: 10px 14px;
    border-radius: 8px;
    background: rgba(0, 0, 0, 0.23);
}

/* RIGHT NAV LINKS */
.nav-links {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

/* ALL LINKS SAME SIZE */
.nav-links a {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 10px 14px;
    min-width: 90px;        /* same size buttons */
    text-align: center;

    color: white;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;

    border-radius: 8px;
    background: rgb(0, 0, 0);
    transition: 0.2s ease;
}

/* HOVER EFFECT */
.nav-links a:hover {
    background: rgba(255,255,255,0.18);
    transform: translateY(-1px);
}

/* BADGES */
.badge {
    background: red;
    color: white;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 6px;
}

/* ADMIN LINK SPECIAL */
.admin-link {
    background: rgba(255, 165, 0, 0.2);
}
.admin-link:hover {
    background: rgba(255, 165, 0, 0.4);
}

/* MOBILE */
@media (max-width: 768px) {
    .nav {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }

    .nav-links {
        width: 100%;
    }

    .nav-links a {
        flex: 1;
        min-width: unset;
    }
}
</style>

<nav class="nav">

  <!-- LEFT BRAND -->
  <div class="nav-brand">
    <a href="index.php">EPOXY</a>
  </div>

  <!-- RIGHT LINKS -->
  <div class="nav-links">

    <a href="gallery.php">Gallery</a>
    <a href="search.php">Search</a>

    <?php if (isset($_SESSION['user_id'])): ?>

      <?php if (isSeller()): ?>
        <a href="upload.php">Upload</a>
      <?php else: ?>
        <a href="apply_seller.php">Sell Art</a>
      <?php endif; ?>

      <a href="cart.php">Cart</a>

      <a href="Compose_message.php">
        Messages
        <?php if ($_navMsgCount > 0): ?>
          <span class="badge"><?= $_navMsgCount ?></span>
        <?php endif; ?>
      </a>

      <a href="notifications.php">
        Notifs
        <?php if ($_navNotifCount > 0): ?>
          <span class="badge"><?= $_navNotifCount ?></span>
        <?php endif; ?>
      </a>

      <a href="profile.php">Profile</a>

      <?php if (isAdmin()): ?>
        <a href="admin.php" class="admin-link">Admin</a>
      <?php endif; ?>

      <a href="auth/logout.php">Logout</a>

    <?php else: ?>
      <a href="auth/login.php">Login</a>
      <a href="auth/signup.php">Sign Up</a>
    <?php endif; ?>

  </div>
</nav>