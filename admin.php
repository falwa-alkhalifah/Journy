<?php
session_start();
require_once 'db_config.php';
require_once 'session_check.php';

checkAdmin(); // This ensures only admins can access

$success = "";
$error = "";

// لما يتم إضافة الحدث
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $eventName   = trim($_POST['eventName']);
    $category    = trim($_POST['category']);
    $city        = trim($_POST['city']);
    $location    = trim($_POST['location']);
    $startDate   = $_POST['startDate'];
    $endDate     = $_POST['endDate'];
    $description = trim($_POST['description']);

    if ($eventName === "" || $startDate === "" || $endDate === "") {
        $error = "Please fill in all required fields.";
    } else {

        $sql = "INSERT INTO events (EventName, Category, City, Location, StartDate, EndDate, Description)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            "sssssss",
            $eventName,
            $category,
            $city,
            $location,
            $startDate,
            $endDate,
            $description
        );

        if (mysqli_stmt_execute($stmt)) {
            $success = "Event added successfully!";
        } else {
            $error = "Error adding the event: " . mysqli_error($link);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Add Event</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .admin-container { padding: 60px 50px; max-width: 900px; margin: 0 auto; }
    .admin-container h2 {
      text-align: center; font-family: 'Playfair Display', serif;
      font-size: 36px; color: #333; margin-bottom: 40px;
    }
    form {
      background: #fff; border-radius: 15px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.1);
      padding: 40px;
    }
    .form-group { margin-bottom: 25px; }
    label { display: block; font-weight: 600; margin-bottom: 8px; color: #444; }
    input[type="text"], input[type="datetime-local"], select, textarea {
      width: 100%; padding: 10px 15px; border: 1px solid #ccc;
      border-radius: 8px; font-size: 15px; transition: border-color 0.3s;
    }
    input:focus, select:focus, textarea:focus { border-color: #ff6b00; outline: none; }
    textarea { resize: vertical; min-height: 100px; }
    .btn-submit {
      background: #ff6b00; color: #fff; border: none; padding: 12px 25px;
      border-radius: 8px; font-size: 16px; font-weight: 600;
      cursor: pointer; transition: 0.3s; width: 100%;
    }
    .btn-submit:hover { background: #e65b00; }
    .msg { margin: 20px 0; font-size: 16px; text-align: center; }
    .success { color: #0f8a4c; }
    .error { color: #d93025; }
  </style>
</head>
<body>

<header>
  <nav>
    <div class="logo">Journy Admin</div>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="discover.php">Discover</a></li>
      <li><a href="planner.php">Planner</a></li>
      <li><a href="reservations.php">Reservations</a></li>
      <li><a href="admin.php" class="active">Admin</a></li>
      <li><a href="logout.php">Log out</a></li>
    </ul>
  </nav>
</header>

<section class="admin-container">
  <h2>Add New Event</h2>

  <?php if ($success): ?>
    <div class="msg success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="msg error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="form-group">
      <label for="eventName">Event Name *</label>
      <input type="text" id="eventName" name="eventName" required>
    </div>

    <div class="form-group">
      <label for="category">Category</label>
      <select id="category" name="category">
        <option value="">Select Category</option>
        <option value="sports">Sports</option>
        <option value="esports">Esports</option>
        <option value="cultural">Cultural</option>
        <option value="music">Music</option>
        <option value="festival">Festival</option>
      </select>
    </div>

    <div class="form-group">
      <label for="city">City</label>
      <input type="text" id="city" name="city">
    </div>

    <div class="form-group">
      <label for="location">Location / Venue</label>
      <input type="text" id="location" name="location">
    </div>

    <div class="form-group">
      <label for="startDate">Start Date & Time *</label>
      <input type="datetime-local" id="startDate" name="startDate" required>
    </div>

    <div class="form-group">
      <label for="endDate">End Date & Time *</label>
      <input type="datetime-local" id="endDate" name="endDate" required>
    </div>

    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description"></textarea>
    </div>

    <button type="submit" class="btn-submit">Add Event</button>
  </form>

</section>

<footer>
  <p>&copy; 2025 Journy Admin. All rights reserved.</p>
</footer>

</body>
</html>