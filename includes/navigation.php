<?php
/**
 * ISS Investigations - Command Sidebar Navigation
 * Modern operational interface with role-based access control.
 * Enhanced for efficiency, security, and user experience.
 */

// Security and session validation
if (session_status() == PHP_SESSION_NONE) {
    if (file_exists(__DIR__ . '/../config.php')) {
        require_once __DIR__ . '/../config.php';
    } else {
        die("Critical System Error: Configuration Node Not Found.");
    }
}

// Exit if not logged in
if (!is_logged_in()) {
    return;
}

// Current page detection for active states
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// User information
$user_role = $_SESSION['user_role'] ?? 'Investigator';
$user_full_name = $_SESSION['full_name'] ?? 'Authorized User';
$user_id = $_SESSION['user_id'] ?? 0;

/**
 * Intelligent Navigation Highlighter
 * Determines active navigation states based on current page and directory
 */
function isActiveNav($link_page, $link_dir = null) {
    global $current_page, $current_dir;

    // Handle different link formats
    if (strpos($link_page, '/') !== false) {
        // Handle directory-based links (e.g., "clients/")
        $link_parts = explode('/', trim($link_page, '/'));
        $target_page = $link_parts[count($link_parts) - 1] ?: 'index.php';
        $target_dir = $link_parts[0];
    } else {
        // Handle direct file links
        $target_page = $link_page;
        $target_dir = $link_dir;
    }

    // Normalize page names
    $current_base = basename($current_page, '.php');
    $target_base = basename($target_page, '.php');

    $is_active = ($current_base === $target_base);

    // Directory-specific matching
    if ($target_dir !== null) {
        $is_active = $is_active && ($target_dir === $current_dir);
    }

    // Special case for dashboard
    if ($target_page === 'dashboard.php' && in_array($current_dir, ['iss', 'public_html', 'system'])) {
        return true;
    }

    return $is_active;
}

/**
 * Check if user has permission for admin features
 */
function canAccessAdmin() {
    return user_has_role('admin');
}
?>

