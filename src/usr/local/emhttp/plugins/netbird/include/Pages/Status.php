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

$netbirdInfo = $netbirdInfo ?? new Info($tr);
$peers       = $netbirdInfo->getPeerStatus();
$total       = $netbirdInfo->getPeerCount();
$connected   = $netbirdInfo->getConnectedPeerCount();
?>
<link type="text/css" rel="stylesheet" href="/plugins/netbird/style.css">
<script src="/webGui/javascript/jquery.tablesorter.widgets.js"></script>

<table class="unraid t1">
    <thead>
        <tr>
            <td><?= $tr->tr("status.peers_header"); ?> (<?= $connected; ?>/<?= $total; ?>)</td>
        </tr>
    </thead>
</table>

<table class="tablesorter unraid" id="peerTable">
    <thead>
        <tr>
            <th><?= $tr->tr("status.name"); ?></th>
            <th><?= $tr->tr("status.ip"); ?></th>
            <th><?= $tr->tr("status.status"); ?></th>
            <th><?= $tr->tr("status.conn_type"); ?></th>
            <th><?= $tr->tr("status.address"); ?></th>
            <th><?= $tr->tr("status.traffic"); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($peers)) { ?>
        <tr>
            <td colspan="6"><?= $tr->tr("status.no_peers"); ?></td>
        </tr>
        <?php } else { ?>
        <?php foreach ($peers as $peer) { ?>
        <tr>
            <td><?= htmlspecialchars($peer->Name); ?></td>
            <td><?= htmlspecialchars($peer->IP); ?></td>
            <td>
                <?php if ($peer->Online) { ?>
                    <span class="green-text"><?= $tr->tr("status.connected"); ?></span>
                <?php } else { ?>
                    <span class="orange-text"><?= htmlspecialchars($peer->Status ?: $tr->tr("status.not_connected")); ?></span>
                <?php } ?>
            </td>
            <td>
                <?php if ($peer->Online) { ?>
                    <?= $peer->Relayed
                        ? '<span title="' . htmlspecialchars($peer->Address) . '">' . $tr->tr("status.relay") . '</span>'
                        : '<span title="' . htmlspecialchars($peer->Address) . '">' . $tr->tr("status.direct") . '</span>'; ?>
                <?php } else { ?>
                    &mdash;
                <?php } ?>
            </td>
            <td><?= $peer->Online ? htmlspecialchars($peer->Address) : '&mdash;'; ?></td>
            <td>
                <?php if ($peer->TxBytes > 0 || $peer->RxBytes > 0) {
                    $tx = round($peer->TxBytes / 1024 / 1024, 2);
                    $rx = round($peer->RxBytes / 1024 / 1024, 2);
                    echo "&#x2191; {$tx} MB / &#x2193; {$rx} MB";
                } else {
                    echo '&mdash;';
                } ?>
            </td>
        </tr>
        <?php } ?>
        <?php } ?>
    </tbody>
</table>

<script>
$(function() {
    $("#peerTable").tablesorter({
        widgets: ["filter"],
        widgetOptions: { filter_placeholder: { search: "<?= $tr->tr("status.filter"); ?>" } }
    });
});
</script>
