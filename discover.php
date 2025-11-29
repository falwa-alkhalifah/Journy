<?php
session_start();
require_once 'db_config.php';
require_once 'session_check.php';

// Get search and filter parameters
$search = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';
$price_filter = isset($_GET['price']) ? mysqli_real_escape_string($link, $_GET['price']) : '';
$distance_filter = isset($_GET['distance']) ? mysqli_real_escape_string($link, $_GET['distance']) : '';
$local_filter = isset($_GET['local']) ? mysqli_real_escape_string($link, $_GET['local']) : '';

// Build events query with filters
$events_query = "SELECT EventID, EventName, City, ImageURL, Price FROM events WHERE 1=1";
if (!empty($search)) {
    $events_query .= " AND (EventName LIKE '%$search%' OR City LIKE '%$search%' OR Description LIKE '%$search%')";
}

if (!empty($price_filter)) {
    switch ($price_filter) {
        case '$':
            $events_query .= " AND Price <= 150.00"; 
            break;
        case '$$':
            $events_query .= " AND Price > 150.00 AND Price <= 350.00"; 
            break;
        case '$$$':
            $events_query .= " AND Price > 350.00"; 
            break;
    }
}

// Build places query with filters
$places_query = "SELECT * FROM places WHERE 1=1";
if (!empty($search)) {
    $places_query .= " AND (Name LIKE '%$search%' OR City LIKE '%$search%')";
}

if (!empty($price_filter)) {
    $places_query .= " AND PriceRange = '$price_filter'";
}

if (!empty($distance_filter)) {
    if ($distance_filter === 'near') {
        $places_query .= " AND DistanceFromEvent <= 3.00";
    } elseif ($distance_filter === 'medium') {
        $places_query .= " AND DistanceFromEvent > 3.00 AND DistanceFromEvent <= 8.00";
    } elseif ($distance_filter === 'far') {
        $places_query .= " AND DistanceFromEvent > 8.00";
    }
}

if (!empty($local_filter) && $local_filter === 'local') {
    $places_query .= " AND LocallyOwned = 1";
}

// Execute queries
$events = mysqli_query($link, $events_query);
$restaurants = mysqli_query($link, $places_query . " AND type='restaurant'");
$hotels = mysqli_query($link, $places_query . " AND type='hotel'");

if (!$events) {
    die("Events query failed: " . mysqli_error($link));
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Discover</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">

<style>
.discover-container{
    padding:50px;
    max-width:1200px;
    margin:0 auto;
}
.section-title{
    font-family:'Playfair Display',serif;
    color:#a2e896;
    font-size:36px;
    text-align:center;
    margin-bottom:30px;
}
.lane{
    display:flex;
    gap:20px;
    overflow-x:auto;
    padding-bottom:15px;
    justify-content:center;
    flex-wrap:wrap;
}
.lane::-webkit-scrollbar{display:none;}
.card{
    text-align:center;
    min-width:250px;
    min-height: 280px;
    background: #1e2a28;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    transition: 0.3s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(162, 232, 150, 0.3);
}
.card img{
    width:100%;
    height:160px;
    object-fit:cover;
}
.card h3 {
    padding: 15px;
    font-family: 'Playfair Display', serif;
    color: #a2e896;
}
.card p {
    padding: 0 15px 15px;
    color: #bbb;
}
.details-btn{
    background: #b8860b;
    color:white;
    border:none;
    padding:8px 16px;
    border-radius:6px;
    cursor:pointer;
    margin:10px 15px 15px;
    text-decoration: none;
    display: inline-block;
    transition: 0.3s;
}
.details-btn:hover{
    background: #d4af37;
}

/* Search and Filter Styles */
.search-filter-section {
    max-width: 800px;
    margin: 0 auto 40px auto;
    padding: 20px;
    background: #1e2a28;
    border-radius: 10px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.3);
}

.search-bar {
    text-align: center;
    margin-bottom: 20px;
}

#searchInput {
    padding: 12px 20px;
    width: 90%;
    max-width: 500px;
    border-radius: 6px;
    border: 1px solid #444;
    font-size: 1rem;
    font-family: 'Poppins', sans-serif;
    background: #2b3e3c;
    color: #eee;
}

.filter-container {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    justify-content: center;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: bold;
    color: #a2e896;
    font-size: 0.9rem;
}

.filter-group select {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #444;
    background: #2b3e3c;
    font-family: 'Poppins', sans-serif;
    color: #eee;
}

.apply-filters {
    background: #b8860b;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    transition: 0.3s;
}

.apply-filters:hover {
    background: #d4af37;
}

.clear-filters {
    background: #666;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    text-decoration: none;
    transition: 0.3s;
}

.clear-filters:hover {
    background: #888;
}

.filter-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    padding-bottom: 2px;
}