<!-- Command Sidebar Navigation -->
<aside id="sidebar" class="bg-secondary text-slate-400 w-64 fixed inset-y-0 left-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-50 shadow-2xl border-r border-white/5 flex flex-col no-print">

    <!-- Header Section -->
    <div class="h-20 px-6 border-b border-white/5 flex items-center flex-shrink-0">
        <a href="<?= BASE_URL; ?>dashboard.php" class="flex items-center gap-3 group">
            <div class="group-hover:scale-110 transition-transform duration-300">
                <img src="images/logo.png" alt="ISS Investigations Lion Logo" class="h-8 w-8 object-contain" title="ISS Investigations - Secure Admin">
            </div>
            <div class="flex flex-col">
                <span class="text-white font-black tracking-tighter text-sm uppercase leading-none">ISS <span class="text-primary font-light">Node</span></span>
                <span class="text-[8px] font-bold text-slate-500 tracking-[0.2em] uppercase mt-1">Operational UI</span>
            </div>
        </a>
    </div>

    <!-- Navigation Content -->
    <nav class="flex-grow overflow-y-auto py-6 px-4 space-y-8 custom-scrollbar">

        <!-- Briefing Section -->
        <div>
            <h4 class="px-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 mb-4">Briefing</h4>
            <ul class="space-y-1">
                <li>
                    <a href="<?= BASE_URL; ?>dashboard.php"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('dashboard.php') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-grid-2 fa-fw <?= isActiveNav('dashboard.php') ? '' : 'text-primary'; ?>"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL; ?>admin/search.php"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('search.php', 'admin') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-search fa-fw <?= isActiveNav('search.php', 'admin') ? '' : 'text-primary'; ?>"></i>
                        <span>Global Search</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Operations Section -->
        <div>
            <h4 class="px-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 mb-4">Operations</h4>
            <ul class="space-y-1">
                <li>
                    <a href="<?= BASE_URL; ?>clients/"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('clients/') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-user-secret fa-fw <?= isActiveNav('clients/') ? '' : 'text-primary'; ?>"></i>
                        <span>Clients</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL; ?>cases/"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('cases/') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-folder-open fa-fw <?= isActiveNav('cases/') ? '' : 'text-primary'; ?>"></i>
                        <span>Active Cases</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL; ?>tasks/"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('tasks/') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-list-check fa-fw <?= isActiveNav('tasks/') ? '' : 'text-primary'; ?>"></i>
                        <span>Task Desk</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL; ?>reports/"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('reports/') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-file-signature fa-fw <?= isActiveNav('reports/') ? '' : 'text-primary'; ?>"></i>
                        <span>Case Reports</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Financials Section -->
        <div>
            <h4 class="px-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 mb-4">Financials</h4>
            <ul class="space-y-1">
                <li>
                    <a href="<?= BASE_URL; ?>invoices/"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('invoices/') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-receipt fa-fw <?= isActiveNav('invoices/') ? '' : 'text-primary'; ?>"></i>
                        <span>Invoices</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL; ?>admin/posts/"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('admin/posts/') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-rss fa-fw <?= isActiveNav('admin/posts/') ? '' : 'text-primary'; ?>"></i>
                        <span>Public Intel</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL; ?>admin/reviews.php"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('reviews.php', 'admin') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-star fa-fw <?= isActiveNav('reviews.php', 'admin') ? '' : 'text-primary'; ?>"></i>
                        <span>Client Reviews</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- System Root Section (Admin Only) -->
        <?php if (canAccessAdmin()): ?>
        <div class="pt-4">
            <h4 class="px-4 text-[10px] font-black uppercase tracking-[0.2em] text-red-500 mb-4">System Root</h4>
            <ul class="space-y-1">
                <li>
                    <a href="<?= BASE_URL; ?>admin/users/"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('admin/users/') ? 'bg-slate-800 text-white border border-white/5' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-users-gear fa-fw text-slate-500"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL; ?>admin/audit_log.php"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('audit_log.php', 'admin') ? 'bg-slate-800 text-white border border-white/5' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-terminal fa-fw text-slate-500"></i>
                        <span>Audit Log</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL; ?>admin/settings.php"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('settings.php', 'admin') ? 'bg-slate-800 text-white border border-white/5' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-cogs fa-fw text-slate-500"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL; ?>admin/analytics.php"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('analytics.php', 'admin') ? 'bg-slate-800 text-white border border-white/5' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-chart-pie fa-fw text-slate-500"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL; ?>admin/bulk_operations.php"
                       class="flex items-center gap-3 py-3 px-4 rounded-xl text-xs font-bold tracking-wide transition-all
                              <?= isActiveNav('bulk_operations.php', 'admin') ? 'bg-slate-800 text-white border border-white/5' : 'hover:bg-white/5 hover:text-white'; ?>">
                        <i class="fas fa-layer-group fa-fw text-slate-500"></i>
                        <span>Bulk Operations</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

    </nav>

    <!-- User Profile Section -->
    <div class="p-4 bg-black/20 border-t border-white/5 mt-auto">
        <div class="flex items-center gap-3 px-2 mb-4">
            <div class="relative">
                <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center text-primary font-black border border-white/10">
                    <?= strtoupper(substr($user_full_name, 0, 1)); ?>
                </div>
                <span class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 border-2 border-secondary rounded-full" title="Active Session"></span>
            </div>
            <div class="overflow-hidden">
                <p class="text-xs font-black text-white truncate" title="<?= htmlspecialchars($user_full_name); ?>">
                    <?= htmlspecialchars($user_full_name); ?>
                </p>
                <p class="text-[9px] tech-mono text-slate-500 uppercase tracking-tighter truncate" title="<?= htmlspecialchars($user_role); ?>">
                    <?= htmlspecialchars($user_role); ?>
                </p>
            </div>
        </div>
        <a href="<?= BASE_URL; ?>logout.php"
           class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white text-[10px] font-black uppercase tracking-widest transition-all"
           title="Secure Logout">
            <i class="fas fa-power-off"></i>
            <span>Secure Logout</span>
        </a>
    </div>

</aside>

<!-- Mobile Header -->
<header id="mobile-header" class="md:hidden bg-secondary text-white h-16 px-4 shadow-xl sticky top-0 z-[60] flex items-center justify-between no-print border-b border-white/5">
    <button id="sidebarToggle" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white/5 text-primary" aria-label="Toggle navigation menu">
        <i class="fas fa-bars-staggered"></i>
    </button>
    <a href="<?= BASE_URL; ?>dashboard.php" class="flex items-center gap-2">
        <img src="images/logo.png" alt="ISS Investigations Lion Logo" class="h-6 w-6 object-contain">
        <span class="text-xs font-black uppercase tracking-[0.3em]">ISS <span class="text-primary">Node</span></span>
    </a>
    <div class="flex items-center gap-2">
        <!-- Notifications Button -->
        <button id="notificationsToggle" class="relative w-10 h-10 flex items-center justify-center rounded-lg bg-white/5 text-primary" aria-label="Notifications">
            <i class="fas fa-bell"></i>
            <span id="notificationBadge" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center hidden">0</span>
        </button>
        <button id="searchToggle" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white/5 text-primary" aria-label="Global search">
            <i class="fas fa-search"></i>
        </button>
    </div>
