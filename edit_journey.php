<?php
ini_set('display_errors', 1);
require 'connect.php';
$user_id = 1;
$jid = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch journey
$j = $conn->query("SELECT * FROM journeys WHERE journeyID=$jid")->fetch_assoc();

// Fetch days
$days = $conn->query("SELECT * FROM journey_days WHERE journeyID=$jid");

// UPDATED QUERY: Fetch all reservations with names from either events or places
$all = $conn->query("
    SELECT r.*, 
           COALESCE(e.eventName, p.name) AS name 
    FROM reservations r
    LEFT JOIN events e ON r.eventID = e.eventID
    LEFT JOIN places p ON r.placeID = p.placeID
    WHERE r.userID=$user_id
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Journey</title>
<link rel="stylesheet" href="style.css">
<style>
.edit-container{ max-width:900px; margin:50px auto; background:#fff; padding:40px; border-radius:12px; box-shadow:0 4px 18px rgba(0,0,0,0.1);}
.section-title{ font-family:'Playfair Display',serif; color:var(--green-dark); font-size:32px; margin-bottom:20px;}
.day-box{ padding:20px; border:2px solid var(--green-mid); border-radius:8px; margin-bottom:20px;}
.remove-btn{ background:#b22; padding:5px 12px; color:#fff; border-radius:6px; margin-left:10px;}
.add-panel{ margin:25px 0; padding:15px; background:#f0f0f0; border-radius:8px;}
.submit-btn{ margin-top:30px; width:100%; padding:12px; border:none; border-radius:6px; background:var(--green-mid); color:white; font-size:17px;}
.day-box p {
    margin: 15px 0;
    padding: 10px 0;
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
      <li><a href="login.php">Log in</a></li>
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
    // UPDATED QUERY: Get reservation name from either events or places
    $items = $conn->query("
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
            <?php while($r = $all->fetch_assoc()): ?>
            <option value="<?= $r['ReservationID'] ?>"><?= $r['name'] ?></option>
            <?php endwhile; $all->data_seek(0); ?>
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
// REMOVE STOP
if(isset($_POST['remove'])){
    $id = intval($_POST['remove']);
    $conn->query("DELETE FROM journey_items WHERE itemID=$id");
    header("Location: edit_journey.php?id=$jid");
    exit;
}

// ADD STOP
if(isset($_POST['save_changes'])){
    foreach($_POST as $key=>$value){
        if(str_starts_with($key,'add_item_day_') && $value!=""){
            $day_id = intval(str_replace("add_item_day_","",$key));
            $res_id = intval($value);
            $conn->query("INSERT INTO journey_items(dayID,reservationID) VALUES($day_id,$res_id)");
        }
    }
    header("Location: edit_journey.php?id=$jid");
    exit;
}
?>