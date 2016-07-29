<?php
require_once('tropo.class.php');
require_once('vendor/autoload.php');
require_once('config.php');

// get POST content sent to page
$json = file_get_contents("php://input");

// if POST content is valid JSON, convert it to an array
if (strlen($json) > 0 && isValidJSON($json))
    $params = json_decode($json, true);

// open database connection
$mysqli = new mysqli($db_server, $db_user, $db_pass, $db_name);

// create Tropo object
$tropo = new Tropo();

// If the query string is "pin", then the user typed in a PIN attempt.
if (array_key_exists('pin', $_GET)) {
    // for debugging, set $debug_on and $called in CONFIG.PHP
    if (!$debug_on) {
        // POST content will be a Result
        $called = $params["result"]["calledid"];
    }

    // get PIN from database for the number called
    $res = $mysqli->query("SELECT pin FROM config WHERE phone_num = $called");
    $row = $res->fetch_assoc();
    $pin = $row["pin"];

    // for debugging, set $answer in CONFIG.PHP
    if (!$debug_on) {
        // get caller's input
        $answer = $params["result"]["actions"]["value"];
    }

    if ($answer != $pin) {
        $tropo->say('The PIN you entered ');

        // repeat entry as digits instead of a number, and slowed down
        $tropo->say("<?xml version='1.0'?><speak><prosody rate='-20%'><say-as interpret-as='vxml:digits'>$answer</say-as></prosody></speak>");

        $tropo->say(' is not correct.');

        // wait two seconds
        $tropo->wait(array(
            'milliseconds' => 2000
        ));

        // play the outgoing message again
        playOGM($tropo);
    }
    else {
        // play the main menu
        playMenu($tropo);
    }
}
// If the query string is "menu", then the user selected a menu option.
elseif (array_key_exists('menu', $_GET)) {
    // for debugging, set $debug_on and $choice in CONFIG.PHP
    if (!$debug_on) {
        // get caller's input
        $choice = $params["result"]["actions"]["value"];
    }

    if ($choice == '1') {
        $prompt = 'Voice mail. Press 1 to listen to new messages. Press 2 to listen ' .
                  'to all messages. You may hang up to end the call at any time.';

        $tropo->say($prompt);
    }
    elseif ($choice == '2') {
        // for debugging, set $debug_on and $session in CONFIG.PHP
        if (!$debug_on) {
            // get Session ID from Result object
            $session = $params["result"]["sessionId"];
        }

        // get call information for this session
        $res = $mysqli->query("SELECT caller, called FROM sessions WHERE id = '$session'");
        $row = $res->fetch_assoc();
        $caller = $row["caller"];
        $called = $row["called"];

        // set call forwarding to this number
        $mysqli->query("UPDATE config SET is_forwarded = 1, forward_num = $caller, last_fwd = NOW() WHERE phone_num = $called");

        $tropo->say('Call forwarding enabled for ');

        // repeat entry as digits instead of a number, and slowed down
        $tropo->say("<?xml version='1.0'?><speak><prosody rate='-20%'><say-as interpret-as='vxml:digits'>$caller</say-as></prosody></speak>");
    }
    elseif ($choice == '3') {
        // for debugging, set $debug_on and $session in CONFIG.PHP
        if (!$debug_on) {
            // get Session ID from Result object
            $session = $params["result"]["sessionId"];
        }

        // get call information for this session
        $res = $mysqli->query("SELECT caller FROM sessions WHERE id = '$session'");
        $row = $res->fetch_assoc();
        $caller = $row["caller"];

        // disable call forwarding to this number
        $mysqli->query("UPDATE config SET is_forwarded = 0 WHERE phone_num = $called");

        $tropo->say('Call forwarding disabled for ');

        // repeat entry as digits instead of a number, and slowed down
        $tropo->say("<?xml version='1.0'?><speak><prosody rate='-20%'><say-as interpret-as='vxml:digits'>$caller</say-as></prosody></speak>");
    }
    else {
        $tropo->say('That is not a valid option.');

        // wait two seconds
        $tropo->wait(array(
            'milliseconds' => 2000
        ));

        // play the main menu again
        playMenu($tropo);
    }
}
// If the query string is "noresponse", then the user listened to the end of the OGM.
elseif (array_key_exists('noresponse', $_GET)) {
    // for debugging, set $debug_on and $called in CONFIG.PHP
    if (!$debug_on) {
        // get the called number from the Result object
        $called = $params["result"]["calledid"];
    }

    // find out if call forwarding is enabled for this number
    $res = $mysqli->query("SELECT is_forwarded, forward_num FROM config WHERE phone_num = $called");
    $row = $res->fetch_assoc();
    $is_forwarded = $row["is_forwarded"];

    if ($is_forwarded == 1) {
        $caller = $row["forward_num"];

        // the transfer method is broken and must have an array as a second parameter,
        // even if empty, so make sure to leave something here
        $options = array(
            'from' => "$called" // no need for a "+1", since it will get stripped anyway,
                                // but this value does need to be a string 
        );

        $tropo->say("Transferring you now, please wait");
        $tropo->transfer('+1' . $caller, $options);
    }
    else {
        // record voicemail
        $tropo->record(array(
            'name' => 'recording',
            'url' => gethost() . '/record.php', // send to RECORD.PHP
            'terminator' => '#',
            'timeout' => 10,
            'maxSilence' => 7,
            'maxTime' => 240,
            'format' => 'audio/mp3'
        ));
    }
}
// If there was no query string, then this is the initial incoming call.
else {
    // for debugging, set $debug_on in CONFIG.PHP
    if (!$debug_on) {
        // POST content will be a Session
        $id = $params["session"]["id"];
        $call_time = $params["session"]["timestamp"];
        $called = $params["session"]["to"]["id"];
        $caller = $params["session"]["from"]["id"];

        // save Session information to the DB
        $mysqli->query("INSERT INTO sessions (id, call_time, called, caller) VALUES ('$id', '$call_time', $called, $caller)");
    }

    // play the outgoing message
    playOGM($tropo);
}

$tropo->renderJson();

// close database connection
$mysqli->close();

function playOGM(&$tropo) {
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
    $tropo->on(array('event' => 'continue', 'next' => getself() . '?pin'));
    $tropo->on(array('event' => 'incomplete', 'next' => getself() . '?noresponse'));
}

function playMenu(&$tropo) {
    // play menu
    $prompt = 'Main menu. Press 1 to access voice mail. Press 2 to begin forwarding calls. ' .
              'Press 3 to stop forwarding calls. To end the call, please hang up.';

    $options = array(
        'choices' => '[1 DIGIT]',
        'mode' => 'dtmf'
    );

    $tropo->ask($prompt, $options);

    // set up events for handling input and recording message
    $tropo->on(array('event' => 'continue', 'next' => getself() . '?menu'));
}

// Simple function to get the full URL of the current script.
function getself() {
    $url = gethost();
    $url .= $_SERVER["PHP_SELF"];

    return $url;
}

function gethost() {
    $url = ($_SERVER["HTTPS"] == "on") ? 'https' : 'http';
    $url .= "://" . $_SERVER["SERVER_NAME"];
    $url .= ($_SERVER["SERVER_PORT"] != "80") ? ':'. $_SERVER["SERVER_PORT"] : '';

    return $url;
}

// Function to validate JSON.
function isValidJSON($str) {
    json_decode($str);

    return json_last_error() == JSON_ERROR_NONE;
}
?>