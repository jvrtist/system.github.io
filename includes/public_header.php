<?php
/**
 * ISS Investigations - Public Header
 * High-performance SEO, dynamic meta-handling, and premium navigation.
 */
if (session_status() == PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config.php';
}
$page = basename($_SERVER['PHP_SELF']);

// --- Dynamic Page Title ---
$site_name = "ISS Investigations";
$tagline = "Private Intelligence & Professional Investigations";
$page_title_full = isset($page_title) ? htmlspecialchars($page_title) . ' | ' . $site_name : $site_name . ' - ' . $tagline;
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$current_url = $scheme . '://' . $host . $request_uri;

// --- Dynamic Meta Description (SEO Optimized) ---
$meta_description = "ISS Investigations: Professional private detective agency in Gauteng offering surveillance, corporate intelligence, and POPIA-compliant investigations in South Africa.";
if (isset($page_title)) {
    switch ($page_title) {
        case "About ISS Investigations | 15+ Years Private Detective Gauteng":
            $meta_description = "Learn about ISS Investigations: 15+ years of certified private investigation expertise in Gauteng. PSIRA registered, POPIA compliant, 500+ cases resolved with absolute discretion.";
            break;
        case "Contact Private Investigator Gauteng | Confidential Consultation | ISS Investigations":
            $meta_description = "Contact ISS Investigations for a confidential consultation with expert investigators. Specialist in corporate fraud, surveillance, and legal investigations across Johannesburg, Pretoria, Gauteng.";
            break;
        case "Professional Investigation Services | Corporate Intelligence, Surveillance Gauteng | ISS Investigations":
            $meta_description = "Professional investigation services in Gauteng: Corporate surveillance, TSCM bug sweeps, digital forensics, fraud investigation, background screening, and legal intelligence. POPIA-compliant.";
            break;
        case "Privacy Policy & POPIA Compliance | ISS Investigations":
            $meta_description = "ISS Investigations privacy policy: POPIA compliant data handling, security protocols, and your rights regarding personal information in our investigations.";
            break;
        case "Terms of Service | ISS Investigations":
            $meta_description = "Terms of Service for ISS Investigations Client Portal: Legal agreement governing access to investigations, confidentiality, security, and professional standards.";
            break;
        case "Investigation & Fraud Prevention Blog | Corporate Intelligence Insights | ISS Gauteng":
            $meta_description = "Expert blog on fraud prevention, corporate intelligence, surveillance techniques, and South African legal standards. Insights from certified investigators in Gauteng.";
            break;
        case "Home | ISS Private Investigations South Africa":
            $meta_description = "ISS Investigations: 15+ years providing discreet, professional private investigation services in Gauteng, South Africa. PSIRA-registered, POPIA-compliant, 500+ cases resolved.";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title_full; ?></title>
    
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="private investigator gauteng, surveillance south africa, corporate intelligence, bug sweeps, private detective johannesburg, POPIA compliant investigations">
    <meta name="author" content="ISS Investigations">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="language" content="English">
    <meta name="revisit-after" content="7 days">
    <meta name="geo.placename" content="Gauteng, South Africa">
    <meta name="geo.region" content="ZA">

    <!-- Canonical URL for SEO -->
    <link rel="canonical" href="<?php echo htmlspecialchars($current_url); ?>" />

    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title_full); ?>" />
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>" />
    <meta property="og:image" content="<?php echo BASE_URL; ?>/images/og-brand.png" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />

    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title_full); ?>" />
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>" />
    <meta name="twitter:image" content="<?php echo BASE_URL; ?>/images/og-brand.png" />
    <meta name="twitter:site" content="@iss_investigations" />

    <!-- Schema.org Structured Data for Organization -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "ISS Investigations",
        "url": "https://iss-investigations.co.za",
        "logo": "https://iss-investigations.co.za/images/logo.png",
        "description": "Professional private investigation services in Gauteng, South Africa specializing in corporate intelligence, surveillance, and legal support.",
        "sameAs": [
            "https://www.facebook.com/iss-investigations",
            "https://www.linkedin.com/company/iss-investigations"
        ],
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Gauteng, South Africa",
            "addressRegion": "Gauteng",
            "addressCountry": "ZA"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "Customer Service",
            "telephone": "+27653087750",
            "email": "info@iss-investigations.co.za"
        },
        "areaServed": {
            "@type": "GeoShape",
            "areaServed": "Gauteng, Johannesburg, Pretoria, South Africa"
        }
    }
    </script>

    <!-- Schema.org LocalBusiness -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "ISS Investigations",
        "image": "https://iss-investigations.co.za/images/logo.png",
        "description": "Professional private investigations and corporate intelligence services",
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "ZA",
            "addressRegion": "Gauteng"
        },
        "telephone": "+27653087750",
        "email": "info@iss-investigations.co.za",
        "url": "https://iss-investigations.co.za",
        "openingHoursSpecification": {
            "@type": "OpeningHoursSpecification",
            "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
            "opens": "08:00",
            "closes": "18:00"
        }
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#ff8800',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                            DEFAULT: '#ff8800'
                        },
                        secondary: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                            DEFAULT: '#0f172a'
                        },
                        accent: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d'
                        },
                        neutral: {
                            50: '#fafafa',
                            100: '#f5f5f5',
                            200: '#e5e5e5',
                            300: '#d4d4d4',
                            400: '#a3a3a3',
                            500: '#737373',
                            600: '#525252',
                            700: '#404040',
                            800: '#262626',
                            900: '#171717'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                        display: ['Inter', 'system-ui', 'sans-serif']
                    },
                    fontSize: {
                        'xs': ['0.75rem', { lineHeight: '1rem' }],
                        'sm': ['0.875rem', { lineHeight: '1.25rem' }],
                        'base': ['1rem', { lineHeight: '1.5rem' }],
                        'lg': ['1.125rem', { lineHeight: '1.75rem' }],
                        'xl': ['1.25rem', { lineHeight: '1.75rem' }],
                        '2xl': ['1.5rem', { lineHeight: '2rem' }],
                        '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
                        '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
                        '5xl': ['3rem', { lineHeight: '1' }],
                        '6xl': ['3.75rem', { lineHeight: '1' }],
                        '7xl': ['4.5rem', { lineHeight: '1' }],
                        '8xl': ['6rem', { lineHeight: '1' }],
                        '9xl': ['8rem', { lineHeight: '1' }]
                    },
                    spacing: {
                        '18': '4.5rem',
                        '88': '22rem',
                        '128': '32rem',
                        '144': '36rem'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'fade-in-up': 'fadeInUp 0.6s ease-out',
                        'fade-in-down': 'fadeInDown 0.6s ease-out',
                        'slide-in-left': 'slideInLeft 0.5s ease-out',
                        'slide-in-right': 'slideInRight 0.5s ease-out',
                        'scale-in': 'scaleIn 0.3s ease-out',
                        'bounce-subtle': 'bounceSubtle 2s infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'float': 'float 6s ease-in-out infinite'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        fadeInDown: {
                            '0%': { opacity: '0', transform: 'translateY(-30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideInLeft: {
                            '0%': { opacity: '0', transform: 'translateX(-30px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' }
                        },
                        slideInRight: {
                            '0%': { opacity: '0', transform: 'translateX(30px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' }
                        },
                        scaleIn: {
                            '0%': { opacity: '0', transform: 'scale(0.9)' },
                            '100%': { opacity: '1', transform: 'scale(1)' }
                        },
                        bounceSubtle: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-5px)' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' }
                        }
                    },
                    boxShadow: {
                        'glow': '0 0 20px rgba(255, 136, 0, 0.3)',
                        'glow-lg': '0 0 40px rgba(255, 136, 0, 0.2)',
                        'inner-glow': 'inset 0 0 20px rgba(255, 136, 0, 0.1)',
                        'card': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                        'card-hover': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                        'elevated': '0 25px 50px -12px rgba(0, 0, 0, 0.25)'
                    },
                    backdropBlur: {
                        'xs': '2px'
                    }
                }
            }
        }
    </script>

    <style type="text/css">
        /* Enhanced Glass Navigation */
        .glass-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        /* Premium Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, #ff8800 0%, #ea580c 100%);
            box-shadow: 0 4px 15px rgba(255, 136, 0, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            box-shadow: 0 8px 25px rgba(255, 136, 0, 0.4);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.4);
            transform: translateY(-2px);
        }

        /* Enhanced Card Styles */
        .card-premium {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.8) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-premium:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        /* Gradient Backgrounds */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #ff8800 0%, #ea580c 100%);
        }

        .bg-gradient-secondary {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        .bg-gradient-accent {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        /* Text Gradients */
        .text-gradient-primary {
            background: linear-gradient(135deg, #ff8800 0%, #ea580c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .text-gradient-secondary {
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Animated Backgrounds */
        .bg-animated {
            background: linear-gradient(-45deg, #ff8800, #ea580c, #c2410c, #9a3412);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #ff8800 0%, #ea580c 100%);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
        }

        /* Focus Styles for Accessibility */
        .focus-ring:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 136, 0, 0.5);
        }

        /* Loading Animation */
        .loading-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Smooth Transitions */
        * {
            scroll-behavior: smooth;
        }

        /* Enhanced Typography */
        .text-shadow {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .text-shadow-lg {
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="flex flex-col min-h-screen font-sans text-slate-900 antialiased">

    <div class="bg-gradient-secondary text-[10px] text-slate-300 py-3 border-b border-white/10 hidden md:block">
        <div class="container mx-auto px-4 flex justify-between items-center tracking-[0.2em] uppercase font-bold">
            <div class="flex gap-6">
                <span class="flex items-center gap-2 hover:text-primary transition-colors cursor-default">
                    <i class="fas fa-shield-halved text-primary text-xs"></i> Professional Standards Certified
                </span>
                <span class="text-slate-600">|</span>
                <span class="flex items-center gap-2 hover:text-primary transition-colors cursor-default">
                    <i class="fas fa-lock text-primary text-xs"></i> End-to-End Encryption
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="animate-pulse-slow">●</span>
                <span>Operations Desk: +27 65 308 7750</span>
            </div>
        </div>
    </div>

    <header class="glass-nav sticky top-0 z-50 border-b border-slate-100">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                
                <div class="flex-shrink-0">
                    <a href="index.php" class="flex items-center gap-3 group">
                        <div class="group-hover:scale-110 transition-transform duration-300">
                            <img src="images/logo.png" alt="ISS Investigations Lion Logo" class="h-12 w-12 object-contain" title="ISS Investigations - Private Investigations Gauteng">
                        </div>
                        <div class="flex flex-col">
                            <span class="text-lg font-black text-secondary tracking-tighter leading-none uppercase">ISS <span class="text-primary font-light">Investigations</span></span>
                            <span class="text-[8px] font-bold text-slate-400 tracking-[0.3em] uppercase leading-none mt-1">Intelligence Division</span>
                        </div>
                    </a>
                </div>

                <div class="hidden md:flex items-center gap-10">
                    <?php 
                    $nav_items = [
                        'index.php' => ['label' => 'Home', 'aria' => 'Go to homepage'],
                        'about.php' => ['label' => 'About', 'aria' => 'Learn about ISS Investigations'],
                        'services.php' => ['label' => 'Services', 'aria' => 'View our investigation services'],
                        'blog.php' => ['label' => 'Insights', 'aria' => 'Read investigation insights and articles'],
                        'contact.php' => ['label' => 'Contact', 'aria' => 'Contact us for a consultation']
                    ];
                    foreach ($nav_items as $url => $item): 
                        $is_active = ($page === $url);
                    ?>
                        <a href="<?= $url ?>" aria-label="<?= $item['aria'] ?>" class="text-xs font-black uppercase tracking-widest transition-all relative py-2 group <?= $is_active ? 'text-primary' : 'text-slate-500 hover:text-secondary' ?>" title="<?= $item['aria'] ?>">
                            <?= $item['label'] ?>
                            <span class="absolute bottom-0 left-0 w-full h-0.5 bg-primary transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left <?= $is_active ? 'scale-x-100' : '' ?>"></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="hidden md:flex items-center gap-4">
                    <a href="login.php" aria-label="Access client portal login" class="btn-primary inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] py-3 px-6 rounded-xl focus-ring" title="Client Portal Login">
                        <i class="fas fa-user-lock"></i> Portal Login
                    </a>
                </div>

                <div class="md:hidden">
                    <button id="mobile-menu-button" aria-label="Toggle mobile navigation menu" class="w-12 h-12 flex items-center justify-center rounded-xl bg-slate-50 text-secondary border border-slate-200 hover:border-primary/30 focus-ring transition-all duration-300 hover:shadow-lg" aria-expanded="false" aria-controls="mobile-menu">
                        <i class="fas fa-bars-staggered text-lg"></i>
                    </button>
                </div>
            </div>
        </nav>

        <div id="mobile-menu" class="md:hidden hidden bg-white/95 backdrop-blur-xl border-t border-slate-100 shadow-2xl animate-fade-in-down">
            <nav class="space-y-2 px-6 py-8">
                <a href="index.php" class="block py-4 px-4 text-sm font-black uppercase tracking-widest text-secondary border-b border-slate-50 hover:text-primary hover:bg-primary/5 transition-all duration-300 rounded-lg" aria-label="Go to homepage">Home</a>
                <a href="about.php" class="block py-4 px-4 text-sm font-black uppercase tracking-widest text-secondary border-b border-slate-50 hover:text-primary hover:bg-primary/5 transition-all duration-300 rounded-lg" aria-label="Learn about ISS Investigations">About</a>
                <a href="services.php" class="block py-4 px-4 text-sm font-black uppercase tracking-widest text-secondary border-b border-slate-50 hover:text-primary hover:bg-primary/5 transition-all duration-300 rounded-lg" aria-label="View our investigation services">Services</a>
                <a href="blog.php" class="block py-4 px-4 text-sm font-black uppercase tracking-widest text-secondary border-b border-slate-50 hover:text-primary hover:bg-primary/5 transition-all duration-300 rounded-lg" aria-label="Read investigation insights and articles">Insights</a>
                <a href="contact.php" class="block py-4 px-4 text-sm font-black uppercase tracking-widest text-secondary border-b border-slate-50 hover:text-primary hover:bg-primary/5 transition-all duration-300 rounded-lg" aria-label="Contact us for a consultation">Contact</a>
                <div class="pt-6 border-t border-slate-100">
                    <a href="login.php" class="block w-full text-center btn-secondary text-xs py-4 rounded-xl focus-ring" aria-label="Access client portal login">
                        <i class="fas fa-user-lock mr-2"></i> Client Portal Login
                    </a>
                </div>
                <div class="pt-4 border-t border-slate-100 space-y-2">
                    <a href="privacy.php" class="block py-3 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-primary transition-colors rounded-lg hover:bg-primary/5" aria-label="View privacy policy">Privacy Policy</a>
                    <a href="terms.php" class="block py-3 px-4 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-primary transition-colors rounded-lg hover:bg-primary/5" aria-label="View terms of service">Terms of Service</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="flex-grow">
