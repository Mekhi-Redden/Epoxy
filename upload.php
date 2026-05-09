<?php include("includes/header.php");
requireLogin('auth/login.php');

if (!isSeller()) {
    header("Location: apply_seller.php"); exit;
}

// Load predefined tags grouped by category
$tagRes = $conn->query("SELECT * FROM predefined_tags ORDER BY category, name");
$tagsByCategory = [];
while ($t = $tagRes->fetch_assoc()) {
    $tagsByCategory[$t['category']][] = $t;
}

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $price       = (float)($_POST['price']    ?? 0);
    $description = trim($_POST['description'] ?? '');
    $type        = ($_POST['type'] ?? '') === 'physical' ? 'physical' : 'digital';
    $userId      = $_SESSION['user_id'];
    $selectedTags = $_POST['tags'] ?? [];

    if (!$title || $price < 0 || empty($_FILES['image']['name'])) {
        $error = "Title, price, and image are all required.";
    } else {
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];

        if (!in_array($ext, $allowed)) {
            $error = "Invalid file type. Allowed: JPG, PNG, WEBP, GIF.";
        } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $error = "Upload failed. Please try again.";
        } else {
            $fileName = uniqid('art_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $fileName)) {
                $error = "Could not save file. Check that /images/ is writable.";
            } else {
                $ins = $conn->prepare(
                    "INSERT INTO images (user_id,title,filename,price,description,type) VALUES (?,?,?,?,?,?)"
                );
                $ins->bind_param("issdss", $userId, $title, $fileName, $price, $description, $type);
                $ins->execute();
                $imageId = $conn->insert_id;

                foreach ($selectedTags as $tagName) {
                    $tagName = trim(htmlspecialchars($tagName));
                    if (!$tagName) continue;
                    $ti = $conn->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
                    $ti->bind_param("s", $tagName); $ti->execute();
                    $ts = $conn->prepare("SELECT id FROM tags WHERE name=?");
                    $ts->bind_param("s", $tagName); $ts->execute();
                    $tagId = $ts->get_result()->fetch_assoc()['id'];
                    $ti2 = $conn->prepare("INSERT IGNORE INTO image_tags (image_id,tag_id) VALUES (?,?)");
                    $ti2->bind_param("ii", $imageId, $tagId); $ti2->execute();
                }

                $msg = "Artwork uploaded! <a href='view_art.php?id=$imageId'>View it →</a>";
            }
        }
    }
}
?>
<button class="toggle" onclick="toggleTheme()">EPOXY</button>

<div class="section">
  <h1>Upload Artwork</h1>

  <?php if ($msg): ?><div class="alert-info"><?= $msg ?></div><?php endif; ?>
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="form-card upload-form">

    <div class="form-group">
      <label>Artwork Title <span class="req">*</span></label>
      <input type="text" name="title" placeholder='e.g. "Sunset Over Lagos"' required
             value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Description</label>
      <textarea name="description" rows="3"
        placeholder="Materials, size, inspiration, what makes this piece special..."
        ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Price (USD) <span class="req">*</span></label>
        <input type="number" name="price" min="0" step="0.01" placeholder="0.00" required
               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Artwork Type <span class="req">*</span></label>
        <div class="type-toggle">
          <label class="radio-card">
            <input type="radio" name="type" value="digital"
                   <?= ($_POST['type'] ?? 'digital') === 'digital' ? 'checked' : '' ?>>
            <span>💻 Digital</span>
            <small>Buyer gets a download</small>
          </label>
          <label class="radio-card">
            <input type="radio" name="type" value="physical"
                   <?= ($_POST['type'] ?? '') === 'physical' ? 'checked' : '' ?>>
            <span>🖼 Physical</span>
            <small>Original shipped to buyer</small>
          </label>
        </div>
      </div>
    </div>

    <div class="form-group">
      <label>Image File <span class="req">*</span></label>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required>
      <small class="muted">JPG, PNG, WEBP or GIF. Your image will be watermarked for protection.</small>
    </div>

    <div class="form-group">
      <label>Tags <small>(select all that apply)</small></label>
      <div class="tag-grid">
        <?php foreach ($tagsByCategory as $category => $tags): ?>
          <div class="tag-category">
            <h4><?= htmlspecialchars($category) ?></h4>
            <?php foreach ($tags as $tag): ?>
              <label class="tag-checkbox">
                <input type="checkbox" name="tags[]" value="<?= htmlspecialchars($tag['name']) ?>"
                  <?= in_array($tag['name'], $_POST['tags'] ?? []) ? 'checked' : '' ?>>
                <?= htmlspecialchars($tag['name']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <button type="submit" class="btn-primary" style="margin-top:20px;">Upload Artwork</button>
  </form>
</div>

<?php include("includes/footer.php"); ?>