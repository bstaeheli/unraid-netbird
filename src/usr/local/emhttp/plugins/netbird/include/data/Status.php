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

$action = $_POST['action'] ?? $_GET['action'] ?? 'get';
$cli    = new NetbirdCLI();

switch ($action) {
    case 'get':
    default:
        try {
            $status = $cli->getStatusSafe();
            $info   = new Info(null);
            $peers  = $info->getPeerStatus();

            $peerData = array_map(function (PeerStatus $p) {
                return [
                    'name'     => $p->Name,
                    'fqdn'     => $p->FQDN,
                    'ip'       => $p->IP,
                    'status'   => $p->Status,
                    'online'   => $p->Online,
                    'relayed'  => $p->Relayed,
                    'connType' => $p->ConnType,
                    'address'  => $p->Address,
                    'txBytes'  => $p->TxBytes,
                    'rxBytes'  => $p->RxBytes,
                ];
            }, $peers);

            echo json_encode([
                'success'   => true,
                'peers'     => $peerData,
                'total'     => $info->getPeerCount(),
                'connected' => $info->getConnectedPeerCount(),
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
}
