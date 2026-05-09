<?php include("includes/header.php"); ?>

<button class="toggle" onclick="toggleTheme()">EPOXY</button>

<div class="section">
  <h1>Gallery</h1>

  <?php
  $sort       = $_GET['sort']  ?? 'newest';
  $tagFilter  = $_GET['tag']   ?? '';
  $typeFilter = $_GET['type']  ?? '';
  $page       = max(1, (int)($_GET['page'] ?? 1));

  $perPage    = 10;
  $offset     = ($page - 1) * $perPage;

  $where  = "1=1";
  $params = [];
  $types  = "";

  if ($tagFilter) {
      $where .= " AND EXISTS (
          SELECT 1 FROM image_tags it 
          JOIN tags t ON t.id=it.tag_id
          WHERE it.image_id=images.id AND t.name=?
      )";
      $params[] = $tagFilter;
      $types   .= "s";
  }

  if ($typeFilter === 'digital' || $typeFilter === 'physical') {
      $where .= " AND images.type=?";
      $params[] = $typeFilter;
      $types   .= "s";
  }

  $orderBy = match($sort) {
      'price_asc'  => "images.price ASC",
      'price_desc' => "images.price DESC",
      'popular'    => "images.views DESC",
      default      => "images.id DESC",
  };

  /* ── COUNT ── */
  $countSql = "SELECT COUNT(DISTINCT images.id) AS cnt FROM images WHERE $where";

  if ($params) {
      $cs = $conn->prepare($countSql);
      $cs->bind_param($types, ...$params);
      $cs->execute();
      $total = $cs->get_result()->fetch_assoc()['cnt'];
  } else {
      $total = $conn->query($countSql)->fetch_assoc()['cnt'];
  }

  $totalPages = (int)ceil($total / $perPage);

  /* ── QUERY ── */
  $sql = "SELECT DISTINCT images.*, u.username AS artist_name
          FROM images 
          JOIN users u ON images.user_id = u.id
          WHERE $where 
          ORDER BY $orderBy
          LIMIT $perPage OFFSET $offset";

  if ($params) {
      $stmt = $conn->prepare($sql);
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $res = $stmt->get_result();
  } else {
      $res = $conn->query($sql);
  }

  $allTags = $conn->query("SELECT DISTINCT name FROM predefined_tags ORDER BY name");
  ?>

  <div class="gallery-layout">

    <!-- FILTERS -->
    <aside class="filter-sidebar">
      <form method="GET">

        <div class="filter-section">
          <h4>Sort By</h4>
          <select name="sort" onchange="this.form.submit()">
            <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
            <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price ↑</option>
            <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price ↓</option>
            <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Most Viewed</option>
          </select>
        </div>

        <div class="filter-section">
          <h4>Type</h4>
          <?php foreach (['' => 'All', 'digital' => '💻 Digital', 'physical' => '🖼 Physical'] as $val => $label): ?>
            <label>
              <input type="radio" name="type" value="<?= $val ?>"
                <?= $typeFilter === $val ? 'checked' : '' ?>
                onchange="this.form.submit()">
              <?= $label ?>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="filter-section">
          <h4>Tag</h4>
          <select name="tag" onchange="this.form.submit()">
            <option value="">All Tags</option>
            <?php while ($t = $allTags->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($t['name']) ?>"
                <?= $tagFilter === $t['name'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($t['name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <input type="hidden" name="page" value="1">

        <button type="button" class="btn-ghost"
                style="width:100%;margin-top:8px;"
                onclick="window.location='gallery.php'">
          Clear Filters
        </button>

      </form>
    </aside>

    <!-- GALLERY -->
    <div class="gallery-main">

      <p class="muted"><?= $total ?> artwork<?= $total != 1 ? 's' : '' ?></p>

      <?php if ($res->num_rows === 0): ?>
        <p class="muted">No artwork found.</p>
      <?php endif; ?>

      <div class="portfolio gallery">

        <?php while ($row = $res->fetch_assoc()): ?>
          <div class="img-wrap">

            <a href="view_art.php?id=<?= $row['id'] ?>">

              <div class="image-container">

                <img src="images/<?= htmlspecialchars($row['filename']) ?>"
                     alt="<?= htmlspecialchars($row['title']) ?>">

                <div class="watermark-layer">
                  <div class="watermark-text">EPOXY ART • EPOXY ART • EPOXY ART</div>
                  <div class="watermark-text">EPOXY ART • EPOXY ART • EPOXY ART</div>
                </div>

              </div>

            </a>

            <!-- INFO + BUTTONS -->
            <div class="img-info">

              <p class="art-title"><?= htmlspecialchars($row['title']) ?></p>

              <p class="art-artist">
                by <?= htmlspecialchars($row['artist_name']) ?>
              </p>

              <p class="art-price">$<?= number_format($row['price'], 2) ?></p>

              <!-- ACTION BUTTONS -->
              <div class="art-actions" style="display:flex; gap:8px; margin-top:8px;">

                <!-- ADD TO CART -->
                <a href="add_to_cart.php?id=<?= $row['id'] ?>">
                  <button class="btn-primary btn-sm">Add to Cart</button>
                </a>

                <!-- FAVORITE -->
                <a href="favorite.php?id=<?= $row['id'] ?>">
                  <button class="btn-ghost btn-sm">❤</button>
                </a>

              </div>

            </div>

          </div>
        <?php endwhile; ?>

      </div>

      <!-- PAGINATION -->
      <?php if ($totalPages > 1):

        $baseParams = http_build_query(array_filter([
            'sort' => $sort !== 'newest' ? $sort : '',
            'tag'  => $tagFilter,
            'type' => $typeFilter,
        ]));

        $base = "gallery.php?" . ($baseParams ? $baseParams . "&" : "");
      ?>

      <div class="pagination">

        <?php if ($page > 1): ?>
          <a href="<?= $base ?>page=<?= $page-1 ?>">← Prev</a>
        <?php endif; ?>

        <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
          <a href="<?= $base ?>page=<?= $p ?>"
             class="<?= $p==$page?'active':'' ?>">
             <?= $p ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="<?= $base ?>page=<?= $page+1 ?>">Next →</a>
        <?php endif; ?>

      </div>

      <?php endif; ?>

    </div>
  </div>
</div>

<?php include("includes/footer.php"); ?>