<?php include("includes/header.php"); ?>

<button class="toggle" onclick="toggleTheme()">EPOXY</button>

<div class="section">
  <h1>Search Artwork</h1>

  <?php
  $q          = trim($_GET['q'] ?? '');
  $sort       = $_GET['sort'] ?? 'newest';
  $tagFilter  = $_GET['tag'] ?? '';
  $typeFilter = $_GET['type'] ?? '';

  $orderBy = match($sort) {
      'price_asc'  => "images.price ASC",
      'price_desc' => "images.price DESC",
      'popular'    => "images.views DESC",
      default      => "images.id DESC",
  };

  $allTags = $conn->query("SELECT DISTINCT name FROM predefined_tags ORDER BY name");
  ?>

  <form method="GET" class="search-bar">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
           placeholder="Search title, artist, or tag...">
    <button type="submit" class="btn-primary">Search</button>
  </form>

  <div class="search-filters">
    <form method="GET" style="display:flex;gap:16px;flex-wrap:wrap;">

      <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">

      <label>Sort:
        <select name="sort" onchange="this.form.submit()">
          <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
          <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price ↑</option>
          <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price ↓</option>
          <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Most Viewed</option>
        </select>
      </label>

      <label>Type:
        <select name="type" onchange="this.form.submit()">
          <option value="">All</option>
          <option value="digital" <?= $typeFilter==='digital'?'selected':'' ?>>Digital</option>
          <option value="physical" <?= $typeFilter==='physical'?'selected':'' ?>>Physical</option>
        </select>
      </label>

      <label>Tag:
        <select name="tag" onchange="this.form.submit()">
          <option value="">All Tags</option>
          <?php while ($t = $allTags->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($t['name']) ?>"
              <?= $tagFilter===$t['name']?'selected':'' ?>>
              <?= htmlspecialchars($t['name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </label>

    </form>
  </div>

  <?php if ($q || $tagFilter || $typeFilter):

    $where  = "1=1";
    $params = [];
    $types  = "";

    if ($q) {
        $like = "%$q%";
        $where .= " AND (images.title LIKE ? OR u.username LIKE ? OR EXISTS (
            SELECT 1 FROM image_tags it JOIN tags t ON t.id=it.tag_id
            WHERE it.image_id=images.id AND t.name LIKE ?
        ))";
        $params = [$like, $like, $like];
        $types = "sss";
    }

    if ($tagFilter) {
        $where .= " AND EXISTS (
            SELECT 1 FROM image_tags it JOIN tags t ON t.id=it.tag_id
            WHERE it.image_id=images.id AND t.name=?
        )";
        $params[] = $tagFilter;
        $types .= "s";
    }

    if ($typeFilter) {
        $where .= " AND images.type=?";
        $params[] = $typeFilter;
        $types .= "s";
    }

    $sql = "SELECT DISTINCT images.*, u.username AS artist_name
            FROM images
            JOIN users u ON images.user_id=u.id
            WHERE $where
            ORDER BY $orderBy";

    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
  ?>

  <p class="muted"><?= $res->num_rows ?> result(s)</p>

  <div class="portfolio gallery">
    <?php while ($row = $res->fetch_assoc()): ?>
      <div class="img-wrap">

        <a href="view_art.php?id=<?= $row['id'] ?>">

          <!-- FIXED -->
          <img src="images/<?= htmlspecialchars($row['filename']) ?>"
               alt="<?= htmlspecialchars($row['title']) ?>" draggable="false">

        </a>

        <div class="img-info">
          <span class="art-type <?= $row['type'] ?>">
            <?= $row['type']==='digital'?'💻':'🖼' ?> <?= ucfirst($row['type']) ?>
          </span>

          <p class="art-title"><?= htmlspecialchars($row['title']) ?></p>
          <p class="art-artist">by <?= htmlspecialchars($row['artist_name']) ?></p>
          <p class="art-price">$<?= number_format($row['price'], 2) ?></p>

        </div>

      </div>
    <?php endwhile; ?>
  </div>

  <?php endif; ?>
</div>

<?php include("includes/footer.php"); ?>