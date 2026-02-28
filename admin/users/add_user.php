<?php
/**
 * ISS Investigations - User Account Creation Interface
 * Admin interface for creating new system user accounts with role assignment.
 */
require_once '../../config.php';
require_login();
if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "You do not have permission to access this page.";
    redirect('dashboard.php');
}

$page_title = "Add New User";

// Initialize variables for form fields
$username = '';
$full_name = '';
$email = '';
$role = 'investigator'; // Default role for new users
$password = '';
$confirm_password = '';
$errors = []; // Array to store validation errors

// Define available roles (from your DB schema ENUM for users.role)
$available_roles = ['admin', 'investigator'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token(); // From config.php

    // Sanitize and retrieve form data
    $username = sanitize_input($_POST['username']);
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $role = sanitize_input($_POST['role']);
    $password = $_POST['password']; // Password itself is not sanitized before hashing
    $confirm_password = $_POST['confirm_password'];

    // --- Validation ---
    if (empty($username)) {
        $errors['username'] = "Username is required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors['username'] = "Username must be 3-30 characters, letters, numbers, and underscores only.";
    }

    if (empty($full_name)) {
        $errors['full_name'] = "Full name is required.";
    }

    if (empty($email)) {
        $errors['email'] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters long.";
    }
    // Example: Add more password complexity requirements if needed
    // elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    //     $errors['password'] = "Password must include uppercase, lowercase, and numbers.";
    // }


    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    if (!in_array($role, $available_roles)) {
        $errors['role'] = "Invalid role selected.";
    }

    // Check for existing username or email
    if (empty($errors)) {
        $conn_check = get_db_connection();
        // Check username
        $stmt_check_user = $conn_check->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt_check_user->bind_param("s", $username);
        $stmt_check_user->execute();
        $stmt_check_user->store_result();
        if ($stmt_check_user->num_rows > 0) {
            $errors['username'] = "This username is already taken.";
        }
        $stmt_check_user->close();

        // Check email
        $stmt_check_email = $conn_check->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            $errors['email'] = "This email address is already registered.";
        }
        $stmt_check_email->close();
        // $conn_check->close(); // Not closing here if get_db_connection uses static
    }


    // If no validation errors, proceed to insert into database
    if (empty($errors)) {
        $conn_insert = get_db_connection();
        if ($conn_insert) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT); // Hash the password

            $sql = "INSERT INTO users (username, password_hash, email, full_name, role) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conn_insert->prepare($sql);

            if ($stmt_insert) {
                $stmt_insert->bind_param("sssss",
                    $username,
                    $password_hash,
                    $email,
                    $full_name,
                    $role
                );

                if ($stmt_insert->execute()) {
                    $new_user_id = $stmt_insert->insert_id;
                    $_SESSION['success_message'] = "User '" . htmlspecialchars($username) . "' added successfully!";
                    // Optional: Log this action
                    // log_audit_action($_SESSION['user_id'], 'create_user', 'user', $new_user_id, 'Admin added user: ' . $username);
                    redirect('admin/users/'); // Redirect to the user list
                } else {
                    $_SESSION['error_message'] = "Error adding user: " . $stmt_insert->error;
                    error_log("Add User Error: " . $stmt_insert->error . " SQL: " . $sql);
                }
                $stmt_insert->close();
            } else {
                $_SESSION['error_message'] = "Database statement preparation error: " . $conn_insert->error;
            }
            // $conn_insert->close(); // Not closing here if get_db_connection uses static
        } else {
            $_SESSION['error_message'] = "Database connection failed.";
        }
    } else {
        $_SESSION['error_message'] = "Please correct the errors in the form.";
    }
}

include_once '../../includes/header.php';
?>

<div class="max-w-2xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Create <span class="text-primary">User</span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Add a new system user account with role assignment</p>
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

    <form action="add_user.php" method="POST" class="space-y-6">
        <?php echo csrf_input(); ?>

        <!-- Section 1: Account Credentials -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">01. Account Credentials</h2>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label for="username" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['username']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <?php if (isset($errors['username'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['username']); ?></p><?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" id="password" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['password']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <?php if (isset($errors['password'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['password']); ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Confirm Password <span class="text-red-500">*</span></label>
                        <input type="password" name="confirm_password" id="confirm_password" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['confirm_password']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <?php if (isset($errors['confirm_password'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['confirm_password']); ?></p><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Personal Information -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">02. Personal Information</h2>
            </div>
            <div class="p-6 space-y-6">
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
                    <select name="role" id="role" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['role']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <?php foreach ($available_roles as $role_opt): ?>
                            <option value="<?php echo htmlspecialchars($role_opt); ?>" <?php echo ($role === $role_opt) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($role_opt)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['role'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['role']); ?></p><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-4">
            <a href="index.php" class="w-full sm:w-auto text-center px-6 py-2.5 border border-slate-600 hover:bg-slate-800/50 text-slate-300 hover:text-slate-100 rounded-lg transition-colors duration-200 font-semibold text-sm">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="w-full sm:w-auto bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider py-2.5 px-8 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5 text-sm">
                <i class="fas fa-user-check mr-2"></i>Create User
            </button>
        </div>
    </form>
</div>

<?php
include_once '../../includes/footer.php'; // Adjust path as needed
?>
