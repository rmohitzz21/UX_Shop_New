<?php
declare(strict_types=1);

/**
 * Inline SVG icons for the profile dropdown — no external font/icon library required.
 */
function nav_profile_icon(string $name): string
{
    $common = 'class="nav-menu-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';

    return match ($name) {
        'user' => "<svg {$common}><path d=\"M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2\"></path><circle cx=\"12\" cy=\"7\" r=\"4\"></circle></svg>",
        'orders' => "<svg {$common}><path d=\"M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z\"></path><polyline points=\"3.27 6.96 12 12.01 20.73 6.96\"></polyline><line x1=\"12\" y1=\"22.08\" x2=\"12\" y2=\"12\"></line></svg>",
        'logout' => "<svg {$common}><path d=\"M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4\"></path><polyline points=\"16 17 21 12 16 7\"></polyline><line x1=\"21\" y1=\"12\" x2=\"9\" y2=\"12\"></line></svg>",
        default => '',
    };
}

function nav_profile_dropdown_html(): string
{
    return '<a href="account.php" role="menuitem">'
        . nav_profile_icon('user')
        . ' Edit Profile</a>'
        . '<a href="orders.php" role="menuitem">'
        . nav_profile_icon('orders')
        . ' My Orders</a>'
        . '<button type="button" role="menuitem" onclick="handleSignOut()">'
        . nav_profile_icon('logout')
        . ' Logout</button>';
}
