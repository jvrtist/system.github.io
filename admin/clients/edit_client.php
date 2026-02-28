<?php
/**
 * ISS Investigations - Admin Edit Client
 * Admin interface for modifying existing client accounts and portal access settings.
 */
require_once '../../config.php'; // Adjust path: up two levels to project root
require_login();
// Optionally, restrict this further
if (!user_has_role('admin')) {
    $_SESSION['admin_error_message'] = "You do not have permission to access this page.";
    redirect('dashboard.php');
}

$page_title = "Edit Client & Portal Access";
$client_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($client_id_to_edit <= 0) {
    $_SESSION['admin_error_message'] = "Invalid client ID specified for editing.";
    redirect('admin/clients/');
}

$conn = get_db_connection();
$client_data = null;

// Fetch existing client data
if ($conn) {
    $stmt_fetch = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $client_id_to_edit);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows === 1) {
            $client_data = $result_fetch->fetch_assoc();
        } else {
            $_SESSION['admin_error_message'] = "Client not found for editing (ID: $client_id_to_edit).";
            $stmt_fetch->close();
            redirect('admin/clients/');
        }
        $stmt_fetch->close();
    } else {
        $_SESSION['admin_error_message'] = "Error preparing statement to fetch client: " . $conn->error;
        redirect('admin/clients/');
    }
} else {
    $_SESSION['admin_error_message'] = "Database connection failed.";
    redirect('admin/clients/');
}

if (!$client_data) { // Should be caught above
    redirect('admin/clients/');
}

// Initialize variables with existing client data
$first_name = $client_data['first_name'];
$last_name = $client_data['last_name'];
$email = $client_data['email'];
$phone = $client_data['phone'];
$address = $client_data['address'];
$company_name = $client_data['company_name'];
$date_of_birth = $client_data['date_of_birth'];
$client_account_status = $client_data['client_account_status'];
// Password fields are for new password, not displaying old one's hash
$client_portal_password = '';
$client_portal_confirm_password = '';
$errors = [];

