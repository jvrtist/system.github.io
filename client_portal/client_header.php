<?php
// client_portal/client_header.php
if (session_status() == PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config.php';
}

// Ensure client is logged in for any page including this header (double safety)
// However, some pages might include this without auth (unlikely in this structure), so we rely on the page's require_once 'client_auth.php'
// But for UI rendering, we need session vars.

$client_name_display = isset($_SESSION[CLIENT_NAME_SESSION_VAR]) ? htmlspecialchars($_SESSION[CLIENT_NAME_SESSION_VAR]) : 'Client';
$base_url = defined('BASE_URL') ? BASE_URL : '/';
$page = basename($_SERVER['PHP_SELF']);

function is_client_nav_active($nav_page_names) {
    global $page;
    if (is_array($nav_page_names)) {
        return in_array($page, $nav_page_names);
    }
    return $page === $nav_page_names;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo (isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '') . SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind = {
            config: {
                theme: {
                    extend: {
                        colors: {
                            primary: '#ea580c', // Orange-600
                            primaryHover: '#dc2626', // Red-600 for hover
                            secondary: '#0f172a', // Slate-900
                            accent: '#1e293b', // Slate-800
                            'gradient-primary': 'linear-gradient(135deg, #ea580c 0%, #dc2626 100%)',
                            'gradient-secondary': 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)',
                            'gradient-accent': 'linear-gradient(135deg, #f59e0b 0%, #ea580c 100%)'
                        },
                        fontFamily: {
                            sans: ['Inter', 'sans-serif'],
                            mono: ['JetBrains Mono', 'monospace']
                        },
                        boxShadow: {
                            'glow': '0 0 20px rgba(234, 88, 12, 0.3)',
                            'glow-lg': '0 0 30px rgba(234, 88, 12, 0.4)',
                            'card': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                            'card-hover': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                            'elevated': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
                        },
                        animation: {
                            'fade-in-up': 'fadeInUp 0.6s ease-out',
                            'float': 'float 3s ease-in-out infinite',
                            'bounce-subtle': 'bounceSubtle 2s infinite',
                            'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                            'scale-in': 'scaleIn 0.3s ease-out'
                        },
                        keyframes: {
                            fadeInUp: {
                                '0%': { opacity: '0', transform: 'translateY(30px)' },
                                '100%': { opacity: '1', transform: 'translateY(0)' }
                            },
                            float: {
                                '0%, 100%': { transform: 'translateY(0px)' },
                                '50%': { transform: 'translateY(-10px)' }
                            },
                            bounceSubtle: {
                                '0%, 100%': { transform: 'translateY(0)' },
                                '50%': { transform: 'translateY(-2px)' }
                            },
                            scaleIn: {
                                '0%': { transform: 'scale(0.9)', opacity: '0' },
                                '100%': { transform: 'scale(1)', opacity: '1' }
                            }
                        }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style> 
        body { font-family: 'Inter', sans-serif; }
        
        /* Premium Button Styles */
        .btn-primary {
            @apply bg-gradient-to-r from-primary to-red-600 hover:from-red-600 hover:to-primary text-white font-black py-3 px-6 rounded-full shadow-glow hover:shadow-glow-lg transform hover:scale-105 transition-all duration-300;
        }
        
        /* Premium Card Styles */
        .card-premium {
            @apply bg-white rounded-3xl shadow-card hover:shadow-card-hover transition-all duration-500 border border-slate-100;
        }
        
        /* Text Shadow for better readability */
        .text-shadow { text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .text-shadow-lg { text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        
        /* Gradient backgrounds */
        .bg-gradient-primary { background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%); }
        .bg-gradient-secondary { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
        .bg-gradient-accent { background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); }
        
        /* Smooth transitions */
        .transition-all { transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 200ms; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Line clamping for text truncation */
        .line-clamp-1 {
            overflow: hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 1;
        }
        .line-clamp-2 {
            overflow: hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        /* Print Styles */
        @media print {
            @page { margin: 0.5in; }
            body { background-color: #fff !important; color: #000 !important; font-size: 12pt; line-height: 1.4; }
            .no-print, nav, footer, .tab-button, .sidebar-summary, .print-button-container, #mobile-menu-btn, #sendMessageModal { display: none !important; }
            main, .container { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
            .print-header { display: block !important; text-align: center; margin-bottom: 2rem; border-bottom: 2px solid #000; padding-bottom: 1rem; }
            .printable-content { box-shadow: none !important; border: none !important; padding: 0 !important; }
            .tab-content { display: block !important; page-break-before: always; margin-top: 1rem; }
            .tab-content:first-of-type { page-break-before: avoid; }
            a { text-decoration: none; color: #000 !important; }
            h1, h2, h3 { page-break-after: avoid; color: #000 !important; }
            .page-break { page-break-before: always; }
            .print-footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10pt; color: #666; border-top: 1px solid #ccc; padding: 0.5rem; }
        }
    </style>
</head>
<body class="flex flex-col min-h-screen text-slate-800 bg-slate-50">
    
    <!-- Top Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-slate-200 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="<?php echo $base_url; ?>client_portal/dashboard.php" class="flex items-center gap-2 group">
                            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center text-white shadow-lg shadow-primary/30 group-hover:scale-105 transition-transform duration-300">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <span class="font-bold text-xl text-slate-800 tracking-tight">Client<span class="text-primary">Portal</span></span>
                        </a>
                    </div>
                    <div class="hidden md:ml-8 md:flex md:space-x-8">
                        <a href="<?php echo $base_url; ?>client_portal/dashboard.php" 
                           class="<?php echo is_client_nav_active('dashboard.php') ? 'border-primary text-primary bg-gradient-to-r from-primary/5 to-red-500/5' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 hover:bg-slate-50'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-300">
                            Dashboard
                        </a>
                        <a href="<?php echo $base_url; ?>client_portal/cases.php" 
                           class="<?php echo is_client_nav_active(['cases.php', 'view_case_details.php']) ? 'border-primary text-primary bg-gradient-to-r from-primary/5 to-red-500/5' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 hover:bg-slate-50'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-300">
                            My Cases
                        </a>
                        <a href="<?php echo $base_url; ?>client_portal/invoices.php" 
                           class="<?php echo is_client_nav_active(['invoices.php', 'view_invoice.php']) ? 'border-primary text-primary bg-gradient-to-r from-primary/5 to-red-500/5' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 hover:bg-slate-50'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-300">
                            Invoices
                        </a>
                        <a href="<?php echo $base_url; ?>client_portal/posts.php" 
                           class="<?php echo is_client_nav_active(['posts.php', 'view_post.php']) ? 'border-primary text-primary bg-gradient-to-r from-primary/5 to-red-500/5' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 hover:bg-slate-50'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition-all duration-300">
                            News & Updates
                        </a>
                    </div>
                </div>
                <div class="hidden md:flex items-center">
                    <div class="ml-3 relative flex items-center gap-4">
                        <span class="text-sm font-medium text-slate-600">Welcome, <span class="text-slate-900"><?php echo $client_name_display; ?></span></span>
                        <div class="h-6 w-px bg-slate-200"></div>
                        <a href="<?php echo $base_url; ?>client_portal/my_account.php" class="text-slate-400 hover:text-primary transition-colors" title="My Profile">
                            <i class="fas fa-user-circle fa-lg"></i>
                        </a>
                        <a href="<?php echo $base_url; ?>client_logout.php" class="text-slate-400 hover:text-red-500 transition-colors" title="Sign Out">
                            <i class="fas fa-sign-out-alt fa-lg"></i>
                        </a>
                    </div>
                </div>
                <div class="-mr-2 flex items-center md:hidden">
                    <button type="button" id="mobile-menu-btn" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-slate-500 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="md:hidden hidden bg-white border-t border-slate-100" id="mobile-menu">
            <div class="pt-2 pb-3 space-y-1">
                <a href="<?php echo $base_url; ?>client_portal/dashboard.php" class="<?php echo is_client_nav_active('dashboard.php') ? 'bg-gradient-to-r from-primary/10 to-red-500/10 border-primary text-primary' : 'border-transparent text-slate-600 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-semibold transition-all duration-300">Dashboard</a>
                <a href="<?php echo $base_url; ?>client_portal/cases.php" class="<?php echo is_client_nav_active(['cases.php', 'view_case_details.php']) ? 'bg-gradient-to-r from-primary/10 to-red-500/10 border-primary text-primary' : 'border-transparent text-slate-600 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-semibold transition-all duration-300">My Cases</a>
                <a href="<?php echo $base_url; ?>client_portal/invoices.php" class="<?php echo is_client_nav_active(['invoices.php', 'view_invoice.php']) ? 'bg-gradient-to-r from-primary/10 to-red-500/10 border-primary text-primary' : 'border-transparent text-slate-600 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-semibold transition-all duration-300">Invoices</a>
                <a href="<?php echo $base_url; ?>client_portal/posts.php" class="<?php echo is_client_nav_active(['posts.php', 'view_post.php']) ? 'bg-gradient-to-r from-primary/10 to-red-500/10 border-primary text-primary' : 'border-transparent text-slate-600 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-semibold transition-all duration-300">News</a>
            </div>
            <div class="pt-4 pb-4 border-t border-slate-200">
                <div class="flex items-center px-4">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-primary font-bold">
                            <?php echo strtoupper(substr($client_name_display, 0, 1)); ?>
                        </div>
                    </div>
                    <div class="ml-3">
                        <div class="text-base font-medium text-slate-800"><?php echo $client_name_display; ?></div>
                        <div class="text-sm font-medium text-slate-500">Client Account</div>
                    </div>
                </div>
                <div class="mt-3 space-y-1">
                    <a href="<?php echo $base_url; ?>client_portal/my_account.php" class="block px-4 py-2 text-base font-medium text-slate-500 hover:text-slate-800 hover:bg-slate-100">Your Profile</a>
                    <a href="<?php echo $base_url; ?>client_logout.php" class="block px-4 py-2 text-base font-medium text-red-500 hover:text-red-700 hover:bg-red-50">Sign out</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuButton = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');

            if (menuButton && mobileMenu) {
                menuButton.addEventListener('click', function() {
                    const isExpanded = menuButton.getAttribute('aria-expanded') === 'true';
                    menuButton.setAttribute('aria-expanded', !isExpanded);
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>
