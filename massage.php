<?php
// ============================================================
// messages.php – User inbox (received) + sent messages
// ============================================================
include("includes/header.php");
requireLogin('auth/login.php');

$uid = (int)$_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'inbox';

// Mark a message as read when opened
if (isset($_GET['read'])) {
    $mid = (int)$_GET['read'];
    // Only mark if this user is the receiver
    $conn->query("UPDATE messages SET is_read=1 WHERE id=$mid AND receiver_id=$uid");
    header("Location: messages.php?tab=inbox&open=$mid");
    exit;
}

// Delete a message 
if (isset($_GET['delete'])) {
    $mid = (int)$_GET['delete'];
    // Allow sender or receiver to delete from their view
    $conn->query("UPDATE messages SET deleted_by_receiver=1 WHERE id=$mid AND receiver_id=$uid");
    $conn->query("UPDATE messages SET deleted_by_sender=1   WHERE id=$mid AND sender_id=$uid");
    header("Location: messages.php?tab=$tab");
    exit;
}

// ── Fetch inbox (received, not deleted by receiver) ──────────
$inbox = $conn->query("
    SELECT m.*, u.username AS sender_name
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.receiver_id = $uid AND (m.deleted_by_receiver IS NULL OR m.deleted_by_receiver = 0)
    ORDER BY m.created_at DESC
");

// ── Fetch sent (not deleted by sender) ──────────────────────
$sent = $conn->query("
    SELECT m.*, u.username AS receiver_name
    FROM messages m
    JOIN users u ON u.id = m.receiver_id
    WHERE m.sender_id = $uid AND (m.deleted_by_sender IS NULL OR m.deleted_by_sender = 0)
    ORDER BY m.created_at DESC
");

// ── Open a single message ────────────────────────────────────
$openMsg = null;
if (isset($_GET['open'])) {
    $mid  = (int)$_GET['open'];
    $stmt = $conn->prepare("
        SELECT m.*, s.username AS sender_name, r.username AS receiver_name
        FROM messages m
        JOIN users s ON s.id = m.sender_id
        JOIN users r ON r.id = m.receiver_id
        WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
    ");
    $stmt->bind_param("iii", $mid, $uid, $uid);
    $stmt->execute();
    $openMsg = $stmt->get_result()->fetch_assoc();

    // Auto-mark as read if we're the receiver
    if ($openMsg && $openMsg['receiver_id'] == $uid && !$openMsg['is_read']) {
        $conn->query("UPDATE messages SET is_read=1 WHERE id=$mid");
        $openMsg['is_read'] = 1;
    }
}
?>

<button class="toggle" onclick="toggleTheme()">EPOXY</button>

<div class="section">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <h1>✉ Messages</h1>
    <a href="compose_message.php"><button class="btn-primary">+ New Message</button></a>
  </div>

  <!-- Tab bar -->
  <nav class="admin-tabs" style="margin-top:16px;">
    <a href="?tab=inbox"  class="<?= $tab==='inbox' ?'active':'' ?>">
      Inbox
      <?php
        // Unread count badge
        $uc = $conn->query("SELECT COUNT(*) AS c FROM messages WHERE receiver_id=$uid AND is_read=0 AND (deleted_by_receiver IS NULL OR deleted_by_receiver=0)")->fetch_assoc()['c'];
        if ($uc > 0): ?><span class="badge"><?= $uc ?></span><?php endif;
      ?>
    </a>
    <a href="?tab=sent" class="<?= $tab==='sent' ?'active':'' ?>">Sent</a>
  </nav>

  <div class="messages-layout">

    <!-- ── Message list ───────────────────────────────────── -->
    <div class="msg-list">
      <?php
      $list = ($tab === 'inbox') ? $inbox : $sent;
      $list->data_seek(0);

      if ($list->num_rows === 0): ?>
        <p class="muted">No messages here yet.</p>
      <?php endif;

      while ($m = $list->fetch_assoc()):
        $isOpen   = isset($_GET['open']) && (int)$_GET['open'] === (int)$m['id'];
        $unread   = ($tab === 'inbox') && !$m['is_read'];
        $nameKey  = $tab === 'inbox' ? 'sender_name' : 'receiver_name';
        $preview  = mb_substr(strip_tags($m['body']), 0, 60);
      ?>
        <a href="?tab=<?= $tab ?>&open=<?= $m['id'] ?><?= (!$m['is_read'] && $tab==='inbox') ? '&read='.$m['id'] : '' ?>"
           class="msg-row <?= $isOpen ? 'active' : '' ?> <?= $unread ? 'unread' : '' ?>">
          <div class="msg-row-meta">
            <strong><?= htmlspecialchars($m[$nameKey]) ?></strong>
            <span class="muted msg-date"><?= date('M j', strtotime($m['created_at'])) ?></span>
          </div>
          <div class="msg-row-subject"><?= htmlspecialchars($m['subject']) ?></div>
          <div class="muted msg-preview"><?= htmlspecialchars($preview) ?>…</div>
        </a>
      <?php endwhile; ?>
    </div>

    <!-- ── Message viewer ─────────────────────────────────── -->
    <div class="msg-viewer">
      <?php if ($openMsg): ?>
        <div class="msg-header">
          <h2><?= htmlspecialchars($openMsg['subject']) ?></h2>
          <p class="muted">
            From <strong><?= htmlspecialchars($openMsg['sender_name']) ?></strong>
            to <strong><?= htmlspecialchars($openMsg['receiver_name']) ?></strong>
            &nbsp;·&nbsp; <?= date('M j, Y g:i A', strtotime($openMsg['created_at'])) ?>
          </p>
        </div>
        <div class="msg-body">
          <?= nl2br(htmlspecialchars($openMsg['body'])) ?>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;">
          <!-- Reply -->
          <?php if ($openMsg['sender_id'] != $uid): ?>
            <a href="compose_message.php?reply_to=<?= $openMsg['sender_id'] ?>&subject=<?= urlencode('Re: '.$openMsg['subject']) ?>">
              <button class="btn-secondary">↩ Reply</button>
            </a>
          <?php endif; ?>
          <!-- Delete -->
          <a href="?tab=<?= $tab ?>&delete=<?= $openMsg['id'] ?>"
             onclick="return confirm('Remove this message from your view?')">
            <button class="btn-ghost">🗑 Delete</button>
          </a>
        </div>
      <?php else: ?>
        <div class="msg-empty">
          <p>Select a message to read it.</p>
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /messages-layout -->
</div>

<style>
.messages-layout {
    display: flex;
    gap: 0;
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 12px;
    overflow: hidden;
    min-height: 420px;
    text-align: left;
    max-width: 900px;
    margin: 0 auto;
}
.msg-list {
    width: 280px;
    min-width: 220px;
    border-right: 1px solid rgba(255,255,255,.1);
    overflow-y: auto;
}
.msg-row {
    display: block;
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255,255,255,.06);
    text-decoration: none;
    color: inherit;
    transition: background .2s;
}
.msg-row:hover, .msg-row.active { background: rgba(255,255,255,.07); }
.msg-row.unread .msg-row-subject { font-weight: 700; }
.msg-row-meta { display: flex; justify-content: space-between; margin-bottom: 3px; }
.msg-row-subject { font-size: .95em; margin-bottom: 2px; }
.msg-preview { font-size: .82em; }
.msg-date { font-size: .8em; }

.msg-viewer {
    flex: 1;
    padding: 28px;
    overflow-y: auto;
}
.msg-header { border-bottom: 1px solid rgba(255,255,255,.1); padding-bottom: 12px; margin-bottom: 20px; }
.msg-header h2 { margin: 0 0 6px; }
.msg-body { line-height: 1.7; white-space: pre-wrap; }
.msg-empty { display: flex; align-items: center; justify-content: center; height: 200px; opacity: .4; }
.badge {
    display: inline-block;
    background: #e74;
    color: #fff;
    font-size: .75em;
    padding: 1px 6px;
    border-radius: 99px;
    margin-left: 5px;
    vertical-align: middle;
}
</style>

<?php include("includes/footer.php"); ?>