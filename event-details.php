<?php

include 'db_config.php';



// Default variables

$item = null;

$show_popup = false;

$reservation_message = "";



// 1. Determine Item Type and ID

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    header("Location: index.php");

    exit();

}



$item_id = (int)$_GET['id'];



// Check if the type parameter is present and set to 'place' 

// This handles links like: event_details.php?id=X&type=place

$item_type = isset($_GET['type']) && $_GET['type'] === 'place' ? 'place' : 'event';

$is_event = ($item_type === 'event');



// Determine table and ID column based on type

$table_name = $is_event ? 'Events' : 'Places';

$id_column = $is_event ? 'EventID' : 'PlaceID';



// --- 2. Reservation Logic (Only for Events) ---



if ($is_event && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reserve'])) {

    $user_id = 1; // Assuming hardcoded UserID

    $num_tickets = filter_input(INPUT_POST, 'tickets', FILTER_VALIDATE_INT);



    // Check available tickets for the event

    $sql_check = "SELECT AvailableTickets, EventName, Price FROM Events WHERE EventID = ?";

    $stmt_check = mysqli_prepare($link, $sql_check);

    mysqli_stmt_bind_param($stmt_check, "i", $item_id);

    mysqli_stmt_execute($stmt_check);

    $result_check = mysqli_stmt_get_result($stmt_check);

    $event_tickets = mysqli_fetch_assoc($result_check);

    mysqli_stmt_close($stmt_check);



    if ($event_tickets && $num_tickets > 0 && $num_tickets <= $event_tickets['AvailableTickets']) { 

        $link->begin_transaction();

        try {

            // 1. Update event ticket count

            $sql_update_event = "UPDATE Events SET AvailableTickets = AvailableTickets - ? WHERE EventID = ?";

            $stmt_update_event = mysqli_prepare($link, $sql_update_event);

            mysqli_stmt_bind_param($stmt_update_event, "ii", $num_tickets, $item_id);

            mysqli_stmt_execute($stmt_update_event);

            mysqli_stmt_close($stmt_update_event);



            // 2. Insert reservation record

            $status = "Confirmed"; 

            $sql_insert_reservation = "INSERT INTO Reservations (UserID, EventID, NumberOfTickets, Status) VALUES (?, ?, ?, ?)";

            $stmt_insert_reservation = mysqli_prepare($link, $sql_insert_reservation);

            mysqli_stmt_bind_param($stmt_insert_reservation, "iiis", $user_id, $item_id, $num_tickets, $status);

            mysqli_stmt_execute($stmt_insert_reservation);



            if (mysqli_stmt_affected_rows($stmt_insert_reservation) === 0) {

                throw new Exception("Failed to insert new reservation.");

            }

            mysqli_stmt_close($stmt_insert_reservation);



            $link->commit();

            // Redirect back to this page with the correct parameters

            header("Location: event_details.php?id=" . $item_id . "&type=" . $item_type . "&success=1");

            exit();



        } catch (Exception $e) {

            $link->rollback();

            $reservation_message = "Reservation error: Could not complete the process. " . $e->getMessage();

        }

    } else {

        $reservation_message = "Please enter a valid number of tickets or check availability.";

    }

}



// --- 3. Fetch Item Details (Event or Place) ---

// Select columns dynamically

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



// --- 4. Setup Display Variables ---

if (isset($_GET['success']) && $_GET['success'] == 1) {

    $show_popup = true;

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

        // Use a default description for places since the DB schema doesn't show a Description column for 'places'

        $description = "This highly-rated " . strtolower($item['Type']) . " offers a unique experience in the heart of " . $item['City'] . ". Known for its excellent service and " . $item['Rating'] . "/5 rating.";

        $image_url = $item['ImageURL'] ?? 'image/default_' . strtolower($item['Type']) . '.jpg';

        $price_range_symbol = str_repeat('$', strlen($item['PriceRange'] ?? ''));

        $location = $item['City'];

    }

}

// This variable is used in the JavaScript block for price calculation

$event_price = $is_event ? $unit_price : 0.00; 

?>



