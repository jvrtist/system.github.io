<?php
/**
 * ISS Investigations - Two-Factor Authentication Verification
 * Handles TOTP code verification during login
 */
require_once 'config.php';

// Redirect if no pending 2FA verification
if (!isset($_SESSION['pending_2fa_user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['pending_2fa_user_id'];
$username = $_SESSION['pending_2fa_username'] ?? '';

$error_message = "";
$success_message = "";

// Handle 2FA verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_method = $_POST['method'] ?? 'totp';
    
    if ($verification_method === 'totp') {
        $totp_code = sanitize_input($_POST['totp_code'] ?? '');
        
        if (empty($totp_code)) {
            $error_message = "Authentication code is required.";
        } elseif (strlen($totp_code) !== 6 || !ctype_digit($totp_code)) {
            $error_message = "Code must be 6 digits.";
        } else {
            // Fetch user's TOTP secret
            $conn = get_db_connection();
            $stmt = $conn->prepare("SELECT totp_secret FROM users WHERE user_id = ? AND totp_enabled = 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $conn->close();
            
            if (!$user_data) {
                $error_message = "2FA is not enabled for this account.";
            } elseif (verify_totp_code($totp_code, $user_data['totp_secret'])) {
                // 2FA verified successfully
                log_audit_action($user_id, null, 'login_2fa_verified', 'user', $user_id, 'Login 2FA verification successful');
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['login_time'] = time();
                unset($_SESSION['pending_2fa_user_id']);
                unset($_SESSION['pending_2fa_username']);
                session_regenerate_id(true);
                redirect(isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'dashboard.php');
            } else {
                $error_message = "Invalid code. Please try again.";
                log_audit_action($user_id, null, 'login_2fa_failed', 'user', $user_id, 'Invalid TOTP code attempt');
            }
        }
    } elseif ($verification_method === 'recovery') {
        $recovery_code = sanitize_input($_POST['recovery_code'] ?? '');
        
        if (empty($recovery_code)) {
            $error_message = "Recovery code is required.";
        } else {
            // Verify and consume recovery code
            if (verify_and_consume_recovery_code($user_id, $recovery_code)) {
                log_audit_action($user_id, null, 'login_2fa_recovery_used', 'user', $user_id, 'Login via recovery code');
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['login_time'] = time();
                unset($_SESSION['pending_2fa_user_id']);
                unset($_SESSION['pending_2fa_username']);
                session_regenerate_id(true);
                redirect(isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'dashboard.php');
            } else {
                $error_message = "Invalid or already used recovery code.";
                log_audit_action($user_id, null, 'login_2fa_recovery_failed', 'user', $user_id, 'Invalid recovery code attempt');
            }
        }
    }
}

include_once 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center p-6 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-slate-900 via-secondary to-black">
    <div class="w-full max-w-md">
        
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-primary/20 border border-primary/50 rounded-3xl mb-4 shadow-2xl shadow-primary/20">
                <i class="fas fa-shield-alt text-primary text-3xl"></i>
            </div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Two-Factor <span class="text-primary">Auth</span></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.3em] mt-2">Verify your identity</p>
        </div>

        <?php if ($error_message): ?>
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/50 rounded-xl flex items-center gap-3 animate-shake">
                <i class="fas fa-exclamation-triangle text-red-500"></i>
                <p class="text-xs font-bold text-red-200"><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-slate-900 border border-white/5 rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-white/[0.03] px-8 py-6 border-b border-white/5">
                <p class="text-sm text-slate-300">Welcome back, <span class="font-bold text-primary"><?= htmlspecialchars($username) ?></span></p>
                <p class="text-[10px] text-slate-500 mt-1">Enter your authentication code</p>
            </div>

            <form id="2fa-form" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="p-8 space-y-6">
                <input type="hidden" name="method" id="method" value="totp">

                <!-- TOTP Tab -->
                <div id="totp-section" class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Authenticator Code</label>
                        <p class="text-[10px] text-slate-400">Enter the 6-digit code from your authenticator app</p>
                        <div class="relative">
                            <i class="fas fa-mobile-alt absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                            <input type="text" name="totp_code" id="totp_code" inputmode="numeric" pattern="\d{6}" maxlength="6" 
                                   placeholder="000000" required
                                   class="w-full bg-slate-950 border border-white/10 rounded-xl pl-12 pr-4 py-4 text-3xl text-center font-mono tracking-widest text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-[0.2em] text-xs py-4 rounded-xl transition-all transform active:scale-[0.98] shadow-lg shadow-primary/20">
                        <i class="fas fa-check-double mr-2"></i> Verify Code
                    </button>
                </div>

                <!-- Recovery Code Option -->
                <div class="relative flex items-center my-6">
                    <div class="flex-grow border-t border-slate-700"></div>
                    <span class="flex-shrink mx-4 text-[10px] text-slate-500 uppercase tracking-widest font-bold">OR</span>
                    <div class="flex-grow border-t border-slate-700"></div>
                </div>

                <div id="recovery-section" class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Recovery Code</label>
                        <p class="text-[10px] text-slate-400">Use a recovery code if you don't have access to your authenticator</p>
                        <div class="relative">
                            <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                            <input type="text" name="recovery_code" id="recovery_code" placeholder="XXXX-XXXX" autocomplete="off"
                                   class="w-full bg-slate-950 border border-white/10 rounded-xl pl-12 pr-4 py-3.5 text-sm font-mono uppercase text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all">
                        </div>
                    </div>

                    <button type="button" onclick="useRecoveryCode()" class="w-full bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs py-3 rounded-xl transition-colors">
                        <i class="fas fa-unlock-alt mr-2"></i> Use Recovery Code
                    </button>
                </div>
            </form>

            <div class="bg-slate-950/50 px-8 py-4 border-t border-white/5 text-center">
                <a href="login.php" class="text-[10px] text-slate-500 hover:text-slate-300 uppercase tracking-widest font-bold transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Login
                </a>
            </div>
        </div>

        <p class="text-center text-[10px] tech-mono text-slate-600 mt-10 uppercase tracking-widest">
            Two-Factor Authentication Enabled
        </p>
    </div>
</div>

<script>
// Auto-focus next input when 6 digits entered
document.getElementById('totp_code').addEventListener('input', function() {
    if (this.value.length === 6) {
        document.getElementById('2fa-form').submit();
    }
});

function useRecoveryCode() {
    const method = document.getElementById('method');
    const recoveryCode = document.getElementById('recovery_code');
    const totpCode = document.getElementById('totp_code');
    
    method.value = 'recovery';
    totpCode.required = false;
    recoveryCode.required = true;
    recoveryCode.focus();
}
</script>

<?php include_once 'includes/footer.php'; ?>
