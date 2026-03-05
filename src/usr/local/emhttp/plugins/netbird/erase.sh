#!/bin/bash

. /usr/local/php/unraid-netbird-utils/log.sh

log "Stopping NetBird"
/etc/rc.d/rc.netbird stop

log "Erasing Configuration"
rm -f /boot/config/plugins/netbird/netbird.cfg
rm -f /boot/config/plugins/netbird/config.json

log "Restarting NetBird"
echo "sleep 5 ; /etc/rc.d/rc.netbird start" | at now 2>/dev/null
