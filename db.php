<?php
mysqli_report(MYSQLI_REPORT_OFF);

$db = mysqli_connect('localhost', 'root', '', 'grocery');

if (!$db) {
    http_response_code(500);
    exit('Could not connect to the grocery database. Check the settings in db.php.');
}

mysqli_set_charset($db, 'utf8mb4');
