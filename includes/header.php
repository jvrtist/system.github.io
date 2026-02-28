<?php
/**
 * ISS Investigations - Internal Command Header
 * Secured environment for case management and administrative operations.
 */
if (session_status() == PHP_SESSION_NONE) {
    if (file_exists(__DIR__ . '/../config.php')) {
        require_once __DIR__ . '/../config.php';
    } else {
        die("Critical System Error: Configuration Node Not Found.");
    }
}

// Access Control Logic
$current_page = basename($_SERVER['PHP_SELF']);
$public_pages = ['login.php', 'client_login.php', 'client_forgot_password.php', 'client_reset_password.php'];

if (!in_array($current_page, $public_pages) && !is_logged_in() && !isset($_SESSION[CLIENT_SESSION_VAR])) {
    $_SESSION['error_message'] = "Unauthorized access. Please authenticate.";
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' | ' : ''; ?>ISS Command</title>
     <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind = {
            config: {
                theme: {
                    extend: {
                        colors: {
                            primary: '#ff8800',
                            secondary: '#0f172a',
                            accent: '#334155',
                        },
                        fontFamily: {
                            sans: ['Inter', 'sans-serif'],
                            mono: ['JetBrains Mono', 'monospace'],
                        },
                    }
                }
            }
        }
    </script>
    
   
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tiny.cloud/1/1p1ugb7n2ixlpx7kj0y35h39tl05a41mzkai254fnd1orqe7/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

    <style>
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .tech-mono { font-family: 'JetBrains Mono', monospace; }

        /* Tooltip refinement */
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 140px;
            background-color: #0f172a;
            color: #fff;
            text-align: center;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 8px;
            position: absolute;
            z-index: 50;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: all 0.2s ease;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .tooltip:hover .tooltiptext { visibility: visible; opacity: 1; bottom: 140%; }

        /* Custom Scrollbar for the Dark UI */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #020617; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #f78f18; }

        /* CKEditor ISS Investigations Brand Styles */
        .investigation-highlight {
            background: linear-gradient(135deg, #f78f18 0%, #ff6b35 100%);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(247, 143, 24, 0.3);
        }

        .case-study-quote {
            border-left: 4px solid #f78f18;
            padding-left: 16px;
            margin: 20px 0;
            font-style: italic;
            background: rgba(247, 143, 24, 0.05);
            padding: 16px 20px;
            border-radius: 8px;
            position: relative;
        }

        .case-study-quote:before {
            content: '"';
            font-size: 48px;
            color: #f78f18;
            position: absolute;
            top: -10px;
            left: 10px;
            font-family: Georgia, serif;
        }

        .evidence-marker {
            background: #1e293b;
            border: 2px solid #f78f18;
            border-radius: 8px;
            padding: 12px 16px;
            margin: 16px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            color: #e2e8f0;
        }

        .evidence-marker:before {
            content: '🔍';
            font-size: 20px;
        }

        .client-testimonial {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border: 1px solid rgba(247, 143, 24, 0.3);
            border-radius: 12px;
            padding: 24px;
            margin: 20px 0;
            position: relative;
            box-shadow: 0 8px 32px rgba(247, 143, 24, 0.1);
        }

        .client-testimonial blockquote {
            margin: 0 0 16px 0;
            font-size: 18px;
            font-style: italic;
            color: #e2e8f0;
            line-height: 1.6;
        }

        .client-testimonial cite {
            color: #f78f18;
            font-weight: 600;
            font-size: 14px;
            text-align: right;
            display: block;
        }

        .client-testimonial:before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #f78f18, #ff6b35);
            border-radius: 14px;
            z-index: -1;
            opacity: 0.3;
        }

        /* ISS Media Embeds */
        .iss-media {
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 8px;
            padding: 16px;
            margin: 12px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #e2e8f0;
            font-weight: 500;
        }

        .iss-media:hover {
            border-color: #f78f18;
            box-shadow: 0 4px 12px rgba(247, 143, 24, 0.2);
        }

        .iss-media i {
            color: #f78f18;
            font-size: 24px;
        }

        .iss-media-audio i { color: #10b981; }
        .iss-media-video i { color: #f59e0b; }
        .iss-media-document i { color: #8b5cf6; }

        /* Enhanced CKEditor UI Styling */
        .ck.ck-toolbar {
            background: #0f172a !important;
            border: 1px solid #334155 !important;
            border-radius: 8px !important;
        }

        .ck.ck-toolbar .ck-button {
            color: #e2e8f0 !important;
        }

        .ck.ck-toolbar .ck-button:hover {
            background: rgba(247, 143, 24, 0.1) !important;
            color: #f78f18 !important;
        }

        .ck.ck-toolbar .ck-button.ck-on {
            background: rgba(247, 143, 24, 0.2) !important;
            color: #f78f18 !important;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; color: #000 !important; }
            main { padding: 0 !important; margin: 0 !important; }
            .print-area { box-shadow: none !important; border: 1px solid #eee !important; color: #000 !important; width: 100% !important; }
            .print-area table th { background-color: #f8fafc !important; color: #000 !important; border-bottom: 2px solid #000 !important; }
        }
    </style>
</head>
<body class="h-full flex flex-col text-slate-300 bg-slate-950 selection:bg-primary selection:text-white">

    <?php if (is_logged_in()): ?>
        <?php include_once __DIR__ . '/navigation.php'; ?>
        
        <div class="md:ml-64 flex-1 flex flex-col min-h-screen relative">
            
            <header class="no-print h-16 border-b border-white/5 bg-slate-900/50 backdrop-blur-md sticky top-0 z-40 flex items-center justify-between px-8">
                <div class="flex items-center gap-4">
                    <img src="images/logo.png" alt="ISS Investigations Lion Logo" class="h-8 w-8 object-contain" title="ISS Investigations - Admin">
                    <span class="text-[10px] tech-mono text-slate-500 uppercase tracking-widest bg-slate-800/50 px-3 py-1 rounded">
                        Terminal: <span class="text-primary font-bold">Encrypted</span>
                    </span>
                </div>
                <div class="flex items-center gap-6">
                    <div class="tooltip">
                        <i class="fas fa-bell text-slate-500 hover:text-primary transition-colors cursor-pointer"></i>
                        <span class="tooltiptext">No Alerts</span>
                    </div>
                    <div class="h-8 w-[1px] bg-white/5"></div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-xs font-bold text-white leading-none"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></p>
                            <p class="text-[9px] tech-mono text-primary uppercase mt-1">Authorized Access</p>
                        </div>
                    </div>
                </div>
            </header>
    <?php endif; ?>

    <main class="flex-grow p-6 lg:p-10">
