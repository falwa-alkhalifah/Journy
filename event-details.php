<?php
include 'db_config.php';

$event = null;
$show_popup = false;
$reservation_message = "";

// 1. Check for Event ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$event_id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reserve'])) {
    $user_id = 1; 
    $num_tickets = filter_input(INPUT_POST, 'tickets', FILTER_VALIDATE_INT);
    
    $sql_check = "SELECT AvailableTickets, EventName FROM Events WHERE EventID = ?";
    $stmt_check = mysqli_prepare($link, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $event_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $event_tickets = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);

    if ($event_tickets && $num_tickets > 0 && $num_tickets <= $event_tickets['AvailableTickets']) {
        
        $link->begin_transaction();
        try {
            $sql_update = "UPDATE Events SET AvailableTickets = AvailableTickets - ? WHERE EventID = ?";
            $stmt_update = mysqli_prepare($link, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "ii", $num_tickets, $event_id);
            mysqli_stmt_execute($stmt_update);

            $sql_insert = "INSERT INTO Reservations (UserID, EventID, NumberOfTickets) VALUES (?, ?, ?)";
            $stmt_insert = mysqli_prepare($link, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "iii", $user_id, $event_id, $num_tickets);
            mysqli_stmt_execute($stmt_insert);

            $link->commit();
            header("Location: event-details.php?id=" . $event_id . "&success=1");
            exit();

        } catch (Exception $e) {
            $link->rollback();
            $reservation_message = "Reservation error: Could not complete the process.";
        }
    } else {
        $reservation_message = "Please enter a valid number of tickets or check availability.";
    }
}


$sql_event = "SELECT * FROM Events WHERE EventID = ?";
if ($stmt = mysqli_prepare($link, $sql_event)) {
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $event = mysqli_fetch_assoc($result);
        } else {
            header("Location: index.php");
            exit();
        }
    }
    mysqli_stmt_close($stmt);
}

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $show_popup = true;
}

