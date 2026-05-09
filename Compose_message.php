<?php

include("includes/header.php");
requireLogin('auth/login.php');

$senderId = (int)$_SESSION['user_id'];

// Pre-fill recipient from query string
$replyTo  = (int)($_GET['reply_to'] ?? 0);
$subject  = htmlspecialchars($_GET['subject'] ?? '');

// Load all users for the "To" dropdown (exclude self)
$usersRes = $conn->query("SELECT id, username FROM users WHERE id != $senderId AND role != 'banned' ORDER BY username");

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiverId = (int)$_POST['receiver_id'];
    $subj       = trim($_POST['subject'] ?? '');
    $body       = trim($_POST['body']    ?? '');

    if (!$receiverId) {
        $error = "Please select a recipient.";
    } elseif (!$subj) {
        $error = "Subject is required.";
    } elseif (!$body) {
        $error = "Message body cannot be empty.";
    } else {
        // Verify receiver exists and isn't banned
        $chk = $conn->prepare("SELECT id FROM users WHERE id=? AND role != 'banned'");
        $chk->bind_param("i", $receiverId);
        $chk->execute();
        if (!$chk->get_result()->fetch_assoc()) {
            $error = "Recipient not found.";
        } else {
            $sEsc = $conn->real_escape_string($subj);
            $bEsc = $conn->real_escape_string($body);
            $conn->query("
                INSERT INTO messages (sender_id, receiver_id, subject, body)
                VALUES ($senderId, $receiverId, '$sEsc', '$bEsc')
            ");

            // Notify the receiver
            $senderName = htmlspecialchars($_SESSION['username']);
            $conn->query("
                INSERT INTO notifications (user_id, message)
                VALUES ($receiverId, 'You have a new message from $senderName.')
            ");

            $msg = "Message sent!";
        }
    }
}
?>

<button class="toggle" onclick="toggleTheme()">EPOXY</button>

<div class="section">
  <h1>✉ New Message</h1>

  <?php if ($msg): ?>
    <div class="alert-info">
      <?= htmlspecialchars($msg) ?>
      &nbsp;<a href="message.php">View Inbox →</a>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="POST" class="form-card" style="max-width:540px;margin:0 auto;text-align:left;">

    <div class="form-group">
      <label>To <span class="req">*</span></label>
      <select name="receiver_id" required>
        <option value="">— Select recipient —</option>
        <?php
        $usersRes->data_seek(0);
        while ($u = $usersRes->fetch_assoc()):
            $sel = ($u['id'] == $replyTo) ? 'selected' : '';
        ?>
          <option value="<?= $u['id'] ?>" <?= $sel ?>>
            <?= htmlspecialchars($u['username']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Subject <span class="req">*</span></label>
      <input type="text" name="subject"
             value="<?= htmlspecialchars($_POST['subject'] ?? $subject) ?>"
             placeholder="e.g. Commission Inquiry" required>
    </div>

    <div class="form-group">
      <label>Message <span class="req">*</span></label>
      <textarea name="body" rows="6"
        placeholder="Describe what you're looking for, your budget, timeline, etc."
        required><?= htmlspecialchars($_POST['body'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn-primary">Send Message</button>
    <a href="messages.php" style="margin-left:16px;">Cancel</a>
  </form>
</div>

<style>
/* Reuse form-card styles; add select styling */
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 6px;
    background: rgba(255,255,255,.07);
    color: inherit;
    font-size: 1em;
    box-sizing: border-box;
}
body.dark .form-group select,
body.dark .form-group textarea {
    background: #1e1e1e;
    border-color: rgba(255,255,255,.2);
    color: #eee;
}
</style>

<?php include("includes/footer.php"); ?>