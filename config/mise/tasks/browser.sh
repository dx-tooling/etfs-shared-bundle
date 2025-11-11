#!/usr/bin/env bash
#MISE description="Open the web application in a browser"

URL="http://$(docker compose ps nginx --format "table {{.Ports}}" | cut -d"-" -f1 | grep -v PORTS)"

case "$OSTYPE" in
   cygwin*)
      cmd /c start "$URL"
      ;;
   linux*)
      xdg-open "$URL"
      ;;
   darwin*)
      open "$URL"
      ;;
esac
