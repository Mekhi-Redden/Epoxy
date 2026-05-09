<?php include("../includes/db.php");

if ($_POST) {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if ($u && password_verify($pass, $u['password'])) {

        // 🚫 BLOCK BANNED USERS
        if ($u['role'] === 'banned') {
            $error = "Your account has been banned. Contact support.";
        } else {
            // ✅ NORMAL LOGIN
            $_SESSION['user_id']  = $u['id'];
            $_SESSION['role']     = $u['role'];
            $_SESSION['username'] = $u['username'];

            header("Location: ../index.php");
            exit;
        }

    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login – EPOXY</title>
  <link rel="stylesheet" href="../css/styles.css">

  <!-- CENTERING STYLES -->
  <style>
    body.auth-page {
      margin: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: #111;
    }

    .auth-box {
      width: 340px;
      padding: 30px;
      border-radius: 12px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      box-shadow: 0 10px 30px rgba(0,0,0,0.4);
      color: #fff;
    }

    .auth-logo {
      text-align: center;
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 10px;
    }

    .auth-logo a {
      color: inherit;
      text-decoration: none;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-top: 10px;
      font-size: 14px;
      opacity: 0.8;
    }

    input {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 6px;
      border: 1px solid rgba(255,255,255,0.2);
      background: rgba(0,0,0,0.3);
      color: #fff;
      outline: none;
    }

    .btn-primary {
      width: 100%;
      padding: 10px;
      margin-top: 16px;
      border: none;
      border-radius: 6px;
      background: #4cafef;
      color: white;
      cursor: pointer;
    }

    .btn-primary:hover {
      background: #3a9edc;
    }

    .error {
      color: #ff4d4d;
      text-align: center;
      margin-bottom: 10px;
    }

    a {
      color: #4cafef;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }

    p {
      text-align: center;
    }
  </style>
</head>

<body class="auth-page">

<div class="auth-box">

  <div class="auth-logo">
    <a href="../index.php">EPOXY</a>
  </div>

  <h2>Welcome back</h2>

  <?php if (isset($error)): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="POST">
    <label>Username</label>
    <input name="username" placeholder="Your username" required autocomplete="username">

    <label>Password</label>
    <input name="password" type="password" placeholder="Password" required autocomplete="current-password">

    <button type="submit" class="btn-primary">Login</button>
  </form>

  <p style="margin-top:16px;">
    <a href="signup.php">Don't have an account? Sign up →</a>
  </p>

</div>

<script>
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }
</script>

</body>
</html>