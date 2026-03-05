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

?>
<table class="unraid t1">
    <thead>
        <tr>
            <td>Error</td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <p>An error occurred while rendering the NetBird plugin page.</p>
                <?php if (defined("PLUGIN_DEBUG") && PLUGIN_DEBUG && isset($e)) { ?>
                <pre><?= htmlspecialchars(print_r($e, true)); ?></pre>
                <?php } else { ?>
                <p>Enable debug mode by creating <code>/boot/config/plugins/netbird/debug</code> for more details.</p>
                <?php } ?>
            </td>
        </tr>
    </tbody>
</table>
