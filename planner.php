<?php
session_start();
require_once 'db_config.php';
require_once 'session_check.php';

checkAuth();
$user_id = $_SESSION['user_id'];

// Fetch journeys
$journeys = $link->query("
    SELECT * FROM journeys WHERE userID = $user_id ORDER BY journeyID DESC
");

// Sanitize GET parameter
$view_journey_id = isset($_GET['view']) ? intval($_GET['view']) : 0;
?>
<!DOCTYPE html>
<html>
<head>
<title>Planner</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style> 
    /* PAGE STYLING */ 
    a{text-decoration: none}
    .planner-container{ padding:50px; max-width: 1000px; margin: 0 auto; } 
    .page-title{ font-family:'Playfair Display',serif; font-size:36px; text-align:center; margin-bottom:30px; color:#a2e896; } 
    .create-btn{ display:block; margin:0 auto 40px; padding:12px 25px; background:#b8860b; color:#fff; border-radius:8px; border:none; cursor:pointer; font-size:16px; text-decoration: none; text-align: center; width: 200px; transition: 0.3s; }
    .create-btn:hover{ background:#d4af37; }
    .journey-card{ background:#1e2a28; border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.3); margin-bottom:30px; overflow:hidden; transition: 0.3s; }
    .journey-card:hover { box-shadow: 0 8px 25px rgba(162, 232, 150, 0.3); }
    .journey-header{ padding:18px 25px; background:#2b3e3c; color:#fff; display:flex; justify-content:space-between; align-items:center; } 
    .journey-header h3 { color: #a2e896; font-family: 'Playfair Display', serif; }
    .itinerary{ padding:25px 40px; border-left:4px solid #a2e896; background: #141f1e; margin: 0; }
    .itinerary p {
        margin: 15px 0;
        padding: 10px 15px;
        background: #1e2a28;
        border-radius: 6px;
        color: #eee;
    }
    .time-tag{ background:#b8860b; padding:6px 14px; color:#fff; border-radius:20px; font-size:14px; margin-right: 10px; }
    .btn-small{ background:#b8860b; color:white; padding:8px 15px; border-radius:6px; margin-left:10px; text-decoration: none; display: inline-block; transition: 0.3s; }
    .btn-small:hover{ background:#d4af37; }
    .journey-details { background: #141f1e; }
    .no-journeys { text-align: center; color: #a2e896; font-size: 1.2rem; margin: 40px 0; }
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
<section class="planner-container">
    <h1 class="page-title">My Journeys</h1>
    <a href="create_plan.php" class="create-btn">+ Create New Journey</a>

    <?php if($journeys->num_rows > 0): ?>
        <?php while($j = $journeys->fetch_assoc()): ?>
            <?php
            $journeyID = isset($j['JourneyID']) ? intval($j['JourneyID']) : 0;
            if ($journeyID > 0) {
                $days = $link->query("SELECT * FROM journey_days WHERE journeyID=$journeyID");
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
                <div class="journey-details">
                    <?php while($d = $days->fetch_assoc()): ?>
                        <div class="itinerary">
                            <h3 style="color:#a2e896;margin-bottom:8px;">Day <?= $d['DayNumber'] ?></h3>
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
                            
                            if($items->num_rows > 0): ?>
                                <?php while($i = $items->fetch_assoc()): ?>
                                    <p><span class="time-tag">Stop</span> <?= htmlspecialchars($i['rname']) ?></p>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p><em style="color: #bbb;">No stops planned for this day</em></p>
                            <?php endif; ?>
                        </div><br>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-journeys">You haven't created any journeys yet. Create your first journey to start planning!</p>
    <?php endif; ?>

</section>
<footer>
  <p>&copy; 2025 Journy. All rights reserved.</p>
</footer>
</body>
</html>
