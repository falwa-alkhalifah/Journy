<?php
// NOTE: Default MAMP Configuration
define('DB_SERVER', 'localhost'); 
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'journy_db'); 

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if($link === false){
    die("ERROR: Could not connect to the database: " . mysqli_connect_error());
}

// Setting charset to utf8mb4 for compatibility
mysqli_set_charset($link, "utf8mb4");

?>