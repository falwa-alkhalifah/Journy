<?php
include 'db_config.php';
function getFeaturedEvents() {
    global $link;
    $sql = "SELECT EventID, EventName, City, StartDate, EndDate, ImageURL FROM Events ORDER BY StartDate DESC LIMIT 4";
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
    $sql = "SELECT PlaceID, Name, City, ImageURL FROM Places WHERE Type = 'Restaurant' AND EventID IS NULL ORDER BY Rating DESC LIMIT 4";
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
    $sql = "SELECT PlaceID, Name, City, ImageURL FROM Places WHERE Type = 'Hotel' AND EventID IS NULL ORDER BY Rating DESC LIMIT 4";
    $result = mysqli_query($link, $sql);
    $hotels = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $hotels[] = $row;
        }
    }
    return $hotels;
}

$featured_events = getFeaturedEvents();
$recommended_restaurants = getRecommendedRestaurants();
$recommended_hotels = getRecommendedHotels();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Journy - Home</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">

<style>
.hero {
  background-image: url('image/header2.jpg'); 
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  padding: 150px 20px; 
}

.hero h1, .hero p {
  color: #fff; 
  text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.7); 
}

main {
    text-align: center;
}

.cards-row {
    display: flex;
    justify-content: center; 
    gap: 20px;
    padding: 20px 0;
}

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
      <li><a href="login.html">Log in</a></li>
    </ul>
  </nav>

  <div class="hero">
    <h1>Discover Amazing Events Across Saudi Arabia</h1>
    <p>Plan your trips, book tickets, and explore local experiences effortlessly</p>
    <a href="discover.html" class="btn">Explore Events</a>
  </div>
</header>

<main>
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
                  <a href="event-details.php?id=<?php echo htmlspecialchars($event['EventID']); ?>" class="btn-small">View Details</a>
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
                  <a href="place-details.php?id=<?php echo htmlspecialchars($restaurant['PlaceID']); ?>" class="btn-small">View Details</a>
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
                  <a href="place-details.php?id=<?php echo htmlspecialchars($hotel['PlaceID']); ?>" class="btn-small">View Details</a>
              </div>
          <?php endforeach; ?>
      <?php else: ?>
          <p>No recommended hotels are currently available.</p>
      <?php endif; ?>
    </div>
  </section>
</main>

<footer>
  <p>&copy; 2025 Journy. All rights reserved.</p>
</footer>
</body>
</html>
<?php
mysqli_close($link);
?>
