<?php
// client_portal/private_key_verification.php
require_once '../config.php';

if (!is_client_logged_in()) {
    redirect('client_login.php');
}

// If already verified, redirect to dashboard
if (isset($_SESSION['private_key_verified']) && $_SESSION['private_key_verified'] === true) {
    redirect('client_portal/dashboard.php');
}

$client_id = $_SESSION[CLIENT_ID_SESSION_VAR];
$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();
    $entered_key = sanitize_input($_POST['private_key']);

    if (empty($entered_key)) {
        $error_message = "Please enter your private key.";
    } else {
        $conn = get_db_connection();
        if ($conn) {
            $stmt = $conn->prepare("SELECT private_key FROM clients WHERE client_id = ? LIMIT 1");
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($client = $result->fetch_assoc()) {
                if (password_verify($entered_key, $client['private_key'])) {
                    $_SESSION['private_key_verified'] = true;
                    log_audit_action(null, $client_id, 'private_key_verified', 'client', $client_id, 'Client provided correct private key for access');

                    // Redirect to intended page or dashboard
                    $redirect_url = $_SESSION['private_key_redirect_url'] ?? 'client_portal/dashboard.php';
                    unset($_SESSION['private_key_redirect_url']);
                    redirect($redirect_url);
                } else {
                    $error_message = "Invalid private key. Please check with your investigator.";
                    log_audit_action(null, $client_id, 'private_key_failed', 'client', $client_id, 'Client entered incorrect private key');
                }
            }
            $stmt->close();
        }
    }
}

include_once '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center p-6 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-slate-900 via-secondary to-black">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl mb-4 shadow-2xl shadow-primary/20">
                <img src="../images/logo.png" alt="ISS Investigations Lion Logo" class="h-20 w-20 object-contain" title="ISS Investigations - Secure Access">
            </div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">ACCESS VERIFICATION</h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.3em]">Private Key Required</p>
        </div>

        <?php if ($error_message): ?>
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/50 rounded-xl flex items-center gap-3 animate-shake">
                <i class="fas fa-exclamation-triangle text-red-500"></i>
                <p class="text-xs font-bold text-red-200"><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="mb-6 p-4 bg-green-500/10 border border-green-500/50 rounded-xl flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500"></i>
                <p class="text-xs font-bold text-green-200"><?= htmlspecialchars($success_message) ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-slate-900 border border-white/5 rounded-3xl shadow-2xl overflow-hidden">
            <div class="p-8 space-y-6">
                <div class="text-center mb-6">
                    <p class="text-slate-400 text-sm">
                        Please enter the private key provided by your investigator or administrator to access your case information.
                    </p>
                </div>

                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-6">
                    <?= csrf_input(); ?>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black uppercase text-slate-500 ml-1 tracking-widest">Private Key</label>
                        <div class="relative">
                            <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                            <input type="password" name="private_key" required
                                   class="w-full bg-slate-950 border border-white/10 rounded-xl pl-12 pr-4 py-3.5 text-sm text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                                   placeholder="Enter your private key...">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-[0.2em] text-xs py-4 rounded-xl transition-all transform active:scale-[0.98] shadow-lg shadow-primary/20">
                        Verify Access
                    </button>
                </form>
            </div>
        </div>

        <div class="text-center mt-6">
            <a href="../logout.php" class="inline-flex items-center gap-2 text-slate-400 hover:text-primary transition-colors text-xs font-semibold uppercase tracking-widest">
                <i class="fas fa-sign-out-alt text-[10px]"></i>
                Logout
            </a>
        </div>

        <p class="text-center text-[10px] tech-mono text-slate-600 mt-10 uppercase tracking-widest leading-loose">
            &copy; <?= date("Y"); ?> ISS Operational Systems<br>
            Encryption: AES-256 Bit Status: <span class="text-green-500">Secure</span>
        </p>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
