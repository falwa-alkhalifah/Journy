<?php
session_start();
require_once 'db_config.php';

// If user is already logged in, redirect to appropriate page
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // نحضّر الاستعلام
        $sql = "SELECT UserID, FullName, Email, Password, Role 
                FROM users 
                WHERE Email = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $u = mysqli_fetch_assoc($result);

            if (!$u) {
                $error = 'No account found. Please sign up first.';
            } else {
                $stored = $u['Password'];
                $ok = false;

                // إذا الباسورد مخزّن كنص عادي (زي 123456 في الدامب)
                if (preg_match('/^\$2[ayb]\$/', $stored)) {
                    // bcrypt hash
                    $ok = password_verify($password, $stored);
                } else {
                    // نص عادي
                    $ok = hash_equals($stored, $password);
                }

                if (!$ok) {
                    $error = 'Incorrect password.';
                } else {
                    // نحفظ بيانات المستخدم في السيشن
                    $_SESSION['user_id']   = (int) $u['UserID'];
                    $_SESSION['user_name'] = $u['FullName'];
                    $_SESSION['role']      = $u['Role'];

                    // توجيه حسب الدور
                    if ($u['Role'] === 'admin') {
                        header('Location: admin.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit;
                }
            }

            mysqli_stmt_close($stmt);
        } else {
            // لو فيه مشكلة في الاستعلام نفسه
            $error = 'Database error: ' . mysqli_error($link);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Journy - Login</title>
<link rel="stylesheet" href="style.css" />

<style>
  .auth-section{
    max-width: 500px; margin: 80px auto; background:#fff;
    border:1px solid rgba(0,0,0,.08); border-radius:12px;
    padding:40px 30px; box-shadow:0 6px 20px rgba(0,0,0,.08);
  }
  .auth-section h2{
    font-family:'Playfair Display',serif; font-size:32px;
    color:#333; text-align:center; margin-bottom:10px;
  }
  .auth-section .subtitle{
    text-align:center; color:#666; margin-bottom:24px; font-size:15px;
  }
  .auth-section label{
    display:block; font-weight:600; color:#333; margin:15px 0 6px;
  }
  .auth-section .input-wrapper{
    display:flex; align-items:center; gap:8px; background:#fff;
    border:1px solid #ddd; border-radius:10px;
    padding:10px 12px; transition:border-color .2s, box-shadow .2s;
  }
  .auth-section .input-wrapper:focus-within{
    border-color:#ff6b00; box-shadow:0 0 0 3px rgba(255,107,0,.15);
  }
  .auth-section input[type="email"],
  .auth-section input[type="password"]{
    width:100%; border:0; outline:0; background:transparent;
    font-size:16px; color:#333;
  }
  .auth-section .peek{
    border:0; background:#f5f5f5; color:#555;
    padding:6px 8px; border-radius:8px; cursor:pointer;
  }
  .auth-section .peek:hover{ background:#eee; }
  .auth-section .btn{
    width:100%; margin-top:18px; background:#ff6b00; color:#fff;
    border:none; border-radius:10px; padding:12px;
    font-weight:600; cursor:pointer; transition:background .2s;
  }
  .auth-section .btn:hover{ background:#e65b00; }
  .auth-section .switch{
    text-align:center; margin-top:15px; font-size:14px; color:#555;
  }
  .auth-section .switch a{ color:#ff6b00; text-decoration:underline; }
  .auth-section .error{
    color:#d93025; font-size:13px; margin-top:6px; display:block;
  }
  @media (max-width:768px){
    .auth-section{ margin:50px 16px; padding:30px 20px; }
  }
</style>
</head>
<body>
<header>
  <nav>
    <div class="logo">Journy</div>
    <ul class="nav-links">
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
      <small class="error"><?= htmlspecialchars($error) ?></small>
    <?php endif; ?>

    <form method="post" novalidate>
      <label for="email">Email</label>
      <div class="input-wrapper">
        <input type="email" id="email" name="email"
               placeholder="Enter your email" autocomplete="email" required>
      </div>

      <label for="password">Password</label>
      <div class="input-wrapper">
        <input type="password" id="password" name="password"
               placeholder="Enter your password" minlength="6" required>
        <button class="peek" type="button" aria-label="Show password" data-target="password">👁</button>
      </div>

      <button type="submit" class="btn">Login</button>
      <p class="switch">Don’t have an account? <a href="signup.php">Sign Up</a></p>
    </form>
  </section>
</main>

<footer>
  <p>&copy; 2025 Journy. All rights reserved.</p>
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