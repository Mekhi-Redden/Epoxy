<?php include("includes/header.php");

$profileId = (int)($_GET['id'] ?? $_SESSION['user_id'] ?? 0);

if (!$profileId) {
  header("Location: auth/login.php");
  exit;
}

/* ---------------- PROFILE USER ---------------- */
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $profileId);
$stmt->execute();
$profileUser = $stmt->get_result()->fetch_assoc();

if (!$profileUser) {
  echo "<div class='section'><p>User not found.</p></div>";
  include("includes/footer.php");
  exit;
}

$isOwn = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $profileId;

/* ---------------- SALES STATS (FIXED) ---------------- */
$totalSales = 0;
$totalRevenue = 0;
$totalViews = 0;

if (in_array($profileUser['role'], ['seller', 'admin'])) {

  $s = $conn->prepare("
    SELECT 
      (SELECT COUNT(*) 
       FROM orders o 
       JOIN images i2 ON o.image_id = i2.id 
       WHERE i2.user_id = ?) AS cnt,

      (SELECT COALESCE(SUM(o.amount),0) 
       FROM orders o 
       JOIN images i2 ON o.image_id = i2.id 
       WHERE i2.user_id = ?) AS rev,

      (SELECT COALESCE(SUM(views),0) 
       FROM images WHERE user_id = ?) AS views
  ");

  $s->bind_param("iii", $profileId, $profileId, $profileId);
  $s->execute();
  $st = $s->get_result()->fetch_assoc();

  $totalSales   = $st['cnt'];
  $totalRevenue = $st['rev'];
  $totalViews   = $st['views'];
}

/* ---------------- ARTWORKS ---------------- */
$artStmt = $conn->prepare("
  SELECT * FROM images 
  WHERE user_id=? 
  ORDER BY id DESC
");
$artStmt->bind_param("i", $profileId);
$artStmt->execute();
$artworks = $artStmt->get_result();

/* ---------------- FAVORITES ---------------- */
$favs = null;
if ($isOwn) {
  $fRes = $conn->prepare("
    SELECT i.*, u.username AS artist_name
    FROM favorites f
    JOIN images i ON f.image_id = i.id
    JOIN users u ON i.user_id = u.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
  ");
  $fRes->bind_param("i", $profileId);
  $fRes->execute();
  $favs = $fRes->get_result();
}

/* ---------------- BIO UPDATE ---------------- */
$updateMsg = '';
if ($isOwn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bio'])) {
  $bio = trim($_POST['bio']);

  $upd = $conn->prepare("UPDATE users SET bio=? WHERE id=?");
  $upd->bind_param("si", $bio, $profileId);
  $upd->execute();

  $profileUser['bio'] = $bio;
  $updateMsg = "Profile updated!";
}
?>

<button class="toggle" onclick="toggleTheme()">EPOXY</button>

<div class="section">

  <!-- PROFILE HEADER -->
  <div class="profile-header">

    <div class="profile-avatar">
      <?= strtoupper(substr($profileUser['username'], 0, 1)) ?>
    </div>

    <div class="profile-info">

      <h1>
        <?= htmlspecialchars($profileUser['username']) ?>

        <?php if ($profileUser['role'] === 'seller'): ?>
          <span class="badge-seller">✓ Seller</span>
        <?php elseif ($profileUser['role'] === 'admin'): ?>
          <span class="badge-admin">Admin</span>
        <?php endif; ?>
      </h1>

      <p>
        <?= $profileUser['bio']
          ? nl2br(htmlspecialchars($profileUser['bio']))
          : '<span class="muted">No bio yet.</span>' ?>
      </p>

    </div>
  </div>

  <!-- STATS -->
  <?php if (in_array($profileUser['role'], ['seller','admin'])): ?>
    <div class="stats-row">
      <div class="stat-card"><h3><?= $artworks->num_rows ?></h3><p>Artworks</p></div>
      <div class="stat-card"><h3><?= $totalSales ?></h3><p>Sales</p></div>
      <div class="stat-card"><h3>$<?= number_format($totalRevenue, 2) ?></h3><p>Revenue</p></div>
      <div class="stat-card"><h3><?= number_format($totalViews) ?></h3><p>Views</p></div>
    </div>
  <?php endif; ?>

  <!-- EDIT BIO -->
  <?php if ($isOwn): ?>
    <details style="margin-bottom:20px;">
      <summary style="cursor:pointer;font-weight:600;">✏ Edit Bio</summary>

      <?php if ($updateMsg): ?>
        <p class="success"><?= $updateMsg ?></p>
      <?php endif; ?>

      <form method="POST" class="form-card" style="margin-top:10px;">
        <textarea name="bio" rows="4"><?= htmlspecialchars($profileUser['bio'] ?? '') ?></textarea>
        <button type="submit" class="btn-primary" style="margin-top:10px;">Save</button>
      </form>
    </details>
  <?php endif; ?>

  <!-- ARTWORKS -->
  <?php if ($artworks->num_rows > 0): ?>
    <h2>Artworks</h2>

    <div class="portfolio gallery">
      <?php while ($row = $artworks->fetch_assoc()): ?>
        <div class="img-wrap">

          <a href="view_art.php?id=<?= $row['id'] ?>">
            <img src="images/<?= htmlspecialchars($row['filename']) ?>"
                 alt="<?= htmlspecialchars($row['title']) ?>" draggable="false">
          </a>

          <div class="img-info">
            <p class="art-title"><?= htmlspecialchars($row['title']) ?></p>
            <p class="art-price">$<?= number_format($row['price'], 2) ?></p>

            <?php if ($isOwn): ?>
              <p class="muted">
                👁 <?= number_format($row['views']) ?> views · <?= ucfirst($row['type']) ?>
              </p>
            <?php else: ?>
              <a href="add_to_cart.php?id=<?= $row['id'] ?>">
                <button class="btn-primary btn-sm">Add to Cart</button>
              </a>
            <?php endif; ?>
          </div>

        </div>
      <?php endwhile; ?>
    </div>

  <?php else: ?>
    <p class="muted">No artworks yet.</p>
  <?php endif; ?>

  <!-- FAVORITES -->
  <?php if ($isOwn && $favs && $favs->num_rows > 0): ?>
    <h2 style="margin-top:40px;">❤ Favorites</h2>

    <div class="portfolio gallery">
      <?php while ($row = $favs->fetch_assoc()): ?>
        <div class="img-wrap">

          <a href="view_art.php?id=<?= $row['id'] ?>">
            <img src="images/<?= htmlspecialchars($row['filename']) ?>"
                 alt="<?= htmlspecialchars($row['title']) ?>" draggable="false">
          </a>

          <div class="img-info">
            <p class="art-title"><?= htmlspecialchars($row['title']) ?></p>
            <p class="muted">by <?= htmlspecialchars($row['artist_name']) ?></p>
          </div>

        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>

</div>

<?php include("includes/footer.php"); ?>