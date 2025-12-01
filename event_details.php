<?php
session_start();
require_once 'db_config.php';
require_once 'session_check.php';

checkAuth(); // Users must be logged in to view details and make reservations
$user_id = $_SESSION['user_id'];

// Default variables
$item = null;
$show_popup = false;
$reservation_message = "";
$vocabulary_list = []; 
$new_reservation_id = null; 
$pending_reservation_id = null; 


// 1. Determine Item Type and ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$item_id = (int)$_GET['id'];

// Check if the type parameter is present and set to 'place' 
$item_type = isset($_GET['type']) && $_GET['type'] === 'place' ? 'place' : 'event'; // هذا المنطق يضمن التعامل مع الأماكن بشكل صحيح
$is_event = ($item_type === 'event');

// Determine table and ID column based on type
$table_name = $is_event ? 'Events' : 'Places';
$id_column = $is_event ? 'EventID' : 'PlaceID';

// Assuming hardcoded UserID for reservation logic
$user_id = 1; 

// This handles the POST request when the 'Complete Booking' button is clicked for a Place
if (!$is_event && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_place_booking'])) {
    
    $res_id_to_confirm = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);

    if ($res_id_to_confirm) {
        // Only confirm the status if it's currently Pending
        $sql_confirm = "UPDATE Reservations SET Status = 'Confirmed' WHERE ReservationID = ? AND UserID = ? AND Status = 'Pending'";
        $stmt_confirm = mysqli_prepare($link, $sql_confirm);
        mysqli_stmt_bind_param($stmt_confirm, "ii", $res_id_to_confirm, $user_id);
        mysqli_stmt_execute($stmt_confirm);

        if (mysqli_stmt_affected_rows($stmt_confirm) > 0) {
            // Success: Redirect back to avoid resubmission and show success message
            header("Location: event_details.php?id=" . $item_id . "&type=place&status_confirmed=1");
            exit();
        } else {
            // Handle error (e.g., reservation not found or already confirmed)
            $reservation_message = "Error: Could not confirm reservation ID " . $res_id_to_confirm . ". It might already be confirmed.";
        }
        mysqli_stmt_close($stmt_confirm);
    }
}

// This handles the POST request when the 'Complete Booking' button is clicked for an Event
if ($is_event && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_event_booking'])) {

    $res_id_to_confirm = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);

    if ($res_id_to_confirm) {
        // Only confirm the status if it's currently Pending
        $sql_confirm = "UPDATE Reservations SET Status = 'Confirmed' WHERE ReservationID = ? AND UserID = ? AND Status = 'Pending'";
        $stmt_confirm = mysqli_prepare($link, $sql_confirm);
        mysqli_stmt_bind_param($stmt_confirm, "ii", $res_id_to_confirm, $user_id);
        mysqli_stmt_execute($stmt_confirm);

        if (mysqli_stmt_affected_rows($stmt_confirm) > 0) {
            // Success: Redirect back to avoid resubmission and show success message
            header("Location: event_details.php?id=" . $item_id . "&type=event&status_confirmed=1");
            exit();
        } else {
            // Handle error (e.g., reservation not found or already confirmed)
            $reservation_message = "Error: Could not confirm event reservation ID " . $res_id_to_confirm . ". It might already be confirmed or not found.";
        }
        mysqli_stmt_close($stmt_confirm);
    }
}

