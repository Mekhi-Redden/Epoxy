<?php
include("includes/header.php");

// Only admins may access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: auth/login.php");
  exit;
}

// ── Handle seller application approvals / rejections ───────
if ($_POST && isset($_POST['app_action'])) {
  $appId  = (int)$_POST['app_id'];
  $action = $_POST['app_action'] === 'approve' ? 'approved' : 'rejected';

  // Update the application status
  $conn->query("UPDATE seller_applications SET status='$action', reviewed_at=NOW() WHERE id=$appId");

  // If approved, promote user to seller role
  if ($action === 'approved') {
    $res = $conn->query("SELECT user_id FROM seller_applications WHERE id=$appId");
    $uid = $res->fetch_assoc()['user_id'];
    $conn->query("UPDATE users SET role='seller' WHERE id=$uid");
    // Notify the user
    $conn->query("INSERT INTO notifications (user_id, message)
                  VALUES ($uid, 'Your seller application has been approved! You can now upload artwork.')");
  }
  header("Location: admin.php?tab=applications");
  exit;
}

// ── Handle order status updates ─────────────────────────────
if ($_POST && isset($_POST['order_status'])) {
  $orderId = (int)$_POST['order_id'];
  $status  = $conn->real_escape_string($_POST['order_status']);
  $conn->query("UPDATE orders SET status='$status' WHERE id=$orderId");
  header("Location: admin.php?tab=orders");
  exit;
}

// ── Handle user ban / unban ─────────────────────────────────
if ($_POST && isset($_POST['ban_action'])) {
  $targetUid = (int)$_POST['target_uid'];
  $newRole   = $_POST['ban_action'] === 'ban' ? 'banned' : 'user';
  $conn->query("UPDATE users SET role='$newRole' WHERE id=$targetUid");
  header("Location: admin.php?tab=users");
  exit;
}

// ── Mark contact message as read ────────────────────────────
if (isset($_GET['read_msg'])) {
  $msgId = (int)$_GET['read_msg'];
  $conn->query("UPDATE contact_messages SET is_read=1 WHERE id=$msgId");
  header("Location: admin.php?tab=messages");
  exit;
}

// ── Active tab ───────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'overview';

// ── Fetch overview stats ─────────────────────────────────────
$totalUsers    = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$totalSales    = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$totalRevenue  = $conn->query("SELECT COALESCE(SUM(amount),0) as s FROM orders")->fetch_assoc()['s'];
$totalArtworks = $conn->query("SELECT COUNT(*) as c FROM images")->fetch_assoc()['c'];
$pendingApps   = $conn->query("SELECT COUNT(*) as c FROM seller_applications WHERE status='pending'")->fetch_assoc()['c'];
$unreadMsgs    = $conn->query("SELECT COUNT(*) as c FROM contact_messages WHERE is_read=0")->fetch_assoc()['c'];

// ── Per-account stats (for the stats tab) ───────────────────
$accountStats = $conn->query("
  SELECT
    u.id,
    u.username,
    u.role,
    COUNT(DISTINCT o.id)        AS total_sales,
    COALESCE(SUM(o.amount), 0)  AS total_revenue,
    COUNT(DISTINCT i.id)        AS total_artworks,
    (SELECT COUNT(*) FROM messages WHERE sender_id=u.id)   AS msgs_sent,
    (SELECT COUNT(*) FROM messages WHERE receiver_id=u.id) AS msgs_received
  FROM users u
  LEFT JOIN images i ON i.user_id = u.id
  LEFT JOIN orders o ON o.image_id = i.id
  GROUP BY u.id
  ORDER BY total_revenue DESC
");
?>

<button class="toggle" onclick="toggleTheme()">EPOXY</button>

<div class="section">
  <h1>⚙️ Admin Dashboard</h1>

  <!-- Tab navigation -->
  <nav class="admin-tabs">
    <a href="?tab=overview"      class="<?= $tab==='overview'      ? 'active':'' ?>">Overview</a>
    <a href="?tab=users"         class="<?= $tab==='users'         ? 'active':'' ?>">Users</a>
    <a href="?tab=stats"         class="<?= $tab==='stats'         ? 'active':'' ?>">Account Stats</a>
    <a href="?tab=applications"  class="<?= $tab==='applications'  ? 'active':'' ?>">
      Applications <?= $pendingApps > 0 ? "($pendingApps)" : '' ?>
    </a>
    <a href="?tab=orders"        class="<?= $tab==='orders'        ? 'active':'' ?>">Orders</a>
    <a href="?tab=messages"      class="<?= $tab==='messages'      ? 'active':'' ?>">
      Messages <?= $unreadMsgs > 0 ? "($unreadMsgs)" : '' ?>
    </a>
    <a href="?tab=user_messages" class="<?= $tab==='user_messages' ? 'active':'' ?>">User DMs</a>
    <a href="?tab=artworks"      class="<?= $tab==='artworks'      ? 'active':'' ?>">Artworks</a>
    <a href="?tab=tags"          class="<?= $tab==='tags'          ? 'active':'' ?>">Tags</a>
  </nav>

  <!-- ── OVERVIEW ─────────────────────────────────── -->
  <?php if ($tab === 'overview'): ?>
  <div class="admin-cards">
    <div class="admin-card"><h3><?= $totalUsers ?></h3><p>Total Users</p></div>
    <div class="admin-card"><h3><?= $totalSales ?></h3><p>Total Sales</p></div>
    <div class="admin-card"><h3>$<?= number_format($totalRevenue, 2) ?></h3><p>Total Revenue</p></div>
    <div class="admin-card"><h3><?= $totalArtworks ?></h3><p>Artworks Listed</p></div>
    <div class="admin-card"><h3><?= $pendingApps ?></h3><p>Pending Seller Apps</p></div>
    <div class="admin-card"><h3><?= $unreadMsgs ?></h3><p>Unread Support Msgs</p></div>
  </div>

  <!-- ── USERS ─────────────────────────────────────── -->
<?php elseif ($tab === 'users'): ?>

<?php
// Handle user deletion
if ($_POST && isset($_POST['delete_user'])) {
  $targetUid = (int)$_POST['target_uid'];
  if ($targetUid !== (int)$_SESSION['user_id']) {
    // Delete related data first to avoid FK constraint errors
    $conn->query("DELETE FROM notifications   WHERE user_id=$targetUid");
    $conn->query("DELETE FROM messages        WHERE sender_id=$targetUid OR receiver_id=$targetUid");
  
    $conn->query("DELETE FROM seller_applications WHERE user_id=$targetUid");
    $conn->query("DELETE FROM orders          WHERE buyer_id=$targetUid");
    $conn->query("DELETE FROM image_tags      WHERE image_id IN (SELECT id FROM images WHERE user_id=$targetUid)");
    $conn->query("DELETE FROM images          WHERE user_id=$targetUid");
    $conn->query("DELETE FROM users           WHERE id=$targetUid");
  }
  header("Location: admin.php?tab=users");
  exit;
}
?>

<h2>All Users</h2>
<table class="admin-table">
  <tr><th>ID</th><th>Username</th><th>Role</th><th>Actions</th></tr>
  <?php
  $users = $conn->query("SELECT * FROM users ORDER BY id DESC");
  while ($u = $users->fetch_assoc()):
    $isSelf = ($u['id'] == $_SESSION['user_id']);
  ?>
  <tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['username']) ?></td>
    <td><?= $u['role'] ?></td>
    <td style="display:flex; gap:6px; flex-wrap:wrap;">
      <?php if (!$isSelf): ?>

        <!-- Ban / Unban -->
        <form method="POST">
          <input type="hidden" name="target_uid" value="<?= $u['id'] ?>">
          <?php if ($u['role'] === 'banned'): ?>
            <button name="ban_action" value="unban">Unban</button>
          <?php else: ?>
            <button name="ban_action" value="ban"
                    onclick="return confirm('Ban this user?')">Ban</button>
          <?php endif; ?>
        </form>

        <!-- Delete -->
        <form method="POST"
              onsubmit="return confirm('Permanently delete <?= htmlspecialchars($u['username'], ENT_QUOTES) ?> and ALL their data? This cannot be undone.')">
          <input type="hidden" name="target_uid"   value="<?= $u['id'] ?>">
          <input type="hidden" name="delete_user"  value="1">
          <button> Delete</button>
        </form>

      <?php else: echo '(you)'; endif; ?>
    </td>
  </tr>
  <?php endwhile; ?>
</table>

  <!-- ── ACCOUNT STATS ─────────────────────────────── -->
  <?php elseif ($tab === 'stats'): ?>
  <h2>Per-Account Sales & Revenue</h2>
  <table class="admin-table">
    <tr>
      <th>User</th><th>Role</th><th>Artworks</th>
      <th>Sales</th><th>Revenue</th><th>Msgs Sent</th><th>Msgs Rcvd</th>
    </tr>
    <?php while ($row = $accountStats->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['username']) ?></td>
      <td><?= $row['role'] ?></td>
      <td><?= $row['total_artworks'] ?></td>
      <td><?= $row['total_sales'] ?></td>
      <td>$<?= number_format($row['total_revenue'], 2) ?></td>
      <td><?= $row['msgs_sent'] ?></td>
      <td><?= $row['msgs_received'] ?></td>
    </tr>
    <?php endwhile; ?>
  </table>

  <!-- ── SELLER APPLICATIONS ───────────────────────── -->
  <?php elseif ($tab === 'applications'): ?>
  <h2>Seller Applications</h2>
  <?php
  $apps = $conn->query("
    SELECT sa.*, u.username
    FROM seller_applications sa
    JOIN users u ON u.id = sa.user_id
    ORDER BY sa.status ASC, sa.created_at DESC
  ");
  while ($app = $apps->fetch_assoc()):
  ?>
  <div class="admin-card" style="margin-bottom:15px">
    <strong><?= htmlspecialchars($app['username']) ?></strong>
    <span style="margin-left:10px;opacity:.6"><?= $app['created_at'] ?></span>
    <span style="margin-left:10px;font-weight:bold;color:<?= $app['status']==='approved'?'green':($app['status']==='rejected'?'red':'orange') ?>">
      <?= strtoupper($app['status']) ?>
    </span>
    <p><?= nl2br(htmlspecialchars($app['reason'])) ?></p>
    <?php if ($app['portfolio_url']): ?>
      <p>Portfolio: <a href="<?= htmlspecialchars($app['portfolio_url']) ?>" target="_blank">View</a></p>
    <?php endif; ?>
    <?php if ($app['status'] === 'pending'): ?>
    <form method="POST" style="display:inline">
      <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
      <button name="app_action" value="approve">✅ Approve</button>
      <button name="app_action" value="reject">❌ Reject</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endwhile; ?>

  <!-- ── ORDERS ─────────────────────────────────────── -->
  <?php elseif ($tab === 'orders'): ?>
  <h2>All Orders</h2>
  <table class="admin-table">
    <tr><th>ID</th><th>Buyer</th><th>Artwork</th><th>Amount</th><th>Status</th><th>Update</th></tr>
    <?php
    $orders = $conn->query("
      SELECT o.*, u.username AS buyer, i.title
      FROM orders o
      JOIN users u ON u.id = o.buyer_id
      JOIN images i ON i.id = o.image_id
      ORDER BY o.id DESC
    ");
    while ($o = $orders->fetch_assoc()):
    ?>
    <tr>
      <td><?= $o['id'] ?></td>
      <td><?= htmlspecialchars($o['buyer']) ?></td>
      <td><?= htmlspecialchars($o['title']) ?></td>
      <td>$<?= number_format($o['amount'], 2) ?></td>
      <td><?= $o['status'] ?></td>
      <td>
        <form method="POST" style="display:flex;gap:5px">
          <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
          <select name="order_status">
            <option <?= $o['status']==='pending'?'selected':'' ?>>pending</option>
            <option <?= $o['status']==='shipped'?'selected':'' ?>>shipped</option>
            <option <?= $o['status']==='completed'?'selected':'' ?>>completed</option>
          </select>
          <button>Save</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>

  <!-- ── CONTACT MESSAGES ──────────────────────────── -->
  <?php elseif ($tab === 'messages'): ?>
  <h2>Support / Contact Messages</h2>
  <?php
  $msgs = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
  while ($m = $msgs->fetch_assoc()):
    $bgStyle = $m['is_read'] ? '' : "border-left:4px solid var(--accent,#e74)";
  ?>
  <div class="admin-card" style="margin-bottom:12px;<?= $bgStyle ?>">
    <strong><?= htmlspecialchars($m['name']) ?></strong>
    &lt;<?= htmlspecialchars($m['email']) ?>&gt;
    <span style="float:right;opacity:.5"><?= $m['created_at'] ?></span>
    <p><?= nl2br(htmlspecialchars($m['message'])) ?></p>
    <?php if (!$m['is_read']): ?>
      <a href="?tab=messages&read_msg=<?= $m['id'] ?>"><button>Mark Read</button></a>
    <?php endif; ?>
  </div>
  <?php endwhile; ?>

  <!-- ── USER DMs (all messages between users) ─────── -->
  <?php elseif ($tab === 'user_messages'): ?>
  <h2>All User Messages / DMs</h2>
  <table class="admin-table">
    <tr><th>From</th><th>To</th><th>Subject</th><th>Preview</th><th>Date</th></tr>
    <?php
    $dms = $conn->query("
      SELECT m.*, s.username AS sender, r.username AS receiver
      FROM messages m
      JOIN users s ON s.id = m.sender_id
      JOIN users r ON r.id = m.receiver_id
      ORDER BY m.created_at DESC
      LIMIT 200
    ");
    while ($dm = $dms->fetch_assoc()):
    ?>
    <tr>
      <td><?= htmlspecialchars($dm['sender']) ?></td>
      <td><?= htmlspecialchars($dm['receiver']) ?></td>
      <td><?= htmlspecialchars($dm['subject']) ?></td>
      <td><?= htmlspecialchars(substr($dm['body'], 0, 60)) ?>…</td>
      <td><?= $dm['created_at'] ?></td>
    </tr>
    <?php endwhile; ?>
  </table>

  <!-- ── ARTWORKS ──────────────────────────────────── -->
  <?php elseif ($tab === 'artworks'): ?>
  <h2>All Artworks</h2>
  <table class="admin-table">
    <tr><th>ID</th><th>Title</th><th>Artist</th><th>Price</th><th>Type</th><th>Views</th><th>Sold</th><th>Delete</th></tr>
    <?php
    $arts = $conn->query("
      SELECT i.*, u.username AS artist
      FROM images i JOIN users u ON u.id = i.user_id
      ORDER BY i.id DESC
    ");
    while ($a = $arts->fetch_assoc()):
    ?>
    <tr>
      <td><?= $a['id'] ?></td>
      <td><?= htmlspecialchars($a['title']) ?></td>
      <td><?= htmlspecialchars($a['artist']) ?></td>
      <td>$<?= $a['price'] ?></td>
      <td><?= $a['type'] ?? 'digital' ?></td>
      <td><?= $a['view_count'] ?? 0 ?></td>
      <td><?= $a['sold'] ?? 0 ?></td>
      <td>
        <a href="admin_delete_art.php?id=<?= $a['id'] ?>"
           onclick="return confirm('Delete this artwork?')">
          <button>🗑</button>
        </a>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>


  

  <!-- ── TAGS MANAGEMENT ───────────────────────────── -->
<?php elseif ($tab === 'tags'): ?>
<h2>Manage Tags</h2>

<!-- Add new tag -->
<form method="POST" action="admin_add_tag.php" 
      style="margin-bottom:20px;display:flex;gap:10px">
  <input type="text" name="tag_name" placeholder="New tag name" required>
  <button>Add Tag</button>
</form>

<table class="admin-table">
  <tr>
    <th>ID</th>
    <th>Tag</th>
    <th>Usage Count</th>
    <th>Delete</th>
  </tr>

  <?php
  $tags = $conn->query("
    SELECT 
      t.id, 
      t.name, 
      COUNT(it.image_id) AS usage_count
    FROM tags t
    LEFT JOIN image_tags it ON it.tag_id = t.id
    GROUP BY t.id, t.name
    ORDER BY usage_count DESC
  ");

  while ($t = $tags->fetch_assoc()):
  ?>
  <tr>
    <td><?= $t['id'] ?></td>
    <td><?= htmlspecialchars($t['name']) ?></td>
    <td><?= $t['usage_count'] ?></td>
    <td>
      <a href="admin_delete_tag.php?id=<?= $t['id'] ?>"
         onclick="return confirm('Delete tag?')">
        <button>🗑</button>
      </a>
    </td>
  </tr>
  <?php endwhile; ?>
</table>

<?php endif; ?>

<!-- Admin-specific styling -->
<style>
.admin-tabs { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:25px; }
.admin-tabs a {
  padding:8px 16px; border-radius:6px; text-decoration:none;
  border:1px solid currentColor; opacity:.6; transition:.2s;
}
.admin-tabs a.active, .admin-tabs a:hover { opacity:1; font-weight:bold; }
.admin-cards { display:flex; flex-wrap:wrap; gap:15px; margin-bottom:30px; }
.admin-card {
  flex:1; min-width:150px; padding:20px; border-radius:10px;
  background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1);
}
.admin-card h3 { font-size:2em; margin:0 0 5px; }
.admin-table { width:100%; border-collapse:collapse; margin-top:15px; }
.admin-table th, .admin-table td {
  padding:10px 12px; text-align:left;
  border-bottom:1px solid rgba(255,255,255,.08);
}
.admin-table th { opacity:.6; font-size:.85em; text-transform:uppercase; }
/* Artwork thumbnails */
.admin-thumb {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid rgba(255,255,255,0.2);
}





/* Slight spacing improvement */
.artwork-table td {
  vertical-align: middle;
}/* Artwork thumbnails */
.admin-thumb {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid rgba(255,255,255,0.2);
}

/* Make delete button more visible */
.delete-btn {
  background: #e74c3c;
  color: white;
  border: none;
  padding: 6px 10px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
}

.delete-btn:hover {
  background: #c0392b;
}

/* Slight spacing improvement */
.artwork-table td {
  vertical-align: middle;
}
</style>

<?php include("includes/footer.php"); ?>