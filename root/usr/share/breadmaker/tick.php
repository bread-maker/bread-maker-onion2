#!/usr/bin/php-cli
<?php
$api = $argv[1];
require_once($api . '/config.php');
require_once($api . '/Gpio.php');
$gpio = new PhpGpio\Gpio();

$next = microtime(true);
$value = 0;

while(1)
{
  while (microtime(true) < $next)
  {
    usleep(100000);
    if (microtime(true) < $next - 0.5) // Time changed backwards?
    {
      $next = microtime(true);
      break;
    }
  }
  $gpio->setup(PIN_SCK, 'out');
  if ($value) $value = 0; else $value = 1;
  $gpio->output(PIN_SCK, $value);
  $next += 0.5;  
  if (microtime(true) >= $next) // Time changed forwards?
    $next = microtime(true) + 0.5;
}
?>
