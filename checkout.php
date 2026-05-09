<?php
include("includes/header.php");
requireLogin('auth/login.php');

$uid = (int)$_SESSION['user_id'];

/* ───────────────── CART ITEMS ───────────────── */
$cartRes = $conn->prepare("
    SELECT c.id AS cart_id, i.*, u.username AS artist_name, u.id AS artist_id
    FROM cart c
    JOIN images i ON c.image_id = i.id
    JOIN users u ON i.user_id = u.id
    WHERE c.user_id = ?
");

$cartRes->bind_param("i", $uid);
$cartRes->execute();
$result = $cartRes->get_result();

$cartItems = [];
$total = 0.0;
$hasPhysical = false;

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $total += (float)$row['price'];

    if ($row['type'] === 'physical') {
        $hasPhysical = true;
    }
}

if (empty($cartItems)) {
    header("Location: cart.php");
    exit;
}

/* ───────────────── FORM HANDLING ───────────────── */
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cardName   = trim($_POST['card_name'] ?? '');
    $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $cardExpiry = trim($_POST['card_expiry'] ?? '');
    $cardCvc    = trim($_POST['card_cvc'] ?? '');

    if (!$cardName) $errors[] = "Cardholder name is required.";
    if (!preg_match('/^\d{13,19}$/', $cardNumber)) $errors[] = "Invalid card number.";
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $cardExpiry)) $errors[] = "Invalid expiry.";
    if (!preg_match('/^\d{3,4}$/', $cardCvc)) $errors[] = "Invalid CVC.";

    /* Shipping */
    if ($hasPhysical) {
        $shipName    = trim($_POST['ship_name'] ?? '');
        $shipAddr    = trim($_POST['ship_address'] ?? '');
        $shipCity    = trim($_POST['ship_city'] ?? '');
        $shipState   = trim($_POST['ship_state'] ?? '');
        $shipZip     = trim($_POST['ship_zip'] ?? '');
        $shipCountry = trim($_POST['ship_country'] ?? '');

        if (!$shipName) $errors[] = "Shipping name required.";
        if (!$shipAddr) $errors[] = "Address required.";
        if (!$shipCity) $errors[] = "City required.";
        if (!$shipState) $errors[] = "State required.";
        if (!$shipZip) $errors[] = "Zip required.";
        if (!$shipCountry) $errors[] = "Country required.";
    }

    /* PROCESS ORDER */
    if (empty($errors)) {

        foreach ($cartItems as $item) {

            $imgId    = (int)$item['id'];
            $amount   = (float)$item['price'];
            $sellerId = (int)$item['artist_id'];

            $shippingJson = null;

            if ($item['type'] === 'physical' && $hasPhysical) {
                $shippingJson = json_encode([
                    'name'    => $shipName ?? '',
                    'address' => $shipAddr ?? '',
                    'city'    => $shipCity ?? '',
                    'state'   => $shipState ?? '',
                    'zip'     => $shipZip ?? '',
                    'country' => $shipCountry ?? ''
                ]);
            }

            $stmt = $conn->prepare("
                INSERT INTO orders (buyer_id, image_id, amount, quantity, status, shipping_details)
                VALUES (?, ?, ?, 1, 'pending', ?)
            ");

            $stmt->bind_param("iids", $uid, $imgId, $amount, $shippingJson);
            $stmt->execute();

            $conn->query("UPDATE images SET sold = sold + 1 WHERE id = $imgId");

            $msg = "Your artwork \"" . $conn->real_escape_string($item['title']) . "\" just sold!";
            $conn->query("INSERT INTO notifications (user_id, message) VALUES ($sellerId, '$msg')");
        }

        $conn->query("DELETE FROM cart WHERE user_id = $uid");

        header("Location: order_success.php");
        exit;
    }
}
?>

<button class="toggle" onclick="toggleTheme()">EPOXY</button>

