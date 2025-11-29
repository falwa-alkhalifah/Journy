<?php
session_start();
require_once 'db_config.php';
require_once 'session_check.php';

checkAuth();
$user_id = $_SESSION['user_id'];

$jid = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify the journey belongs to the current user
$j = mysqli_query($link, "SELECT * FROM journeys WHERE journeyID=$jid AND userID=$user_id");
if (!$j || mysqli_num_rows($j) === 0) {
    header("Location: planner.php");
    exit;
}
$j = mysqli_fetch_assoc($j);

// Fetch days
$days = mysqli_query($link, "SELECT * FROM journey_days WHERE journeyID=$jid");

// Fetch all reservations and store them in an array
$all_reservations = [];
$all_result = mysqli_query($link, "
    SELECT r.*, 
           COALESCE(e.eventName, p.name) AS name 
    FROM reservations r
    LEFT JOIN events e ON r.eventID = e.eventID
    LEFT JOIN places p ON r.placeID = p.placeID
    WHERE r.userID=$user_id
");

while($r = mysqli_fetch_assoc($all_result)) {
    $all_reservations[] = $r;
}

// REMOVE STOP
if(isset($_POST['remove'])){
    $id = intval($_POST['remove']);
    mysqli_query($link, "DELETE FROM journey_items WHERE itemID=$id");
    header("Location: edit_journey.php?id=$jid");
    exit;
}

// ADD STOP
if(isset($_POST['save_changes'])){
    foreach($_POST as $key=>$value){
        if(str_starts_with($key,'add_item_day_') && $value!=""){
            $day_id = intval(str_replace("add_item_day_","",$key));
            $res_id = intval($value);
            mysqli_query($link, "INSERT INTO journey_items(dayID,reservationID) VALUES($day_id,$res_id)");
        }
    }
    header("Location: edit_journey.php?id=$jid");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Journey</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.edit-container{ 
    max-width:900px; 
    margin:50px auto; 
    background:#1e2a28; 
    padding:40px; 
    border-radius:12px; 
    box-shadow:0 6px 20px rgba(0,0,0,0.3);
}
.section-title{ 
    font-family:'Playfair Display',serif; 
    color:#a2e896; 
    font-size:32px; 
    margin-bottom:20px;
    text-align: center;
}
.day-box{ 
    padding:20px; 
    border:2px solid #a2e896; 
    border-radius:8px; 
    margin-bottom:25px;
    background: #2b3e3c;
}
.day-box h3 {
    color: #a2e896;
    margin-bottom: 15px;
}
.day-box p {
    margin: 15px 0;
    padding: 12px 15px;
    background: #1e2a28;
    border-radius: 6px;
    border-left: 3px solid #b8860b;
    color: #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.remove-btn{ 
    background:#dc3545; 
    padding:6px 14px; 
    color:#fff; 
    border-radius:6px; 
    border: none;
    cursor: pointer;
    transition: 0.3s;
}
.remove-btn:hover{ 
    background:#c82333;
}
.add-panel{ 
    margin:25px 0; 
    padding:15px; 
    background:#1e2a28; 
    border-radius:8px;
}
.add-panel label {
    color: #a2e896;
    font-weight: bold;
    display: block;
    margin-bottom: 8px;
}
.add-panel select {
    width: 100%;
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #444;
    background: #2b3e3c;
    color: #eee;
    font-family: 'Poppins', sans-serif;
}
.submit-btn{ 
    margin-top:30px; 
    width:100%; 
    padding:12px; 
    border:none; 
    border-radius:6px; 
    background:#b8860b; 
    color:white; 
    font-size:17px;
    cursor: pointer;
    transition: 0.3s;
    font-family: 'Poppins', sans-serif;
}
.submit-btn:hover{ 
    background:#d4af37;
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
      <?php if (isLoggedIn()): ?>
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <li><a href="admin.php">Admin</a></li>
        <?php endif; ?>
        <li><a href="logout.php">Log out</a></li>
      <?php else: ?>
        <li><a href="login.php">Log in</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>

<section class="edit-container">
<h1 class="section-title">Edit Journey: <?= htmlspecialchars($j['JourneyName']) ?></h1>
<form method="POST">
<?php while($d = mysqli_fetch_assoc($days)): ?>
<div class="day-box">
    <h3>Day <?= $d['DayNumber'] ?></h3>

    <?php
    $items = mysqli_query($link, "
        SELECT ji.*, 
               COALESCE(e.eventName, p.name) AS rname 
        FROM journey_items ji
        JOIN reservations r ON r.reservationID = ji.reservationID
        LEFT JOIN events e ON r.eventID = e.eventID
        LEFT JOIN places p ON r.placeID = p.placeID
        WHERE ji.dayID={$d['DayID']}
    ");
    ?>

    <?php while($i = mysqli_fetch_assoc($items)): ?>
        <p>
            <span><?= htmlspecialchars($i['rname']) ?></span>
            <button name="remove" value="<?= $i['ItemID'] ?>" class="remove-btn">Remove</button>
        </p>
    <?php endwhile; ?>

    <div class="add-panel">
        <label>Add Stop to Day <?= $d['DayNumber'] ?></label>
        <select name="add_item_day_<?= $d['DayID'] ?>">
            <option value="">Choose reservation</option>
            <?php foreach($all_reservations as $r): ?>
            <option value="<?= $r['ReservationID'] ?>"><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<?php endwhile; ?>

<button class="submit-btn" name="save_changes">Save Changes</button>
</form>
</section>

<footer>
  <p>&copy; 2025 Journy. All rights reserved.</p>
</footer>
</body>
</html>
<?php
mysqli_close($link);
?>
