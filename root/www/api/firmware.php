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
		if (!EMULATION)
		{
			shell_exec("/etc/init.d/breadmaker stop");
			$gpio = new PhpGpio\Gpio();
			foreach(array(PIN_RESET, PIN_SCK, PIN_MOSI, PIN_MISO) as $pin)
				$gpio->unexport($pin);
			//$result['output'] = shell_exec("avrdude -p m16 -c linuxgpio -U flash:w:\"" . $_FILES['firmware']['tmp_name'] . "\"  -U lfuse:w:0x$lfuse:m -U hfuse:w:0x$hfuse:m -q 2>&1");
			$flashcmd = "-U flash:w:\"{$_FILES['firmware']['tmp_name']}\"";
			if (isset($_REQUEST['LFUSE'])) $flashcmd .= " -U lfuse:w:0x{$_REQUEST['LFUSE']}:m";
			if (isset($_REQUEST['HFUSE'])) $flashcmd .= " -U hfuse:w:0x{$_REQUEST['HFUSE']}:m";
			$output = shell_exec("avrdude -p m16 -c linuxgpio $flashcmd -q 2>&1 ; echo ~~~ $?");
			shell_exec("/etc/init.d/breadmaker start");
		} else {
			$output = '{"result":true,"return_code":0,"output":"\navrdude: AVR device initialized and ready to accept instructions\navrdude: Device signature = 0x1e9403\navrdude: NOTE: \"flash\" memory has been specified, an erase cycle will be performed\n         To disable this feature, specify the -D option.\navrdude: erasing chip\navrdude: reading input file \"\/tmp\/phpnhHcIM\"\navrdude: input file \/tmp\/phpnhHcIM auto detected as Intel Hex\navrdude: writing flash (12844 bytes):\navrdude: 12844 bytes of flash written\navrdude: verifying flash memory against \/tmp\/phpnhHcIM:\navrdude: load data flash data from input file \/tmp\/phpnhHcIM:\navrdude: input file \/tmp\/phpnhHcIM auto detected as Intel Hex\navrdude: input file \/tmp\/phpnhHcIM contains 12844 bytes\navrdude: reading on-chip flash data:\navrdude: verifying ...\navrdude: 12844 bytes of flash verified\navrdude: reading input file \"0xBF\"\navrdude: writing lfuse (1 bytes):\navrdude: 1 bytes of lfuse written\navrdude: verifying lfuse memory against 0xBF:\navrdude: load data lfuse data from input file 0xBF:\navrdude: input file 0xBF contains 1 bytes\navrdude: reading on-chip lfuse data:\navrdude: verifying ...\navrdude: 1 bytes of lfuse verified\navrdude: reading input file \"0xD9\"\navrdude: writing hfuse (1 bytes):\navrdude: 1 bytes of hfuse written\navrdude: verifying hfuse memory against 0xD9:\navrdude: load data hfuse data from input file 0xD9:\navrdude: input file 0xD9 contains 1 bytes\navrdude: reading on-chip hfuse data:\navrdude: verifying ...\navrdude: 1 bytes of hfuse verified\n\navrdude done.  Thank you.\n\n~~~ 0\n"}';
		}
		$code = strstr($output, '~~~');
		$code = (int)trim(substr($code, 4));
		$result['result'] = $code == 0;
		$result['return_code'] = $code;
		$result['output'] = $output;
		if (isset($_REQUEST['raw'])) die($output);
	}
?>