</header>

<!-- Mobile Overlay -->
<div id="sidebar-overlay" class="md:hidden fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 hidden no-print" aria-hidden="true"></div>

<!-- Search Overlay -->
<div id="search-overlay" class="fixed inset-0 bg-slate-950/95 backdrop-blur-sm z-50 hidden no-print">
    <div class="flex items-center justify-center h-full p-4">
        <div class="bg-secondary rounded-2xl border border-white/5 shadow-2xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-white">Global Search</h3>
                <button id="closeSearch" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/5 text-slate-400 hover:text-white transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="GET" action="admin/search.php">
                <div class="space-y-4">
                    <div>
                        <input type="text" name="q" placeholder="Search cases, clients, posts, invoices..."
                               class="w-full bg-slate-800 border border-white/10 rounded-lg px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50"
                               required minlength="2">
                    </div>
                    <select name="type" class="w-full bg-slate-800 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50">
                        <option value="all">All Content</option>
                        <option value="cases">Cases</option>
                        <option value="clients">Clients</option>
                        <option value="posts">Posts</option>
                        <option value="invoices">Invoices</option>
                        <option value="tasks">Tasks</option>
                    </select>
                    <button type="submit" class="w-full bg-primary hover:bg-orange-600 text-white font-semibold py-3 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                </div>
            </form>
            <div class="mt-6 pt-4 border-t border-white/5">
                <p class="text-xs text-slate-400 text-center">Quick search across all system data</p>
            </div>
        </div>
    </div>
</div>

<!-- Notifications Dropdown -->
<div id="notifications-dropdown" class="absolute top-16 right-4 md:right-20 w-80 bg-secondary border border-white/5 rounded-2xl shadow-2xl z-50 hidden no-print max-h-96 overflow-hidden">
    <div class="p-4 border-b border-white/5">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-white">Notifications</h3>
            <div class="flex items-center gap-2">
                <button id="markAllRead" class="text-[10px] text-slate-400 hover:text-white transition-colors">Mark all read</button>
                <button id="closeNotifications" class="w-6 h-6 flex items-center justify-center rounded bg-white/5 text-slate-400 hover:text-white transition-colors">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="notificationsList" class="max-h-80 overflow-y-auto">
        <!-- Notifications will be loaded here -->
        <div class="p-4 text-center text-slate-500">
            <i class="fas fa-bell-slash text-2xl mb-2"></i>
            <p class="text-sm">No notifications</p>
        </div>
    </div>
    <div class="p-3 border-t border-white/5 bg-slate-900/50">
        <a href="#" class="text-xs text-primary hover:text-orange-400 transition-colors">View all notifications</a>
    </div>
</div>

