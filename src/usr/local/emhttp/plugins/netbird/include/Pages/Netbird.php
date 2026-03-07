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

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once "{$docroot}/plugins/netbird/include/common.php";

if ( ! defined(__NAMESPACE__ . '\PLUGIN_ROOT') || ! defined(__NAMESPACE__ . '\PLUGIN_NAME')) {
    throw new \RuntimeException("Common file not loaded.");
}

$tr = $tr ?? new Translator(PLUGIN_ROOT);

if ( ! Utils::pageChecks($tr)) {
    return;
}

$netbirdInfo   = $netbirdInfo   ?? new Info($tr);
$netbirdConfig = $netbirdConfig ?? new Config();
$connInfo      = $netbirdInfo->getConnectionInfo();

// Disable connect/disconnect when connected via NetBird to avoid breaking the connection
$nbDisconnect = $netbirdInfo->connectedViaNetbird() ? " disabled" : "";
?>
<link type="text/css" rel="stylesheet" href="/plugins/netbird/style.css">

<script>
function netbirdConnect() {
    $.post('/plugins/netbird/include/data/Config.php',
        { action: 'up' },
        function(data) {
            var result = JSON.parse(data);
            if (result.authURL) {
                window.open(result.authURL, '_blank');
            }
            setTimeout(function() { window.location.reload(); }, 3000);
        }
    );
}

function netbirdDisconnect() {
    $.post('/plugins/netbird/include/data/Config.php',
        { action: 'down' },
        function() {
            setTimeout(function() { window.location.reload(); }, 3000);
        }
    );
}
</script>

<table class="unraid t1">
    <thead>
        <tr>
            <td><?= $tr->tr("connection.title"); ?></td>
            <td>&nbsp;</td>
        </tr>
    </thead>
    <tbody>
        <?php
        echo Utils::printRow($tr->tr("info.hostname"), htmlspecialchars($connInfo->HostName));
echo Utils::printRow($tr->tr("info.fqdn"), htmlspecialchars($connInfo->FQDN));
echo Utils::printRow($tr->tr("info.ip"), htmlspecialchars($connInfo->NetbirdIP));
echo Utils::printRow($tr->tr("info.daemon_state"), htmlspecialchars($connInfo->DaemonState));
echo Utils::printRow($tr->tr("info.server_url"), htmlspecialchars($connInfo->ServerURL));

if ( ! empty($connInfo->AuthURL)) {
    echo Utils::printRow(
        $tr->tr("info.auth_url"),
        "<a href='" . htmlspecialchars($connInfo->AuthURL) . "' target='_blank'>" . $tr->tr("info.auth_url_link") . "</a>"
    );
}
?>
    </tbody>
</table>

<table class="unraid t1">
    <thead>
        <tr>
            <td><?= $tr->tr("connection.actions"); ?></td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <dl>
                    <dt><?= $tr->tr("connection.connect_info"); ?></dt>
                    <dd>
                        <button type="button" onclick="netbirdConnect()" <?= $nbDisconnect; ?>>
                            <?= $tr->tr("connection.connect"); ?>
                        </button>
                        &nbsp;
                        <button type="button" onclick="netbirdDisconnect()" <?= $nbDisconnect; ?>>
                            <?= $tr->tr("connection.disconnect"); ?>
                        </button>
                    </dd>
                </dl>
                <?php if ($netbirdInfo->connectedViaNetbird()) { ?>
                <blockquote class='inline_help'><?= $tr->tr("warnings.connected_via_netbird"); ?></blockquote>
                <?php } ?>
            </td>
        </tr>
    </tbody>
</table>

<?php if ($netbirdConfig->AdminURL) { ?>
<table class="unraid t1">
    <thead>
        <tr>
            <td><?= $tr->tr("connection.admin"); ?></td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <dl>
                    <dt><?= $tr->tr("connection.admin_info"); ?></dt>
                    <dd>
                        <a href="<?= htmlspecialchars($netbirdConfig->AdminURL); ?>" target="_blank">
                            <?= $tr->tr("connection.open_admin"); ?>
                        </a>
                    </dd>
                </dl>
            </td>
        </tr>
    </tbody>
</table>
<?php } ?>
