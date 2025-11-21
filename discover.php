<?php
require 'db_config.php';

// Get search and filter parameters
$search = isset($_GET['search']) ? $link->real_escape_string($_GET['search']) : '';
$price_filter = isset($_GET['price']) ? $_GET['price'] : '';
$distance_filter = isset($_GET['distance']) ? $_GET['distance'] : '';
$local_filter = isset($_GET['local']) ? $_GET['local'] : '';

// Build events query with filters
$events_query = "SELECT * FROM events WHERE 1=1";
if (!empty($search)) {
    $events_query .= " AND (EventName LIKE '%$search%' OR City LIKE '%$search%' OR Description LIKE '%$search%')";
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
if (!empty($local_filter)) {
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
}
.card img{
    width:100%;
    height:160px;
    object-fit:cover;
    border-radius:8px;
}
.details-btn{
    background:var(--green-mid);
    color:white;
    border:none;
    padding:8px 16px;
    border-radius:6px;
    cursor:pointer;
    margin-top:10px;
}
.details-btn:hover{
    background:var(--green-dark);
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
    background: var(--green-mid);
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    align-self: flex-end;
}

.apply-filters:hover {
    background: var(--green-dark);
}

.clear-filters {
    background: var(--brown-subtle);
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
    align-self: flex-end;
    text-decoration: none;
}

.clear-filters:hover {
    opacity: 0.9;
}

.filter-actions {
    display: flex;
    gap: 10px;
    align-self: flex-end;
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
      <li><a href="login.php">Log in</a></li>
    </ul>
  </nav>
</header>

<section class="discover-container">
    <!-- Search and Filter Section -->
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
                    <button type="submit" class="apply-filters">Apply Filters</button>
                    <a href="discover.php" class="clear-filters">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Events Section -->
    <h1 class="section-title">Events</h1>
    <div class="lane">
    <?php if($events->num_rows > 0): ?>
        <?php while($e=$events->fetch_assoc()): ?>
            <div class="card">
                <img src="<?= !empty($e['ImageURL']) ? $e['ImageURL'] : 'image/default_event.jpg' ?>" alt="<?= $e['EventName'] ?>">
                <h3><?= $e['EventName'] ?></h3>
                <p><?= $e['City'] ?></p>
                <a href="event-details.php?id=<?= $e['EventID'] ?>">
                    <button class="details-btn">Details</button>
                </a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-results">No events found matching your criteria.</p>
    <?php endif; ?>
    </div>

    <!-- Restaurants Section -->
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
                <a href="event-details.php?id=<?= $r['PlaceID'] ?>&type=place">
                    <button class="details-btn">Details</button>
                </a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-results">No restaurants found matching your criteria.</p>
    <?php endif; ?>
    </div>

    <!-- Hotels Section -->
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
                <a href="event-details.php?id=<?= $h['PlaceID'] ?>&type=place">
                    <button class="details-btn">Details</button>
                </a>
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