.no-results {
    text-align: center;
    color: #a2e896;
    font-size: 1.1rem;
    margin: 40px 0;
    font-style: italic;
}

.local-owned {
    color: #a2e896;
    font-size: 0.9rem;
    margin: 5px 0;
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

<section class="discover-container">
    <div class="search-filter-section">
        <form method="GET" action="discover.php">
            <div class="search-bar">
                <input type="text" id="searchInput" name="search" placeholder="Search events, restaurants, hotels..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="filter-container">
                <div class="filter-group">
                    <label for="price">Price Range</label>
                    <select id="price" name="price">
                        <option value="">Any Price</option>
                        <option value="$" <?= $price_filter === '$' ? 'selected' : '' ?>>$ (Budget)</option>
                        <option value="$$" <?= $price_filter === '$$' ? 'selected' : '' ?>>$$ (Moderate)</option>
                        <option value="$$$" <?= $price_filter === '$$$' ? 'selected' : '' ?>>$$$ (Premium)</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="distance">Distance</label>
                    <select id="distance" name="distance">
                        <option value="">Any Distance</option>
                        <option value="near" <?= $distance_filter === 'near' ? 'selected' : '' ?>>Near (≤ 3km)</option>
                        <option value="medium" <?= $distance_filter === 'medium' ? 'selected' : '' ?>>Medium (3-8km)</option>
                        <option value="far" <?= $distance_filter === 'far' ? 'selected' : '' ?>>Far (> 8km)</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="local">Local Business</label>
                    <select id="local" name="local">
                        <option value="">All Businesses</option>
                        <option value="local" <?= $local_filter === 'local' ? 'selected' : '' ?>>Locally Owned Only</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="apply-filters">Search and Filter</button>
                    <a href="discover.php" class="clear-filters">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <h1 class="section-title">Events</h1>
    <div class="lane">
    <?php if(mysqli_num_rows($events) > 0): ?>
        <?php while($e = mysqli_fetch_assoc($events)): ?>
            <div class="card">
                <img src="<?= !empty($e['ImageURL']) ? $e['ImageURL'] : 'image/default_event.jpg' ?>" alt="<?= $e['EventName'] ?>">
                <h3><?= $e['EventName'] ?></h3>
                <p><?= $e['City'] ?></p>
                <?php if ($e['Price'] > 0): ?>
                    <p>Price: SAR <?= number_format($e['Price'], 2) ?></p>
                <?php endif; ?>
                <a href="event_details.php?id=<?= $e['EventID'] ?>" class="details-btn">Details</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-results">No events found matching your criteria.</p>
    <?php endif; ?>
    </div>

    <h1 class="section-title">Restaurants</h1>
    <div class="lane">
    <?php if(mysqli_num_rows($restaurants) > 0): ?>
        <?php while($r = mysqli_fetch_assoc($restaurants)): ?>
            <div class="card">
                <img src="<?= !empty($r['ImageURL']) ? $r['ImageURL'] : 'image/default_restaurant.jpg' ?>" alt="<?= $r['Name'] ?>">
                <h3><?= $r['Name'] ?></h3>
                <p><?= $r['City'] ?></p>
                <p>Price: <?= str_repeat('$', strlen($r['PriceRange'])) ?> | Distance: <?= $r['DistanceFromEvent'] ?>km</p>
                <?php if($r['LocallyOwned']): ?><p class="local-owned">🏠 Locally Owned</p><?php endif; ?>
                <a href="event_details.php?id=<?= $r['PlaceID'] ?>&type=place" class="details-btn">Details</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-results">No restaurants found matching your criteria.</p>
    <?php endif; ?>
    </div>

    <h1 class="section-title">Hotels</h1>
    <div class="lane">
    <?php if(mysqli_num_rows($hotels) > 0): ?>
        <?php while($h = mysqli_fetch_assoc($hotels)): ?>
            <div class="card">
                <img src="<?= !empty($h['ImageURL']) ? $h['ImageURL'] : 'image/default_hotel.jpg' ?>" alt="<?= $h['Name'] ?>">
                <h3><?= $h['Name'] ?></h3>
                <p><?= $h['City'] ?></p>
                <p>Price: <?= str_repeat('$', strlen($h['PriceRange'])) ?> | Distance: <?= $h['DistanceFromEvent'] ?>km</p>
                <?php if($h['LocallyOwned']): ?><p class="local-owned">🏠 Locally Owned</p><?php endif; ?>
                <a href="event_details.php?id=<?= $h['PlaceID'] ?>&type=place" class="details-btn">Details</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-results">No hotels found matching your criteria.</p>
    <?php endif; ?>
    </div>

</section>

<footer>
  <p>&copy; 2025 Journy. All rights reserved.</p>
</footer>
</body>
</html>
<?php
mysqli_close($link);
?>
