<?php
include 'db_config.php';

$place = null;

// 1. التحقق من وجود Place ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$place_id = $_GET['id'];

// 2. جلب تفاصيل المكان (المطعم/الفندق) من جدول Places
$sql_place = "SELECT * FROM Places WHERE PlaceID = ?";
if ($stmt = mysqli_prepare($link, $sql_place)) {
    mysqli_stmt_bind_param($stmt, "i", $place_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $place = mysqli_fetch_assoc($result);
        } else {
            // إذا لم يتم العثور على المكان
            header("Location: index.php");
            exit();
        }
    }
    mysqli_stmt_close($stmt);
}

// تحديد العنوان بناءً على النوع
$title_type = ($place['Type'] === 'Hotel') ? 'Hotel Details' : 'Restaurant Details';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Journy - <?php echo htmlspecialchars($title_type); ?></title>
<link rel="stylesheet" href="style.css">

<style>
/* تنسيق بسيط لعرض تفاصيل المكان */
.place-container { padding: 60px 100px; max-width: 1400px; margin: auto; display: flex; flex-wrap: wrap; gap: 40px; align-items: flex-start; justify-content: center; }
.place-image-area { flex: 1; min-width: 500px; max-width: 600px; }
.place-details-card { flex: 1; min-width: 500px; max-width: 600px; padding: 40px; }
.place-image-area img { border-radius: 10px; height: 380px; width: 100%; object-fit: cover; }
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
  <section class="place-container">
    
    <div class="place-image-area">
        <img src="<?php echo htmlspecialchars($place['ImageURL'] ?? 'image/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($place['Name']); ?>">
    </div>

    <div class="card place-details-card">
      <h3 style="color:#ff6b00;font-size:34px;font-family:'Playfair Display',serif;margin-bottom:25px;"><?php echo htmlspecialchars($place['Name']); ?></h3>
      
      <p style="font-size:18px;margin-bottom:10px;"><strong>Type:</strong> <?php echo htmlspecialchars($place['Type']); ?></p>
      <p style="font-size:18px;margin-bottom:10px;"><strong>City:</strong> <?php echo htmlspecialchars($place['City']); ?></p>
      <p style="font-size:18px;margin-bottom:30px;"><strong>Rating:</strong> <?php echo htmlspecialchars($place['Rating']); ?>/5</p>
      
      <p style="font-size:18px;margin-bottom:20px;text-align:justify;">
        This highly-rated <?php echo strtolower(htmlspecialchars($place['Type'])); ?> offers a unique experience in the heart of <?php echo htmlspecialchars($place['City']); ?>. Known for its excellent service.
      </p>

      <a href="planner.html" class="btn-small" style="background-color: #3f51b5;">Add to Planner</a>
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