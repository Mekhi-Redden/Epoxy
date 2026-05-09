<?php include("../includes/db.php");

if ($_POST) {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    $terms = isset($_POST['terms']);

    if (strlen($user) < 3) {
        $error = "Username must be at least 3 characters.";
    }
    elseif (strlen($pass) < 6) {
        $error = "Password must be at least 6 characters.";
    }
    elseif (!$terms) {
        $error = "You must agree to the Terms of Service.";
    }
    else {
        $chk = $conn->prepare("SELECT id FROM users WHERE username=?");
        $chk->bind_param("s", $user);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins  = $conn->prepare("INSERT INTO users (username, password) VALUES (?,?)");
            $ins->bind_param("ss", $user, $hash);
            $ins->execute();

            header("Location: login.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign Up – EPOXY</title>
<link rel="stylesheet" href="../css/styles.css">

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

/* ✅ TERMS CHECKBOX STYLE */
.terms {
    margin-top: 12px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.terms input {
    width: auto;
    margin: 0;
}
</style>
</head>

<body class="auth-page">

<div class="auth-box">

    <div class="auth-logo">
        <a href="../index.php">EPOXY</a>
    </div>

    <h2>Create account</h2>

    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Username</label>
        <input name="username"
               placeholder="Choose a username"
               required
               autocomplete="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

        <label>Password</label>
        <input name="password"
               type="password"
               placeholder="Min. 6 characters"
               required
               autocomplete="new-password">

        <!-- ✅ TERMS OF SERVICE -->
        <div class="terms">
            <input type="checkbox" name="terms" id="terms"
                <?= isset($_POST['terms']) ? 'checked' : '' ?>>
            <label for="terms">
                I agree to the <a href="../tos.php" target="_blank">Terms of Service</a>
            </label>
        </div>

        <button type="submit" class="btn-primary">Create Account</button>
    </form>

    <p style="margin-top:16px;">
        <a href="login.php">Already have an account? Login →</a>
    </p>

</div>

</body>
</html>