<?php

require 'tropo.class.php';

// get POST content sent to page
$json = file_get_contents("php://input");

// if POST content is valid JSON, convert it to an array
if (strlen($json) > 0 && isValidJSON($json))
    $params = json_decode($json, true);

$initialText = $params["result"]["initialText"];

$tropo = new Tropo(); 

if($initialText == "Yes")
	$tropo->say("Awesome, I totally agree!");

elseif($initialText == "No")
	$tropo->say("Well that's just too bad.");

else 
	$tropo->say("That wasn't an option, sorry.");

return $tropo->RenderJson(); 
?>