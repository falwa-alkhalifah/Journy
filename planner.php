<?php
<?php
require 'db_config.php'; // CHANGED FROM connect.php
$user_id = 1;

// Fetch journeys
$journeys = $link->query(" // CHANGED $conn TO $link
    SELECT * FROM journeys WHERE userID = $user_id ORDER BY journeyID DESC
");

// Sanitize GET parameter
$view_journey_id = isset($_GET['view']) ? intval($_GET['view']) : 0;
?>
<!DOCTYPE html>
<html>
<head>
<title>Planner</title>
<link rel="stylesheet" href="style.css">
<style> 
    /* PAGE STYLING */ 
    a{text-decoration: none}
    .planner-container{ padding:50px; } 
    .page-title{ font-family:'Playfair Display',serif; font-size:36px; text-align:center; margin-bottom:30px; color:var(--green-dark); } 
    .create-btn{ display:block; margin:0 auto 40px; padding:12px 25px; background:var(--green-mid); color:#fff; border-radius:8px; border:none; cursor:pointer; font-size:16px; }
    .create-btn:hover{ background:var(--green-dark); }
    .journey-card{ background:#fff; border-radius:12px; box-shadow:0 4px 18px rgba(0,0,0,0.12); margin-bottom:30px; overflow:hidden; } 
    .journey-header{ padding:18px 25px; background:var(--green-mid); color:#fff; display:flex; justify-content:space-between; align-items:center; } 
    .itinerary{ padding:25px 40px; border-left:4px solid var(--green-mid); }
    .time-tag{ background:var(--brown-subtle); padding:4px 12px; color:#fff; border-radius:20px; font-size:14px; } 
    .btn-small{ background:var(--brown-subtle); color:white; padding:8px 15px; border-radius:6px; margin-left:10px; }
    .btn-small:hover{ background:var(--brown-dark); }
.itinerary p {
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
<section class="planner-container">
    <h1 class="page-title">My Journeys</h1>
    <a href="create_plan.php"><button class="create-btn">+ Create New Journey</button></a>

    <?php while($j = $journeys->fetch_assoc()): ?>
        <?php
        $journeyID = isset($j['JourneyID']) ? intval($j['JourneyID']) : 0;
        if ($journeyID > 0) {
            $days = $conn->query("SELECT * FROM journey_days WHERE journeyID=$journeyID");
        } else {
            $days = false;
        }
        ?>
        <div class="journey-card">
            <div class="journey-header">
                <h3><?= isset($j['JourneyName']) ? htmlspecialchars($j['JourneyName']) : 'Unnamed Journey' ?></h3>
                <div>
                    <?php if($view_journey_id && $view_journey_id === $journeyID): ?>
                        <a class="btn-small" href="planner.php">Hide</a>
                    <?php else: ?>
                        <a class="btn-small" href="planner.php?view=<?= $journeyID ?>">View</a>
                    <?php endif; ?>
                    <a class="btn-small" href="edit_journey.php?id=<?= $journeyID ?>">Edit</a>
                </div>
            </div>

            <?php if($view_journey_id && $view_journey_id === $journeyID && $days): ?>
            <div class="journey-details" style="padding:25px;">
                <?php while($d = $days->fetch_assoc()): ?>
                    <div class="itinerary">
                        <h3 style="color:var(--green-dark);margin-bottom:8px;">Day <?= $d['DayNumber'] ?></h3>
                        <?php
                        $items = $conn->query("
                            SELECT ji.*, 
                                   COALESCE(e.eventName, p.name) AS rname 
                            FROM journey_items ji
                            JOIN reservations r ON r.reservationID = ji.reservationID
                            LEFT JOIN events e ON r.eventID = e.eventID
                            LEFT JOIN places p ON r.placeID = p.placeID
                            WHERE ji.dayID={$d['DayID']}
                        ");
                        
                        if($items->num_rows > 0): ?>
                            <?php while($i = $items->fetch_assoc()): ?>
                                <p><span class="time-tag">Stop</span> <?= htmlspecialchars($i['rname']) ?></p>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p><em>No stops planned for this day</em></p>
                        <?php endif; ?>
                    </div><br>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>

</section>
<footer>
  <p>&copy; 2025 Journy. All rights reserved.</p>
</footer>
</body>
</html>
