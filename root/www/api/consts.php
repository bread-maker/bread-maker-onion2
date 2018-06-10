<?php
	define("ERROR_AUTH_REQURED", array('error_code' => 10, 'error_text' => 'auth required', 'http_response_code' => 401));
	define("ERROR_INVALID_PASSWORD", array('error_code' => 11, 'error_text' => 'invalid password', 'http_response_code' => 401));
	define("ERROR_INVALID_TOKEN", array('error_code' => 12, 'error_text' => 'invalid token', 'http_response_code' => 401));
	define("ERROR_TOKEN_EXPIRED", array('error_code' => 13, 'error_text' => 'token expired', 'http_response_code' => 401));
	define("ERROR_NO_METHOD", array('error_code' => 14, 'error_text' => 'method name required', 'http_response_code' => 400));
	define("ERROR_INVALID_METHOD", array('error_code' => 15, 'error_text' => 'invalid method name', 'http_response_code' => 400));
	define("ERROR_MISSED_ARGUMENT", array('error_code' => 16, 'error_text' => 'missed argument', 'http_response_code' => 400));
	define("ERROR_INVALID_ARGUMENT", array('error_code' => 17, 'error_text' => 'invalid argument', 'http_response_code' => 400));
	define("ERROR_REMOTE_ERROR", array('error_code' => 18, 'error_text' => 'core exception', 'http_response_code' => 500));
	define("ERROR_INVALID_STATE", array('error_code' => 19, 'error_text' => 'invalid baking state', 'http_response_code' => 406));
	define("ERROR_COMMUNICATION_TIMEOUT", array('error_code' => 20, 'error_text' => 'communication timeout', 'http_response_code' => 500));
	define("ERROR_PROGRAM_CORRUPTED", array('error_code' => 21, 'error_text' => 'baking program corrupted', 'http_response_code' => 500));
	define("ERROR_INTERNAL_EXCEPTION", array('error_code' => 22, 'error_text' => 'internal exception', 'http_response_code' => 500));
	define("ERROR_TIMEOUT", array('error_code' => 23, 'error_text' => 'timeout', 'http_response_code' => 500));

	define("REMOTE_ERRORS", array(
		1 => 'power was lost during baking',
		2 => 'emergency watchdog reset occured during baking',
		3 => 'external reset occured during baking',
		4 => 'no sync signal',
		5 => 'no response on baking program request',
		6 => 'thermistor is not connected or broken',
		7 => 'case is too hot to start baking',
	));
?>