<div class="section">
  <h1>Checkout</h1>

  <?php if ($errors): ?>
    <div class="alert-error">
      <?php foreach ($errors as $e): ?>
        <p>⚠ <?= htmlspecialchars($e) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="checkout-layout">

    <!-- LEFT: SUMMARY -->
    <div class="checkout-summary">
      <h2>Order Summary</h2>

      <?php foreach ($cartItems as $item): ?>
        <div class="checkout-item">

          <img src="images/<?= htmlspecialchars($item['filename']) ?>"
               alt="<?= htmlspecialchars($item['title']) ?>">

          <div>
            <p class="checkout-item-title"><?= htmlspecialchars($item['title']) ?></p>
            <p class="muted">by <?= htmlspecialchars($item['artist_name']) ?></p>

            <span class="art-type <?= $item['type'] ?>">
              <?= $item['type'] === 'digital' ? '💻 Digital' : '🖼 Physical' ?>
            </span>
          </div>

          <strong>$<?= number_format($item['price'], 2) ?></strong>
        </div>
      <?php endforeach; ?>

      <div class="checkout-total">
        <span>Total</span>
        <strong>$<?= number_format($total, 2) ?></strong>
      </div>
    </div>

    <!-- RIGHT: PAYMENT FORM -->
    <form method="POST" class="checkout-form">

      <h2>Payment Details</h2>
      <p class="muted" style="margin-top:-8px;">
        🔒 Secure checkout 
      </p>

      <!-- CARD NAME -->
      <div class="form-group">
        <label>Cardholder Name</label>
        <input name="card_name" placeholder="John Doe" required>
      </div>

      <!-- CARD NUMBER -->
      <div class="form-group">
        <label>Card Number</label>
        <input name="card_number"
               placeholder="1234 5678 9012 3456"
               maxlength="19"
               inputmode="numeric"
               required>
      </div>

      <!-- EXPIRY + CVC -->
      <div class="form-row">
        <div class="form-group">
          <label>Expiry Date</label>
          <input name="card_expiry"
                 placeholder="MM / YY"
                 maxlength="5"
                 inputmode="numeric"
                 required>
        </div>

        <div class="form-group">
          <label>CVC</label>
          <input name="card_cvc"
                 placeholder="123"
                 maxlength="4"
                 inputmode="numeric"
                 required>
        </div>
      </div>

      <?php if ($hasPhysical): ?>
        <h2 style="margin-top:28px;">Shipping Address</h2>

        <div class="form-group">
          <label>Full Name</label>
          <input name="ship_name" required>
        </div>

        <div class="form-group">
          <label>Street Address</label>
          <input name="ship_address" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>City</label>
            <input name="ship_city" required>
          </div>

          <div class="form-group">
            <label>State</label>
            <input name="ship_state" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Zip</label>
            <input name="ship_zip" required>
          </div>

          <div class="form-group">
            <label>Country</label>
            <input name="ship_country" required>
          </div>
        </div>
      <?php endif; ?>

      <button class="btn-primary" style="width:100%; margin-top:20px;">
        Pay $<?= number_format($total, 2) ?>
      </button>

    </form>

  </div>
</div>

<!-- LAYOUT + STYLE FIXES -->
<style>
.checkout-layout {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: flex-start;
    gap: 40px;
    max-width: 1100px;
    margin: 0 auto;
}

.checkout-summary {
    flex: 1;
    min-width: 320px;
}

.checkout-form {
    flex: 1;
    min-width: 320px;
    max-width: 480px;
}

/* SMALLER IMAGES */
.checkout-summary .checkout-item img {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: 8px;
    flex-shrink: 0;
}

/* FORM STYLE UPGRADE (INDUSTRY LOOK) */
.checkout-form input {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.05);
    transition: 0.2s;
}

.checkout-form input:focus {
    outline: none;
    border-color: #4da3ff;
    box-shadow: 0 0 0 2px rgba(77,163,255,0.2);
}

.checkout-form h2 {
    margin-top: 18px;
    margin-bottom: 10px;
}

/* MOBILE */
@media (max-width: 768px) {
    .checkout-layout {
        flex-direction: column;
    }

    .checkout-summary,
    .checkout-form {
        width: 100%;
    }
}
</style>

<?php include("includes/footer.php"); ?>