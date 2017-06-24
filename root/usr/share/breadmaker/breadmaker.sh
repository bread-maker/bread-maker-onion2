#!/bin/sh

API_DIR=/www/api/
PHP=php-cli

load_config_var()
{
  $PHP -r "include('$API_DIR/config.php'); echo $1;"
}

bmsend()
{
  echo $@ > $UART_OUT
  echo "-> $@"
}

program_selected()
{
  echo Selection: $@
  cd $API_DIR
  echo "<?php require_once('commands.php');
	\$result = array();
        bake($1, $2, $3);
	print_r(\$result); ?>" | $PHP
  cd $(dirname "$0")
}

baked()
{
  echo Baked!
}

write_stats()
{
  local data=$@
  sec=$(date +%s)
  sec=$(($sec + $T))
  sec5=$(($sec / 5))
  sec15=$(($sec / 15))
  sec30=$(($sec / 30))
  min=$(($sec / 60))
  min5=$(($sec / 300))
  stat={\"time\":$sec,$data}
  echo $stat > $STATS_DIR/breadmaker_stats_last.json
  [ "$sec" != "$last_sec" ] && echo $stat, >> $STATS_DIR/breadmaker_stats_sec.json      
  [ "$sec5" != "$last_sec5" ] && $PHP grouplogs.php $API_DIR 5 >> $STATS_DIR/breadmaker_stats_5sec.json
  [ "$sec15" != "$last_sec15" ] && $PHP grouplogs.php $API_DIR 15 >> $STATS_DIR/breadmaker_stats_15sec.json
  [ "$sec30" != "$last_sec30" ] && $PHP grouplogs.php $API_DIR 30 >> $STATS_DIR/breadmaker_stats_30sec.json
  if [ "$min" != "$last_min" ]; then
    $PHP grouplogs.php $API_DIR 60 >> $STATS_DIR/breadmaker_stats_min.json
    for f in $STATS_DIR/breadmaker_stats_*.json
    do 
      tail -n $LOG_SIZE $f > $f.tr
      mv -f $f.tr $f
    done
  fi
  [ "$min5" != "$last_min5" ] && $PHP grouplogs.php $API_DIR 300 >> $STATS_DIR/breadmaker_stats_5min.json
  last_sec=$sec
  last_sec5=$sec5
  last_sec15=$sec15
  last_sec30=$sec30
  last_min=$min
  last_min5=$min5
}

reset()
{
  pw=`load_config_var DEFAULT_PASSWORD`
  printf "$pw\n$pw" | passwd
  uci import $HOME_DIR/reset/uci_config
  uci commit
  cp -R $HOME_DIR/reset/settings/* $SETTINGS_DIR
}

main()
{
  if [ "$EMULATION" -eq "0" ]; then
    fast-gpio pwm $PIN_SCK 1 50
    stty -F $UART_OUT speed $UART_SETTINGS
    stty -F $UART_IN speed $UART_SETTINGS
    trap "fast-gpio set-output $PIN_SCK" TERM KILL INT
  else
    $PHP emulator.php &
    trap "kill $!" TERM KILL INT
  fi

  rm -f $STATS_DIR/breadmaker_stats_*
  rm -f $STATS_DIR/breadmaker_program.json
  while read l; do
    echo "<- $l"
    local cmd=${l:0:5}
    local data=${l:6}
    #echo Command: $cmd
    #[ ! -z "$data" ] && echo Data: $data
    case $cmd in
      "TIME?")
        [ "$(date +%s)" -gt "1000000000" ] && bmsend $(date +"TIME %H %M %S")
        ;;
      "STATS")
        write_stats "$data"
        ;;
      "SELCT")
        program_selected $data
        ;;
      "PROGR")
        echo $data > /tmp/breadmaker_program.json
        ;;
      "BAKED")
      	baked
        ;;
      "RESET")
        reset
       ;;
      "SKIPT")
        T=$data
       ;;
    esac
  done <$UART_IN
}

cd $(dirname "$0")
UART_OUT=`load_config_var UART_OUT`
UART_IN=`load_config_var UART_IN`
UART_SETTINGS=`load_config_var UART_SETTINGS`
PIN_SCK=`load_config_var PIN_SCK`
HOME_DIR=`load_config_var HOME_DIR`
STATS_DIR=`load_config_var STATS_DIR`
LOG_SIZE=`load_config_var LOG_SIZE`
SETTINGS_DIR=`load_config_var SETTINGS_DIR`
EMULATION=`load_config_var EMULATION`
T=0

main $@