// Define available account statuses
$available_client_account_statuses = ['Pending Activation', 'Active', 'Disabled', 'Password Reset'];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    // Sanitize and retrieve form data
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email_posted = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    $company_name = sanitize_input($_POST['company_name']);
    $date_of_birth = sanitize_input($_POST['date_of_birth']);
    
    $client_portal_password = $_POST['client_portal_password'];
    $client_portal_confirm_password = $_POST['client_portal_confirm_password'];
    $client_account_status_posted = sanitize_input($_POST['client_account_status']);

    // --- Validation ---
    if (empty($first_name)) $errors['first_name'] = "First name is required.";
    if (empty($last_name)) $errors['last_name'] = "Last name is required.";
    if (empty($email_posted)) {
        $errors['email'] = "Email address is required for client portal access.";
    } elseif (!filter_var($email_posted, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }
    if (!empty($phone) && !preg_match('/^[0-9\s\+\-\(\)]+$/', $phone)) {
        $errors['phone'] = "Invalid phone number format.";
    }
    if (!empty($date_of_birth)) {
        $d = DateTime::createFromFormat('Y-m-d', $date_of_birth);
        if (!$d || $d->format('Y-m-d') !== $date_of_birth) {
            $errors['date_of_birth'] = "Invalid date format (YYYY-MM-DD).";
        } elseif (new DateTime() < $d) {
             $errors['date_of_birth'] = "Date of birth cannot be in the future.";
        }
    }

    // Password validation (only if a new password is provided)
    $new_password_hash_for_db = $client_data['password_hash']; // Keep old hash by default
    if (!empty($client_portal_password)) {
        if (strlen($client_portal_password) < 8) {
            $errors['client_portal_password'] = "New portal password must be at least 8 characters long.";
        }
        if ($client_portal_password !== $client_portal_confirm_password) {
            $errors['client_portal_confirm_password'] = "New portal passwords do not match.";
        }
        if (empty($errors['client_portal_password']) && empty($errors['client_portal_confirm_password'])) {
             $new_password_hash_for_db = password_hash($client_portal_password, PASSWORD_DEFAULT);
        }
    } elseif (empty($client_data['password_hash']) && $client_account_status_posted === 'Active') {
        // If activating an account that previously had no password, and no new password was entered
        $errors['client_portal_password'] = "A password must be set to change status to 'Active' if no password was previously set.";
    }


    if (!in_array($client_account_status_posted, $available_client_account_statuses)) {
        $errors['client_account_status'] = "Invalid account status selected.";
    }

    // Check if email is changed and if the new email already exists for another client
    if (empty($errors['email']) && $email_posted !== $client_data['email']) {
        $conn_check_email = get_db_connection();
        $stmt_check_email = $conn_check_email->prepare("SELECT client_id FROM clients WHERE email = ? AND client_id != ?");
        $stmt_check_email->bind_param("si", $email_posted, $client_id_to_edit);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            $errors['email'] = "This email address is already registered to another client.";
        }
        $stmt_check_email->close();
    }

    // If no validation errors, proceed to update database
    if (empty($errors)) {
        $conn_update = get_db_connection();
        if ($conn_update) {
            $sql = "UPDATE clients SET 
                        first_name = ?, last_name = ?, email = ?, password_hash = ?, 
                        phone = ?, address = ?, company_name = ?, date_of_birth = ?, 
                        client_account_status = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE client_id = ?";
            $stmt_update = $conn_update->prepare($sql);

            if ($stmt_update) {
                $dob_to_update = !empty($date_of_birth) ? $date_of_birth : NULL;
                
                $stmt_update->bind_param("sssssssssi",
                    $first_name, $last_name, $email_posted, $new_password_hash_for_db,
                    $phone, $address, $company_name, $dob_to_update,
                    $client_account_status_posted, $client_id_to_edit
                );

                if ($stmt_update->execute()) {
                    $_SESSION['admin_success_message'] = "Client '" . htmlspecialchars($first_name . ' ' . $last_name) . "' updated successfully!";
                    if ($new_password_hash_for_db !== $client_data['password_hash'] && !empty($client_portal_password)) {
                         $_SESSION['admin_success_message'] .= " Client portal password has been changed.";
                    }
                    if ($client_account_status_posted !== $client_data['client_account_status']) {
                         $_SESSION['admin_success_message'] .= " Client account status updated to '" . htmlspecialchars($client_account_status_posted) . "'.";
                    }
                    redirect('admin/clients/view_client.php?id=' . $client_id_to_edit);
                } else {
                    $_SESSION['admin_error_message'] = "Error updating client: " . $stmt_update->error;
                    error_log("Edit Client (with portal) Error: " . $stmt_update->error . " SQL: " . $sql);
                    // To retain POSTed values on error:
                    $email = $email_posted;
                    $client_account_status = $client_account_status_posted;
                }
                $stmt_update->close();
            } else {
                $_SESSION['admin_error_message'] = "Database statement preparation error for update: " . $conn_update->error;
            }
        } else {
            $_SESSION['admin_error_message'] = "Database connection failed for update.";
        }
    } else {
        $_SESSION['admin_error_message'] = "Please correct the errors in the form.";
        // To retain POSTed values on error:
        $email = $email_posted;
        $client_account_status = $client_account_status_posted;
    }
}

// Close connection if it was opened for initial fetch and not handled by footer
if ($conn && php_sapi_name() !== 'cli') { // Avoid closing in CLI context if script ends
    // $conn->close(); // Let config.php's static connection handle itself or footer close it
}


