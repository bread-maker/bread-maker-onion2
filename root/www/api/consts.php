<?php
	define("ERROR_AUTH_REQURED", array('error_code' => 10, 'error_text' => 'Auth required'));
	define("ERROR_INVALID_PASSWORD", array('error_code' => 11, 'error_text' => 'Invalid password'));
	define("ERROR_INVALID_TOKEN", array('error_code' => 12, 'error_text' => 'Invalid token'));
	define("ERROR_TOKEN_EXPIRED", array('error_code' => 13, 'error_text' => 'Token expired'));
	define("ERROR_NO_METHOD", array('error_code' => 14, 'error_text' => 'Method name required'));
	define("ERROR_INVALID_METHOD", array('error_code' => 15, 'error_text' => 'Invalid method name'));
	define("ERROR_MISSED_ARGUMENT", array('error_code' => 16, 'error_text' => 'Missed argument'));
	define("ERROR_INVALID_ARGUMENT", array('error_code' => 17, 'error_text' => 'Invalid argument'));
	define("ERROR_REMOTE_ERROR", array('error_code' => 18, 'error_text' => 'Remote error'));
	define("ERROR_INVALID_STATE", array('error_code' => 19, 'error_text' => 'Invalid baking state'));
	define("ERROR_COMMUNICATION_TIMEOUT", array('error_code' => 20, 'error_text' => 'Communication timeout'));
	define("ERROR_PROGRAM_CORRUPTED", array('error_code' => 21, 'error_text' => 'Baking program corrupted'));
	define("ERROR_INTERNAL_EXCEPTION", array('error_code' => 22, 'error_text' => 'Internal exception'));

	$remote_errors = array(
		1 => 'Power was lost during baking',
		2 => 'Emergency watchdog reset occured during baking',
		3 => 'External reset occured during baking',
		4 => 'No sync signal',
		5 => 'No response on baking program request',
		6 => 'Thermistor is not connected or broken',
		7 => 'Case is too hot to start baking',
	);
?>