// This runs if no pending reservation is found, or if the user initiates a new booking (standard flow)
if ($is_event && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reserve'])) {
    
    $num_tickets = filter_input(INPUT_POST, 'tickets', FILTER_VALIDATE_INT);
    // Check available tickets for the event
    $sql_check = "SELECT AvailableTickets FROM Events WHERE EventID = ?";
    $stmt_check = mysqli_prepare($link, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $item_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $event_tickets = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);

    if ($event_tickets && $num_tickets > 0 && $num_tickets <= $event_tickets['AvailableTickets']) { 
        // Start transaction for atomicity
        $link->begin_transaction();

        try {
            $sql_update_event = "UPDATE Events SET AvailableTickets = AvailableTickets - ? WHERE EventID = ?";
            $stmt_update_event = mysqli_prepare($link, $sql_update_event);
            mysqli_stmt_bind_param($stmt_update_event, "ii", $num_tickets, $item_id);
            mysqli_stmt_execute($stmt_update_event);
            mysqli_stmt_close($stmt_update_event);
            
            $status = "Confirmed"; // New bookings are immediately confirmed
            $sql_insert_reservation = "INSERT INTO Reservations (UserID, EventID, NumberOfTickets, Status) VALUES (?, ?, ?, ?)";
            $stmt_insert_reservation = mysqli_prepare($link, $sql_insert_reservation);
            mysqli_stmt_bind_param($stmt_insert_reservation, "iiis", $user_id, $item_id, $num_tickets, $status);
            mysqli_stmt_execute($stmt_insert_reservation);
            if (mysqli_stmt_affected_rows($stmt_insert_reservation) === 0) {
                throw new Exception("Failed to insert new reservation.");
            }
            // Get the ID of the new reservation 
            $new_reservation_id = mysqli_insert_id($link);
            mysqli_stmt_close($stmt_insert_reservation);
            // COMMIT THE TRANSACTION HERE
            $link->commit();

            // --- REDIRECTION: Redirect back to the details page with the new reservation ID ---
            // تم التعديل هنا لضمان تمرير type=event
            header("Location: event_details.php?id=" . $item_id . "&type=event&success_id=" . $new_reservation_id); 
            exit();

        } catch (Exception $e) {
            $link->rollback();
            $reservation_message = "Reservation error: Could not complete the process. " . $e->getMessage();
        }
    } else {
        $reservation_message = "Please enter a valid number of tickets or check availability. Available: " . ($event_tickets['AvailableTickets'] ?? 0);
    }
}

if (isset($_GET['success_id']) && is_numeric($_GET['success_id']) && $is_event) {
    $show_popup = true;
    $new_reservation_id = (int)$_GET['success_id'];
    // Fetch vocabulary for the reserved event
    $sql_vocab = "
        SELECT 
            EV.ArabicWord, EV.EnglishTranslation, EV.ContextPhrase 
        FROM EventVocabulary EV
        JOIN Reservations R ON R.EventID = EV.EventID
        WHERE R.ReservationID = ?
    ";
    if ($stmt_vocab = mysqli_prepare($link, $sql_vocab)) {
        mysqli_stmt_bind_param($stmt_vocab, "i", $new_reservation_id);
        mysqli_stmt_execute($stmt_vocab);
        $result_vocab = mysqli_stmt_get_result($stmt_vocab);
        while ($row = mysqli_fetch_assoc($result_vocab)) {
            $vocabulary_list[] = $row;
        }
        mysqli_stmt_close($stmt_vocab);
    }
}

if (!$is_event) {
    // Find the pending reservation ID for this UserID (1) and this PlaceID
    $sql_find_res = "SELECT ReservationID FROM Reservations WHERE UserID = ? AND PlaceID = ? AND Status = 'Pending' ORDER BY BookingDate DESC LIMIT 1";
    $type_id = $item_id;
} else {
    // Find the pending reservation ID for this UserID (1) and this EventID
    $sql_find_res = "SELECT ReservationID FROM Reservations WHERE UserID = ? AND EventID = ? AND Status = 'Pending' ORDER BY BookingDate DESC LIMIT 1";
    $type_id = $item_id;
}

if ($stmt_find_res = mysqli_prepare($link, $sql_find_res)) {
    mysqli_stmt_bind_param($stmt_find_res, "ii", $user_id, $type_id);
    mysqli_stmt_execute($stmt_find_res);
    $result_find_res = mysqli_stmt_get_result($stmt_find_res);
    if ($row_res = mysqli_fetch_assoc($result_find_res)) {
        $pending_reservation_id = $row_res['ReservationID'];
    }
    mysqli_stmt_close($stmt_find_res);
}

// Check if the status was confirmed via GET parameter redirect
if (isset($_GET['status_confirmed'])) {
    $reservation_message = "✅ Booking Confirmed! See your confirmed reservation details.";
}

