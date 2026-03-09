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

class Config
{
    public bool $IncludeInterface;
    public bool $IPForward;
    public bool $Enable;
    public bool $AllowServerSSH;
    public bool $EnableSSHRoot;
    public bool $AddPeersToHosts;

    public string $ManagementURL;
    public string $SetupKey;
    public string $AdminURL;

    public function __construct()
    {
        $config_file = '/boot/config/plugins/netbird/netbird.cfg';

        if (file_exists($config_file)) {
            $saved_config = parse_ini_file($config_file) ?: array();
        } else {
            $saved_config = array();
        }

        $this->IncludeInterface = boolval($saved_config["INCLUDE_INTERFACE"] ?? "1");
        $this->IPForward        = boolval($saved_config["SYSCTL_IP_FORWARD"] ?? "1");
        $this->Enable           = boolval($saved_config["ENABLE_NETBIRD"] ?? "1");
        $this->AllowServerSSH   = boolval($saved_config["ALLOW_SERVER_SSH"] ?? "0");
        $this->EnableSSHRoot    = boolval($saved_config["ENABLE_SSH_ROOT"] ?? "0");
        $this->AddPeersToHosts  = boolval($saved_config["ADD_PEERS_TO_HOSTS"] ?? "0");

        $this->ManagementURL = $saved_config["MANAGEMENT_URL"] ?? "https://api.netbird.io:443";
        $this->SetupKey      = $saved_config["SETUP_KEY"]      ?? "";
        $this->AdminURL      = $saved_config["ADMIN_URL"]      ?? "https://app.netbird.io";
    }
}