<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Journy - Details | <?php echo htmlspecialchars($item_name); ?></title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="style2.css">


<style>

/* ... (Your CSS Styles) ... */

.popup-overlay {

    position: fixed; top: 0; left: 0; width: 100%; height: 100%;

    background-color: rgba(0, 0, 0, 0.6); display: <?php echo $show_popup && $is_event ? 'flex' : 'none'; ?>; 

    justify-content: center; align-items: center; z-index: 1000;

}

.popup-box {

    background: white; border-radius: 15px; padding: 40px 50px; text-align: center; max-width: 480px; width: 90%;

    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); font-family: 'Poppins', sans-serif;

}

.popup-box h1 { color: #333; font-size: 24px; margin-bottom: 15px; line-height: 1.4; }

.popup-box h3 { color: #ff6b00; font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 20px; }

.popup-box button { background-color:#ff6b00;color:white;border:none;border-radius:8px;font-size:17px;padding:12px 30px;cursor:pointer; margin-top: 15px; }

.event-container { padding:60px 100px; display:flex; flex-wrap:wrap; justify-content:center; gap:60px; align-items:flex-start; max-width:1400px; margin:auto;}

.event-image-area { flex:1; min-width:550px; max-width:650px; text-align: center; } 

.event-details-card { flex:1; min-width:500px; max-width:600px; padding:40px;}

.event-image-area img:first-child { border-radius:10px; height: 450px; width: 100%; object-fit:cover;} 

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

            <li><a href="login.php">Log out</a></li>

        </ul>

    </nav>

</header>



<main>

    <section class="event-container">  

        <div class="event-image-area">

            <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($item_name); ?>">

        </div>



        <div class="card event-details-card">

            <h3 style="color:#ff6b00;font-size:34px;font-family:'Playfair Display',serif;margin-bottom:25px;"><?php echo htmlspecialchars($item_name); ?></h3>  



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

                <?php if (!empty($reservation_message)): ?>

                    <p style="color: red; font-weight: bold;"><?php echo $reservation_message; ?></p>

                <?php endif; ?>



                <form action="event_details.php?id=<?php echo $item_id; ?>&type=event" method="POST">

                    <div style="margin-bottom:15px;">

                        <label for="tickets" style="font-size:18px;margin-right:10px;color: #555; padding: 0 15px 15px;"><strong>Number of Tickets:</strong></label>

                        <input type="number" id="tickets" name="tickets" min="1" max="<?php echo htmlspecialchars($available_tickets); ?>" value="1" style="width:80px;padding:8px;border:2px solid #ff6b00;border-radius:8px;text-align:center;">

                    </div>

                    <p style="font-size:20px; margin-bottom: 25px; font-weight: bold;">

                        Total Cost: <span id="totalCost" style="color:#ff6b00;">SAR 0.00</span>

                    </p>

                    <button type="submit" name="reserve" style="background-color:#ff6b00;color:white;border:none;border-radius:8px;font-size:17px;padding:14px 35px;cursor:pointer;" <?php echo ($available_tickets <= 0) ? 'disabled' : ''; ?>>

                        <?php echo ($available_tickets <= 0) ? 'Tickets Sold Out' : 'Reserve Now'; ?>

                    </button>

                </form>

            <?php else: ?>

                <a href="planner.php" class="btn-small" style="background-color: #3f51b5; padding: 14px 35px; border-radius: 8px; color: white; text-decoration: none; font-size: 17px; display: inline-block;">Reserve Now</a>

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

        <p><strong>Musiqa</strong> (Music) — Used during concerts</p>

        <p><strong>Haflah</strong> (Concert) — Major gathering</p>

        <p><strong>Tathkira</strong> (Ticket) — Pass to the event</p>

        <button onclick="window.location.href='index.php'">Continue</button>  

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

        ticketsInput.addEventListener('input', updateTotalPrice);

        updateTotalPrice(); 

    <?php endif; ?>



    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('success') === '1') {

        history.replaceState(null, '', window.location.pathname + window.location.search.replace(/&success=1|success=1&|success=1$/, ''));

    }

</script>



</body>

</html>



<?php

mysqli_close($link);

?>