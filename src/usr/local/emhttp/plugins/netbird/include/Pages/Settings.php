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

$netbirdConfig = $netbirdConfig ?? new Config();

if (( ! isset($var)) || ( ! isset($display))) {
    echo("Missing required WebGUI variables");
    return;
}

// Disable destructive actions when connected via NetBird (would drop the connection)
$nbDisconnect = " disabled";
$authURL      = "";
if ($netbirdConfig->Enable) {
    $netbirdInfo = $netbirdInfo ?? new Info($tr);
    $connInfo    = $netbirdInfo->getConnectionInfo();
    $authURL     = $connInfo->AuthURL;
    if ( ! $netbirdInfo->connectedViaNetbird()) {
        $nbDisconnect = "";
    }
}

?>

<link type="text/css" rel="stylesheet" href="<?= Utils::auto_v('/webGui/styles/jquery.switchbutton.css');?>">
<span class="status vhshift"><input type="checkbox" class="advancedview"></span>
<form method="POST" action="/update.php" target="progressFrame">
<input type="hidden" name="#file"
    value="netbird/netbird.cfg">
<input type="hidden" name="#cleanup" value="">
<input type="hidden" name="#command" value="/usr/local/emhttp/plugins/netbird/restart.sh">

<table class="unraid tablesorter"><thead><tr><td><?= $tr->tr("settings.system_settings"); ?></td></tr></thead></table>

<div class="advanced">
    <dl>
        <dt><?= $tr->tr("settings.enable_netbird"); ?></dt>
        <dd>
            <select name='ENABLE_NETBIRD' size='1' class='narrow'>
                <?= Utils::make_option($netbirdConfig->Enable, '1', $tr->tr("yes"));?>
                <?= Utils::make_option( ! $netbirdConfig->Enable, '0', $tr->tr("no"));?>
            </select>
        </dd>
    </dl>

    <dl>
        <dt><?= $tr->tr("settings.unraid_listen"); ?></dt>
        <dd>
            <select name='INCLUDE_INTERFACE' size='1' class='narrow'>
                <?= Utils::make_option($netbirdConfig->IncludeInterface, '1', $tr->tr("yes"));?>
                <?= Utils::make_option( ! $netbirdConfig->IncludeInterface, '0', $tr->tr("no"));?>
            </select>
        </dd>
    </dl>
    <blockquote class='inline_help'><?= $tr->tr("settings.context.unraid_listen"); ?></blockquote>

    <dl>
        <dt><?= $tr->tr("settings.ip_forward"); ?></dt>
        <dd>
            <select name='SYSCTL_IP_FORWARD' size='1' class='narrow'>
                <?= Utils::make_option($netbirdConfig->IPForward, '1', $tr->tr("yes"));?>
                <?= Utils::make_option( ! $netbirdConfig->IPForward, '0', $tr->tr("no"));?>
            </select>
        </dd>
    </dl>
    <blockquote class='inline_help'><?= $tr->tr("settings.context.ip_forward"); ?></blockquote>

    <dl>
        <dt><?= $tr->tr("settings.enable_ssh"); ?></dt>
        <dd>
            <select name='ALLOW_SERVER_SSH' size='1' class='narrow'>
                <?= Utils::make_option($netbirdConfig->AllowServerSSH, '1', $tr->tr("yes"));?>
                <?= Utils::make_option( ! $netbirdConfig->AllowServerSSH, '0', $tr->tr("no"));?>
            </select>
        </dd>
    </dl>
    <blockquote class='inline_help'><?= $tr->tr("settings.context.enable_ssh"); ?></blockquote>

    <dl>
        <dt><?= $tr->tr("settings.enable_ssh_root"); ?></dt>
        <dd>
            <select name='ENABLE_SSH_ROOT' size='1' class='narrow'>
                <?= Utils::make_option($netbirdConfig->EnableSSHRoot, '1', $tr->tr("yes"));?>
                <?= Utils::make_option( ! $netbirdConfig->EnableSSHRoot, '0', $tr->tr("no"));?>
            </select>
        </dd>
    </dl>
    <blockquote class='inline_help'><?= $tr->tr("settings.context.enable_ssh_root"); ?></blockquote>

    <dl>
        <dt><?= $tr->tr("settings.hosts"); ?></dt>
        <dd>
            <select name='ADD_PEERS_TO_HOSTS' size='1' class='narrow'>
                <?= Utils::make_option( ! $netbirdConfig->AddPeersToHosts, '0', $tr->tr("no"));?>
                <?= Utils::make_option($netbirdConfig->AddPeersToHosts, '1', $tr->tr("yes"));?>
            </select>
        </dd>
    </dl>
    <blockquote class='inline_help'><?= $tr->tr("settings.context.hosts"); ?></blockquote>
</div>

<table class="unraid tablesorter"><thead><tr><td><?= $tr->tr("settings.connection"); ?></td></tr></thead></table>

<dl>
    <dt><?= $tr->tr("settings.management_url"); ?></dt>
    <dd>
        <input type="text" name="MANAGEMENT_URL" class="narrow" value="<?= htmlspecialchars($netbirdConfig->ManagementURL); ?>"
               placeholder="https://api.netbird.io:443">
    </dd>
</dl>
<blockquote class='inline_help'><?= $tr->tr("settings.context.management_url"); ?></blockquote>

