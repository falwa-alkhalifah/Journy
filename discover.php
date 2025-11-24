<?php
session_start();
require_once 'db_config.php';
require_once 'session_check.php';

// Get search and filter parameters
$link = $GLOBALS['link']; 
$search = isset($_GET['search']) ? $link->real_escape_string($_GET['search']) : '';
$price_filter = isset($_GET['price']) ? $link->real_escape_string($_GET['price']) : '';
$distance_filter = isset($_GET['distance']) ? $link->real_escape_string($_GET['distance']) : '';
$local_filter = isset($_GET['local']) ? $link->real_escape_string($_GET['local']) : '';

// Build events query with filters
$events_query = "SELECT EventID, EventName, City, ImageURL, Price, LocallyOwned FROM events WHERE 1=1"; // التأكد من جلب عمود LocallyOwned و Price
// تطبيق فلتر البحث النصي على Events
if (!empty($search)) {
    $events_query .= " AND (EventName LIKE '%$search%' OR City LIKE '%$search%' OR Description LIKE '%$search%')";
}

// تطبيق فلتر نطاق السعر على سعر تذكرة EventID
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

// تطبيق فلتر الأعمال المحلية على Events
if (!empty($local_filter) && $local_filter === 'local') {
    $events_query .= " AND LocallyOwned = 1";
}


// Build places query with filters
$places_query = "SELECT * FROM places WHERE 1=1";
if (!empty($search)) {
    $places_query .= " AND (Name LIKE '%$search%' OR City LIKE '%$search%')";
}

// 1. تصفية نطاق السعر (PriceRange)
if (!empty($price_filter)) {
    $places_query .= " AND PriceRange = '$price_filter'";
}

// 2. تصفية المسافة (DistanceFromEvent)
if (!empty($distance_filter)) {
    if ($distance_filter === 'near') {
        $places_query .= " AND DistanceFromEvent <= 3.00";
    } elseif ($distance_filter === 'medium') {
        $places_query .= " AND DistanceFromEvent > 3.00 AND DistanceFromEvent <= 8.00";
    } elseif ($distance_filter === 'far') {
        $places_query .= " AND DistanceFromEvent > 8.00";
    }
}

// 3. تصفية الأعمال المحلية (LocallyOwned)
if (!empty($local_filter) && $local_filter === 'local') {
    $places_query .= " AND LocallyOwned = 1";
}

// Execute queries
$events = $link->query($events_query);
$restaurants = $link->query($places_query . " AND type='restaurant'");
$hotels = $link->query($places_query . " AND type='hotel'");
?>
<!DOCTYPE html>
<html>
<head>
<title>Discover</title>
<link rel="stylesheet" href="style.css">

<style>
.discover-container{
    padding:50px;
    max-width:1200px;
    margin:0 auto;
}
.section-title{
    font-family:'Playfair Display',serif;
    color:var(--green-dark);
    font-size:32px;
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
}
.card img{
    width:100%;
    height:160px;
    object-fit:cover;
    border-radius:8px;
}
.details-btn{
    background: #ff6b00; 
    color:white;
    border:none;
    padding:8px 16px;
    border-radius:6px;
    cursor:pointer;
    margin-top:10px;
    text-decoration: none; 
    display: inline-block; 
}
.details-btn:hover{
    background: #e65c00;
}

/* Search and Filter Styles */
.search-filter-section {
    max-width: 800px;
    margin: 0 auto 40px auto;
    padding: 20px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.1);
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
    border: 1px solid #ddd;
    font-size: 1rem;
    font-family: 'Poppins', sans-serif;
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
    color: var(--green-dark);
    font-size: 0.9rem;
}

.filter-group select {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #ddd;
    background: white;
    font-family: 'Poppins', sans-serif;
}

.apply-filters {
    background: #ff6b00;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
}

.apply-filters:hover {
    background: #e65c00;
}

.clear-filters {
    background: #888;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    text-decoration: none;
}

.clear-filters:hover {
    opacity: 0.9;
}

.filter-actions {
    display: flex;
    gap: 10px;
    align-items: center; 
    padding-bottom: 2px; 
}

.no-results {
    text-align: center;
    color: var(--green-dark);
    font-size: 1.1rem;
    margin: 40px 0;
    font-style: italic;
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
    <?php if($events->num_rows > 0): ?>
        <?php while($e=$events->fetch_assoc()): ?>
            <div class="card">
                <img src="<?= !empty($e['ImageURL']) ? $e['ImageURL'] : 'image/default_event.jpg' ?>" alt="<?= $e['EventName'] ?>">
                <h3><?= $e['EventName'] ?></h3>
                <p><?= $e['City'] ?></p>
                <?php if ($e['Price'] > 0): ?>
                    <p>Price: SAR <?= number_format($e['Price'], 2) ?></p>
                <?php endif; ?>
                <?php if($e['LocallyOwned']): ?>
                    <p style="color: var(--green-mid);">🏠 Locally Owned</p>
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
    <?php if($restaurants->num_rows > 0): ?>
        <?php while($r=$restaurants->fetch_assoc()): ?>
            <div class="card">
                <img src="<?= !empty($r['ImageURL']) ? $r['ImageURL'] : 'image/default_restaurant.jpg' ?>" alt="<?= $r['Name'] ?>">
                <h3><?= $r['Name'] ?></h3>
                <p><?= $r['City'] ?></p>
                <p>Price: <?= str_repeat('$', strlen($r['PriceRange'])) ?> | Distance: <?= $r['DistanceFromEvent'] ?>km</p>
                <?php if($r['LocallyOwned']): ?><p style="color: var(--green-mid);">🏠 Locally Owned</p><?php endif; ?>
                <a href="event_details.php?id=<?= $r['PlaceID'] ?>&type=place" class="details-btn">Details</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-results">No restaurants found matching your criteria.</p>
    <?php endif; ?>
    </div>

    <h1 class="section-title">Hotels</h1>
    <div class="lane">
    <?php if($hotels->num_rows > 0): ?>
        <?php while($h=$hotels->fetch_assoc()): ?>
            <div class="card">
                <img src="<?= !empty($h['ImageURL']) ? $h['ImageURL'] : 'image/default_hotel.jpg' ?>" alt="<?= $h['Name'] ?>">
                <h3><?= $h['Name'] ?></h3>
                <p><?= $h['City'] ?></p>
                <p>Price: <?= str_repeat('$', strlen($h['PriceRange'])) ?> | Distance: <?= $h['DistanceFromEvent'] ?>km</p>
                <?php if($h['LocallyOwned']): ?><p style="color: var(--green-mid);">🏠 Locally Owned</p><?php endif; ?>
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
