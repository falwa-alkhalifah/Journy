<?php
session_start();
require_once 'db_config.php';
require_once 'session_check.php';

checkAdmin(); // This ensures only admins can access

$success = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventName   = trim($_POST['eventName']);
    $category    = trim($_POST['category']);
    $city        = trim($_POST['city']);
    $location    = trim($_POST['location']);
    $description = trim($_POST['description']);
    $startDate   = $_POST['startDate'];
    $endDate     = $_POST['endDate'];
    $availableTickets = (int)$_POST['availableTickets'];
    $price       = (float)$_POST['price'];
    $imageURL    = trim($_POST['imageURL']);
    $locallyOwned = isset($_POST['locallyOwned']) ? 1 : 0;

    // Basic validation
    if (empty($eventName) || empty($startDate) || empty($endDate)) {
        $error = "Please fill in all required fields (Event Name, Start Date, End Date).";
    } else {
        // Prepare SQL statement
        $sql = "INSERT INTO events (EventName, Category, City, Location, Description, StartDate, EndDate, AvailableTickets, Price, ImageURL, LocallyOwned) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($link, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "sssssssidsi",
                $eventName,
                $category,
                $city,
                $location,
                $description,
                $startDate,
                $endDate,
                $availableTickets,
                $price,
                $imageURL,
                $locallyOwned
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $success = "Event added successfully!";
                // Clear form if you want
                $_POST = array();
            } else {
                $error = "Error adding the event: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Database error: " . mysqli_error($link);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Event - Journy Admin</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }

    body { 
        font-family: 'Poppins', sans-serif; 
        background-color:#141f1e; 
        color:#eee; 
        line-height:1.6; 
    }

    /* Header & Navbar */
    header { position: relative; }
    nav { 
        display:flex; 
        justify-content:space-between; 
        align-items:center; 
        padding:20px 50px; 
        background:#1e2a28; 
        box-shadow:0 3px 8px rgba(0,0,0,0.3); 
        position:sticky; 
        top:0; 
        z-index:1000; 
    }
    
    nav .logo { 
        font-family:'Playfair Display', serif; 
        font-size:28px; 
        color:#a2e896; 
        font-weight:bold; 
    }
    
    nav .nav-links { 
        list-style:none; 
        display:flex; 
        gap:25px; 
    }
    
    nav .nav-links li a { 
        text-decoration:none; 
        color:#fff; 
        font-weight:500; 
        transition:0.3s; 
    }
    
    nav .nav-links li a:hover { 
        color:#a2e896; 
    }
    
    nav .nav-links li a.active {
        color:#a2e896;
        font-weight:600;
        position:relative;
    }
    
    nav .nav-links li a.active::after {
        content:'';
        position:absolute;
        bottom:-5px;
        left:0;
        width:100%;
        height:2px;
        background:#a2e896;
    }

    /* Admin Container */
    .admin-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 50px;
    }

    /* Page Header */
    .page-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .page-header h1 {
        font-family:'Playfair Display', serif;
        font-size:42px;
        color:#a2e896;
        margin-bottom:20px;
        text-align:center;
    }
    
    .page-header p {
        font-size:18px;
        color:#bbb;
        max-width:700px;
        margin:0 auto;
        text-align:center;
    }

    /* Form Card */
    .form-card {
        background:#1e2a28;
        border-radius:12px;
        box-shadow:0 8px 30px rgba(0,0,0,0.4);
        padding:40px;
        margin-bottom:50px;
        border:1px solid #2b3e3c;
    }

    /* Form Layout */
    .form-row {
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
        gap:25px;
        margin-bottom:25px;
    }

    .form-group {
        margin-bottom:25px;
    }

    .form-group label {
        display:block;
        font-weight:600;
        color:#ddd;
        margin-bottom:8px;
        font-size:15px;
    }

    .form-group label.required::after {
        content:' *';
        color:#ff6b6b;
    }

    /* Form Controls */
    .form-control {
        width:100%;
        padding:12px 16px;
        background:#2b3e3c;
        border:1px solid #3a504e;
        border-radius:8px;
        font-family:'Poppins', sans-serif;
        font-size:15px;
        color:#eee;
        transition:all 0.3s;
    }

    .form-control:focus {
        outline:none;
        border-color:#a2e896;
        box-shadow:0 0 0 2px rgba(162, 232, 150, 0.2);
    }

    textarea.form-control {
        min-height:120px;
        resize:vertical;
    }

    select.form-control {
        cursor:pointer;
    }

    /* Checkbox */
    .checkbox-group {
        display:flex;
        align-items:center;
        gap:12px;
        padding:15px;
        background:#2b3e3c;
        border-radius:8px;
        border:1px solid #3a504e;
    }

    .checkbox-group input[type="checkbox"] {
        width:20px;
        height:20px;
        accent-color:#a2e896;
        cursor:pointer;
    }

    .checkbox-group label {
        margin-bottom:0;
        font-weight:500;
        color:#ddd;
        cursor:pointer;
    }

    /* Form Actions */
    .form-actions {
        display:flex;
        gap:20px;
        justify-content:center;
        margin-top:40px;
        padding-top:30px;
        border-top:1px solid #2b3e3c;
    }

    /* Buttons */
    .btn {
        padding:14px 35px;
        border:none;
        border-radius:8px;
        font-family:'Poppins', sans-serif;
        font-size:16px;
        font-weight:600;
        cursor:pointer;
        transition:all 0.3s;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
    }

    .btn-primary {
        background:#b8860b;
        color:#fff;
        box-shadow:0 4px 15px rgba(184, 134, 11, 0.3);
    }

    .btn-primary:hover {
        background:#d4af37;
        transform:translateY(-2px);
        box-shadow:0 6px 20px rgba(184, 134, 11, 0.4);
    }

    .btn-secondary {
        background:#2b3e3c;
        color:#ddd;
        border:1px solid #3a504e;
    }

    .btn-secondary:hover {
        background:#3a504e;
        color:#fff;
    }

    /* Alerts */
    .alert {
        padding:18px 25px;
        border-radius:8px;
        margin-bottom:30px;
        font-weight:500;
        display:flex;
        align-items:center;
        gap:12px;
        animation:slideDown 0.3s ease;
        border-left:4px solid transparent;
    }

    .alert-success {
        background:#1e3521;
        color:#a2e896;
        border-left-color:#a2e896;
    }

    .alert-error {
        background:#35211e;
        color:#ff6b6b;
        border-left-color:#ff6b6b;
    }

    @keyframes slideDown {
        from {
            opacity:0;
            transform:translateY(-15px);
        }
        to {
            opacity:1;
            transform:translateY(0);
        }
    }

    /* Footer */
    footer { 
        text-align:center; 
        padding:30px; 
        background:#1e2a28; 
        color:#aaa; 
        box-shadow:0 -3px 8px rgba(0,0,0,0.3); 
        margin-top:50px; 
    }

    /* Responsive */
    @media(max-width:768px){ 
        nav { 
            flex-direction:column; 
            gap:15px; 
            padding:15px 20px;
        }
        
        .nav-links {
            flex-wrap:wrap;
            justify-content:center;
            gap:15px;
        }
        
        .admin-container {
            padding:0 20px;
            margin:30px auto;
        }
        
        .form-card {
            padding:25px;
        }
        
        .form-row {
            grid-template-columns:1fr;
            gap:15px;
        }
        
        .page-header h1 {
            font-size:32px;
        }
        
        .page-header p {
            font-size:16px;
        }
        
        .form-actions {
            flex-direction:column;
        }
        
        .btn {
            width:100%;
        }
    }

    @media(max-width:480px){
        .form-card {
            padding:20px;
        }
        
        .page-header h1 {
            font-size:28px;
        }
        
        nav .logo {
            font-size:24px;
        }
    }
  </style>
