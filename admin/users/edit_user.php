<?php
/**
 * ISS Investigations - User Account Modification Interface
 * Admin interface for editing existing user accounts and role management.
 */
require_once '../../config.php';
require_login();
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "You do not have permission to access this page.";
    redirect('dashboard.php');
}

$page_title = "Edit User";
$user_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id_to_edit <= 0) {
    $_SESSION['error_message'] = "Invalid user ID specified for editing.";
    redirect('admin/users/');
}

// Prevent editing the primary admin (e.g., user_id 1) or self in a way that locks out
if ($user_id_to_edit == 1 && $_SESSION['user_id'] != 1) { // Non-primary admin trying to edit primary admin
    // $_SESSION['error_message'] = "You cannot edit the primary administrator account.";
    // redirect('admin/users/');
    // OR allow editing some fields but not role/password for primary admin by others.
    // For simplicity now, we'll allow editing by another admin but be cautious.
}


$conn = get_db_connection();
$user_data = null;

// Fetch existing user data
if ($conn) {
    $stmt_fetch = $conn->prepare("SELECT user_id, username, email, full_name, role FROM users WHERE user_id = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $user_id_to_edit);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows === 1) {
            $user_data = $result_fetch->fetch_assoc();
        } else {
            $_SESSION['error_message'] = "User not found for editing (ID: $user_id_to_edit).";
            $stmt_fetch->close();
            // $conn->close(); // Not closing here if get_db_connection uses static
            redirect('admin/users/');
        }
        $stmt_fetch->close();
    } else {
        $_SESSION['error_message'] = "Error preparing statement to fetch user: " . $conn->error;
        // $conn->close(); // Not closing here
        redirect('admin/users/');
    }
} else {
    $_SESSION['error_message'] = "Database connection failed.";
    redirect('admin/users/');
}

if (!$user_data) { // Should be caught above
    redirect('admin/users/');
}

// Initialize variables with existing user data
$username = $user_data['username']; // Username is typically not editable or editable with extreme caution
$full_name = $user_data['full_name'];
$email = $user_data['email'];
$role = $user_data['role'];
$password = ''; // Password fields are for new password, not displaying old one
$confirm_password = '';
$errors = [];

$available_roles = ['admin', 'investigator']; // From your DB schema
$admin_count = null;
if ($conn) {
    $stmt_admin_count = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
    if ($stmt_admin_count) {
        $stmt_admin_count->execute();
        $admin_count = (int)$stmt_admin_count->get_result()->fetch_assoc()['admin_count'];
        $stmt_admin_count->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    // Sanitize and retrieve form data
    // Username is not taken from POST as it's generally not editable or handled specially.
    $full_name = sanitize_input($_POST['full_name']);
    $email_posted = sanitize_input($_POST['email']);
    $role_posted = sanitize_input($_POST['role']);
    $password = $_POST['password']; // New password
    $confirm_password = $_POST['confirm_password']; // Confirm new password

    // --- Validation ---
    if (empty($full_name)) {
        $errors['full_name'] = "Full name is required.";
    }

    if (empty($email_posted)) {
        $errors['email'] = "Email address is required.";
    } elseif (!filter_var($email_posted, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    // Password validation only if a new password is provided
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors['password'] = "New password must be at least 8 characters long.";
        }
        if ($password !== $confirm_password) {
            $errors['confirm_password'] = "New passwords do not match.";
        }
    }

    if (!in_array($role_posted, $available_roles)) {
        $errors['role'] = "Invalid role selected.";
    }
    
    // Prevent demoting the last admin or changing own role if last admin
    if ($user_data['role'] === 'admin' && $role_posted !== 'admin') {
        $stmt_count_admins = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
        $stmt_count_admins->execute();
        $result_count_admins = $stmt_count_admins->get_result()->fetch_assoc();
        $stmt_count_admins->close();
        if ($result_count_admins['admin_count'] <= 1) {
            $errors['role'] = "Cannot remove the last administrator role. Assign another user as admin first.";
        }
    }
    // Prevent user ID 1 (typically super admin) from being demoted from admin by anyone.
    if ($user_id_to_edit == 1 && $role_posted !== 'admin') {
         $errors['role'] = "The primary administrator (User ID 1) role cannot be changed from 'admin'.";
    }


    // Check if email is changed and if the new email already exists for another user
    if (empty($errors) && $email_posted !== $user_data['email']) {
        $conn_check_email = get_db_connection();
        $stmt_check_email = $conn_check_email->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt_check_email->bind_param("si", $email_posted, $user_id_to_edit);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            $errors['email'] = "This email address is already registered to another user.";
        }
        $stmt_check_email->close();
    }


    // If no validation errors, proceed to update database
    if (empty($errors)) {
        $conn_update = get_db_connection();
        if ($conn_update) {
            if (!empty($password)) {
                // New password provided, update it
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET full_name = ?, email = ?, role = ?, password_hash = ? WHERE user_id = ?";
                $stmt_update = $conn_update->prepare($sql);
                $stmt_update->bind_param("ssssi", $full_name, $email_posted, $role_posted, $password_hash, $user_id_to_edit);
            } else {
                // No new password, update other fields
                $sql = "UPDATE users SET full_name = ?, email = ?, role = ? WHERE user_id = ?";
                $stmt_update = $conn_update->prepare($sql);
                $stmt_update->bind_param("sssi", $full_name, $email_posted, $role_posted, $user_id_to_edit);
            }

            if ($stmt_update) {
                if ($stmt_update->execute()) {
                    $_SESSION['success_message'] = "User '" . htmlspecialchars($username) . "' updated successfully!";
                    // Optional: Log this action
                    // log_audit_action($_SESSION['user_id'], 'update_user', 'user', $user_id_to_edit, 'Admin updated user: ' . $username);
                    redirect('admin/users/');
                } else {
                    $_SESSION['error_message'] = "Error updating user: " . $stmt_update->error;
                    error_log("Edit User Error: " . $stmt_update->error . " SQL: " . $sql);
                    // To retain POSTed values on error:
                    $email = $email_posted;
                    $role = $role_posted;
                }
                $stmt_update->close();
            } else {
                $_SESSION['error_message'] = "Database statement preparation error for update: " . $conn_update->error;
            }
        } else {
            $_SESSION['error_message'] = "Database connection failed for update.";
        }
    } else {
        $_SESSION['error_message'] = "Please correct the errors in the form.";
        // To retain POSTed values on error:
        $email = $email_posted;
        $role = $role_posted;
    }
}

