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

        /** @var array<string, mixed> $localPeer */
        $localPeer = (array)($status['localPeerState'] ?? []);
        /** @var array<string, mixed> $server */
        $server = (array)($status['serverState'] ?? []);

        $info = new StatusInfo();

        $info->NbVersion    = (string)($status['cliVersion'] ?? $this->tr("unknown"));
        $info->DaemonState  = (string)($status['daemonState'] ?? $this->tr("unknown"));
        $info->LocalIP      = (string)($localPeer['IP'] ?? $this->tr("unknown"));
        $info->FQDN         = (string)($localPeer['fqdn'] ?? $this->tr("unknown"));
        $info->PubKey       = (string)($localPeer['pubKey'] ?? $this->tr("unknown"));
        $info->ServerURL    = (string)($server['url'] ?? $this->tr("unknown"));
        $info->ServerOnline = isset($server['connected'])
            ? ($server['connected'] ? $this->tr("yes") : $this->tr("no"))
            : $this->tr("unknown");

        $serverError = (string)($server['error'] ?? '');
        if ( ! empty($serverError)) {
            $info->Health = $serverError;
        }

        return $info;
    }

    public function getConnectionInfo(): ConnectionInfo
    {
        $status = $this->status;

        /** @var array<string, mixed> $localPeer */
        $localPeer = (array)($status['localPeerState'] ?? []);
        /** @var array<string, mixed> $server */
        $server = (array)($status['serverState'] ?? []);

        $info = new ConnectionInfo();

        $info->HostName    = gethostname() ?: $this->tr("unknown");
        $info->FQDN        = (string)($localPeer['fqdn'] ?? $this->tr("unknown"));
        $info->NetbirdIP   = (string)($localPeer['IP'] ?? $this->tr("unknown"));
        $info->DaemonState = (string)($status['daemonState'] ?? $this->tr("unknown"));
        $info->ServerURL   = (string)($server['url'] ?? $this->tr("unknown"));
        $info->AuthURL     = (string)($status['authURL'] ?? '');

        return $info;
    }

    public function getDashboardInfo(): DashboardInfo
    {
        $status = $this->status;

        /** @var array<string, mixed> $localPeer */
        $localPeer = (array)($status['localPeerState'] ?? []);

        $info = new DashboardInfo();

        $info->HostName    = gethostname() ?: $this->tr("Unknown");
        $info->FQDN        = (string)($localPeer['fqdn'] ?? $this->tr("Unknown"));
        $info->NetbirdIP   = (string)($localPeer['IP'] ?? $this->tr("Unknown"));
        $info->DaemonState = (string)($status['daemonState'] ?? $this->tr("Unknown"));
        $info->Online      = $this->isConnected() ? $this->tr("yes") : $this->tr("no");

        return $info;
    }

    /**
     * @return array<int, PeerStatus>
     */
    public function getPeerStatus(): array
    {
        $result = array();

        /** @var array<string, mixed> $peers */
        $peers = (array)($this->status['peers'] ?? []);
        /** @var array<int, array<string, mixed>> $details */
        $details = (array)($peers['details'] ?? []);

        foreach ($details as $peer) {
            /** @var array<string, mixed> $peer */
            $peer = (array)$peer;
            $p    = new PeerStatus();

            $p->FQDN   = (string)($peer['fqdn'] ?? '');
            $p->Name   = $p->FQDN ? rtrim($p->FQDN, '.') : (string)($peer['pubKey'] ?? '');
            $p->IP     = (string)($peer['netbirdIp'] ?? '');
            $p->Status = (string)($peer['status'] ?? '');

            $isConnected = strtolower($p->Status) === 'connected';
            $p->Online   = $isConnected;

            if ($isConnected) {
                $connType    = (string)($peer['connType'] ?? 'P2P');
                $p->ConnType = $connType;
                $p->Relayed  = strtolower($connType) === 'relay';

                if ($p->Relayed) {
                    $p->Address = (string)($peer['relayAddress'] ?? '');
                } else {
                    /** @var array<string, mixed> $icePair */
                    $icePair    = (array)($peer['iceCandidateEndpoint'] ?? []);
                    $p->Address = (string)($icePair['remote'] ?? '');
                }
            }

            $p->TxBytes = intval((string)($peer['bytesTx'] ?? 0));
            $p->RxBytes = intval((string)($peer['bytesRx'] ?? 0));

            $result[] = $p;
        }

        return $result;
    }

    public function isConnected(): bool
    {
        $state = strtolower((string)($this->status['daemonState'] ?? ''));
        return $state === 'running' || $state === 'connected';
    }

    public function needsLogin(): bool
    {
        $state = strtolower((string)($this->status['daemonState'] ?? ''));
        return $state === 'needlogin' || $state === 'need login';
    }

    public function getAuthURL(): string
    {
        return (string)($this->status['authURL'] ?? '');
    }

    public function getNetbirdIP(): string
    {
        /** @var array<string, mixed> $localPeer */
        $localPeer = (array)($this->status['localPeerState'] ?? []);
        $ip        = (string)($localPeer['IP'] ?? '');
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
        /** @var array<string, mixed> $peers */
        $peers = (array)($this->status['peers'] ?? []);
        return intval((string)($peers['total'] ?? 0));
    }

    public function getConnectedPeerCount(): int
    {
        /** @var array<string, mixed> $peers */
        $peers = (array)($this->status['peers'] ?? []);
        return intval((string)($peers['connected'] ?? 0));
    }
}