$date_display = date('F j, Y', strtotime($event['StartDate']));
if ($event['EndDate'] && $event['StartDate'] !== $event['EndDate']) {
    $date_display = date('F j, Y', strtotime($event['StartDate'])) . ' - ' . date('F j, Y', strtotime($event['EndDate']));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Journy - Event Details</title>
<link rel="stylesheet" href="style.css">

<style>
/* --- Styles for Confirmation Popup --- */
.popup-overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(0, 0, 0, 0.6); display: <?php echo $show_popup ? 'flex' : 'none'; ?>; 
  justify-content: center; align-items: center; z-index: 1000;
}
.popup-box {
  background: white; border-radius: 15px; padding: 40px 50px; text-align: center; max-width: 480px; width: 90%;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); font-family: 'Poppins', sans-serif;
}
.popup-box h1 { color: #333; font-size: 24px; margin-bottom: 15px; line-height: 1.4; }
.popup-box h3 { color: #ff6b00; font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 20px; }

/* --- Layout Styles for Event Details --- */
.event-container { padding:60px 100px; display:flex; flex-wrap:wrap; justify-content:center; gap:60px; align-items:flex-start; max-width:1400px; margin:auto;}
.event-image-area { flex:1; min-width:550px; max-width:650px;}
.event-details-card { flex:1; min-width:500px; max-width:600px; padding:40px;}
.event-image-area img:first-child { border-radius:10px; height:380px; width:550px; object-fit:cover;}
</style>
</head>
<body>

<header>
  <nav>
    <div class="logo">Journy</div>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="discover.html">Discover</a></li>
      <li><a href="planner.html">Planner</a></li>
      <li><a href="reservations.html">Reservations</a></li>
      <li><a href="login.html">Log out</a></li>
    </ul>
  </nav>
</header>

<main>
  <section class="event-container">
    
    <div class="event-image-area">
        <img src="<?php echo htmlspecialchars($event['ImageURL']); ?>" alt="<?php echo htmlspecialchars($event['EventName']); ?>" style="border-radius:10px;height:380px;width:550px;object-fit:cover;">
      
      <div style="display:flex; gap:15px; margin-top:15px; flex-wrap:wrap;">
        <img src="<?php echo htmlspecialchars($event['GalleryImage1'] ?? 'image/placeholder.png'); ?>" alt="Gallery Image 1" style="border-radius:10px;width:calc(50% - 7.5px);height:160px;object-fit:cover;">
        <img src="<?php echo htmlspecialchars($event['GalleryImage2'] ?? 'image/placeholder.png'); ?>" alt="Gallery Image 2" style="border-radius:10px;width:calc(50% - 7.5px);height:160px;object-fit:cover;">
        <img src="<?php echo htmlspecialchars($event['GalleryImage3'] ?? 'image/placeholder.png'); ?>" alt="Gallery Image 3" style="border-radius:10px;width:calc(50% - 7.5px);height:160px;object-fit:cover;">
        <img src="<?php echo htmlspecialchars($event['GalleryImage4'] ?? 'image/placeholder.png'); ?>" alt="Gallery Image 4" style="border-radius:10px;width:calc(50% - 7.5px);height:160px;object-fit:cover;">
      </div>
    </div>

    <div class="card event-details-card">
      <h3 style="color:#ff6b00;font-size:34px;font-family:'Playfair Display',serif;margin-bottom:25px;"><?php echo htmlspecialchars($event['EventName']); ?></h3>
      
      <p style="font-size:18px;margin-bottom:10px;"><strong>Date:</strong> <?php echo htmlspecialchars($date_display); ?></p>
      <p style="font-size:18px;margin-bottom:10px;"><strong>Location:</strong> <?php echo htmlspecialchars($event['Location']); ?>, <?php echo htmlspecialchars($event['City']); ?></p>
      
      <p style="font-size:18px;margin-bottom:20px;text-align:justify;"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($event['Description'])); ?></p>
      
      <?php if (!empty($reservation_message)): ?>
          <p style="color: red; font-weight: bold;"><?php echo $reservation_message; ?></p>
      <?php endif; ?>

      <form action="event-details.php?id=<?php echo $event_id; ?>" method="POST">
          <div style="margin-bottom:25px;">
            <label for="tickets" style="font-size:18px;margin-right:10px;color: #555;  padding: 0 15px 15px;"><strong>Number of Tickets:</strong></label>
            <input type="number" id="tickets" name="tickets" min="1" max="<?php echo htmlspecialchars($event['AvailableTickets'] ?? 0); ?>" value="1" style="width:80px;padding:8px;border:2px solid #ff6b00;border-radius:8px;text-align:center;">
          </div>

          <button type="submit" name="reserve" style="background-color:#ff6b00;color:white;border:none;border-radius:8px;font-size:17px;padding:14px 35px;cursor:pointer;" <?php echo ($event['AvailableTickets'] <= 0) ? 'disabled' : ''; ?>>
              <?php echo ($event['AvailableTickets'] <= 0) ? 'Tickets Sold Out' : 'Reserve Now'; ?>
          </button>
      </form>
    </div>
  </section>
</main>

<footer>
  <p>&copy; 2025 Journy. All rights reserved.</p>
</footer>

<div class="popup-overlay" id="popupOverlay">
  <div class="popup-box">
    <h1>You have reserved tickets for the <?php echo htmlspecialchars($event['EventName']); ?>!</h1>
    <h3> Useful Arabic Words </h3>
    <p><strong>Musiqa</strong> (Music) — Used during concerts</p>
    <p><strong>Haflah</strong> (Concert) — Major gathering</p>
    <p><strong>Tathkira</strong> (Ticket) — Pass to the event</p>
    <button onclick="window.location.href='event-details.php?id=<?php echo $event_id; ?>'">Close</button>
  </div>
</div>

</body>
</html>
<?php
mysqli_close($link);
?>