$select_columns = $is_event 
    ? "EventID, EventName, Location, City, Description, StartDate, EndDate, AvailableTickets, ImageURL, Price"
    : "PlaceID, Name, Type, City, DistanceFromEvent, PriceRange, Rating, LocallyOwned, ImageURL";

$sql_item = "SELECT $select_columns FROM $table_name WHERE $id_column = ?";

if ($stmt = mysqli_prepare($link, $sql_item)) {
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $item = mysqli_fetch_assoc($result);
        } else {
            header("Location: index.php"); 
            exit();
        }
    }
    mysqli_stmt_close($stmt);
}


$date_display = '';
$item_name = '';
$city = '';
$description = '';
$image_url = '';
$unit_price = 0.00;
$available_tickets = 0;
$location = ''; 
$price_range_symbol = '';

if ($item) {
    if ($is_event) {
        // Event specific data mapping
        $item_name = $item['EventName'];
        $city = $item['City'];
        $description = $item['Description'];
        $image_url = $item['ImageURL'] ?? 'image/default_event.jpg';
        $unit_price = number_format($item['Price'] ?? 0, 2, '.', '');
        $available_tickets = (int)($item['AvailableTickets'] ?? 0); 
        $location = $item['Location'];
        $date_display = date('F j, Y', strtotime($item['StartDate']));
        if ($item['EndDate'] && $item['StartDate'] !== $item['EndDate']) {
            $date_display .= ' - ' . date('F j, Y', strtotime($item['EndDate']));
        }
    } else {
        // Place specific data mapping
        $item_name = $item['Name'];
        $city = $item['City'];
        $description = "This highly-rated " . strtolower($item['Type']) . " offers a unique experience in the heart of " . $item['City'] . ". Known for its excellent service and " . $item['Rating'] . "/5 rating.";
        $image_url = $item['ImageURL'] ?? 'image/default_' . strtolower($item['Type']) . '.jpg';
        $price_range_symbol = str_repeat('$', strlen($item['PriceRange'] ?? ''));
        $location = $item['City'];
    }
}

$event_price = $is_event ? $unit_price : 0.00; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Journy - Details | <?php echo htmlspecialchars($item_name); ?></title>
<link rel="stylesheet" href="style.css">
<style>

/* --- POPUP STYLES --- */

.popup-overlay {

    position: fixed; top: 0; left: 0; width: 100%; height: 100%;

    background-color: rgba(0, 0, 0, 0.6);

    display: <?php echo $show_popup && $is_event ? 'flex' : 'none'; ?>;

    justify-content: center; align-items: center; z-index: 1000;

}

.popup-box {

    background: #1e2a28; border-radius: 15px; padding: 40px 50px; text-align: center; max-width: 480px; width: 90%;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); font-family: 'Poppins', sans-serif;

}

