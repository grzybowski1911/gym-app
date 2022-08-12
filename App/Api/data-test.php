<?php 

use PDO;
use App\Config;

$dsn = 'mysql:host=' . Config::DB_HOST . ';dbname=' . Config::DB_NAME . ';charset=utf8';
$db = new PDO($dsn, Config::DB_USER, Config::DB_PASSWORD);

// Throw an Exception when an error occurs
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = 'SELECT date FROM lift_sessions';
//$db = static::getDB();
$stmt = $db->prepare($sql);
$stmt->execute();
$dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
//error_log(print_r($dates, true));
$new_dates = [];
$regex = '/[0-9]{4}-[0-9]{2}-[0-9]{2}/i';
foreach($dates as $date) {
    $clean = preg_match($regex, $date['date'], $m);
    array_push($new_dates, $m[0]);
}
return json_endcode(array_unique($new_dates));