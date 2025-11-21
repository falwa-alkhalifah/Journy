<?php
// db_config.php
define('DB_SERVER', 'localhost'); 
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'journy_db'); 

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($link === false){
    // Don't display detailed errors in production
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please try again later.");
}

mysqli_set_charset($link, "utf8mb4");
?>