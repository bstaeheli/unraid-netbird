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

    /**
     * @param array<string, mixed> $data
     */
    private static function strAt(array $data, string $key, string $default = ''): string
    {
        $v = $data[$key] ?? null;
        return is_string($v) ? $v : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function intAt(array $data, string $key, int $default = 0): int
    {
        $v = $data[$key] ?? null;
        if (is_int($v)) {
            return $v;
        }
        return is_numeric($v) ? intval($v) : $default;
    }

    public function getStatusInfo(): StatusInfo
    {
        $status = $this->status;

        // Support both old (pre-0.66) and new (0.66+) JSON schema
        /** @var array<string, mixed> $localPeer */
        $localPeer = (array)($status['localPeerState'] ?? []);
        /** @var array<string, mixed> $server */
        $server = (array)($status['serverState'] ?? []);
        /** @var array<string, mixed> $management */
        $management = (array)($status['management'] ?? []);

        $info = new StatusInfo();

        $info->NbVersion    = self::strAt($status, 'cliVersion', $this->tr("unknown"));
        $info->DaemonState  = self::strAt($status, 'daemonState', $this->tr("unknown"));
        // New schema: netbirdIp is at top level; old schema: IP under localPeerState
        $info->LocalIP      = self::strAt($status, 'netbirdIp') ?: self::strAt($localPeer, 'IP', $this->tr("unknown"));
        // New schema: fqdn at top level; old schema: fqdn under localPeerState
        $info->FQDN         = self::strAt($status, 'fqdn') ?: self::strAt($localPeer, 'fqdn', $this->tr("unknown"));
        // New schema: publicKey at top level; old schema: pubKey under localPeerState
        $info->PubKey       = self::strAt($status, 'publicKey') ?: self::strAt($localPeer, 'pubKey', $this->tr("unknown"));
        // New schema: management.url; old schema: serverState.url
        $info->ServerURL    = self::strAt($management, 'url') ?: self::strAt($server, 'url', $this->tr("unknown"));
        // New schema: management.connected; old schema: serverState.connected
        $mgmtConnected = $management['connected'] ?? null;
        $srvConnected = $server['connected'] ?? null;
        $connected = $mgmtConnected ?? $srvConnected;
        $info->ServerOnline = ($connected !== null)
            ? ($connected ? $this->tr("yes") : $this->tr("no"))
            : $this->tr("unknown");

        $serverError = self::strAt($server, 'error') ?: self::strAt($management, 'error');
        if ( ! empty($serverError)) {
            $info->Health = $serverError;
        }

        $info->AuthURL = self::strAt($status, 'authURL');

        return $info;
    }

    public function getConnectionInfo(): ConnectionInfo
    {
        $status = $this->status;

        // Support both old and new JSON schema
        /** @var array<string, mixed> $localPeer */
        $localPeer = (array)($status['localPeerState'] ?? []);
        /** @var array<string, mixed> $server */
        $server = (array)($status['serverState'] ?? []);
        /** @var array<string, mixed> $management */
        $management = (array)($status['management'] ?? []);

        $info = new ConnectionInfo();

        $info->HostName    = gethostname() ?: $this->tr("unknown");
        $info->FQDN        = self::strAt($status, 'fqdn') ?: self::strAt($localPeer, 'fqdn', $this->tr("unknown"));
        $info->NetbirdIP   = self::strAt($status, 'netbirdIp') ?: self::strAt($localPeer, 'IP', $this->tr("unknown"));
        $info->DaemonState = self::strAt($status, 'daemonState', $this->tr("unknown"));
        $info->ServerURL   = self::strAt($management, 'url') ?: self::strAt($server, 'url', $this->tr("unknown"));
        $info->AuthURL     = self::strAt($status, 'authURL');

        return $info;
    }

    public function getDashboardInfo(): DashboardInfo
    {
        $status = $this->status;

        // Support both old and new JSON schema
        /** @var array<string, mixed> $localPeer */
        $localPeer = (array)($status['localPeerState'] ?? []);

        $info = new DashboardInfo();

        $info->HostName    = gethostname() ?: $this->tr("Unknown");
        $info->FQDN        = self::strAt($status, 'fqdn') ?: self::strAt($localPeer, 'fqdn', $this->tr("Unknown"));
        $info->NetbirdIP   = self::strAt($status, 'netbirdIp') ?: self::strAt($localPeer, 'IP', $this->tr("Unknown"));
        $info->DaemonState = self::strAt($status, 'daemonState', $this->tr("Unknown"));
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

            $p->FQDN   = self::strAt($peer, 'fqdn');
            $p->Name   = $p->FQDN ? rtrim($p->FQDN, '.') : self::strAt($peer, 'pubKey');
            $p->IP     = self::strAt($peer, 'netbirdIp');
            $p->Status = self::strAt($peer, 'status');

            $isConnected = strtolower($p->Status) === 'connected';
            $p->Online   = $isConnected;

            if ($isConnected) {
                $connType    = self::strAt($peer, 'connType', 'P2P');
                $p->ConnType = $connType;
                $p->Relayed  = strtolower($connType) === 'relay';

                if ($p->Relayed) {
                    $p->Address = self::strAt($peer, 'relayAddress');
                } else {
                    /** @var array<string, mixed> $icePair */
                    $icePair    = (array)($peer['iceCandidateEndpoint'] ?? []);
                    $p->Address = self::strAt($icePair, 'remote');
                }
            }

            $p->TxBytes = self::intAt($peer, 'bytesTx');
            $p->RxBytes = self::intAt($peer, 'bytesRx');

            $result[] = $p;
        }

        return $result;
    }

    public function isConnected(): bool
    {
        $state = strtolower(self::strAt($this->status, 'daemonState'));
        return $state === 'running' || $state === 'connected';
    }

    public function needsLogin(): bool
    {
        $state = strtolower(self::strAt($this->status, 'daemonState'));
        return $state === 'needlogin' || $state === 'need login';
    }

    public function getAuthURL(): string
    {
        return self::strAt($this->status, 'authURL');
    }

    public function getNetbirdIP(): string
    {
        // Support both old and new JSON schema
        /** @var array<string, mixed> $localPeer */
        $localPeer = (array)($this->status['localPeerState'] ?? []);
        $ip        = self::strAt($this->status, 'netbirdIp') ?: self::strAt($localPeer, 'IP');
        // Strip CIDR prefix if present
        return explode('/', $ip)[0];
    }

    public function connectedViaNetbird(): bool
    {
        $nbIP = $this->getNetbirdIP();
        if (empty($nbIP)) {
            return false;
        }
        $serverAddr = $_SERVER['SERVER_ADDR'] ?? null;
        return is_string($serverAddr) && $serverAddr === $nbIP;
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
        return self::intAt($peers, 'total');
    }

    public function getConnectedPeerCount(): int
    {
        /** @var array<string, mixed> $peers */
        $peers = (array)($this->status['peers'] ?? []);
        return self::intAt($peers, 'connected');
    }
}
