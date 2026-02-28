<?php
/**
 * ISS Investigations - Unified Security Gateway
 * Handles authentication for both Operations Staff and Case Assets (Clients).
 */
require_once 'config.php';

// Redirect if an active session is already established
if (is_logged_in()) { redirect('dashboard.php'); }
if (is_client_logged_in()) { redirect('client_portal/dashboard.php'); }

$input_value = "";
$error_message = "";
$success_message = "";
$active_tab = isset($_GET['type']) && $_GET['type'] === 'client' ? 'client' : 'staff';

// Harvest session-based flashes
if (isset($_SESSION['success_message'])) { $success_message = $_SESSION['success_message']; unset($_SESSION['success_message']); }
if (isset($_SESSION['error_message'])) { $error_message = $_SESSION['error_message']; unset($_SESSION['error_message']); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();
    $login_type = $_POST['login_type'] ?? 'staff';
    $active_tab = $login_type;
    $identifier = sanitize_input($_POST['identifier']); 
    $password = $_POST['password'];
    $input_value = $identifier;

    if (empty($identifier) || empty($password)) {
        $error_message = "Authentication parameters missing.";
    } else {
        $conn = get_db_connection();
        if ($conn) {
            if ($login_type === 'staff') {
                $stmt = $conn->prepare("SELECT user_id, username, password, full_name, role FROM users WHERE username = ? LIMIT 1");
                $stmt->bind_param("s", $identifier);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($user = $result->fetch_assoc()) {
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['login_time'] = time();
                        session_regenerate_id(true);
                        redirect(isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'dashboard.php');
                    }
                }
                $error_message = "Invalid operational credentials.";
            } else {
                // Client Logic
                $stmt = $conn->prepare("SELECT client_id, first_name, last_name, email, password_hash, client_account_status FROM clients WHERE email = ? LIMIT 1");
                $stmt->bind_param("s", $identifier);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($client = $result->fetch_assoc()) {
                    // Use null coalescing operator to handle potential NULL password_hash
                    if ($client['client_account_status'] === 'Active' && password_verify($password, $client['password_hash'] ?? '')) {
                        $_SESSION[CLIENT_SESSION_VAR] = true;
                        $_SESSION[CLIENT_ID_SESSION_VAR] = $client['client_id'];
                        $_SESSION[CLIENT_NAME_SESSION_VAR] = $client['first_name'] . ' ' . $client['last_name'];
                        session_regenerate_id(true);
                        $stmt_update = $conn->prepare("UPDATE clients SET last_login_at = NOW() WHERE client_id = ?");
                        if ($stmt_update) {
                            $stmt_update->bind_param("i", $client['client_id']);
                            $stmt_update->execute();
                            $stmt_update->close();
                        }
                        redirect('client_portal/dashboard.php');
                    }
                }
                $error_message = "Access denied. Check credentials or account status.";
            }
        }
    }
}

