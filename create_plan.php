<?php
session_start();
require_once 'db_config.php';
require_once 'session_check.php';

checkAuth();
$user_id = $_SESSION['user_id']; 

// SAVE JOURNEY 
if(isset($_POST['save'])){
    $name = mysqli_real_escape_string($link, $_POST['journeyName']);
    $days = intval($_POST['days']);
    $selected = isset($_POST['reservations']) ? $_POST['reservations'] : [];

    if (empty($selected)) {
        header("Location: create_plan.php?error=no_reservations");
        exit;
    }

    // Insert journey
    mysqli_query($link, "INSERT INTO journeys (userID, journeyName, days) VALUES ($user_id, '$name', $days)");
    $jid = mysqli_insert_id($link);

    // Create days and get their IDs
    $dayIDs = [];
    for($d=1; $d<=$days; $d++){
        mysqli_query($link, "INSERT INTO journey_days (journeyID, dayNumber) VALUES ($jid, $d)");
        $dayIDs[] = mysqli_insert_id($link);
    }

    // Simple distribution: put reservations in order across days
    $index = 0;
    foreach($selected as $reservationID){
        $day_index = $index % $days;
        $dayID = $dayIDs[$day_index];
        $resID = intval($reservationID);
        
        mysqli_query($link, "INSERT INTO journey_items (dayID, reservationID) VALUES ($dayID, $resID)");
        
        if(mysqli_error($link)){
            die("Database error: " . mysqli_error($link));
        }
        
        $index++;
    }

    header("Location: planner.php");
    exit;
}

// Fetch reservations for the form
$res = mysqli_query($link, "
    SELECT r.*, 
           COALESCE(e.eventName, p.name) AS name 
    FROM reservations r
    LEFT JOIN events e ON r.eventID = e.eventID
    LEFT JOIN places p ON r.placeID = p.placeID
    WHERE r.userID=$user_id
");
$hasReservations = mysqli_num_rows($res) > 0;
?>
<!DOCTYPE html>
<html>
<head>
<title>Create Journey</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.create-container{ 
    max-width:700px; 
    margin:50px auto; 
    background:#1e2a28; 
    padding:35px; 
    border-radius:12px; 
    box-shadow:0 6px 20px rgba(0,0,0,0.3);
}
.section-title{ 
    font-family:'Playfair Display',serif; 
    font-size:32px; 
    text-align:center; 
    color:#a2e896; 
    margin-bottom:25px;
}
label{
    font-weight:bold;
    color: #a2e896;
    display: block;
    margin-top: 15px;
}
input, select{ 
    width:100%; 
    padding:10px; 
    margin:10px 0 20px; 
    border-radius:6px; 
    border:1px solid #444;
    background: #2b3e3c;
    color: #eee;
    font-family: 'Poppins', sans-serif;
}
.submit-btn{ 
    width:100%; 
    padding:12px; 
    background:#b8860b; 
    color:white; 
    border:none; 
    cursor:pointer; 
    border-radius:6px; 
    font-size:17px;
    margin-top: 20px;
    transition: 0.3s;
    font-family: 'Poppins', sans-serif;
}
.submit-btn:hover{ 
    background:#d4af37;
}
.empty-box{ 
    text-align:center; 
    padding:40px; 
    background:#1e2a28; 
    border-radius:12px; 
    box-shadow:0 4px 18px rgba(0,0,0,0.3);
}
.empty-box h2 {
    color: #a2e896;
    font-family:'Playfair Display',serif;
    margin-bottom: 20px;
}
.empty-box p {
    color: #bbb;
    margin:20px 0;
}
/* SIMPLE CHECKBOX STYLING - FIXED ALIGNMENT */
.checkbox-item {
    margin: 10px 0;
    padding: 10px 15px;
    display: flex;
    align-items: center;
    background: #2b3e3c;
    border-radius: 6px;
    transition: 0.3s;
}
.checkbox-item:hover {
    background: #344e4c;
}
.checkbox-item input[type="checkbox"] {
    width: auto;
    margin: 0 10px 0 0;
}
.checkbox-item label {
    font-weight: normal;
    margin-left: 0;
    color: #eee;
    cursor: pointer;
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
        <h2>You have no reservations yet!</h2>
        <p>Make a reservation first to create your journey.</p>
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
            <?php while($r = mysqli_fetch_assoc($res)): ?>
            <div class="checkbox-item">
                <input type="checkbox" name="reservations[]" value="<?= $r['ReservationID'] ?>" id="res_<?= $r['ReservationID'] ?>">
                <label for="res_<?= $r['ReservationID'] ?>"><?= htmlspecialchars($r['name']) ?></label>
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
<?php
mysqli_close($link);
?>
