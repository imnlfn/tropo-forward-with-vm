<?php
// KLogger is a logging class from 
// https://github.com/katzgrau/KLogger
$log = new Katzgrau\KLogger\Logger(__DIR__ . '/logs');

// This is an incoming voicemail message.

// The location where files are saved.
$target_path = 'msgs/' . $_FILES['filename']['name'];

if (move_uploaded_file($_FILES['filename']['tmp_name'], $target_path)) {
    $log->info("$target_path [{$_FILES['filename']['size']} bytes] was saved");
}
else {
    $log->error("$target_path could not be saved.");
}
?>