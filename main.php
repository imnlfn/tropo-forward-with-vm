<?php
require_once('tropo.class.php');
require_once('vendor/autoload.php');
require_once('config.php');

// open database connection
$mysqli = new mysqli($db_server, $db_user, $db_pass, $db_name);

try {
    // If there is not a session object in the POST body,
    // then this isn't a new session. Tropo will throw
    // an exception, so check for that.
    $session = new Session();
}
catch (TropoException $e) {
    // This is a normal case, so we don't really need to
    // do anything if we catch this.
}

/*
// get source and destination numbers from Session
$caller = $session->getFrom();
$call_src = $caller['id'];

$called = $session->getTo();
$call_dst = $called['id'];
*/

// create Tropo object
$tropo = new Tropo();

// If the query string is "pin", then the user typed in a PIN attempt.
if (array_key_exists('pin', $_GET)) {
    // get PIN from database for the number called
    $res = $mysqli->query("SELECT pin FROM config WHERE phone_num = $called");
    $row = $res->fetch_assoc();
    $pin = $row['pin'];

    // get value that user entered
    @$result = new Result();
    $answer = $result->getValue();

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
    @$result = new Result();

    $answer = $result->getValue();

    if ($answer == '1') {
        $prompt = 'Voice mail. Press 1 to listen to new messages. Press 2 to listen ' .
                  'to all messages. You may hang up to end the call at any time.';

        $tropo->say($prompt);
    }
    elseif ($answer == '2') {
        $prompt = 'Call forwarding enabled.';

        $tropo->say($prompt);
    }
    elseif ($answer == '3') {
        $prompt = 'Call forwarding disabled.';

        $tropo->say($prompt);
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
// If there was no query string, then this is the initial incoming call.
else {
    // play the outgoing message
    playOGM($tropo);
}

$tropo->RenderJson();

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
?>