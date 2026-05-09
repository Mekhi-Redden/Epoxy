<?php include("includes/header.php");

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
  header("Location: gallery.php");
  exit;
}

$stmt = $conn->prepare("
  SELECT i.*, u.username AS artist_name, u.id AS artist_id, u.bio AS artist_bio
  FROM images i
  JOIN users u ON i.user_id = u.id
  WHERE i.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$art = $stmt->get_result()->fetch_assoc();

if (!$art) {
  header("Location: gallery.php");
  exit;
}

$isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $art['artist_id'];

/* ---------------- VIEW COUNT ---------------- */
if (!$isOwner) {
  $upd = $conn->prepare("UPDATE images SET views = views + 1 WHERE id = ?");
  $upd->bind_param("i", $id);
  $upd->execute();
  $art['views']++;
}

/* ---------------- TAGS ---------------- */
$ts = $conn->prepare("
  SELECT t.name 
  FROM tags t
  JOIN image_tags it ON t.id = it.tag_id
  WHERE it.image_id = ?
");

$ts->bind_param("i", $id);
$ts->execute();
$tagRows = $ts->get_result();

$tagList = [];
while ($t = $tagRows->fetch_assoc()) {
  $tagList[] = $t['name'];
}
?>

<button class="toggle" onclick="toggleTheme()">EPOXY</button>

<div class="section">

  <p><a href="gallery.php">← Back to Gallery</a></p>

  <div class="art-detail">

    <!-- IMAGE COLUMN -->
    <div class="art-image-col">

      <div class="img-wrap art-main-img">

        <!-- ✅ FIXED IMAGE PATH -->
        <img src="images/<?= htmlspecialchars($art['filename']) ?>"
             alt="<?= htmlspecialchars($art['title']) ?>"
             draggable="false">

      </div>

      <p class="view-count">👁 <?= number_format($art['views']) ?> views</p>

    </div>

    <!-- INFO COLUMN -->
    <div class="art-info-col">

      <h1><?= htmlspecialchars($art['title']) ?></h1>

      <p class="art-meta">
        by 
        <a href="profile.php?id=<?= $art['artist_id'] ?>">
          <strong><?= htmlspecialchars($art['artist_name']) ?></strong>
        </a>
        &nbsp;·&nbsp;
        <span class="art-type <?= $art['type'] ?>">
          <?= $art['type'] === 'digital' ? '💻 Digital' : '🖼 Physical' ?>
        </span>
      </p>

      <h2 class="art-price">$<?= number_format($art['price'], 2) ?></h2>

      <?php if (!empty($art['description'])): ?>
        <p class="art-description">
          <?= nl2br(htmlspecialchars($art['description'])) ?>
        </p>
      <?php endif; ?>

      <!-- TAGS -->
      <?php if (!empty($tagList)): ?>
        <div class="tag-list">
          <?php foreach ($tagList as $tag): ?>
            <a href="search.php?tag=<?= urlencode($tag) ?>" class="tag-chip">
              <?= htmlspecialchars($tag) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- SHIPPING INFO -->
      <div class="shipping-note">
        <?php if ($art['type'] === 'physical'): ?>
          🚚 <strong>Physical Artwork</strong> — Shipping handled at checkout.
        <?php else: ?>
          💻 <strong>Digital Artwork</strong> — Instant download after purchase.
        <?php endif; ?>
      </div>

      <!-- ACTION BUTTONS -->
      <?php if (!$isOwner): ?>
        <div class="art-buttons">

          <a href="add_to_cart.php?id=<?= $art['id'] ?>">
            <button class="btn-primary">🛒 Add to Cart</button>
          </a>

          <a href="favorite.php?id=<?= $art['id'] ?>">
            <button class="btn-ghost">❤ Like</button>
          </a>

        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="compose_message.php?reply_to=<?= $art['artist_id'] ?>&subject=<?= urlencode('Commission Inquiry: '.$art['title']) ?>">
            <button class="btn-secondary" style="margin-top:12px;">
              ✉ Message Artist / Commission
            </button>
          </a>
        <?php else: ?>
          <p style="margin-top:12px;">
            <a href="auth/login.php">Login to message or commission</a>
          </p>
        <?php endif; ?>

      <?php else: ?>

        <div class="alert-info">
          <strong>This is your artwork.</strong>
          👁 <?= number_format($art['views']) ?> views so far.
        </div>

      <?php endif; ?>

      <!-- ARTIST CARD -->
      <div class="artist-card">

        <h4>About the Artist</h4>

        <p>
          <a href="profile.php?id=<?= $art['artist_id'] ?>">
            <strong><?= htmlspecialchars($art['artist_name']) ?></strong>
          </a>
        </p>

        <?php if (!empty($art['artist_bio'])): ?>
          <p>
            <?= nl2br(htmlspecialchars(mb_substr($art['artist_bio'], 0, 200))) ?>
            <?= mb_strlen($art['artist_bio']) > 200 ? '...' : '' ?>
          </p>
        <?php else: ?>
          <p class="muted">No bio yet.</p>
        <?php endif; ?>

        <a href="profile.php?id=<?= $art['artist_id'] ?>">
          View full profile →
        </a>

      </div>

    </div>
  </div>
</div>

<?php include("includes/footer.php"); ?>