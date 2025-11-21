<?php
session_start();
require_once 'db_config.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$errors = ['name'=>'','email'=>'','password'=>'','form'=>''];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    // Validate Name
    if ($name === '') {
        $errors['name'] = 'Please enter your full name.';
    }

    // Validate Email
    if ($email === '') {
        $errors['email'] = 'Please enter your email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    } else {
        // Check if email exists
        $sql = "SELECT Email FROM users WHERE Email = ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if (mysqli_fetch_assoc($res)) {
            $errors['email'] = 'Email is already registered.';
        }
    }

    // Validate Password
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

 // If everything OK → Insert into DB
if ($errors['name']==='' && $errors['email']==='' && $errors['password']==='') {

    $hash = password_hash($password, PASSWORD_BCRYPT);

    // إدخال المستخدم في قاعدة البيانات
    $sql  = "INSERT INTO users (FullName, Email, Password, Role) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hash, $role);
    mysqli_stmt_execute($stmt);

    // الحصول على الـ ID الجديد
    $newId = mysqli_insert_id($link);

    // فتح جلسة للمستخدم مباشرة بعد التسجيل
    $_SESSION['user_id']   = $newId;
    $_SESSION['user_name'] = $name;
    $_SESSION['role']      = $role;

    // توجيه حسب نوع الحساب
    if ($role === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Journy - Sign Up</title>
<link rel="stylesheet" href="style.css" />

<style>
  .auth-section{
    max-width:680px; margin:80px auto; background:#fff;
    border:1px solid rgba(0,0,0,.08); border-radius:12px;
    padding:40px 30px; box-shadow:0 6px 20px rgba(0,0,0,.08);
  }
  .auth-section h2{
    font-family:'Playfair Display',serif; font-size:32px;
    color:#333; text-align:center; margin-bottom:8px;
  }
  .auth-section .subtitle{
    text-align:center; color:#666; margin-bottom:24px; font-size:15px;
  }
  .auth-section label{
    display:block; font-weight:600; color:#333; margin:14px 0 6px;
  }
  .auth-section .input-wrapper{
    display:flex; align-items:center; gap:8px; background:#fff;
    border:1px solid #ddd; border-radius:10px; padding:10px 12px;
    transition:border-color .2s, box-shadow .2s;
  }
  .auth-section .input-wrapper:focus-within{
    border-color:#ff6b00; box-shadow:0 0 0 3px rgba(255,107,0,.15);
  }
  .auth-section input{
    width:100%; border:0; outline:0; background:transparent;
    font-size:16px; color:#333;
  }
  .auth-section .peek{
    border:0; background:#f5f5f5; color:#555;
    padding:6px 8px; border-radius:8px; cursor:pointer;
  }
  .auth-section .grid-2{
    display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:8px;
  }
  .auth-section .checkbox{
    display:flex; align-items:center; gap:10px; padding:10px 12px;
    border:1px solid #e9e9e9; border-radius:10px; background:#fafafa;
    cursor:pointer; user-select:none;
  }
  .auth-section .checkbox:hover{
    border-color:#ffeadf; background:#fff7f2;
  }
  .auth-section .checkbox input{
    accent-color:#ff6b00; width:18px; height:18px;
  }
  .auth-section .pill{
    padding:2px 8px; border-radius:999px; font-size:12px;
    color:#ff6b00; background:#fff2e9; border:1px solid #ffe1cc;
  }
  .auth-section .error{
    color:#d93025; font-size:13px; margin-top:6px; display:block;
  }
  .auth-section .btn{
    width:100%; margin-top:14px; background:#ff6b00; color:#fff;
    border:none; border-radius:10px; padding:12px; font-weight:600;
    cursor:pointer; transition:background .2s;
  }
  .auth-section .btn:hover{ background:#e65b00; }
</style>
</head>

<body>
<header>
  <nav>
    <div class="logo">Journy</div>
    <ul class="nav-links">
      <li><a href="login.php">Login</a></li>
      <li><a href="signup.php" class="active">Sign Up</a></li>
    </ul>
  </nav>
</header>

<main>
  <section class="auth-section">
    <h2>Create a Journy Account</h2>
    <p class="subtitle">Join Journy to book events and discover nearby restaurants & hotels.</p>

    <form method="post">

      <label for="name">Full Name</label>
      <div class="input-wrapper">
        <input type="text" id="name" name="name" value="<?=h($name)?>" required>
      </div>
      <small class="error"><?=$errors['name']?></small>

      <label for="email">Email</label>
      <div class="input-wrapper">
        <input type="email" id="email" name="email" value="<?=h($email)?>" required>
      </div>
      <small class="error"><?=$errors['email']?></small>

      <label for="password">Password</label>
      <div class="input-wrapper">
        <input type="password" id="password" name="password" minlength="8" required>
        <button type="button" class="peek" data-target="password">👁</button>
      </div>
      <small class="error"><?=$errors['password']?></small>

      <label>Account Type</label>
      <div class="grid-2">
        <label class="checkbox">
          <input type="radio" name="role" value="user" checked>
          <span>User</span> <span class="pill">Book & explore places</span>
        </label>
        <label class="checkbox">
          <input type="radio" name="role" value="admin">
          <span>Admin</span> <span class="pill">Manage events & places</span>
        </label>
      </div>

      <button class="btn" type="submit">Sign Up</button>

      <p class="switch">Already have an account?
        <a href="login.php">Login</a>
      </p>

    </form>
  </section>
</main>

<footer>
  <p>&copy; 2025 Journy. All rights reserved.</p>
</footer>

<script>
document.querySelectorAll('.peek').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const f = document.getElementById(btn.dataset.target);
    f.type = f.type === 'password' ? 'text' : 'password';
  });
});
</script>

</body>
</html>
