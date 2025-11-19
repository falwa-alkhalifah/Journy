<?php
include 'db_config.php';

$current_user_id = 1;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_reservation'])) {

    $res_id_to_cancel = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);

    $event_id_to_restore = null;

    $tickets_to_restore = 0;

    if ($res_id_to_cancel) {

        $link->begin_transaction();

        try {

            $sql_fetch = "SELECT EventID, NumberOfTickets FROM Reservations WHERE ReservationID = ? AND UserID = ?";

            $stmt_fetch = mysqli_prepare($link, $sql_fetch);

            mysqli_stmt_bind_param($stmt_fetch, "ii", $res_id_to_cancel, $current_user_id);

            mysqli_stmt_execute($stmt_fetch);

            $result_fetch = mysqli_stmt_get_result($stmt_fetch);

            $reservation_details = mysqli_fetch_assoc($result_fetch);

            mysqli_stmt_close($stmt_fetch);

            if ($reservation_details) {

                $event_id_to_restore = $reservation_details['EventID'];

                $tickets_to_restore = $reservation_details['NumberOfTickets'];

                $sql_delete = "DELETE FROM Reservations WHERE ReservationID = ? AND UserID = ?";

                $stmt_delete = mysqli_prepare($link, $sql_delete);

                mysqli_stmt_bind_param($stmt_delete, "ii", $res_id_to_cancel, $current_user_id);

                mysqli_stmt_execute($stmt_delete);

                if ($event_id_to_restore !== NULL) {

                    $sql_restore = "UPDATE Events SET AvailableTickets = AvailableTickets + ? WHERE EventID = ?";

                    $stmt_restore = mysqli_prepare($link, $sql_restore);

                    mysqli_stmt_bind_param($stmt_restore, "ii", $tickets_to_restore, $event_id_to_restore);

                    mysqli_stmt_execute($stmt_restore);

                }

                $link->commit();

                header("Location: reservations.php?cancel_success=1");

                exit();

            } else {

                header("Location: reservations.php?cancel_error=1");

                exit();

            }

        } catch (Exception $e) {

            $link->rollback();

            header("Location: reservations.php?cancel_error=1");

            exit();

        }

    }

}

$confirmed_reservations = [];

$pending_reservations = [];

$sql = "
    SELECT
        r.ReservationID,
        r.UserID,
        r.EventID,
        r.PlaceID,
        r.NumberOfTickets,
        r.BookingDate,
        r.Status,
        e.EventName,
        e.City AS EventCity,
        e.Location AS EventLocation,
        e.Price AS EventPrice,  
        p.Name AS PlaceName,
        p.Type AS PlaceType,
        p.City AS PlaceCity,
        p.PriceRange
    FROM
        reservations r
    LEFT JOIN
        events e ON r.EventID = e.EventID
    LEFT JOIN
        places p ON r.PlaceID = p.PlaceID
    WHERE
        r.UserID = ?
    ORDER BY
        r.BookingDate DESC
";

if ($stmt = $link->prepare($sql)) {

    $stmt->bind_param("i", $current_user_id);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        if ($row['EventID'] !== NULL) {

            $row['ItemName'] = $row['EventName'];

            $row['ItemCity'] = $row['EventCity'];

            $row['ItemType'] = 'Event';

            $row['ItemReferralID'] = $row['EventID'];

            $row['ItemPrice'] = $row['EventPrice']; 

        } elseif ($row['PlaceID'] !== NULL) {

            $row['ItemName'] = $row['PlaceName'];

            $row['ItemCity'] = $row['PlaceCity'];

            $row['ItemType'] = $row['PlaceType'];

            $row['ItemReferralID'] = $row['PlaceID'];

            $row['ItemPrice'] = 0.00; 

        } else {

            $row['ItemName'] = 'Unknown Item';

            $row['ItemCity'] = 'Unknown';

            $row['ItemType'] = 'Unknown';

            $row['ItemReferralID'] = NULL;

            $row['ItemPrice'] = 0.00;

        }

        if ($row['Status'] === 'Confirmed') {

            $confirmed_reservations[] = $row;

        } elseif ($row['Status'] === 'Pending') {

            $pending_reservations[] = $row;

        }

    }

    $stmt->close();

} else {

    echo "ERROR: Could not prepare query: " . $link->error;

}

