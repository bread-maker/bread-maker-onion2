#!/bin/sh

PHP=php-cli

killall -q breadmaker.sh
killall -q tick.php
(
  sleep 1
  . ../breadmaker.sh
) &
$PHP emulator.php


