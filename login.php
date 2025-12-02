<?php
session_start();
require_once 'db_config.php'; // this file defines $link (mysqli)

// helper
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// if already logged in -> redirect
if (isset($_SESSION['user_id'])) {
    header('Location: ' . (($_SESSION['role'] ?? '') === 'admin' ? 'admin.php' : 'index.php'));
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $sql = "SELECT UserID, FullName, Email, Password, Role FROM users WHERE Email = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $u = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if (!$u) {
                $error = 'No account found. Please sign up first.';
            } else {
                $stored = $u['Password'];
                $ok = false;
                if (preg_match('/^\$2[ayb]\$/', $stored)) {
                    $ok = password_verify($password, $stored);
                } else {
                    $ok = hash_equals($stored, $password);
                }

                if (!$ok) {
                    $error = 'Incorrect password.';
                } else {
                    // login successful
                    $_SESSION['user_id']   = (int)$u['UserID'];
                    $_SESSION['user_name'] = $u['FullName'];
                    $_SESSION['role']      = $u['Role'];

                    header('Location: ' . ($u['Role'] === 'admin' ? 'admin.php' : 'index.php'));
                    exit;
                }
            }
        } else {
            $error = 'Database error: ' . mysqli_error($link);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Journy - Login</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* scoped auth styles - dark theme compatible (won't affect other pages) */
    .auth-section {
      max-width: 520px;
      margin: 80px auto;
      background: #1e2a28;
      border: 1px solid rgba(0,0,0,0.4);
      border-radius: 12px;
      padding: 36px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.45);
      color: #eee;
    }
    .auth-section h2 {
      font-family: 'Playfair Display', serif;
      font-size: 30px;
      color: #f6f7f6;
      margin-bottom: 8px;
      text-align:center;
    }
    .auth-section .subtitle { color:#d6d6d6; text-align:center; margin-bottom:18px; font-size:14px; }
    .auth-section label { display:block; font-weight:600; color:#dfeee0; margin:12px 0 6px; }
    .input-wrapper {
      display:flex; align-items:center; gap:8px;
      background: #13201f; border:1px solid #2b3e3c; border-radius:10px;
      padding:10px 12px;
    }
    .input-wrapper:focus-within { border-color:#a2e896; box-shadow: 0 0 0 4px rgba(162,232,150,0.06); }
    .input-wrapper input { width:100%; border:0; outline:0; background:transparent; color:#eee; font-size:15px; }
    .peek { border:0; background:#23312f; color:#dfeee0; padding:6px 8px; border-radius:8px; cursor:pointer; }
    .peek:hover{ background:#2b3e3c; }
    .btn { width:100%; margin-top:18px; background:#b8860b; color:#141f1e; border:none; border-radius:10px; padding:12px; font-weight:700; cursor:pointer; }
    .btn:hover{ background:#d4af37; }
    .switch { text-align:center; margin-top:14px; color:#cfe6c3; font-size:14px; }
    .switch a { color:#a2e896; text-decoration:underline; }
    .error { color:#ff9aa2; font-size:13px; margin-top:8px; display:block; text-align:center; }
    @media (max-width:768px) { .auth-section{ margin:40px 16px; padding:22px; } }
  </style>
</head>
<body>
<header>
  <nav>
    <div class="logo">Journy</div>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="login.php" class="active">Login</a></li>
      <li><a href="signup.php">Sign Up</a></li>
    </ul>
  </nav>
</header>

<main>
  <section class="auth-section" aria-labelledby="login-title">
    <h2 id="login-title">Login to Journy</h2>
    <p class="subtitle">Access your account to explore and manage your bookings.</p>

    <?php if ($error): ?>
      <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <label for="email">Email</label>
      <div class="input-wrapper">
        <input id="email" name="email" type="email" placeholder="Enter your email" value="<?= h($email) ?>" required autofocus>
      </div>

      <label for="password">Password</label>
      <div class="input-wrapper">
        <input id="password" name="password" type="password" placeholder="Enter your password" minlength="6" required>
        <button class="peek" type="button" data-target="password" aria-label="Show password">👁</button>
      </div>

      <button class="btn" type="submit">Login</button>

      <p class="switch">Don’t have an account? <a href="signup.php">Sign Up</a></p>
    </form>
  </section>
</main>

<footer>
  <p style="text-align:center; color:#9fbf9a; padding:30px 0;">&copy; 2025 Journy. All rights reserved.</p>
</footer>

<script>
  document.querySelectorAll('.peek').forEach(b=>{
    b.addEventListener('click', ()=>{
      const t = document.getElementById(b.dataset.target);
      const isPwd = t.type === 'password';
      t.type = isPwd ? 'text' : 'password';
      b.setAttribute('aria-label', isPwd ? 'Hide password' : 'Show password');
    });
  });
</script>
</body>
</html>
