<?php
/**
 * ISS Investigations - Two-Factor Authentication Setup
 * Allows admin users to enable/disable TOTP-based 2FA
 */
require_once '../config.php';
require_login();

// Only allow admins
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "Access denied. Admin access required.";
    redirect('dashboard.php');
}

$page_title = "Two-Factor Authentication";
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$conn = get_db_connection();

// Fetch current 2FA status
$stmt = $conn->prepare("SELECT totp_enabled, totp_secret FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totp_enabled = (bool)$user_data['totp_enabled'];
$totp_secret = $user_data['totp_secret'];

$error_message = "";
$success_message = "";
$qr_code_url = "";
$temporary_secret = "";
$recovery_codes = [];

// Handle setup request (generate new secret and QR code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    $action = sanitize_input($_POST['action']);
    
    if ($action === 'setup') {
        // Generate new TOTP secret
        $temporary_secret = generate_totp_secret();
        $qr_code_url = get_totp_qr_code_url($username, $temporary_secret);
        $_SESSION['temp_totp_secret'] = $temporary_secret;
        
    } elseif ($action === 'confirm_setup') {
        // Verify TOTP code and enable 2FA
        $totp_code = sanitize_input($_POST['totp_code'] ?? '');
        $temp_secret = $_SESSION['temp_totp_secret'] ?? '';
        
        if (empty($totp_code)) {
            $error_message = "TOTP code is required.";
        } elseif (empty($temp_secret)) {
            $error_message = "Setup session expired. Please start over.";
        } elseif (!verify_totp_code($totp_code, $temp_secret)) {
            $error_message = "Invalid TOTP code. Please check your authenticator app and try again.";
        } else {
            // Generate recovery codes
            $recovery_codes = generate_recovery_codes(10);
            $hashed_codes = array_map('hash_recovery_code', $recovery_codes);
            $codes_json = json_encode($hashed_codes);
            
            // Update user to enable 2FA
            $stmt = $conn->prepare("UPDATE users SET totp_secret = ?, totp_enabled = 1, recovery_codes = ?, two_fa_enabled_at = NOW() WHERE user_id = ?");
            $stmt->bind_param("ssi", $temp_secret, $codes_json, $user_id);
            if ($stmt->execute()) {
                log_audit_action($user_id, null, '2fa_enabled', 'user', $user_id, 'Two-Factor Authentication enabled');
                $success_message = "✅ Two-Factor Authentication enabled successfully! Please save your recovery codes.";
                $totp_enabled = true;
                unset($_SESSION['temp_totp_secret']);
            } else {
                $error_message = "Failed to enable 2FA. Please try again.";
            }
            $stmt->close();
        }
        
    } elseif ($action === 'disable_2fa') {
        // Disable 2FA (requires current password confirmation)
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            $error_message = "Password is required to disable 2FA.";
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!password_verify($password, $user['password'])) {
                $error_message = "Incorrect password.";
            } else {
                $stmt = $conn->prepare("UPDATE users SET totp_secret = NULL, totp_enabled = 0, recovery_codes = NULL WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    log_audit_action($user_id, null, '2fa_disabled', 'user', $user_id, 'Two-Factor Authentication disabled');
                    $success_message = "Two-Factor Authentication has been disabled.";
                    $totp_enabled = false;
                } else {
                    $error_message = "Failed to disable 2FA. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}

$conn->close();
include_once '../includes/header.php';
?>

<div class="max-w-2xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Two-Factor <span class="text-primary">Authentication</span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Secure your admin account with TOTP authentication</p>
    </header>

    <?php if ($success_message): ?>
        <div class="bg-green-500/10 border border-green-500/50 rounded-xl p-4 flex items-start gap-3">
            <i class="fas fa-check-circle text-green-500 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-semibold text-green-200"><?= htmlspecialchars($success_message) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3">
            <i class="fas fa-exclamation-circle text-red-500 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-semibold text-red-200"><?= htmlspecialchars($error_message) ?></p>
        </div>
    <?php endif; ?>

    <!-- Current Status -->
    <div class="bg-slate-900 border border-white/5 rounded-2xl p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Current Status</h2>
                <p class="mt-2 text-sm text-slate-300">Your 2FA configuration</p>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg <?= $totp_enabled ? 'bg-green-500/20 text-green-300' : 'bg-slate-700/50 text-slate-400' ?>">
                    <i class="fas <?= $totp_enabled ? 'fa-lock-open text-green-400' : 'fa-lock text-slate-500' ?>"></i>
                    <?= $totp_enabled ? 'Enabled' : 'Disabled' ?>
                </span>
            </div>
        </div>

        <?php if ($totp_enabled): ?>
            <p class="text-sm text-slate-400 mb-6">Your account is protected with Time-Based One-Time Password (TOTP) authentication.</p>
            
            <form method="POST" class="space-y-4">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="disable_2fa">
                
                <div class="space-y-2">
                    <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest">Confirm Password to Disable 2FA</label>
                    <div class="relative">
                        <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                        <input type="password" name="password" required
                               class="w-full bg-slate-950 border border-white/10 rounded-xl pl-12 pr-4 py-3 text-sm text-white focus:border-red-500 outline-none">
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-red-600/20 hover:bg-red-600/30 text-red-300 border border-red-600/50 font-semibold text-sm py-3 rounded-xl transition-colors">
                    <i class="fas fa-trash-alt mr-2"></i> Disable Two-Factor Authentication
                </button>
            </form>
        <?php else: ?>
            <p class="text-sm text-slate-400 mb-6">Enable TOTP authentication to protect your admin account with an authenticator app.</p>
            
            <form method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="setup">
                
                <button type="submit" class="w-full bg-primary hover:bg-orange-600 text-white font-semibold text-sm py-3 rounded-xl transition-colors">
                    <i class="fas fa-shield-alt mr-2"></i> Enable Two-Factor Authentication
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Setup Guide -->
    <?php if (isset($_SESSION['temp_totp_secret']) && !$totp_enabled): ?>
    <div class="bg-slate-900 border border-primary/20 rounded-2xl p-8 space-y-6">
        <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Step 1: Scan QR Code</h2>
        
        <p class="text-sm text-slate-300">Use an authenticator app to scan the QR code below:</p>
        
        <div class="bg-white p-6 rounded-xl flex justify-center">
            <img src="<?= htmlspecialchars($qr_code_url ?? '') ?>" alt="TOTP QR Code" class="w-48 h-48">
        </div>

        <div class="bg-slate-950/50 border border-slate-700 rounded-lg p-4">
            <p class="text-[10px] font-bold uppercase text-slate-500 mb-2 tracking-widest">Manual Entry:</p>
            <p class="font-mono text-sm text-slate-300 break-all"><?= htmlspecialchars($_SESSION['temp_totp_secret'] ?? '') ?></p>
        </div>

        <p class="text-[10px] text-slate-400">Recommended authenticator apps:</p>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-[10px] text-slate-300">
            <span>✓ Google Authenticator</span>
            <span>✓ Authy</span>
            <span>✓ Microsoft Authenticator</span>
            <span>✓ FreeOTP</span>
            <span>✓ Duo Mobile</span>
            <span>✓ 1Password</span>
        </div>

        <!-- Step 2: Verify Code -->
        <form method="POST" class="pt-6 border-t border-slate-700 space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="confirm_setup">
            
            <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Step 2: Verify Code</h2>
            
            <p class="text-sm text-slate-300">Enter the 6-digit code from your authenticator app:</p>
            
            <div class="relative">
                <i class="fas fa-mobile-alt absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                <input type="text" name="totp_code" pattern="\d{6}" maxlength="6" required placeholder="000000"
                       class="w-full bg-slate-950 border border-white/10 rounded-xl pl-12 pr-4 py-3 text-2xl text-center font-mono tracking-widest text-white focus:border-primary outline-none">
            </div>
            
            <button type="submit" class="w-full bg-primary hover:bg-orange-600 text-white font-semibold text-sm py-3 rounded-xl transition-colors">
                <i class="fas fa-check mr-2"></i> Verify & Enable 2FA
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Recovery Codes -->
    <?php if (!empty($recovery_codes)): ?>
    <div class="bg-yellow-500/10 border border-yellow-500/50 rounded-2xl p-8 space-y-4">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 flex-shrink-0"></i>
            <div>
                <h2 class="text-sm font-bold text-yellow-200">⚠️ Save Your Recovery Codes</h2>
                <p class="text-[10px] text-yellow-200/80 mt-1">Keep these codes in a safe place. Use them if you lose access to your authenticator app.</p>
            </div>
        </div>

        <div class="bg-slate-950 border border-yellow-500/30 rounded-lg p-4 font-mono text-sm text-slate-300 space-y-1 max-h-64 overflow-y-auto">
            <?php foreach ($recovery_codes as $code): ?>
                <div><?= htmlspecialchars($code) ?></div>
            <?php endforeach; ?>
        </div>

        <p class="text-[10px] text-slate-400">Each recovery code can only be used once.</p>
    </div>
    <?php endif; ?>

</div>

<?php include_once '../includes/footer.php'; ?>

