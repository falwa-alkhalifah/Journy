<?php
session_start();
require_once 'db_config.php';
require_once 'session_check.php';

checkAuth();
$user_id = $_SESSION['user_id'];


$jid = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify the journey belongs to the current user
$j = $link->query("SELECT * FROM journeys WHERE journeyID=$jid AND userID=$user_id")->fetch_assoc();
if (!$j) {
    header("Location: planner.php");
    exit;
}

// Fetch journey
$j = $link->query("SELECT * FROM journeys WHERE journeyID=$jid")->fetch_assoc();

// Fetch days
$days = $link->query("SELECT * FROM journey_days WHERE journeyID=$jid");

// Fetch all reservations and store them in an array
$all_reservations = [];
$all_result = $link->query("
    SELECT r.*, 
           COALESCE(e.eventName, p.name) AS name 
    FROM reservations r
    LEFT JOIN events e ON r.eventID = e.eventID
    LEFT JOIN places p ON r.placeID = p.placeID
    WHERE r.userID=$user_id
");

while($r = $all_result->fetch_assoc()) {
    $all_reservations[] = $r;
}

// REMOVE STOP
if(isset($_POST['remove'])){
    $id = intval($_POST['remove']);
    $link->query("DELETE FROM journey_items WHERE itemID=$id");
    header("Location: edit_journey.php?id=$jid");
    exit;
}

// ADD STOP
if(isset($_POST['save_changes'])){
    foreach($_POST as $key=>$value){
        if(str_starts_with($key,'add_item_day_') && $value!=""){
            $day_id = intval(str_replace("add_item_day_","",$key));
            $res_id = intval($value);
            $link->query("INSERT INTO journey_items(dayID,reservationID) VALUES($day_id,$res_id)");
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
<link rel="stylesheet" href="style.css">
<style>
.edit-container{ max-width:900px; margin:50px auto; background:#fff; padding:40px; border-radius:12px; box-shadow:0 4px 18px rgba(0,0,0,0.1);}
.section-title{ font-family:'Playfair Display',serif; color:var(--green-dark); font-size:32px; margin-bottom:20px;}
.day-box{ padding:20px; border:2px solid var(--green-mid); border-radius:8px; margin-bottom:25px;}
.day-box p {
    margin: 15px 0;
    padding: 10px 0;
    background: #f9f9f9;
    border-radius: 6px;
    border-left: 3px solid var(--green-mid);
}
.remove-btn{ background:#b22; padding:6px 14px; color:#fff; border-radius:6px; margin-left:10px;}
.add-panel{ margin:25px 0; padding:15px; background:#f0f0f0; border-radius:8px;}
.submit-btn{ margin-top:30px; width:100%; padding:12px; border:none; border-radius:6px; background:var(--green-mid); color:white; font-size:17px;}
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
<h1 class="section-title">Edit Journey: <?= $j['JourneyName'] ?></h1>
<form method="POST">
<?php while($d = $days->fetch_assoc()): ?>
<div class="day-box">
    <h3>Day <?= $d['DayNumber'] ?></h3>

    <?php
    $items = $link->query("
        SELECT ji.*, 
               COALESCE(e.eventName, p.name) AS rname 
        FROM journey_items ji
        JOIN reservations r ON r.reservationID = ji.reservationID
        LEFT JOIN events e ON r.eventID = e.eventID
        LEFT JOIN places p ON r.placeID = p.placeID
        WHERE ji.dayID={$d['DayID']}
    ");
    ?>

    <?php while($i=$items->fetch_assoc()): ?>
        <p><?= $i['rname'] ?>
            <button name="remove" value="<?= $i['ItemID'] ?>" class="remove-btn">Remove</button>
        </p>
    <?php endwhile; ?>

    <div class="add-panel">
        <label>Add Stop to Day <?= $d['DayNumber'] ?></label>
        <select name="add_item_day_<?= $d['DayID'] ?>">
            <option value="">Choose reservation</option>
            <?php foreach($all_reservations as $r): ?>
            <option value="<?= $r['ReservationID'] ?>"><?= $r['name'] ?></option>
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