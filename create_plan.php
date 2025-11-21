<?php
session_start();
require_once 'db_config.php';
require_once 'session_check.php';

checkAuth(); // Ensure user is logged in

$user_id = $_SESSION['user_id']; 

// SAVE JOURNEY 
if(isset($_POST['save'])){
    $name = $conn->real_escape_string($_POST['journeyName']);
    $days = intval($_POST['days']);
    $selected = isset($_POST['reservations']) ? $_POST['reservations'] : [];

    if (empty($selected)) {
        header("Location: create_plan.php?error=no_reservations");
        exit;
    }

    // Insert journey
    $conn->query("INSERT INTO journeys (userID, journeyName, days) VALUES ($user_id, '$name', $days)");
    $jid = $conn->insert_id;

    // Create days and get their IDs
    $dayIDs = [];
    for($d=1; $d<=$days; $d++){
        $conn->query("INSERT INTO journey_days (journeyID, dayNumber) VALUES ($jid, $d)");
        $dayIDs[] = $conn->insert_id;
    }

    // Simple distribution: put reservations in order across days
    $index = 0;
    foreach($selected as $reservationID){
        $day_index = $index % $days; // Cycle through days
        $dayID = $dayIDs[$day_index];
        $resID = intval($reservationID);
        
        // Insert without specifying ItemID (let auto-increment handle it)
        $conn->query("INSERT INTO journey_items (dayID, reservationID) VALUES ($dayID, $resID)");
        
        // Check for errors
        if($conn->error){
            die("Database error: " . $conn->error);
        }
        
        $index++;
    }

    // REDIRECT TO PLANNER PAGE
    header("Location: planner.php");
    exit;
}

// Fetch reservations for the form (AFTER processing POST)
$res = $conn->query("
    SELECT r.*, 
           COALESCE(e.eventName, p.name) AS name 
    FROM reservations r
    LEFT JOIN events e ON r.eventID = e.eventID
    LEFT JOIN places p ON r.placeID = p.placeID
    WHERE r.userID=$user_id
");
$hasReservations = $res->num_rows > 0;
?>
<!DOCTYPE html>
<html>
<head>
<title>Create Journey</title>
<link rel="stylesheet" href="style.css">
<style>
.create-container{ max-width:700px; margin:50px auto; background:#fff; padding:35px; border-radius:12px; box-shadow:0 4px 18px rgba(0,0,0,0.1);}
.section-title{ font-family:'Playfair Display',serif; font-size:32px; text-align:center; color:var(--green-dark); margin-bottom:25px;}
label{font-weight:bold;}
input, select{ width:100%; padding:10px; margin:10px 0 20px; border-radius:6px; border:1px solid #ddd;}
.submit-btn{ width:100%; padding:12px; background:var(--green-mid); color:white; border:none; cursor:pointer; border-radius:6px; font-size:17px;}
.submit-btn:hover{ background:var(--green-dark);}
.empty-box{ text-align:center; padding:40px; background:#fff; border-radius:12px; box-shadow:0 4px 18px rgba(0,0,0,0.1);}
/* SIMPLE CHECKBOX STYLING - FIXED ALIGNMENT */
.checkbox-item {
    margin: 10px 0;
    padding: 5px 0;
    display: flex;
    align-items: center;
}
.checkbox-item input[type="checkbox"] {
    width: auto;
    margin: 0 10px 0 0;
}
.checkbox-item label {
    font-weight: normal;
    margin-left: 0;
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
<section class="create-container">

<?php if(!$hasReservations): ?>
    <div class="empty-box">
        <h2 style="color:var(--green-dark);font-family:'Playfair Display',serif;">You have no reservations yet!</h2>
        <p style="margin:20px 0;">Make a reservation first to create your journey.</p>
        <a href="discover.php"><button class="submit-btn">Browse Events</button></a>
    </div>
<?php else: ?>
    <h2 class="section-title">Create Your Journey</h2>
    <form method="POST">
        <label>Journey Name</label>
        <input type="text" name="journeyName" required>

        <label>How many days?</label>
        <input type="number" name="days" min="1" required>

        <label>Select Reservations</label>
        <div>
            <?php while($r = $res->fetch_assoc()): ?>
            <div class="checkbox-item">
                <input type="checkbox" name="reservations[]" value="<?= $r['ReservationID'] ?>" id="res_<?= $r['ReservationID'] ?>">
                <label for="res_<?= $r['ReservationID'] ?>"><?= $r['name'] ?></label>
            </div>
            <?php endwhile; ?>
        </div>

        <button class="submit-btn" name="save">Create Journey</button>
    </form>
<?php endif; ?>

</section>
<footer>
  <p>&copy; 2025 Journy. All rights reserved.</p>
</footer>
</body>
</html>