function renderReservationCard($reservation, $type) {

    $id = $reservation['ReservationID'];

    $name = htmlspecialchars($reservation['ItemName']);

    $city = htmlspecialchars($reservation['ItemCity']);

    $booking_date = date("M d, Y", strtotime($reservation['BookingDate']));

    $tickets = $reservation['NumberOfTickets'];    

    $amount_due = "350"; 

    if ($reservation['ItemType'] === 'Event') {

        $item_price = $reservation['ItemPrice'];

        $total_paid = number_format($tickets * $item_price, 2);

    } else {

        $total_paid = "400"; 

    }

    $details = '';

    $actions = '';

    $status_line = '';

    $referral_id = $reservation['ItemReferralID'];

    if ($type === 'confirmed') {

        $status_color = '#28a745';

        $status_text = 'Confirmed & Paid';        

        if ($reservation['ItemType'] === 'Event') { 

            $details = "

                <p>Location: {$city}</p>

                <p>Date: {$booking_date}</p>

                <p>Tickets: {$tickets}</p>

                <p>Total Paid: {$total_paid} SAR</p>

            ";

        } elseif ($reservation['ItemType'] === 'Hotel') { 

             $details = "

                 <p>Location: {$city}</p>

                 <p>Check-in: Dec 10, 2025</p>

                 <p>Stay: 3 Nights</p>

                 <p>Total Paid: {$total_paid} SAR</p>

             ";

        } else { 

             $details = "

                 <p>Location: {$city}</p>

                 <p>Date: {$booking_date}</p>

                 <p>Guests: {$tickets}</p>

                 <p>Total Paid: {$total_paid} SAR</p>

             ";

        }

        $status_line = '<p>Status: <span style="color:'.$status_color.';">&#10003;</span> '.$status_text.'</p>';

        $actions = '<button class="cancel-btn" data-id="'.$id.'">Cancel</button>'; 

    } else { 

        if ($reservation['ItemType'] === 'Event') { 

            $details = "

                <p>Location: {$city} | Date: {$booking_date}</p>

                <p>Reservation Required</p>

            ";

            $reserve_btn_text = 'Reserve Now';

            $target_link = "event-details.php?id=" . $referral_id; 

        } elseif ($reservation['ItemType'] === 'Hotel') { 

             $details = "

                 <p>City: {$city} | Check-in: Dec 10, 2025</p>

                 <p>Amount Due: {$amount_due} SAR</p>

             ";

             $reserve_btn_text = 'Pay Now';

             $target_link = "place-details.php?id=" . $referral_id; 

        } else { 

             $details = "

                 <p>City: {$city} | Dinner for {$tickets} | Date: {$booking_date}</p>

                 <p>Amount Due: {$amount_due} SAR</p>

             ";

             $reserve_btn_text = 'Complete Booking';

             $target_link = "place-details.php?id=" . $referral_id; 

        }

        $actions = '<button class="btn-small" onclick="goToDetails(\''.$target_link.'\')">'.$reserve_btn_text.'</button><button class="cancel-btn" data-id="'.$id.'">Cancel</button>';

    }

    echo <<<HTML

    <div class="res-card">

        <div class="res-info">

            <h3>{$name}</h3>

            {$details}

            {$status_line}

        </div>

        <div class="res-actions">

            {$actions}

        </div>

    </div>

HTML;

}

?>



<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Journy - Reservations</title>

<link rel="stylesheet" href="style.css">

<style>

.reservations-section { width: 90%; max-width: 900px; margin: 60px auto;}