.popup-box h1 { color: #a2e896; font-size: 24px; margin-bottom: 15px; line-height: 1.4; }

.popup-box h3 { color: #a2e896; font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 20px; }

.popup-box button { background-color:#b8860b;color:white;border:none;border-radius:8px;font-size:17px;padding:12px 30px;cursor:pointer; margin-top: 15px; }



.event-container { padding:60px 100px; display:flex; flex-wrap:wrap; justify-content:center; gap:60px; align-items:flex-start; max-width:1400px; margin:auto;}

.event-image-area { flex:1; min-width:550px; max-width:650px; text-align: center; }

.event-details-card { flex:1; min-width:500px; max-width:600px; padding:40px;}

.event-image-area img:first-child { border-radius:10px; height: 450px; width: 100%; object-fit:cover;}

/* Vocabulary style for alignment */

.vocab-item {

    text-align: left;

    margin-bottom: 8px;

    font-size: 16px;

}

.vocab-word {

    font-weight: bold;

    color: var(--green-dark);

}

.vocab-context {

    font-style: italic;

    color: #a2e896;

    margin-left: 5px;

}

/* --- POPUP STYLES END --- */

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

<main>
    <section class="event-container">  
        <div class="event-image-area">
            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($item_name); ?>">
        </div>
        <div class="card event-details-card">
            <h3 style="color:#a2e896;font-size:34px;font-family:'Playfair Display',serif;margin-bottom:25px;"><?php echo htmlspecialchars($item_name); ?></h3>  
            <?php if ($is_event): ?>
                <p style="font-size:18px;margin-bottom:10px;"><strong>Date:</strong> <?php echo htmlspecialchars($date_display); ?></p>
                <p style="font-size:18px;margin-bottom:10px;"><strong>Location:</strong> <?php echo htmlspecialchars($location); ?>, <?php echo htmlspecialchars($city); ?></p>
            <?php else: ?>
                <p style="font-size:18px;margin-bottom:10px;"><strong>Type:</strong> <?php echo htmlspecialchars(ucfirst($item['Type'])); ?></p>
                <p style="font-size:18px;margin-bottom:10px;"><strong>City:</strong> <?php echo htmlspecialchars($city); ?></p>
                <p style="font-size:18px;margin-bottom:10px;"><strong>Price Range:</strong> <?php echo htmlspecialchars($price_range_symbol); ?></p>
                <p style="font-size:18px;margin-bottom:10px;"><strong>Rating:</strong> <?php echo htmlspecialchars($item['Rating'] ?? 'N/A'); ?>/5</p>
                <p style="font-size:18px;margin-bottom:10px;"><strong>Distance:</strong> <?php echo htmlspecialchars($item['DistanceFromEvent'] ?? 'N/A'); ?>km</p>
                <?php if ($item['LocallyOwned']): ?>
                    <p style="font-size:18px;margin-bottom:10px; color: var(--green-mid);"><strong>🏠 Locally Owned</strong></p>
                <?php endif; ?>
            <?php endif; ?>
            <p style="font-size:18px;margin-bottom:20px;text-align:justify;"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($description)); ?></p>
            <?php if ($is_event): ?>
                <?php if (!empty($reservation_message) && !isset($_GET['status_confirmed'])): ?>
                    <p style="color: red; font-weight: bold;"><?php echo $reservation_message; ?></p>
                <?php endif; ?>
                <?php if (!empty($reservation_message) && isset($_GET['status_confirmed'])): ?>
                    <p style="color: green; font-weight: bold; font-size: 1.2rem;"><?php echo $reservation_message; ?></p>
                <?php endif; ?>
                <?php if ($pending_reservation_id && !isset($_GET['status_confirmed'])): ?>
                    <form action="event_details.php?id=<?php echo $item_id; ?>&type=event" method="POST">
                        <p style="font-size: 1.1rem; color: #b8860b; font-weight: bold;">A booking for this event is pending. Complete your reservation now:</p>
                        <input type="hidden" name="reservation_id" value="<?php echo $pending_reservation_id; ?>">
                        <button type="submit" name="confirm_event_booking" class="btn-small" style="background-color: #b8860b;color:white;border:none;border-radius:8px;font-size:17px;padding:14px 35px;cursor:pointer;">
                            Complete Event Booking
                        </button>
                    </form>
                <?php else: ?>
                    <form action="event_details.php?id=<?php echo $item_id; ?>&type=event" method="POST">
                        <div style="margin-bottom:15px;">
                            <label for="tickets" style="font-size:18px;margin-right:10px;color: #555; padding: 0 15px 15px;"><strong>Number of Tickets:</strong></label>
                            <input type="number" id="tickets" name="tickets" min="1" max="<?php echo htmlspecialchars($available_tickets); ?>" value="1" style="width:80px;padding:8px;border:2px solid #b8860b;border-radius:8px;text-align:center;">
                        </div>
                        <p style="font-size:20px; margin-bottom: 25px; font-weight: bold;">
                            Total Cost: <span id="totalCost" style="color:#b8860b;">SAR 0.00</span>
                        </p>
                        <button type="submit" name="reserve" style="background-color:#b8860b;color:white;border:none;border-radius:8px;font-size:17px;padding:14px 35px;cursor:pointer;" <?php echo ($available_tickets <= 0) ? 'disabled' : ''; ?>>
                            <?php echo ($available_tickets <= 0) ? 'Tickets Sold Out' : 'Reserve Now'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            <?php else: // Logic for Places (Hotel/Restaurant) ?>
                <?php if (!empty($reservation_message) && !isset($_GET['status_confirmed'])): ?>
                    <p style="color: red; font-weight: bold; font-size: 1.2rem;"><?php echo $reservation_message; ?></p>
                <?php endif; ?>
                <?php if (!empty($reservation_message) && isset($_GET['status_confirmed'])): ?>
                    <p style="color: green; font-weight: bold; font-size: 1.2rem;"><?php echo $reservation_message; ?></p>
                <?php endif; ?>
                <?php if ($pending_reservation_id && !isset($_GET['status_confirmed'])): ?>
                    <form action="event_details.php?id=<?php echo $item_id; ?>&type=place" method="POST">
                        <input type="hidden" name="reservation_id" value="<?php echo $pending_reservation_id; ?>">
                        <button type="submit" name="confirm_place_booking" class="btn-small" style="background-color: #b8860b;color:white;border:none;border-radius:8px;font-size:17px;padding:14px 35px;cursor:pointer;">
                            Complete Booking 
                        </button>
                    </form>
                <?php else: ?>
                    <?php if (!isset($_GET['status_confirmed'])): ?>
                    <p style="color: #555; font-weight: bold; font-size: 1.1rem;">No Pending Reservation found for this item. Please reserve it on the Discover page.</p>
                    <?php endif; ?>
                    <a href="reservations.php" class="btn-small" style="background-color: #b8860b; padding: 14px 35px; border-radius: 8px; color: white; text-decoration: none; font-size: 17px; display: inline-block;">Go to Reservations</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<footer>
    <p>&copy; 2025 Journy. All rights reserved.</p>
</footer>

<?php if ($is_event): // Popup only for Events ?>
<div class="popup-overlay" id="popupOverlay">
    <div class="popup-box">
        <h1>You have reserved tickets for the <?php echo htmlspecialchars($item_name); ?>!</h1>
        <h3> Useful Arabic Words </h3>
        <?php if (!empty($vocabulary_list)): ?>
            <?php foreach ($vocabulary_list as $vocab): ?>
                <p class="vocab-item">
                    <span class="vocab-word"><?php echo htmlspecialchars($vocab['ArabicWord']); ?></span> 
                    (<?php echo htmlspecialchars($vocab['EnglishTranslation']); ?>) 
                    — <span class="vocab-context"><?php echo htmlspecialchars($vocab['ContextPhrase']); ?></span>
                </p>
            <?php endforeach; ?>
        <?php else: ?>
             <p>No specific vocabulary found for this event, but try saying **'Shukran'** (Thank you)!</p>
        <?php endif; ?>
        <button onclick="window.location.href='reservations.php'">Continue to Reservations</button> 
    </div>
</div>
<?php endif; ?>

<script>
    const UNIT_PRICE = parseFloat("<?php echo $event_price; ?>");

    <?php if ($is_event): ?>
        const ticketsInput = document.getElementById('tickets');
        const totalCostSpan = document.getElementById('totalCost');
        function updateTotalPrice() {
            const numTickets = parseInt(ticketsInput.value) || 0;
            const total = numTickets * UNIT_PRICE;
            totalCostSpan.textContent = 'SAR ' + total.toFixed(2);
        }
        if(ticketsInput) {
            ticketsInput.addEventListener('input', updateTotalPrice);
            updateTotalPrice(); 
        }
    <?php endif; ?>

    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.has('success_id') || urlParams.has('status_confirmed')) {
        let newUrl = window.location.pathname + window.location.search.replace(/&success_id=\d+|success_id=\d+&?|&status_confirmed=1|status_confirmed=1&?/, '');
        newUrl = newUrl.replace(/(\?|&)$/, ''); 
        history.replaceState(null, '', newUrl);
    }
</script>

</body>
</html>
<?php
mysqli_close($link);
?>
