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

/**
 * Wraps the netbird CLI binary to retrieve status and control the daemon.
 *
 * NetBird does not expose a local HTTP API socket like Tailscale; instead it
 * communicates via its CLI tool.  The daemon must already be running (started
 * by rc.netbird) before any of these methods are called.
 */
class NetbirdCLI
{
    private const BINARY = '/usr/local/sbin/netbird';
    private Utils $utils;

    public function __construct()
    {
        if ( ! defined(__NAMESPACE__ . "\PLUGIN_ROOT") || ! defined(__NAMESPACE__ . "\PLUGIN_NAME")) {
            throw new \RuntimeException("Common file not loaded.");
        }
        $this->utils = new Utils(PLUGIN_NAME);
    }

    private function run(string $args): string
    {
        $cmd    = self::BINARY . ' ' . $args . ' 2>&1';
        $output = shell_exec($cmd);
        return is_string($output) ? $output : '';
    }

    /**
     * Returns true when the daemon process is running and reports a usable state.
     */
    public function isReady(): bool
    {
        // Rely on status output rather than process grep; the WebGUI context may
        // not always see the process list even when the daemon is reachable.
        try {
            $status = $this->getStatus();
            if (isset($status['daemonState']) && is_string($status['daemonState'])) {
                return true;
            }

            // Fallback for schema variations: any non-empty decoded status means
            // the CLI can talk to the daemon, so the backend is ready.
            return ! empty($status);
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    /**
     * Returns the parsed JSON output of `netbird status --json`.
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        $output = $this->run('status --json');

        if (empty($output)) {
            throw new \RuntimeException("netbird status returned empty output.");
        }

        $decoded = json_decode($output, true);

        if ( ! is_array($decoded)) {
            throw new \RuntimeException("Failed to decode netbird status JSON: " . json_last_error_msg());
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * Returns the parsed status or an empty array on failure.
     *
     * @return array<string, mixed>
     */
    public function getStatusSafe(): array
    {
        try {
            return $this->getStatus();
        } catch (\RuntimeException $e) {
            $this->utils->logmsg("Failed to get netbird status: " . $e->getMessage());
            return array();
        }
    }

    /**
     * Connects NetBird to the management server.
     * If a setup key is provided it is used for automatic authentication.
     * Otherwise, the user must authenticate via the browser using the returned
     * auth URL.
     */
    public function up(
        string $managementURL,
        string $setupKey = '',
        bool $allowServerSSH = false,
        bool $enableSSHRoot = false
    ): void
    {
        $args = 'up --management-url ' . escapeshellarg($managementURL);

        if ( ! empty($setupKey)) {
            $args .= ' --setup-key ' . escapeshellarg($setupKey);
        }

        if ($allowServerSSH) {
            $args .= ' --allow-server-ssh';

            if ($enableSSHRoot) {
                $args .= ' --enable-ssh-root';
            }
        }

        $this->utils->logmsg("Running: netbird {$args}");
        $this->run($args);
    }

    /**
     * Disconnects NetBird from the network (stops the WireGuard tunnel).
     */
    public function down(): void
    {
        $this->utils->logmsg("Running: netbird down");
        $this->run('down');
    }

    /**
     * Returns the installed NetBird version string.
     */
    public function version(): string
    {
        $output = trim($this->run('version'));
        return $output ?: 'unknown';
    }

    /**
     * Returns the authentication URL when the daemon is in NeedLogin state.
     * Returns an empty string if no auth URL is available.
     */
    public function getAuthURL(): string
    {
        $status = $this->getStatusSafe();
        $url    = $status['authURL'] ?? null;
        return is_string($url) ? $url : '';
    }

    /**
     * Triggers re-authentication (login) using the configured management URL.
     * Returns the auth URL the user should open in a browser.
     */
    public function login(string $managementURL): string
    {
        $args   = 'login --management-url ' . escapeshellarg($managementURL);
        $output = $this->run($args);

        // The output may contain a URL — extract it
        if (preg_match('/(https?:\/\/\S+)/', $output, $matches)) {
            return $matches[1];
        }

        // Try to get the auth URL from daemon status
        return $this->getAuthURL();
    }
}