include_once 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center p-6 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-slate-900 via-secondary to-black">
    <div class="w-full max-w-md">
        
        <!-- Back to Home Link -->
        <div class="text-center mb-6">
            <a href="index.php" class="inline-flex items-center gap-2 text-slate-400 hover:text-primary transition-colors text-xs font-semibold uppercase tracking-widest">
                <i class="fas fa-arrow-left text-[10px]"></i>
                Back to Home
            </a>
        </div>
        
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl mb-4 shadow-2xl shadow-primary/20">
                <img src="images/logo.png" alt="ISS Investigations Lion Logo" class="h-20 w-20 object-contain" title="ISS Investigations - Secure Login">
            </div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">ISS <span class="text-primary">NODE</span></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.3em]">Authorized Personnel Only</p>
        </div>

        <?php if ($error_message): ?>
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/50 rounded-xl flex items-center gap-3 animate-shake">
                <i class="fas fa-exclamation-triangle text-red-500"></i>
                <p class="text-xs font-bold text-red-200"><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-slate-900 border border-white/5 rounded-3xl shadow-2xl overflow-hidden">
            <div class="flex bg-black/20 p-2">
                <button id="staff-tab-btn" class="flex-1 py-3 text-[10px] font-black uppercase tracking-widest rounded-2xl transition-all duration-300" onclick="switchTab('staff')">
                    Staff Portal
                </button>
                <button id="client-tab-btn" class="flex-1 py-3 text-[10px] font-black uppercase tracking-widest rounded-2xl transition-all duration-300" onclick="switchTab('client')">
                    Client Access
                </button>
            </div>

            <form id="login-form" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="p-8 space-y-6">
                <?= csrf_input(); ?>
                <input type="hidden" name="login_type" id="login_type" value="<?= htmlspecialchars($active_tab); ?>">
                
                <div class="space-y-1">
                    <label id="identifier-label" class="text-[10px] font-black uppercase text-slate-500 ml-1 tracking-widest">Username</label>
                    <div class="relative">
                        <i class="fas fa-user-shield absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                        <input type="text" name="identifier" id="identifier" required 
                               class="w-full bg-slate-950 border border-white/10 rounded-xl pl-12 pr-4 py-3.5 text-sm text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                               placeholder="Enter credentials..." value="<?= htmlspecialchars($input_value); ?>">
                    </div>
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between ml-1">
                        <label class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Security Phrase</label>
                        <div id="forgot-password-link-container"></div>
                    </div>
                    <div class="relative">
                        <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                        <input type="password" name="password" id="password" required 
                               class="w-full bg-slate-950 border border-white/10 rounded-xl pl-12 pr-4 py-3.5 text-sm text-white focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                               placeholder="********">
                    </div>
                </div>

                <button type="submit" class="w-full bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-[0.2em] text-xs py-4 rounded-xl transition-all transform active:scale-[0.98] shadow-lg shadow-primary/20">
                    Initialize Session
                </button>
            </form>
        </div>

        <p class="text-center text-[10px] tech-mono text-slate-600 mt-10 uppercase tracking-widest leading-loose">
            &copy; <?= date("Y"); ?> ISS Operational Systems<br>
            Encryption: AES-256 Bit Status: <span class="text-green-500">Secure</span>
        </p>
    </div>
</div>

<script>
function switchTab(tab) {
    const staffBtn = document.getElementById('staff-tab-btn');
    const clientBtn = document.getElementById('client-tab-btn');
    const typeInput = document.getElementById('login_type');
    const idLabel = document.getElementById('identifier-label');
    const idInput = document.getElementById('identifier');
    const forgotContainer = document.getElementById('forgot-password-link-container');

    if (tab === 'staff') {
        staffBtn.className = 'flex-1 py-3 text-[10px] font-black uppercase tracking-widest rounded-2xl bg-primary text-white shadow-lg';
        clientBtn.className = 'flex-1 py-3 text-[10px] font-black uppercase tracking-widest rounded-2xl text-slate-500 hover:text-slate-300';
        idLabel.textContent = 'Operational Username';
        idInput.type = 'text';
        forgotContainer.innerHTML = '';
        typeInput.value = 'staff';
    } else {
        clientBtn.className = 'flex-1 py-3 text-[10px] font-black uppercase tracking-widest rounded-2xl bg-primary text-white shadow-lg';
        staffBtn.className = 'flex-1 py-3 text-[10px] font-black uppercase tracking-widest rounded-2xl text-slate-500 hover:text-slate-300';
        idLabel.textContent = 'Registered Email Address';
        idInput.type = 'email';
        forgotContainer.innerHTML = `<a href="client_forgot_password.php" class="text-[9px] font-black text-primary hover:underline uppercase tracking-tighter">Reset Key?</a>`;
        typeInput.value = 'client';
    }
}

// Set initial state
document.addEventListener('DOMContentLoaded', () => {
    const initialTab = <?= json_encode($active_tab) ?>;
    switchTab(initialTab);
});
</script>

<?php include_once 'includes/footer.php'; ?>
