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

if ( ! defined(__NAMESPACE__ . '\PLUGIN_ROOT') || ! defined(__NAMESPACE__ . '\PLUGIN_NAME')) {
    throw new \RuntimeException("Common file not loaded.");
}

$tr = $tr ?? new Translator(PLUGIN_ROOT);

if ( ! Utils::pageChecks($tr)) {
    return;
}

$netbirdInfo    = $netbirdInfo ?? new Info($tr);
$netbirdStatus  = $netbirdInfo->getStatusInfo();
?>
<table class="unraid t1">
    <thead>
        <tr>
            <td><?= $tr->tr('status'); ?></td>
            <td>&nbsp;</td>
        </tr>
    </thead>
    <tbody>
        <?php
        echo Utils::printRow($tr->tr("info.version"), htmlspecialchars($netbirdStatus->NbVersion));
        echo Utils::printRow($tr->tr("info.daemon_state"), htmlspecialchars($netbirdStatus->DaemonState));
        echo Utils::printRow($tr->tr("info.server_url"), htmlspecialchars($netbirdStatus->ServerURL));
        echo Utils::printRow($tr->tr("info.server_connected"), htmlspecialchars($netbirdStatus->ServerOnline));
        echo Utils::printRow($tr->tr("info.ip"), htmlspecialchars($netbirdStatus->LocalIP));
        echo Utils::printRow($tr->tr("info.fqdn"), htmlspecialchars($netbirdStatus->FQDN));
        echo Utils::printRow($tr->tr("info.pub_key"), '<code>' . htmlspecialchars($netbirdStatus->PubKey) . '</code>');
        echo Utils::printRow($tr->tr("info.connected_via"), $netbirdInfo->connectedViaNetbird() ? $tr->tr("yes") : $tr->tr("no"));

        if ( ! empty($netbirdStatus->Health)) {
            echo Utils::printRow($tr->tr("info.health"), '<span class="error">' . htmlspecialchars($netbirdStatus->Health) . '</span>');
        }
        ?>
    </tbody>
</table>
