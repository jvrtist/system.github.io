<?php
// admin/settings.php
require_once '../config.php'; // Adjust path: up two levels to project root
require_login();
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "You do not have permission to access the System Settings page.";
    redirect('dashboard.php'); // Redirect to their own dashboard or an appropriate page
}

$page_title = "System Settings";

// In a more advanced version, you might fetch settings from a database table.
// For now, we can display some constants from config.php or placeholders.

// Example: Values that might be configurable in a future version
$current_site_name = defined('SITE_NAME') ? SITE_NAME : 'N/A';
$current_base_url = defined('BASE_URL') ? BASE_URL : 'N/A';
$current_timezone = defined('DEFAULT_TIMEZONE') ? DEFAULT_TIMEZONE : 'N/A';
$current_app_version = defined('APP_VERSION') ? APP_VERSION : 'N/A';
$max_file_size_mb = defined('MAX_FILE_SIZE_BYTES') ? (MAX_FILE_SIZE_BYTES / (1024*1024)) : 'N/A';
$allowed_file_types_display = defined('ALLOWED_MIME_TYPES') ? implode(', ', array_values(ALLOWED_MIME_TYPES)) : 'N/A';


// Handle form submission if settings were made editable
$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();
    // This section would handle updating settings if the form was implemented.
    // For example, if you were allowing SITE_NAME to be changed:
    // $new_site_name = sanitize_input($_POST['site_name']);
    // if (empty($new_site_name)) {
    //     $errors['site_name'] = "Site name cannot be empty.";
    // }
    // if (empty($errors)) {
    //     // Logic to update the setting (e.g., in a config file or database)
    //     // This is complex for file-based configs like config.php constants.
    //     // Database-driven settings are easier to update via a UI.
    //     $_SESSION['success_message'] = "Settings would have been updated (functionality not fully implemented).";
    //     // Refresh current values if they were changed
    //     // $current_site_name = $new_site_name; // Example
    //     redirect('admin/settings.php');
    // } else {
    //    $_SESSION['error_message'] = "Please correct the errors.";
    // }
    $_SESSION['admin_warning_message'] = "Settings modification is not yet implemented in this version.";
    redirect('admin/settings.php');
}


// Check for messages passed via session
if (isset($_SESSION['admin_success_message'])) {
    $success_message = $_SESSION['admin_success_message'];
    unset($_SESSION['admin_success_message']);
}
if (isset($_SESSION['admin_error_message']) && empty($errors)) {
    $error_message_from_session = $_SESSION['admin_error_message'];
    unset($_SESSION['admin_error_message']);
    if (empty($errors['form'])) {
        $errors['form'] = $error_message_from_session;
    }
}
if (isset($_SESSION['admin_warning_message'])) {
    $warning_message = $_SESSION['admin_warning_message'];
    unset($_SESSION['admin_warning_message']);
}


include_once '../includes/header.php'; // Adjust path for includes
?>

<div class="max-w-5xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">System <span class="text-primary">Settings</span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Global configuration and maintenance</p>
    </header>

    <?php if (!empty($success_message)): ?>
        <div class="bg-green-500/10 border border-green-500/50 rounded-xl p-4 flex items-start gap-3 animate-fade-in">
            <i class="fas fa-check-circle text-green-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-green-200"><?php echo $success_message; // Allow HTML for line breaks ?></p>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors['form'])): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-fade-in">
            <i class="fas fa-exclamation-circle text-red-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-red-200"><?php echo $errors['form']; // Allow HTML for line breaks ?></p>
        </div>
    <?php endif; ?>
    <?php if (!empty($warning_message)): ?>
        <div class="bg-yellow-500/10 border border-yellow-500/50 rounded-xl p-4 flex items-start gap-3 animate-fade-in">
            <i class="fas fa-exclamation-triangle text-yellow-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-yellow-200"><?php echo htmlspecialchars($warning_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Config Values -->
        <div class="lg:col-span-2 space-y-8">
            <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
                <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02]">
                    <h2 class="text-xs font-black uppercase tracking-widest text-slate-400">Current Configuration</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-slate-950 p-4 rounded-xl border border-white/5">
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Site Name</p>
                            <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($current_site_name); ?></p>
                        </div>
                        <div class="bg-slate-950 p-4 rounded-xl border border-white/5">
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">App Version</p>
                            <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($current_app_version); ?></p>
                        </div>
                        <div class="bg-slate-950 p-4 rounded-xl border border-white/5 sm:col-span-2">
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Base URL</p>
                            <p class="text-xs font-mono text-primary break-all"><?php echo htmlspecialchars($current_base_url); ?></p>
                        </div>
                        <div class="bg-slate-950 p-4 rounded-xl border border-white/5">
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Timezone</p>
                            <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($current_timezone); ?></p>
                        </div>
                        <div class="bg-slate-950 p-4 rounded-xl border border-white/5">
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Max Upload</p>
                            <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($max_file_size_mb); ?> MB</p>
                        </div>
                        <div class="bg-slate-950 p-4 rounded-xl border border-white/5 sm:col-span-2">
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1">Allowed File Types</p>
                            <p class="text-xs text-slate-400 leading-relaxed"><?php echo htmlspecialchars($allowed_file_types_display); ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl opacity-75">
                <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                    <h2 class="text-xs font-black uppercase tracking-widest text-slate-400">Modify Settings</h2>
                    <span class="text-[9px] font-bold bg-slate-800 text-slate-500 px-2 py-1 rounded">Read Only</span>
                </div>
                <div class="p-8 text-center">
                    <i class="fas fa-lock text-slate-700 text-3xl mb-3"></i>
                    <p class="text-sm font-bold text-slate-400">Configuration Locked</p>
                    <p class="text-xs text-slate-600 mt-1 max-w-md mx-auto">
                        System settings are currently managed via the <code>config.php</code> file for security. 
                        Database-driven configuration will be available in a future update.
                    </p>
                </div>
            </section>
        </div>

        <!-- Right Column: Actions -->
        <div class="space-y-8">
            <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
                <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02]">
                    <h2 class="text-xs font-black uppercase tracking-widest text-primary">Database Operations</h2>
                </div>
                <div class="p-6">
                    <div class="bg-blue-500/5 border border-blue-500/20 rounded-xl p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                            <p class="text-xs text-blue-200/80 leading-relaxed">
                                Ensure your database schema is synchronized with the latest application code. 
                                This operation is safe to run multiple times.
                            </p>
                        </div>
                    </div>
                    
                    <a href="run_migrations.php" 
                       class="block w-full bg-blue-600 hover:bg-blue-500 text-white text-xs font-black uppercase tracking-widest py-4 rounded-xl text-center transition-all shadow-lg shadow-blue-900/20"
                       onclick="return confirm('Are you sure you want to run all database migrations? This action cannot be undone.');">
                        <i class="fas fa-database mr-2"></i> Run Full Migration
                    </a>
                </div>
            </section>

            <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
                <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02]">
                    <h2 class="text-xs font-black uppercase tracking-widest text-slate-400">System Info</h2>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500">PHP Version</span>
                        <span class="font-mono text-slate-300"><?php echo phpversion(); ?></span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500">Server Software</span>
                        <span class="font-mono text-slate-300"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-500">Database Driver</span>
                        <span class="font-mono text-slate-300">MySQLi</span>
                    </div>
                </div>
            </section>
        </div>

    </div>
</div>

<?php
include_once '../includes/footer.php'; // Adjust path for includes
?>