</head>
<body>

<header>
  <nav>
    <div class="logo">Journy</div>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="discover.php">Discover</a></li>
      <li><a href="planner.php">Planner</a></li>
      <li><a href="reservations.php">Reservations</a></li>
      <li><a href="admin.php" class="active">Admin</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main class="admin-container">
  <div class="page-header">
    <h1>Add New Event</h1>
    <p>Create a new event to showcase on Journy platform. Fill in the details below.</p>
  </div>
  
  <?php if ($success): ?>
    <div class="alert alert-success">
      <span>✓</span> <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>
  
  <?php if ($error): ?>
    <div class="alert alert-error">
      <span>✗</span> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>
  
  <div class="form-card">
    <form method="post" id="eventForm">
      <div class="form-row">
        <div class="form-group">
          <label for="eventName" class="required">Event Name</label>
          <input type="text" id="eventName" name="eventName" class="form-control" 
                 value="<?= htmlspecialchars($_POST['eventName'] ?? '') ?>" 
                 required placeholder="Enter event name">
        </div>
        
        <div class="form-group">
          <label for="category" class="required">Category</label>
          <select id="category" name="category" class="form-control" required>
            <option value="">Select Category</option>
            <option value="Music" <?= ($_POST['category'] ?? '') === 'Music' ? 'selected' : '' ?>>Music</option>
            <option value="Entertainment" <?= ($_POST['category'] ?? '') === 'Entertainment' ? 'selected' : '' ?>>Entertainment</option>
            <option value="Technology" <?= ($_POST['category'] ?? '') === 'Technology' ? 'selected' : '' ?>>Technology</option>
            <option value="Sports" <?= ($_POST['category'] ?? '') === 'Sports' ? 'selected' : '' ?>>Sports</option>
            <option value="Cultural" <?= ($_POST['category'] ?? '') === 'Cultural' ? 'selected' : '' ?>>Cultural</option>
            <option value="Festival" <?= ($_POST['category'] ?? '') === 'Festival' ? 'selected' : '' ?>>Festival</option>
          </select>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label for="city" class="required">City</label>
          <input type="text" id="city" name="city" class="form-control"
                 value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" 
                 required placeholder="Enter city">
        </div>
        
        <div class="form-group">
          <label for="location">Location / Venue</label>
          <input type="text" id="location" name="location" class="form-control"
                 value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" 
                 placeholder="Enter venue name or address">
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label for="startDate" class="required">Start Date & Time</label>
          <input type="datetime-local" id="startDate" name="startDate" class="form-control"
                 value="<?= htmlspecialchars($_POST['startDate'] ?? '') ?>" 
                 required>
        </div>
        
        <div class="form-group">
          <label for="endDate" class="required">End Date & Time</label>
          <input type="datetime-local" id="endDate" name="endDate" class="form-control"
                 value="<?= htmlspecialchars($_POST['endDate'] ?? '') ?>" 
                 required>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label for="availableTickets" class="required">Available Tickets</label>
          <input type="number" id="availableTickets" name="availableTickets" class="form-control"
                 value="<?= htmlspecialchars($_POST['availableTickets'] ?? '0') ?>" 
                 required min="0" placeholder="Number of tickets">
        </div>
        
        <div class="form-group">
          <label for="price" class="required">Price (SAR)</label>
          <input type="number" id="price" name="price" class="form-control"
                 value="<?= htmlspecialchars($_POST['price'] ?? '0.00') ?>" 
                 required min="0" step="0.01" placeholder="0.00">
        </div>
      </div>
      
      <div class="form-group">
        <label for="imageURL">Image URL</label>
        <input type="text" id="imageURL" name="imageURL" class="form-control"
               value="<?= htmlspecialchars($_POST['imageURL'] ?? '') ?>" 
               placeholder="Enter image URL or path">
      </div>
      
      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" class="form-control"
                  placeholder="Enter event description..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>
      
      <div class="form-group">
        <div class="checkbox-group">
          <input type="checkbox" id="locallyOwned" name="locallyOwned" value="1"
                 <?= isset($_POST['locallyOwned']) && $_POST['locallyOwned'] == '1' ? 'checked' : '' ?>>
          <label for="locallyOwned">Locally Owned Event</label>
        </div>
      </div>
      
      <div class="form-actions">
        <a href="admin.php" class="btn btn-secondary">← Back to Admin</a>
        <button type="submit" class="btn btn-primary">Add Event</button>
      </div>
    </form>
  </div>
</main>

<footer>
  <p>&copy; 2025 Journy Admin. All rights reserved.</p>
</footer>

</body>
</html>