include_once '../../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">
            Edit <span class="text-primary">Client</span>
        </h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Modify client account and portal access settings</p>
    </header>

    <?php if (isset($_SESSION['admin_error_message']) && empty($errors) ): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-shake">
            <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-red-200"><?php echo htmlspecialchars($_SESSION['admin_error_message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['admin_error_message']); ?>
    <?php endif; ?>
     <?php if (!empty($errors) && isset($_SESSION['admin_error_message']) ): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3">
            <i class="fas fa-exclamation-circle text-red-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-red-200">Please correct the errors below:</p>
                <ul class="text-xs text-red-300 mt-2 list-disc list-inside">
                    <?php foreach ($errors as $field => $message): ?>
                        <li><?php echo htmlspecialchars($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <form action="edit_client.php?id=<?php echo $client_id_to_edit; ?>" method="POST" class="space-y-6">
        <?php echo csrf_input(); ?>
        <input type="hidden" name="client_id" value="<?php echo $client_id_to_edit; ?>">

        <!-- Section 1: Client Information -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">01. Client Information</h2>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required
                               class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['first_name']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <?php if (isset($errors['first_name'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['first_name']); ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="last_name" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required
                               class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['last_name']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <?php if (isset($errors['last_name'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['last_name']); ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="company_name" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Company Name</label>
                        <input type="text" name="company_name" id="company_name" value="<?php echo htmlspecialchars($company_name); ?>"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    </div>
                    <div>
                        <label for="date_of_birth" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" value="<?php echo htmlspecialchars($date_of_birth ?: ''); ?>"
                               class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['date_of_birth']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all"
                               max="<?php echo date('Y-m-d'); ?>">
                        <?php if (isset($errors['date_of_birth'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['date_of_birth']); ?></p><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Contact Information -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">02. Contact Information</h2>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label for="email" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Email Address (for Portal Login) <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required
                           class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['email']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <?php if (isset($errors['email'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['email']); ?></p><?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="phone" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Phone Number</label>
                        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($phone); ?>"
                               class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['phone']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                         <?php if (isset($errors['phone'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['phone']); ?></p><?php endif; ?>
                    </div>
                    <div></div>
                </div>

                <div>
                    <label for="address" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Address</label>
                    <textarea name="address" id="address" rows="3"
                              class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($address); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Section 3: Portal Access -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">03. Portal Access</h2>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="client_portal_password" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">New Portal Password</label>
                        <input type="password" name="client_portal_password" id="client_portal_password"
                               class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['client_portal_password']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <p class="text-xs text-slate-400 mt-1">Min. 8 characters. Leave blank to keep current password (if set).</p>
                        <?php if (isset($errors['client_portal_password'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['client_portal_password']); ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="client_portal_confirm_password" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Confirm New Password</label>
                        <input type="password" name="client_portal_confirm_password" id="client_portal_confirm_password"
                               class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['client_portal_confirm_password']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <?php if (isset($errors['client_portal_confirm_password'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['client_portal_confirm_password']); ?></p><?php endif; ?>
                    </div>
                </div>
                <div>
                    <label for="client_account_status" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Client Account Status <span class="text-red-500">*</span></label>
                    <select name="client_account_status" id="client_account_status" required
                            class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['client_account_status']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <?php foreach ($available_client_account_statuses as $status_opt): ?>
                            <option value="<?php echo htmlspecialchars($status_opt); ?>" <?php echo ($client_account_status === $status_opt) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status_opt))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['client_account_status'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['client_account_status']); ?></p><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-4">
            <a href="view_client.php?id=<?php echo $client_id_to_edit; ?>" class="w-full sm:w-auto text-center px-6 py-2.5 border border-slate-600 hover:bg-slate-800/50 text-slate-300 hover:text-slate-100 rounded-lg transition-colors duration-200 font-semibold text-sm">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="w-full sm:w-auto bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider py-2.5 px-8 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5 text-sm">
                <i class="fas fa-save mr-2"></i>Update Client & Portal Settings
            </button>
        </div>
    </form>
</div>

<?php
include_once '../../includes/footer.php';
?>