<!-- Navigation Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const searchToggle = document.getElementById('searchToggle');
    const searchOverlay = document.getElementById('search-overlay');
    const closeSearch = document.getElementById('closeSearch');
    const notificationsToggle = document.getElementById('notificationsToggle');
    const notificationsDropdown = document.getElementById('notifications-dropdown');
    const closeNotifications = document.getElementById('closeNotifications');
    const markAllRead = document.getElementById('markAllRead');
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationsList = document.getElementById('notificationsList');

    if (!sidebar || !sidebarToggle || !sidebarOverlay) {
        console.warn('Navigation elements not found');
        return;
    }

    const toggleSidebar = (show) => {
        if (show) {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
            sidebarToggle.setAttribute('aria-expanded', 'true');
        } else {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
            sidebarToggle.setAttribute('aria-expanded', 'false');
        }
    };

    const toggleSearch = (show) => {
        if (show) {
            searchOverlay.classList.remove('hidden');
            // Focus on search input when opened
            const searchInput = searchOverlay.querySelector('input[name="q"]');
            if (searchInput) {
                setTimeout(() => searchInput.focus(), 100);
            }
        } else {
            searchOverlay.classList.add('hidden');
        }
    };

    const toggleNotifications = (show) => {
        if (show) {
            notificationsDropdown.classList.remove('hidden');
            loadNotifications();
        } else {
            notificationsDropdown.classList.add('hidden');
        }
    };

    // Load notifications from API
    const loadNotifications = async () => {
        try {
            const response = await fetch('api/notifications.php?limit=10');
            const data = await response.json();

            if (data.success) {
                updateNotificationBadge(data.unread_count);
                renderNotifications(data.notifications);
            }
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    };

    // Update notification badge
    const updateNotificationBadge = (count) => {
        if (count > 0) {
            notificationBadge.textContent = count > 99 ? '99+' : count;
            notificationBadge.classList.remove('hidden');
        } else {
            notificationBadge.classList.add('hidden');
        }
    };

    // Render notifications in dropdown
    const renderNotifications = (notifications) => {
        if (notifications.length === 0) {
            notificationsList.innerHTML = `
                <div class="p-4 text-center text-slate-500">
                    <i class="fas fa-bell-slash text-2xl mb-2"></i>
                    <p class="text-sm">No notifications</p>
                </div>
            `;
            return;
        }

        const html = notifications.map(notification => {
            const typeColors = {
                'info': 'bg-blue-500/10 border-blue-500/20 text-blue-400',
                'warning': 'bg-yellow-500/10 border-yellow-500/20 text-yellow-400',
                'error': 'bg-red-500/10 border-red-500/20 text-red-400',
                'success': 'bg-green-500/10 border-green-500/20 text-green-400'
            };

            const priorityIcon = {
                'urgent': 'fas fa-exclamation-triangle',
                'high': 'fas fa-exclamation-circle',
                'medium': 'fas fa-info-circle',
                'low': 'fas fa-info'
            };

            return `
                <div class="p-4 border-b border-white/5 hover:bg-white/[0.02] transition-colors ${!notification.is_read ? 'bg-blue-500/5' : ''}" data-id="${notification.notification_id}">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-lg ${typeColors[notification.type] || typeColors['info']} flex items-center justify-center">
                                <i class="${priorityIcon[notification.priority] || priorityIcon['medium']} text-xs"></i>
                            </div>
                        </div>
                        <div class="flex-grow min-w-0">
                            <h4 class="text-sm font-bold text-white truncate">${notification.title}</h4>
                            <p class="text-xs text-slate-400 mt-1 line-clamp-2">${notification.message}</p>
                            <p class="text-[10px] text-slate-500 mt-2">${formatTimeAgo(notification.created_at)}</p>
                        </div>
                        ${!notification.is_read ? '<div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2"></div>' : ''}
                    </div>
                </div>
            `;
        }).join('');

        notificationsList.innerHTML = html;
    };

    // Format time ago
    const formatTimeAgo = (dateString) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        return date.toLocaleDateString();
    };

    // Sidebar toggle functionality
    sidebarToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = sidebar.classList.contains('-translate-x-full');
        toggleSidebar(isHidden);
        toggleNotifications(false); // Close notifications when opening sidebar
    });

    sidebarOverlay.addEventListener('click', () => toggleSidebar(false));

    // Search toggle functionality
    if (searchToggle) {
        searchToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleSearch(true);
            toggleNotifications(false); // Close notifications when opening search
        });
    }

    if (closeSearch) {
        closeSearch.addEventListener('click', () => toggleSearch(false));
    }

    if (searchOverlay) {
        searchOverlay.addEventListener('click', (e) => {
            if (e.target === searchOverlay) {
                toggleSearch(false);
            }
        });
    }

    // Notifications toggle functionality
    if (notificationsToggle) {
        notificationsToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = notificationsDropdown.classList.contains('hidden');
            toggleNotifications(isHidden);
            toggleSearch(false); // Close search when opening notifications
        });
    }

    if (closeNotifications) {
        closeNotifications.addEventListener('click', () => toggleNotifications(false));
    }

    if (markAllRead) {
        markAllRead.addEventListener('click', async () => {
            try {
                const response = await fetch('api/notifications.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mark_all_read: true })
                });
                const data = await response.json();
                if (data.success) {
                    loadNotifications();
                }
            } catch (error) {
                console.error('Failed to mark notifications as read:', error);
            }
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!notificationsDropdown.contains(e.target) && e.target !== notificationsToggle) {
            toggleNotifications(false);
        }
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (!sidebar.classList.contains('-translate-x-full')) {
                toggleSidebar(false);
            }
            if (searchOverlay && !searchOverlay.classList.contains('hidden')) {
                toggleSearch(false);
            }
            if (notificationsDropdown && !notificationsDropdown.classList.contains('hidden')) {
                toggleNotifications(false);
            }
        }
    });

    // Close search on form submission
    const searchForm = searchOverlay ? searchOverlay.querySelector('form') : null;
    if (searchForm) {
        searchForm.addEventListener('submit', () => {
            toggleSearch(false);
        });
    }

    // Load notifications on page load
    loadNotifications();

    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);
});
</script>