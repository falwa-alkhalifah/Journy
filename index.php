<?php
session_start();
require_once 'db_config.php';
require_once 'session_check.php';

function getFeaturedEvents() {
    global $link;
    $sql = "SELECT EventID, EventName, Category, City, StartDate, EndDate, ImageURL FROM Events ORDER BY StartDate DESC";
    $result = mysqli_query($link, $sql);
    $events = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $events[] = $row;
        }
    }
    return $events;
}

function getRecommendedRestaurants() {
    global $link;
    $sql = "SELECT PlaceID, Name, Type, City, ImageURL FROM Places WHERE Type = 'Restaurant' AND EventID IS NULL ORDER BY Rating DESC";
    $result = mysqli_query($link, $sql);
    $restaurants = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $restaurants[] = $row;
        }
    }
    return $restaurants;
}

function getRecommendedHotels() {
    global $link;
    $sql = "SELECT PlaceID, Name, Type, City, ImageURL FROM Places WHERE Type = 'Hotel' AND EventID IS NULL ORDER BY Rating DESC";
    $result = mysqli_query($link, $sql);
    $hotels = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $hotels[] = $row;
        }
    }
    return $hotels;
}

$all_events = getFeaturedEvents();
$all_restaurants = getRecommendedRestaurants();
$all_hotels = getRecommendedHotels();

$featured_events = array_slice($all_events, 0, 4);
$recommended_restaurants = array_slice($all_restaurants, 0, 4);
$recommended_hotels = array_slice($all_hotels, 0, 4);

$carousel_items = array_merge($all_events, $all_restaurants, $all_hotels);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Journy - Home</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
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

 <div class="hero">
    <div class="hero-content">
        <h1>Discover Amazing Events Across Saudi Arabia</h1>
        <p>Plan your trips, book tickets, and explore local experiences effortlessly</p>
        <a href="discover.html" class="btn">Explore Events</a>
    </div>
  </div>
</header>

<main>
    <section class="top-carousel-section">
        <div class="carousel-container" id="carousel-container">
            <?php if (count($carousel_items) > 0): ?>
                <?php foreach ($carousel_items as $item): ?>
                    <?php
                    $is_event = isset($item['EventID']);
                    $id = $is_event ? $item['EventID'] : $item['PlaceID'];
                    $name = $is_event ? $item['EventName'] : $item['Name'];
                    $city = $item['City'];
                    $image_url = $item['ImageURL'];

                    if ($is_event) {
                        $date_display = date('M j, Y', strtotime($item['StartDate']));
                        if ($item['EndDate'] && $item['StartDate'] !== $item['EndDate']) {
                            $date_display = date('M j', strtotime($item['StartDate'])) . '-' . date('j, Y', strtotime($item['EndDate']));
                        }
                        $details_url = "event_details.php?id=" . htmlspecialchars($id);
                        $info_line = htmlspecialchars($date_display) . ' | ' . htmlspecialchars($city);
                    } else {
                        $type_display = isset($item['Type']) ? $item['Type'] : 'Place';
                        $details_url = "event_details.php?id=" . htmlspecialchars($id) . "&type=place";
                        $info_line = htmlspecialchars($city) . ' | ' . htmlspecialchars($type_display);
                    }
                    ?>
                    <div class="card carousel-card">
                        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($name); ?>">
                        <h3><?php echo htmlspecialchars($name); ?></h3>
                        <p><?php echo $info_line; ?></p>
                        <a href="<?php echo $details_url; ?>" class="btn-small">View Details</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #fff; text-align: center; width: 100%;">No featured items are currently available.</p>
            <?php endif; ?>
        </div>
        <button class="carousel-nav prev-btn" onclick="scrollCarousel(-1)"><</button>
        <button class="carousel-nav next-btn" onclick="scrollCarousel(1)">></button>
    </section>

    <div class="yellow-box-content">
        <section class="featured-events">
            <h2>Featured Events</h2>
            <div class="cards-row">
                <?php if (count($featured_events) > 0): ?>
                    <?php foreach ($featured_events as $event):
                        $date_display = date('M j, Y', strtotime($event['StartDate']));
                        if ($event['EndDate'] && $event['StartDate'] !== $event['EndDate']) {
                            $date_display = date('M j', strtotime($event['StartDate'])) . '-' . date('j, Y', strtotime($event['EndDate']));
                        }
                    ?>
                        <div class="card">
                            <img src="<?php echo htmlspecialchars($event['ImageURL']); ?>" alt="<?php echo htmlspecialchars($event['EventName']); ?>">
                            <h3><?php echo htmlspecialchars($event['EventName']); ?></h3>
                            <p><?php echo htmlspecialchars($date_display); ?> | <?php echo htmlspecialchars($event['City']); ?></p>
                            <a href="event_details.php?id=<?php echo htmlspecialchars($event['EventID']); ?>" class="btn-small">View Details</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No featured events are currently available.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="featured-restaurants">
            <h2>Recommended Restaurants</h2>
            <div class="cards-row">
                <?php if (count($recommended_restaurants) > 0): ?>
                    <?php foreach ($recommended_restaurants as $restaurant): ?>
                        <div class="card">
                            <img src="<?php echo htmlspecialchars($restaurant['ImageURL']); ?>" alt="<?php echo htmlspecialchars($restaurant['Name']); ?>">
                            <h3><?php echo htmlspecialchars($restaurant['Name']); ?></h3>
                            <p><?php echo htmlspecialchars($restaurant['City']); ?></p>
                            <a href="event_details.php?id=<?php echo htmlspecialchars($restaurant['PlaceID']); ?>&type=place" class="btn-small">View Details</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No recommended restaurants are currently available.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="featured-hotels">
            <h2>Recommended Hotels</h2>
            <div class="cards-row">
                <?php if (count($recommended_hotels) > 0): ?>
                    <?php foreach ($recommended_hotels as $hotel): ?>
                        <div class="card">
                            <img src="<?php echo htmlspecialchars($hotel['ImageURL']); ?>" alt="<?php echo htmlspecialchars($hotel['Name']); ?>">
                            <h3><?php echo htmlspecialchars($hotel['Name']); ?></h3>
                            <p><?php echo htmlspecialchars($hotel['City']); ?></p>
                            <a href="event_details.php?id=<?php echo htmlspecialchars($hotel['PlaceID']); ?>&type=place" class="btn-small">View Details</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No recommended hotels are currently available.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<footer>
  <p>&copy; 2025 Journy. All rights reserved.</p>
</footer>
<script>
    function scrollCarousel(direction) {
        const container = document.getElementById('carousel-container');
        const scrollAmount = container.clientWidth / 4 * 4; 
        container.scrollLeft += direction * scrollAmount;
    }
</script>
</body>
</html>
<?php
mysqli_close($link);
?>
