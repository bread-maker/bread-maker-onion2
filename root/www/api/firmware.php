<?php
	require_once('config.php');
	require_once('consts.php');
	require_once('misc.php');
	require_once('Gpio.php');

	function flash()
	{
		global $result;
		if (!isset($_FILES['firmware']))
			error(ERROR_MISSED_ARGUMENT, 'firmware');

		if (isset($_FILES['firmware']['error']) && is_array($_FILES['firmware']['error']))
		{
			$result['error'] = $_FILES['firmware']['error'];
			$result['result'] = false;
			return;
		}

		$gpio = new PhpGpio\Gpio();
		shell_exec("/etc/init.d/breadmaker stop");
		foreach(array(PIN_RESET, PIN_SCK, PIN_MOSI, PIN_MISO) as $pin)
			$gpio->unexport($pin);
		//$result['output'] = shell_exec("avrdude -p m16 -c linuxgpio -U flash:w:\"" . $_FILES['firmware']['tmp_name'] . "\"  -U lfuse:w:0x$lfuse:m -U hfuse:w:0x$hfuse:m -q 2>&1");
		$flashcmd = "-U flash:w:\"{$_FILES['firmware']['tmp_name']}\"";
		if (isset($_REQUEST['LFUSE'])) $flashcmd .= " -U lfuse:w:0x{$_REQUEST['LFUSE']}:m";
		if (isset($_REQUEST['HFUSE'])) $flashcmd .= " -U hfuse:w:0x{$_REQUEST['HFUSE']}:m";
		$output = shell_exec("avrdude -p m16 -c linuxgpio $flashcmd -q 2>&1 ; echo ~~~ $?");
		shell_exec("/etc/init.d/breadmaker start");
		$code = strstr($output, '~~~');
		$code = (int)trim(substr($code, 4));
		$result['result'] = $code == 0;
		$result['return_code'] = $code;
		$result['output'] = $output;
		if (isset($_REQUEST['raw'])) die($output);
	}
?>
