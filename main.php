<?php
require_once('tropo.class.php');
require_once('vendor/autoload.php');

// KLogger is a logging class from 
// https://github.com/katzgrau/KLogger
$log = new Katzgrau\KLogger\Logger(__DIR__ . '/logs');

// If the query string is "record", this is an incoming voicemail message.
if (array_key_exists('record', $_GET)) {
    // The location where files are saved.
    $target_path = 'msgs/' . $_FILES['filename']['name'];

    if (move_uploaded_file($_FILES['filename']['tmp_name'], $target_path)) {
        $log->info("$target_path [{$_FILES['filename']['size']} bytes] was saved");
    }
    else {
        $log->error("$target_path could not be saved.");
    }
}
// If the query string is "incomplete", then the user listened to the end of the OGM.
elseif (array_key_exists('incomplete', $_GET)) {
    $tropo = new Tropo();

    // record voicemail
    $tropo->record(array(
        'name' => 'recording',
        'url' => getself() . '?record', // append ?record to the URL
        'terminator' => '#',
        'timeout' => 10,
        'maxSilence' => 7,
        'maxTime' => 240,
        'format' => 'audio/mp3'
    ));

    $tropo->RenderJson();
}
// If the query string is "continue", then the user typed in a value.
elseif (array_key_exists('continue', $_GET)) {
    $tropo = new Tropo();

    @$result = new Result();

    $answer = $result->getValue();

    $tropo->say("You entered ");

    // repeat entry as digits instead of a number, and slowed down
    $tropo->say("<?xml version='1.0'?><speak><prosody rate='-20%'><say-as interpret-as='vxml:digits'>$answer</say-as></prosody></speak>");

    $tropo->RenderJson();
}
// If there was no query string, then this is the initial incoming call.
else {
    $tropo = new Tropo();

    // play prompt
    $prompt = 'Thank you for calling. If someone is available to take your call, you will ' .
              'be connected after this message. Otherwise, please leave a detailed message ' .
              'after the beep and someone will return your call as soon as possible.';

    $options = array(
        'choices' => '[4 DIGITS]',
        'mode' => 'dtmf',
        'required' => false,
        'timeout' => 1
    );

    $tropo->ask($prompt, $options);

    // set up events for handling input and recording message
    $tropo->on(array('event' => 'continue', 'next' => getself() . '?continue'));
    $tropo->on(array('event' => 'incomplete', 'next' => getself() . '?incomplete'));

    $tropo->RenderJson();
}

// Simple function to get the full URL of the current script.
function getself() {
    $pageURL = 'http';
    
    $url = ($_SERVER["HTTPS"] == "on") ? 'https' : 'http';
    $url .= "://" . $_SERVER["SERVER_NAME"];
    $url .= ($_SERVER["SERVER_PORT"] != "80") ? ':'. $_SERVER["SERVER_PORT"] : '';
    $url .= $_SERVER["PHP_SELF"];

    return $url;
}
?>