.section-title { color: #ff6b00; font-family: 'Playfair Display', serif; font-size: 1.7rem; margin-bottom: 20px; border-left: 4px solid #ff6b00; padding-left: 10px;}

.reservations-container { display: flex; flex-direction: column; gap: 20px;}

.res-card { display: flex; align-items: center; justify-content: space-between; background: #fff; border-radius: 10px; padding: 15px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: 0.3s;}

.res-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(255,107,0,0.3);}

.res-info h3 { font-family: 'Playfair Display', serif; color: #ff6b00; margin-bottom: 5px;}

.res-info p { color: #555; font-size: 0.9rem;}

.res-actions { display: flex; gap: 10px;}

.res-actions button { padding: 8px 18px; border: none; border-radius: 5px; color: #fff; font-weight: 500; cursor: pointer; transition: 0.3s;}

.res-actions button:hover { opacity: 0.9; }

.cancel-btn { background-color: #dc3545; }

.btn-small { background-color: #ff6b00; } 

.search-section { text-align: center; margin: 30px 0;}

#searchInput { padding: 10px 15px; width: 90%; max-width: 500px; border-radius: 5px; border: 1px solid #ccc; font-size: 1rem;}

.empty-message { text-align: center; color: #555; margin-top: 40px; font-size: 1rem;}

#cancelForm { display: none; }

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

      <li><a href="login.php">Log Out</a></li>

    </ul>

  </nav>

</header>

<main>

    <section class="search-section">

    <input type="text" id="searchInput" placeholder="Search reservations...">

  </section>

<?php if (isset($_GET['cancel_success'])): ?>

    <div style="text-align: center; color: #28a745; font-weight: bold; margin-bottom: 20px;">

        ✅ Reservation successfully cancelled!

    </div>

<?php endif; ?>

<?php if (isset($_GET['cancel_error'])): ?>

    <div style="text-align: center; color: #dc3545; font-weight: bold; margin-bottom: 20px;">

        ❌ Error cancelling reservation. Please try again.

    </div>

<?php endif; ?>

<section class="reservations-section">

  <h2 class="section-title"><span style="color:#28a745;">&#10003;</span> Confirmed Reservations</h2>

  <div class="reservations-container" id="confirmedReservations">

    <?php

    if (count($confirmed_reservations) > 0) {

        foreach ($confirmed_reservations as $reservation) {

            renderReservationCard($reservation, 'confirmed');

        }

    } else {

        echo '<p class="empty-message">No confirmed reservations found.</p>';

    }

    ?>

  </div>

</section>

<section class="reservations-section">

  <h2 class="section-title">💳 Pending Reservations</h2>

  <div class="reservations-container" id="pendingReservations">

    <?php

    if (count($pending_reservations) > 0) {

        foreach ($pending_reservations as $reservation) {

            renderReservationCard($reservation, 'pending');

        }

    } else {

        echo '<p class="empty-message">No pending reservations found.</p>';

    }

    ?>

  </div>

</section>

<p class="empty-message" id="emptyMessage" style="display:none;">

  No reservations match your search.

</p>

</main>

<footer>

  <p>&copy; 2025 Journy. All rights reserved.</p>

</footer>

<form id="cancelForm" action="reservations.php" method="POST">

    <input type="hidden" name="cancel_reservation" value="1">

    <input type="hidden" name="reservation_id" id="cancelReservationId">

</form>

<script>

const allCards = document.querySelectorAll('.res-card');

const searchInput = document.getElementById('searchInput');

const emptyMessage = document.getElementById('emptyMessage');

const cancelForm = document.getElementById('cancelForm');

const cancelReservationIdInput = document.getElementById('cancelReservationId');

searchInput.addEventListener('keyup', () => {

  const filter = searchInput.value.toLowerCase();

  let visibleCount = 0;

  allCards.forEach(card => {

    const infoText = card.querySelector('.res-info').textContent.toLowerCase();

    if (infoText.includes(filter)) {

      card.style.display = 'flex';

      visibleCount++;

    } else {

      card.style.display = 'none';

    }

  });

  emptyMessage.style.display = visibleCount === 0 ? 'block' : 'none';

});

function goToDetails(url) {

    window.location.href = url;

}

document.querySelectorAll('.cancel-btn').forEach(button => {

    button.addEventListener('click', (e) => {

        const resId = e.target.getAttribute('data-id');

        if (confirm('Are you sure you want to cancel reservation ID ' + resId + '? This action cannot be undone.')) {

            cancelReservationIdInput.value = resId;

            cancelForm.submit();

        }

    });

});

</script>

</body>

</html>

<?php

mysqli_close($link);

?>