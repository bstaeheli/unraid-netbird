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

use EDACerton\PluginUtils\Translator;

class Info
{
    private ?Translator $tr;
    private NetbirdCLI $cli;

    /** @var array<string, mixed> */
    private array $status;

    public function __construct(?Translator $tr)
    {
        $this->cli    = new NetbirdCLI();
        $this->tr     = $tr;
        $this->status = $this->cli->getStatusSafe();
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        return $this->status;
    }

    private function tr(string $message): string
    {
        if ($this->tr === null) {
            return $message;
        }

        return $this->tr->tr($message);
    }

    public function getStatusInfo(): StatusInfo
    {
        $status = $this->status;

        $info = new StatusInfo();

        $info->NbVersion    = $status['cliVersion'] ?? $this->tr("unknown");
        $info->DaemonState  = $status['daemonState'] ?? $this->tr("unknown");
        $info->LocalIP      = $status['localPeerState']['IP'] ?? $this->tr("unknown");
        $info->FQDN         = $status['localPeerState']['fqdn'] ?? $this->tr("unknown");
        $info->PubKey       = $status['localPeerState']['pubKey'] ?? $this->tr("unknown");
        $info->ServerURL    = $status['serverState']['url'] ?? $this->tr("unknown");
        $info->ServerOnline = isset($status['serverState']['connected'])
            ? ($status['serverState']['connected'] ? $this->tr("yes") : $this->tr("no"))
            : $this->tr("unknown");

        $serverError = $status['serverState']['error'] ?? '';
        if ( ! empty($serverError)) {
            $info->Health = $serverError;
        }

        return $info;
    }

    public function getConnectionInfo(): ConnectionInfo
    {
        $status = $this->status;

        $info = new ConnectionInfo();

        $info->HostName    = gethostname() ?: $this->tr("unknown");
        $info->FQDN        = $status['localPeerState']['fqdn'] ?? $this->tr("unknown");
        $info->NetbirdIP   = $status['localPeerState']['IP'] ?? $this->tr("unknown");
        $info->DaemonState = $status['daemonState'] ?? $this->tr("unknown");
        $info->ServerURL   = $status['serverState']['url'] ?? $this->tr("unknown");
        $info->AuthURL     = $status['authURL'] ?? '';

        return $info;
    }

    public function getDashboardInfo(): DashboardInfo
    {
        $status = $this->status;

        $info = new DashboardInfo();

        $info->HostName    = gethostname() ?: $this->tr("Unknown");
        $info->FQDN        = $status['localPeerState']['fqdn'] ?? $this->tr("Unknown");
        $info->NetbirdIP   = $status['localPeerState']['IP'] ?? $this->tr("Unknown");
        $info->DaemonState = $status['daemonState'] ?? $this->tr("Unknown");
        $info->Online      = $this->isConnected() ? $this->tr("yes") : $this->tr("no");

        return $info;
    }

    /**
     * @return array<int, PeerStatus>
     */
    public function getPeerStatus(): array
    {
        $result  = array();
        $details = $this->status['peers']['details'] ?? array();

        foreach ($details as $peer) {
            $p = new PeerStatus();

            $p->FQDN   = $peer['fqdn'] ?? '';
            $p->Name   = $p->FQDN ? rtrim($p->FQDN, '.') : ($peer['pubKey'] ?? '');
            $p->IP     = $peer['netbirdIp'] ?? '';
            $p->Status = $peer['status'] ?? '';

            $isConnected = strtolower($p->Status) === 'connected';
            $p->Online   = $isConnected;

            if ($isConnected) {
                $connType  = $peer['connType'] ?? 'P2P';
                $p->ConnType = $connType;
                $p->Relayed  = strtolower($connType) === 'relay';

                if ($p->Relayed) {
                    $p->Address = $peer['relayAddress'] ?? '';
                } else {
                    // For P2P show the remote endpoint if available
                    $icePair     = $peer['iceCandidateEndpoint'] ?? array();
                    $p->Address  = $icePair['remote'] ?? '';
                }
            }

            $p->TxBytes = intval($peer['bytesTx'] ?? 0);
            $p->RxBytes = intval($peer['bytesRx'] ?? 0);

            $result[] = $p;
        }

        return $result;
    }

    public function isConnected(): bool
    {
        $state = strtolower($this->status['daemonState'] ?? '');
        return $state === 'running' || $state === 'connected';
    }

    public function needsLogin(): bool
    {
        $state = strtolower($this->status['daemonState'] ?? '');
        return $state === 'needlogin' || $state === 'need login';
    }

    public function getAuthURL(): string
    {
        return $this->status['authURL'] ?? '';
    }

    public function getNetbirdIP(): string
    {
        $ip = $this->status['localPeerState']['IP'] ?? '';
        // Strip CIDR prefix if present
        return explode('/', $ip)[0];
    }

    public function connectedViaNetbird(): bool
    {
        $nbIP = $this->getNetbirdIP();
        if (empty($nbIP)) {
            return false;
        }
        return ($_SERVER['SERVER_ADDR'] ?? '') === $nbIP;
    }

    /**
     * @return array<int, PeerStatus>
     */
    public function getPeers(): array
    {
        return $this->getPeerStatus();
    }

    public function getPeerCount(): int
    {
        return intval($this->status['peers']['total'] ?? 0);
    }

    public function getConnectedPeerCount(): int
    {
        return intval($this->status['peers']['connected'] ?? 0);
    }
}
