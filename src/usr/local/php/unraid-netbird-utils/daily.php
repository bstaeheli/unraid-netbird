#!/usr/bin/php -q
<?php

/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

namespace Netbird;

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';

require_once "{$docroot}/plugins/netbird/include/common.php";
if ( ! defined(__NAMESPACE__ . '\PLUGIN_ROOT') || ! defined(__NAMESPACE__ . '\PLUGIN_NAME')) {
    throw new \RuntimeException("Common file not loaded.");
}
$utils = new Utils(PLUGIN_NAME);

// Daily tasks - currently just a health check log entry
$utils->logmsg("Daily check running");

$cli    = new NetbirdCLI();
$config = new Config();

if ( ! $config->Enable) {
    $utils->logmsg("NetBird is disabled, skipping daily check.");
    exit(0);
}

if ( ! $cli->isReady()) {
    $utils->logmsg("NetBird daemon is not running, skipping daily check.");
    exit(0);
}

$status = $cli->getStatusSafe();
$state  = $status['daemonState'] ?? 'unknown';
$utils->logmsg("NetBird daemon state: {$state}");
