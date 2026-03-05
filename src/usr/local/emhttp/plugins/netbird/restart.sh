#!/bin/bash

. /usr/local/php/unraid-netbird-utils/log.sh

log "Restarting NetBird in 5 seconds"
echo "sleep 5 ; /etc/rc.d/rc.netbird restart" | at now 2>/dev/null
