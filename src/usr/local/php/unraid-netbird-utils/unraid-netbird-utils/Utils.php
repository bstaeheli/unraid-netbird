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

class Utils extends \EDACerton\PluginUtils\Utils
{
    public function setPHPDebug(): void
    {
        $debug = file_exists("/boot/config/plugins/netbird/debug");

        if ($debug && ! defined("PLUGIN_DEBUG")) {
            error_reporting(E_ALL);
            define("PLUGIN_DEBUG", true);
        }
    }

    public static function printRow(string $title, string $value): string
    {
        return "<tr><td>{$title}</td><td>{$value}</td></tr>" . PHP_EOL;
    }

    public static function printDash(string $title, string $value): string
    {
        return "<tr><td><span class='w26'>{$title}</span>{$value}</td></tr>" . PHP_EOL;
    }

    public static function formatWarning(?Warning $warning): string
    {
        if ($warning == null) {
            return "";
        }

        return "<span class='{$warning->Priority}' style='text-align: center; font-size: 1.4em; font-weight: bold;'>" . $warning->Message . "</span>";
    }

    public static function logwrap(string $message, bool $debug = false, bool $rateLimit = false): void
    {
        if ( ! defined(__NAMESPACE__ . "\PLUGIN_NAME")) {
            throw new \RuntimeException("PLUGIN_NAME is not defined.");
        }
        $utils = new Utils(PLUGIN_NAME);
        $utils->logmsg($message, $debug, $rateLimit);
    }

    /**
     * @return array<string>
     */
    public static function runwrap(string $command, bool $alwaysShow = false, bool $show = true): array
    {
        if ( ! defined(__NAMESPACE__ . "\PLUGIN_NAME")) {
            throw new \RuntimeException("PLUGIN_NAME is not defined.");
        }
        $utils = new Utils(PLUGIN_NAME);
        return $utils->run_command($command, $alwaysShow, $show);
    }

    public static function pageChecks(Translator $tr): bool
    {
        static $config = null;
        static $cli    = null;

        if ($config === null) {
            $config = new Config();
        }
        if ($cli === null) {
            $cli = new NetbirdCLI();
        }

        if ( ! $config->Enable) {
            echo($tr->tr("netbird_disabled"));
            return false;
        }

        if ( ! $cli->isReady()) {
            echo($tr->tr("warnings.not_ready"));
            echo(<<<EOT
                <script>
                    $(function() {
                        const reloadKey = 'netbird_not_ready_last_reload';
                        const reloadIntervalMs = 60000;
                        const now = Date.now();
                        const lastReload = Number(sessionStorage.getItem(reloadKey) || 0);

                        // Avoid tight reload loops when tabs are preloaded in the background.
                        if (now - lastReload > reloadIntervalMs) {
                            sessionStorage.setItem(reloadKey, String(now));
                            setTimeout(function() {
                                window.location = window.location.href;
                            }, 5000);
                        }
                    });
                </script>
                EOT
            );
            return false;
        }

        return true;
    }
}