if ($conn) $conn->close(); // Close initial connection if still open

include_once '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Modify <span class="text-primary">User</span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Edit user account: <?php echo htmlspecialchars($user_data['username']); ?></p>
    </header>

    <?php if (!empty($errors)): ?>
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

    <form action="edit_user.php?id=<?php echo $user_id_to_edit; ?>" method="POST" class="space-y-6">
        <?php echo csrf_input(); ?>
        <input type="hidden" name="user_id" value="<?php echo $user_id_to_edit; ?>">

        <!-- Section 1: Account Information -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">01. Account Information</h2>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label for="username_display" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Username (Read-Only)</label>
                    <input type="text" id="username_display" value="<?php echo htmlspecialchars($username); ?>" readonly class="w-full px-4 py-2.5 bg-slate-800/50 border border-slate-700 rounded-lg text-slate-500 cursor-not-allowed">
                </div>

                <div>
                    <label for="full_name" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['full_name']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <?php if (isset($errors['full_name'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['full_name']); ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['email']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <?php if (isset($errors['email'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['email']); ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="role" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Role <span class="text-red-500">*</span></label>
                    <select name="role" id="role" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['role']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all" <?php if ($user_id_to_edit == $_SESSION['user_id'] && $user_data['role'] === 'admin' && $admin_count !== null && $admin_count <= 1 ): ?>disabled title="Cannot change your own role as the only administrator."<?php elseif ($user_id_to_edit == 1): ?>disabled title="Role of primary administrator (User ID 1) cannot be changed."<?php endif; ?>>
                        <?php foreach ($available_roles as $role_opt): ?>
                            <option value="<?php echo htmlspecialchars($role_opt); ?>" <?php echo ($role === $role_opt) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($role_opt)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['role'])): ?>
                        <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['role']); ?></p>
                    <?php elseif ($user_id_to_edit == $_SESSION['user_id'] && $user_data['role'] === 'admin' && $admin_count !== null && $admin_count <= 1 ): ?>
                        <p class="text-amber-400 text-xs mt-1.5">You cannot change your own role as you are the only administrator.</p>
                    <?php elseif ($user_id_to_edit == 1): ?>
                        <p class="text-amber-400 text-xs mt-1.5">The role of the primary administrator (User ID 1) cannot be changed.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Section 2: Change Password -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">02. Password (Leave Blank to Keep Current)</h2>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">New Password</label>
                        <input type="password" name="password" id="password" class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['password']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <?php if (isset($errors['password'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['password']); ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['confirm_password']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <?php if (isset($errors['confirm_password'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['confirm_password']); ?></p><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-4">
            <a href="index.php" class="w-full sm:w-auto text-center px-6 py-2.5 border border-slate-600 hover:bg-slate-800/50 text-slate-300 hover:text-slate-100 rounded-lg transition-colors duration-200 font-semibold text-sm">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="w-full sm:w-auto bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider py-2.5 px-8 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5 text-sm">
                <i class="fas fa-save mr-2"></i>Update User
            </button>
        </div>
    </form>
</div>

<?php
include_once '../../includes/footer.php';
?>
