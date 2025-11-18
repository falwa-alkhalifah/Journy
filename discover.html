<?php
require 'connect.php';

// Fetch events
$events = $conn->query("SELECT * FROM events");

// Fetch places grouped
$restaurants = $conn->query("SELECT * FROM places WHERE type='restaurant'");
$hotels = $conn->query("SELECT * FROM places WHERE type='hotel'");
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
</style>
</head>

<body>
<?php include 'header.php'; ?>

<section class="discover-container">

<h1 class="section-title">Events</h1>
<div class="lane">
<?php while($e=$events->fetch_assoc()): ?>
    <div class="card">
        <img src="<?= !empty($e['ImageURL']) ? $e['ImageURL'] : 'image/default_event.jpg' ?>" alt="<?= $e['EventName'] ?>">
        <h3><?= $e['EventName'] ?></h3>
        <p><?= $e['City'] ?></p>
        <a href="event_details.php?id=<?= $e['EventID'] ?>">
            <button class="details-btn">Details</button>
        </a>
    </div>
<?php endwhile; ?>
</div>

<h1 class="section-title">Restaurants</h1>
<div class="lane">
<?php while($r=$restaurants->fetch_assoc()): ?>
    <div class="card">
        <img src="<?= !empty($r['ImageURL']) ? $r['ImageURL'] : 'image/default_restaurant.jpg' ?>" alt="<?= $r['Name'] ?>">
        <h3><?= $r['Name'] ?></h3>
        <p><?= $r['City'] ?></p>
        <a href="event_details.php?id=<?= $r['PlaceID'] ?>&type=place">
            <button class="details-btn">Details</button>
        </a>
    </div>
<?php endwhile; ?>
</div>

<h1 class="section-title">Hotels</h1>
<div class="lane">
<?php while($h=$hotels->fetch_assoc()): ?>
    <div class="card">
        <img src="<?= !empty($h['ImageURL']) ? $h['ImageURL'] : 'image/default_hotel.jpg' ?>" alt="<?= $h['Name'] ?>">
        <h3><?= $h['Name'] ?></h3>
        <p><?= $h['City'] ?></p>
        <a href="event_details.php?id=<?= $h['PlaceID'] ?>&type=place">
            <button class="details-btn">Details</button>
        </a>
    </div>
<?php endwhile; ?>
</div>

</section>

<footer>
  <p>&copy; 2025 Journy. All rights reserved.</p>
</footer>
</body>
</html>
