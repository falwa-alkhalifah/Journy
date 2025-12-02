<?php
session_start();
require_once 'db_config.php'; // provides $link (mysqli)

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$errors = ['name'=>'','email'=>'','password'=>''];
$name = $email = '';
$role = 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

    // validate
    if ($name === '') $errors['name'] = 'Please enter your full name.';
    if ($email === '') $errors['email'] = 'Please enter your email.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format.';

    if (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';

    // check duplicate email if no error so far
    if ($errors['email'] === '') {
        $sql = "SELECT 1 FROM users WHERE Email = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors['email'] = 'Email is already registered.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors['email'] = 'Database error: ' . mysqli_error($link);
        }
    }

    // if all ok -> insert and auto-login
    if ($errors['name']==='' && $errors['email']==='' && $errors['password']==='') {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (FullName, Email, Password, Role) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hash, $role);
            $ok = mysqli_stmt_execute($stmt);
            if ($ok) {
                $user_id = mysqli_insert_id($link);
                // set session and redirect
                $_SESSION['user_id'] = (int)$user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['role'] = $role;
                header('Location: ' . ($role === 'admin' ? 'admin.php' : 'index.php'));
                exit;
            } else {
                $errors['email'] = 'Failed to create account. Try again.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors['email'] = 'Database error: ' . mysqli_error($link);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Journy - Sign Up</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* scoped signup styles - dark theme */
    .auth-section {
      max-width: 700px;
      margin: 70px auto;
      background: #1e2a28;
      border: 1px solid rgba(0,0,0,0.4);
      border-radius: 12px;
      padding: 34px;
      color: #eee;
      box-shadow: 0 8px 30px rgba(0,0,0,0.45);
    }
    .auth-section h2 { font-family:'Playfair Display', serif; font-size:30px; text-align:center; color:#f6f7f6; margin-bottom:6px; }
    .auth-section .subtitle { color:#d6d6d6; text-align:center; margin-bottom:18px; font-size:14px; }
    label { display:block; color:#dfeee0; font-weight:600; margin:12px 0 6px; }
    .input-wrapper {
      display:flex; align-items:center; gap:8px; background:#13201f; border:1px solid #2b3e3c; border-radius:10px; padding:10px 12px;
    }
    .input-wrapper input { width:100%; border:0; outline:0; background:transparent; color:#eee; font-size:15px; }
    .peek { border:0; background:#23312f; color:#dfeee0; padding:6px 8px; border-radius:8px; cursor:pointer; }
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:8px; }
    .checkbox { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; background:#13201f; border:1px solid #2b3e3c; cursor:pointer; color:#dfeee0; }
    .checkbox input { accent-color:#a2e896; width:18px; height:18px; }
    .pill { padding:2px 8px; border-radius:999px; font-size:12px; color:#a2e896; background:#10221a; border:1px solid #21342e; margin-left:8px; }
    .btn { width:100%; margin-top:16px; background:#b8860b; color:#141f1e; border:none; border-radius:10px; padding:12px; font-weight:700; }
    .btn:hover { background:#d4af37; }
    .error { color:#ff9aa2; font-size:13px; margin-top:8px; display:block; }
    .switch { margin-top:12px; color:#cfe6c3; text-align:center; }
    .switch a { color:#a2e896; text-decoration:underline; }
    @media (max-width:768px) { .auth-section{ margin:30px 16px; padding:20px; } .grid-2{ grid-template-columns:1fr; } }
  </style>
</head>
<body>
<header>
  <nav>
    <div class="logo">Journy</div>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="login.php">Login</a></li>
      <li><a href="signup.php" class="active">Sign Up</a></li>
    </ul>
  </nav>
</header>

<main>
  <section class="auth-section" aria-labelledby="signup-title">
    <h2 id="signup-title">Create a Journy Account</h2>
    <p class="subtitle">Join Journy to book events and discover nearby places.</p>

    <form method="post" novalidate>
      <label for="name">Full Name</label>
      <div class="input-wrapper">
        <input id="name" name="name" type="text" value="<?= h($name) ?>" required>
      </div>
      <?php if($errors['name']): ?><div class="error"><?= h($errors['name']) ?></div><?php endif; ?>

      <label for="email">Email</label>
      <div class="input-wrapper">
        <input id="email" name="email" type="email" value="<?= h($email) ?>" required>
      </div>
      <?php if($errors['email']): ?><div class="error"><?= h($errors['email']) ?></div><?php endif; ?>

      <label for="password">Password</label>
      <div class="input-wrapper">
        <input id="password" name="password" type="password" minlength="8" required>
        <button class="peek" type="button" data-target="password" aria-label="Show password">👁</button>
      </div>
      <?php if($errors['password']): ?><div class="error"><?= h($errors['password']) ?></div><?php endif; ?>

      <label>Account Type</label>
      <div class="grid-2">
        <label class="checkbox"><input type="radio" name="role" value="user" <?= $role==='user' ? 'checked' : '' ?>> User <span class="pill">Book events</span></label>
        <label class="checkbox"><input type="radio" name="role" value="admin" <?= $role==='admin' ? 'checked' : '' ?>> Admin <span class="pill">Manage events</span></label>
      </div>

      <button class="btn" type="submit">Sign Up & Login</button>

      <p class="switch">Already have an account? <a href="login.php">Login</a></p>
    </form>
  </section>
</main>

<footer>
  <p style="text-align:center; color:#9fbf9a; padding:28px 0;">&copy; 2025 Journy. All rights reserved.</p>
</footer>

<script>
  document.querySelectorAll('.peek').forEach(b=>{
    b.addEventListener('click', ()=>{
      const t = document.getElementById(b.dataset.target);
      t.type = t.type === 'password' ? 'text' : 'password';
      b.setAttribute('aria-label', t.type === 'password' ? 'Show password' : 'Hide password');
    });
  });
</script>
</body>
</html>
