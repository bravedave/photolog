#!/bin/bash

WD=`pwd`
PORT=$[RANDOM%1000+1024]
PORT=8015

apache=`command -v httpd`

if [[ "" == $apache ]]; then
  cd www

  php=php
  if [[ -x /usr/bin/php8 ]]; then php=php8; fi

  echo "this application is available at http://0.0.0.0:$PORT"
  $php -S 0.0.0.0:$PORT _mvp.php

  cd $WD

else
  data="`pwd`/src/application/data"
  access_log="$data/access.log"
  config="$data/httpd.conf"
  pidFile="$data/httpd.pid"

  [[ -d $data ]] || mkdir -p $data
  [[ -d $data ]] || exit 0

  if [ "$1" == "kill" ]; then
    if [[ -f $pidFile ]]; then
      kill `cat $pidFile`
      if [[ -f $pidFile ]]; then
        rm $pidFile

      fi

    fi

  else

    if [[ ! -f $config ]]; then
      cp vendor/bravedave/dvc/httpd-minimal.conf $config

      echo "CustomLog $access_log common" >>$config
      echo "DocumentRoot `pwd`/www" >>$config
      echo "<Directory `pwd`/www>" >>$config
      echo "  AllowOverride all" >>$config
      echo "  Require all granted" >>$config
      echo "</Directory>" >>$config

    fi

    if [[ -f $pidFile ]] ; then
      echo "running ..`cat $pidFile`"

    else
      [[ ! -f $access_log ]] || rm $access_log

      echo "this application is available at http://localhost:$PORT"
      httpd -DFOREGROUND \
        -f $config \
        -c "Listen $PORT" \
        -c "PidFile $data/httpd.pid"

    fi

  fi

fi
