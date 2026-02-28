<?php
/**
 * ISS Investigations - 403 Forbidden Error Page
 * Access denied error page.
 */
$page_title = "Access Denied";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - ISS Investigations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind = {
            config: {
                theme: {
                    extend: {
                        colors: {
                            primary: '#2563eb',
                            primaryHover: '#1d4ed8',
                            secondary: '#0f172a',
                        },
                        fontFamily: {
                            sans: ['Inter', 'sans-serif'],
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
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto text-center">
        <div class="mb-8">
            <div class="w-24 h-24 rounded-full bg-red-500/20 border-2 border-red-500/50 flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-shield-alt text-red-400 text-3xl"></i>
            </div>
            <h1 class="text-6xl font-black text-slate-800 mb-4">403</h1>
            <h2 class="text-2xl font-bold text-slate-700 mb-4">Access Denied</h2>
            <p class="text-slate-600 mb-8">You don't have permission to access this resource.</p>
        </div>

        <div class="space-y-4">
            <a href="login.php" class="inline-block bg-primary hover:bg-primaryHover text-white font-bold py-3 px-6 rounded-lg shadow-lg shadow-primary/30 hover:shadow-lg hover:shadow-primary/40 transition-all transform hover:-translate-y-0.5">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </a>
            <br>
            <a href="index.php" class="text-slate-500 hover:text-primary transition-colors text-sm">
                <i class="fas fa-home mr-1"></i>Go Home
            </a>
        </div>
    </div>
</body>
</html>
