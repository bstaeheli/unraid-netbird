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

enum NotificationType: string
{
    case NORMAL  = 'normal';
    case WARNING = 'warning';
    case ALERT   = 'alert';
}

class System extends \EDACerton\PluginUtils\System
{
    public const RESTART_COMMAND = "/usr/local/emhttp/webGui/scripts/reload_services";
    public const NOTIFY_COMMAND  = "/usr/local/emhttp/webGui/scripts/notify";

    public static function enableIPForwarding(Config $config): void
    {
        if ($config->Enable) {
            Utils::logwrap("Enabling IP forwarding");
            $sysctl = "net.ipv4.ip_forward = 1" . PHP_EOL . "net.ipv6.conf.all.forwarding = 1";
            file_put_contents('/etc/sysctl.d/99-netbird.conf', $sysctl);
            Utils::runwrap("sysctl -p /etc/sysctl.d/99-netbird.conf", true);
        }
    }

    public static function applyGRO(): void
    {
        /** @var array<int, array<string>> $ip_route */
        $ip_route = (array) json_decode(implode(Utils::runwrap('ip -j route get 8.8.8.8')), true);

        if ( ! isset($ip_route[0]['dev'])) {
            Utils::logwrap("Default interface could not be detected.");
            return;
        }

        $dev = $ip_route[0]['dev'];

        /** @var array<string, array<string>> $ethtool */
        $ethtool = ((array) json_decode(implode(Utils::runwrap("ethtool --json -k {$dev}")), true))[0];

        if (isset($ethtool['rx-udp-gro-forwarding']) && ! $ethtool['rx-udp-gro-forwarding']['active']) {
            Utils::runwrap("ethtool -K {$dev} rx-udp-gro-forwarding on");
        }

        if (isset($ethtool['rx-gro-list']) && $ethtool['rx-gro-list']['active']) {
            Utils::runwrap("ethtool -K {$dev} rx-gro-list off");
        }
    }

    public static function setExtraInterface(Config $config): void
    {
        if (file_exists(self::RESTART_COMMAND)) {
            $include_array      = array();
            $exclude_interfaces = "";
            $write_file         = true;
            $network_extra_file = '/boot/config/network-extra.cfg';
            $ifname             = 'wt0';

            if (file_exists($network_extra_file)) {
                $netExtra = parse_ini_file($network_extra_file);
                if ($netExtra['include_interfaces'] ?? false) {
                    $include_array = explode(' ', $netExtra['include_interfaces']);
                }
                if ($netExtra['exclude_interfaces'] ?? false) {
                    $exclude_interfaces = $netExtra['exclude_interfaces'];
                }
                $write_file = false;
            }

            $in_array = in_array($ifname, $include_array);

            if ($in_array != $config->IncludeInterface) {
                if ($config->IncludeInterface) {
                    $include_array[] = $ifname;
                    Utils::logwrap("{$ifname} added to include_interfaces");
                } else {
                    $include_array = array_diff($include_array, [$ifname]);
                    Utils::logwrap("{$ifname} removed from include_interfaces");
                }
                $write_file = true;
            }

            if ($write_file) {
                $include_interfaces = implode(' ', $include_array);

                $file = <<<END
                    include_interfaces="{$include_interfaces}"
                    exclude_interfaces="{$exclude_interfaces}"

                    END;

                file_put_contents($network_extra_file, $file);
                Utils::logwrap("Updated network-extra.cfg");
            }
        }
    }

    public static function restartSystemServices(Config $config): void
    {
        if ($config->IncludeInterface) {
            Utils::runwrap(self::RESTART_COMMAND);
        }
    }

    public static function checkWebgui(Config $config, string $netbird_ipv4, bool $allowRestart): bool
    {
        if ($config->IncludeInterface) {
            $ident_config = parse_ini_file("/boot/config/ident.cfg") ?: array();

            $connection = @fsockopen($netbird_ipv4, $ident_config['PORT']);

            if (is_resource($connection)) {
                Utils::logwrap("WebGUI listening on {$netbird_ipv4}:{$ident_config['PORT']}", false, true);
            } else {
                if ( ! $allowRestart) {
                    Utils::logwrap("WebGUI not listening on {$netbird_ipv4}:{$ident_config['PORT']}, waiting for next check");
                    return true;
                }

                Utils::logwrap("WebGUI not listening on {$netbird_ipv4}:{$ident_config['PORT']}, terminating and restarting");
                Utils::runwrap("/etc/rc.d/rc.nginx term");
                sleep(5);
                Utils::runwrap("/etc/rc.d/rc.nginx start");
            }
        }

        return false;
    }

    public static function addToHostFile(Info $info): void
    {
        $peers = $info->getPeerStatus();

        foreach ($peers as $peer) {
            if ( ! empty($peer->IP) && ! empty($peer->FQDN)) {
                // Strip CIDR if present
                $ip = explode('/', $peer->IP)[0];
                Utils::logwrap("Adding peer {$peer->FQDN} with IP {$ip} to hosts file");
                self::updateHostsFile(rtrim($peer->FQDN, '.'), $ip);
            }
        }

        // Add self
        $nbIP = $info->getNetbirdIP();
        /** @var array<string, mixed> $localPeer */
        $localPeer = (array)($info->getStatus()['localPeerState'] ?? []);
        $fqdnRaw   = $localPeer['fqdn'] ?? null;
        $fqdn      = is_string($fqdnRaw) ? $fqdnRaw : '';
        if ( ! empty($nbIP) && ! empty($fqdn)) {
            Utils::logwrap("Adding self {$fqdn} with IP {$nbIP} to hosts file");
            self::updateHostsFile(rtrim($fqdn, '.'), $nbIP);
        }
    }

    public static function sendNotification(string $event, string $subject, string $message, NotificationType $priority): void
    {
        $command = self::NOTIFY_COMMAND . " -l '/Settings/Netbird' -e " . escapeshellarg($event) . " -s " . escapeshellarg($subject) . " -d " . escapeshellarg("{$message}") . " -i \"{$priority->value}\" -x 2>/dev/null";
        exec($command);
    }
}
