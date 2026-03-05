# unraid-netbird

Unraid plugin to integrate [NetBird](https://netbird.io) — an open-source WireGuard-based mesh VPN — directly into Unraid OS.

This plugin is modelled closely on the official [unraid-tailscale](https://github.com/unraid/unraid-tailscale) plugin.

## Features

- Run the NetBird daemon directly on Unraid OS (no Docker container required)
- Connect to the NetBird cloud or a self-hosted management server
- Support for automatic registration via setup keys
- Browser-based login flow for interactive authentication
- WebGUI tabs for connection status, peer list, and diagnostic info
- Dashboard widget
- Unraid services (WebGUI, SSH, SMB) listen on the NetBird `wt0` interface
- Automatic IP forwarding configuration
- Add NetBird peers to `/etc/hosts` for DNS resolution
- Log rotation for NetBird logs

## Requirements

- Unraid OS 7.0.0 or later

## Installation

> **Before publishing:** You must update the `plugin/netbird.plg` file with the correct GitHub repository URL, binary SHA256, and package SHA256. See the [Build](#build) section.

Install via Unraid's Plugin Manager using the raw URL of `plugin/netbird.plg` from your GitHub repository.

## Configuration

After installation, go to **Settings → NetBird**:

| Setting | Default | Description |
|---------|---------|-------------|
| Enable NetBird | Yes | Enable/disable the daemon |
| Unraid listens on NetBird interface | Yes | Makes WebGUI/SSH/SMB accessible over the NetBird network |
| Enable IP forwarding | Yes | Required for routing traffic through this host |
| Management Server URL | `https://api.netbird.io:443` | Use default for NetBird cloud or enter your self-hosted server |
| Setup Key | *(empty)* | For automatic registration; leave empty to authenticate via browser |
| Admin Dashboard URL | `https://app.netbird.io` | URL for the admin dashboard link |
| Add peers to /etc/hosts | No | Adds NetBird peer FQDNs to `/etc/hosts` for DNS resolution |

## Authentication

After installing and configuring the management server URL:

1. Go to **Settings → NetBird → Connection tab**
2. Click **Connect**
3. If a Setup Key is configured, the daemon connects automatically
4. Otherwise, an authentication URL appears — open it in your browser to complete login

## Architecture

The plugin follows the same dual-process model as the Tailscale plugin:

- **`rc.netbird`**: SysV-style init script that starts/stops the daemon and the watcher
- **`netbird service run`**: The NetBird daemon, runs in the background, state stored in `/boot/config/plugins/netbird/config.json`
- **`netbird-watcher.php`**: Background process that polls the `wt0` interface and applies Unraid-specific integration (nginx restart, hosts file, GRO offloading)
- **`pre-startup.php`**: Runs before daemon startup to apply system configuration (IP forwarding, GRO, interface inclusion)
- **`daily.sh`**: Daily cron job for health checks

## Build

To build and release:

1. Update `plugin/plugin.json` with the current NetBird version
2. Download the NetBird binary and compute its SHA256:
   ```sh
   curl -L https://github.com/netbirdio/netbird/releases/download/v0.36.4/netbird_0.36.4_linux_amd64.tar.gz | sha256sum
   ```
3. Update the SHA256 in `plugin/plugin.json`
4. Build the `.txz` package from `src/`
5. Compute the package SHA256
6. Generate `netbird.plg` from `plugin/plugin.j2` with the CI environment variables

## License

GPL-3.0-or-later
