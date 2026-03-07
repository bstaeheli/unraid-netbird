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

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$config = new Config();
$cli    = new NetbirdCLI();

switch ($action) {
    case 'up':
        try {
            $cli->up($config->ManagementURL, $config->SetupKey);
            // Wait a moment for the daemon to process
            sleep(2);
            $status  = $cli->getStatusSafe();
            $authURL = $status['authURL'] ?? '';
            echo json_encode(['success' => true, 'authURL' => $authURL]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
    case 'down':
        try {
            $cli->down();
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
    case 'status':
        try {
            $status = $cli->getStatusSafe();
            echo json_encode(['success' => true, 'status' => $status]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        break;
}