<dl>
    <dt><?= $tr->tr("settings.setup_key"); ?></dt>
    <dd>
        <input type="text" name="SETUP_KEY" class="narrow" value="<?= htmlspecialchars($netbirdConfig->SetupKey); ?>"
               placeholder="<?= $tr->tr("settings.setup_key_placeholder"); ?>">
    </dd>
</dl>
<blockquote class='inline_help'><?= $tr->tr("settings.context.setup_key"); ?></blockquote>

<dl>
    <dt><?= $tr->tr("settings.admin_url"); ?></dt>
    <dd>
        <input type="text" name="ADMIN_URL" class="narrow" value="<?= htmlspecialchars($netbirdConfig->AdminURL); ?>"
               placeholder="https://app.netbird.io">
    </dd>
</dl>
<blockquote class='inline_help'><?= $tr->tr("settings.context.admin_url"); ?></blockquote>

<table class="unraid tablesorter"><thead><tr><td><?= $tr->tr("connection.actions"); ?></td></tr></thead></table>

<dl>
    <dt><?= $tr->tr("connection.connect_info"); ?></dt>
    <dd>
        <button type="button" onclick="netbirdConnect()" style="width:auto;display:inline-block;"<?= $nbDisconnect; ?>><?= $tr->tr("connection.connect"); ?></button>
        <button type="button" onclick="netbirdDisconnect()" style="width:auto;display:inline-block;"<?= $nbDisconnect; ?>><?= $tr->tr("connection.disconnect"); ?></button>
    </dd>
</dl>

<?php if ( ! empty($authURL)) { ?>
<dl>
    <dt><?= $tr->tr("info.auth_url"); ?></dt>
    <dd><a href="<?= htmlspecialchars($authURL); ?>" target="_blank"><?= $tr->tr("info.auth_url_link"); ?></a></dd>
</dl>
<?php } ?>

<?php if ($netbirdConfig->Enable && isset($netbirdInfo) && $netbirdInfo->connectedViaNetbird()) { ?>
<blockquote class='inline_help'><?= $tr->tr("warnings.connected_via_netbird"); ?></blockquote>
<?php } ?>

<table class="unraid tablesorter"><thead><tr><td><?= $tr->tr("settings.save"); ?></td></tr></thead></table>

<dl>
    <dt><strong><?= $tr->tr("settings.context.save"); ?></strong></dt>
    <dd>
        <span><input type="submit" name="#apply" value="<?= $tr->tr('apply'); ?>"><input type="button" id="DONE" value="<?= $tr->tr('back'); ?>" onclick="done()"></span>
    </dd>
</dl>
</form>

<table class="unraid tablesorter"><thead><tr><td><?= $tr->tr("settings.restart"); ?></td></tr></thead></table>

<form method="POST" action="/update.php" target="progressFrame">
<input type="hidden" name="#command" value="/usr/local/emhttp/plugins/netbird/restart.sh">
<dl>
    <dt><?= $tr->tr("settings.context.restart"); ?></dt>
    <dd>
        <span><input type="submit" value="<?= $tr->tr('restart'); ?>"></span>
    </dd>
</dl>
</form>

<?php if (file_exists('/usr/local/emhttp/plugins/plugin-diagnostics/download.php')) { ?>
<table class="unraid tablesorter"><thead><tr><td><?= $tr->tr("settings.diagnostics"); ?></td></tr></thead></table>

<form method="GET" action="/plugins/plugin-diagnostics/download.php" target="_blank">
<input type="hidden" name="plugin" value="netbird">
<dl>
    <dt><?= $tr->tr("settings.context.diagnostics"); ?></dt>
    <dd>
        <span><input type="submit" value="<?= $tr->tr('download'); ?> "></span>
    </dd>
</dl>
</form>
<?php } ?>

<div class="advanced">
<table class="unraid tablesorter"><thead><tr><td><?= $tr->tr("settings.erase"); ?></td></tr></thead></table>

<form method="POST" action="/update.php" target="progressFrame">
<input type="hidden" name="#command" value="/usr/local/emhttp/plugins/netbird/erase.sh">
<dl>
    <dt><?= $tr->tr("settings.context.erase"); ?></dt>
    <dd>
        <span>
            <input type="button" value="<?= $tr->tr('erase'); ?>" onclick="requestErase(this)" <?= $nbDisconnect; ?>>
            <input id="netbird_erase_confirm" type="submit" value="<?= $tr->tr('confirm'); ?>" style="display: none;">
        </span>
    </dd>
</dl>
</form>
</div>

<script src="<?= Utils::auto_v('/webGui/javascript/jquery.switchbutton.js');?>"></script>
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

    function requestErase(e) {
        e.disabled = true;
        var confirmButton = document.getElementById('netbird_erase_confirm');
        confirmButton.style.display = "inline";
    }

    $(function() {
        if ($.cookie('netbird_view_mode') == 'advanced') {
            $('.advanced').show();
        } else {
            $('.advanced').hide();
        }

        $('.advancedview').switchButton({
            labels_placement: "left",
            on_label: "<?= $tr->tr("settings.advanced"); ?>",
            off_label: "<?= $tr->tr("settings.basic"); ?>",
            checked: $.cookie('netbird_view_mode') == 'advanced'
        });
        $('.advancedview').change(function(){
            if($('.advancedview').is(':checked')) {
                $('.advanced').show('slow');
            } else {
                $('.advanced').hide('slow');
            }
            $.cookie('netbird_view_mode', $('.advancedview').is(':checked') ? 'advanced' : 'basic', {expires:3650});
        });
    });
</script>
