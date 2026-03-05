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

class Watcher
{
    private Config $config;

    public function __construct()
    {
        $this->config = new Config();
    }

    public function run(): void
    {
        $timer               = 15;
        $need_ip             = true;
        $allow_check_restart = false;

        if ( ! defined(__NAMESPACE__ . '\PLUGIN_ROOT') || ! defined(__NAMESPACE__ . '\PLUGIN_NAME')) {
            throw new \RuntimeException("Common file not loaded.");
        }
        $utils = new Utils(PLUGIN_NAME);

        $utils->logmsg("Starting netbird-watcher");

        while ( ! file_exists('/var/local/emhttp/var.ini')) {
            $utils->logmsg("Waiting for system to finish booting");
            sleep(10);
        }

        // @phpstan-ignore while.alwaysTrue
        while (true) {
            unset($netbird_ipv4);

            $interfaces = net_get_interfaces();

            // NetBird uses the wt0 WireGuard interface
            if (isset($interfaces["wt0"]["unicast"])) {
                foreach ($interfaces["wt0"]["unicast"] as $interface) {
                    if (isset($interface["address"])) {
                        if ($interface["family"] == 2) {
                            $netbird_ipv4 = $interface["address"];
                            $timer        = 60;
                        }
                    }
                }
            }

            if (isset($netbird_ipv4)) {
                if ($need_ip) {
                    $utils->logmsg("NetBird IP detected ({$netbird_ipv4}), applying configuration");
                    $need_ip = false;

                    $utils->run_task('Netbird\System::applyGRO');
                    $utils->run_task('Netbird\System::restartSystemServices', array($this->config));

                    if ($this->config->AddPeersToHosts) {
                        $info = new Info(null);
                        $utils->run_task('Netbird\System::addToHostFile', array($info));
                    }
                }

                $allow_check_restart = $utils->run_task('Netbird\System::checkWebgui', array($this->config, $netbird_ipv4, $allow_check_restart));
            } else {
                $utils->logmsg("Waiting for NetBird IP on wt0");
            }

            sleep($timer);
        }
    }
}
