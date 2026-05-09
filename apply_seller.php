<?php include("includes/header.php");
requireLogin('auth/login.php');

$uid = $_SESSION['user_id'];

if (isSeller()) {
    echo '<div class="section"><h1>You\'re already a seller!</h1>
          <p><a href="upload.php" class="btn-primary">→ Upload Artwork</a></p></div>';
    include("includes/footer.php"); exit;
}

$stmt = $conn->prepare("SELECT status, admin_note FROM seller_applications WHERE user_id=? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $uid); $stmt->execute();
$app = $stmt->get_result()->fetch_assoc();

$msg = ''; $error = '';

if ($_POST) {
    $bio       = trim($_POST['bio']           ?? '');
    $portfolio = trim($_POST['portfolio_url'] ?? '');
    $reason    = trim($_POST['reason']        ?? '');

    if (!$bio || !$reason) {
        $error = "Bio and reason are required.";
    } else {
        $del = $conn->prepare("DELETE FROM seller_applications WHERE user_id=? AND status='rejected'");
        $del->bind_param("i", $uid); $del->execute();

        $ins = $conn->prepare("INSERT INTO seller_applications (user_id, bio, portfolio_url, reason) VALUES (?,?,?,?)");
        $ins->bind_param("isss", $uid, $bio, $portfolio, $reason); $ins->execute();

        $app = ['status' => 'pending', 'admin_note' => ''];
        $msg = "Application submitted! You'll be notified when reviewed.";
    }
}
?>
<button class="toggle" onclick="toggleTheme()">EPOXY</button>

<div class="section">
  <h1>Apply to Become a Seller</h1>

  <?php if ($msg): ?><div class="alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <?php if ($app && $app['status'] === 'pending'): ?>
    <div class="alert-info">
      <strong>⏳ Application Pending</strong><br>
      We're reviewing your application. You'll receive a notification once a decision is made.
    </div>

  <?php else: ?>
    <?php if ($app && $app['status'] === 'rejected'): ?>
      <div class="alert-error">
        <strong>❌ Previous Application Rejected</strong>
        <?php if ($app['admin_note']): ?>
          <p>Admin note: <?= htmlspecialchars($app['admin_note']) ?></p>
        <?php endif; ?>
        <p>You may re-apply below.</p>
      </div>
    <?php endif; ?>

    <p>Tell us about your work. An admin will review your application before granting seller access.</p>

    <form method="POST" class="form-card">
      <div class="form-group">
        <label>Artist Bio <span class="req">*</span></label>
        <textarea name="bio" rows="4"
          placeholder="Your art style, experience, what you create..."
          required><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Portfolio URL <small>(Instagram, Behance, personal site, etc.)</small></label>
        <input type="url" name="portfolio_url" placeholder="https://"
               value="<?= htmlspecialchars($_POST['portfolio_url'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Why do you want to sell on EPOXY? <span class="req">*</span></label>
        <textarea name="reason" rows="3"
          placeholder="Your motivation for joining..."
          required><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn-primary">Submit Application</button>
    </form>
  <?php endif; ?>
</div>

<?php include("includes/footer.php"); ?>