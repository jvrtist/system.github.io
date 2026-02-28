<?php
// client_portal/my_account.php
require_once '../config.php';
require_once 'client_auth.php'; 

$page_title = "My Account";
$client_id = $_SESSION[CLIENT_ID_SESSION_VAR];
$errors = [];
$success_message = '';

// Fetch current client data to populate the form
$conn = get_db_connection();
if ($conn) {
    $stmt_fetch = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
    $stmt_fetch->bind_param("i", $client_id);
    $stmt_fetch->execute();
    $client_data = $stmt_fetch->get_result()->fetch_assoc();
    $stmt_fetch->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';

    // --- Handle Password Change ---
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_new_password = $_POST['confirm_new_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
            $errors['form'] = "All password fields are required.";
        } elseif (strlen($new_password) < 8) {
            $errors['new_password'] = "New password must be at least 8 characters long.";
        } elseif ($new_password !== $confirm_new_password) {
            $errors['confirm_new_password'] = "New passwords do not match.";
        } else {
            if ($client_data && password_verify($current_password, $client_data['password_hash'])) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE clients SET password_hash = ? WHERE client_id = ?");
                $stmt_update->bind_param("si", $new_password_hash, $client_id);
                if ($stmt_update->execute()) {
                    $success_message = "Your password has been updated successfully.";
                } else {
                    $errors['form'] = "Failed to update password. Please try again.";
                }
                $stmt_update->close();
            } else {
                $errors['current_password'] = "Your current password is incorrect.";
            }
        }
    }

    // --- Handle Profile Information Update ---
    if ($action === 'update_profile') {
        // Sanitize and retrieve POST data
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $phone_number = sanitize_input($_POST['phone_number']);
        $street_address = sanitize_input($_POST['street_address']);
        $city = sanitize_input($_POST['city']);
        $state_province = sanitize_input($_POST['state_province']);
        $postal_code = sanitize_input($_POST['postal_code']);
        $country = sanitize_input($_POST['country']);

        if (empty($first_name) || empty($last_name)) {
            $errors['form'] = "First and Last Name are required.";
        } else {
            $stmt_update = $conn->prepare(
                "UPDATE clients SET first_name = ?, last_name = ?, phone = ?, address = ?, city = ?, state_province = ?, postal_code = ?, country = ? WHERE client_id = ?"
            );
            $stmt_update->bind_param("ssssssssi", $first_name, $last_name, $phone_number, $street_address, $city, $state_province, $postal_code, $country, $client_id);
            if ($stmt_update->execute()) {
                $success_message = "Your profile has been updated successfully.";
                // Refresh client data to show updated info
                $client_data = array_merge($client_data, [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'phone' => $phone_number,
                    'address' => $street_address,
                    'city' => $city,
                    'state_province' => $state_province,
                    'postal_code' => $postal_code,
                    'country' => $country
                ]);
                // Also update session name variable
                $_SESSION[CLIENT_NAME_SESSION_VAR] = $first_name . ' ' . $last_name;
            } else {
                 $errors['form'] = "Failed to update profile. Please try again.";
            }
            $stmt_update->close();
        }
    }
}
$conn->close();
}
// Display success message from session if it exists (e.g., after a redirect)
if (isset($_SESSION['client_success_message'])) {
    $success_message = $_SESSION['client_success_message'];
    unset($_SESSION['client_success_message']);
}

include_once 'client_header.php';
?>

<div class="max-w-5xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6 animate-fade-in-up">
        <h1 class="text-3xl font-black text-secondary mb-2">My Account</h1>
        <p class="text-slate-600 text-lg">Manage your profile and portal account settings.</p>
    </header>

    <?php if ($success_message): ?>
        <div class="card-premium p-6 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 animate-scale-in">
            <div class="flex items-start gap-3">
                <i class="fas fa-check-circle text-green-600 mt-0.5 flex-shrink-0 text-lg"></i>
                <div class="text-green-800 font-semibold"><?php echo htmlspecialchars($success_message); ?></div>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="card-premium p-6 bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 animate-scale-in">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-red-600 mt-0.5 flex-shrink-0 text-lg"></i>
                <div class="text-red-800 font-semibold">
                    <ul class="list-disc list-inside mt-1">
                        <?php foreach($errors as $error): echo '<li>' . htmlspecialchars($error) . '</li>'; endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="card-premium overflow-hidden animate-fade-in-up" style="animation-delay: 0.1s">
                 <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4">
                    <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                        <button id="tab-btn-profile" onclick="showTab('profile')" class="tab-button active-tab group inline-flex items-center py-3 px-4 border-b-2 font-bold text-sm text-primary border-primary rounded-t-lg transition-all duration-300">
                            <i class="fas fa-user-edit mr-2"></i> Profile Details
                        </button>
                        <button id="tab-btn-password" onclick="showTab('password')" class="tab-button group inline-flex items-center py-3 px-4 border-b-2 font-bold text-sm text-slate-500 hover:text-primary hover:border-primary border-transparent rounded-t-lg transition-all duration-300">
                            <i class="fas fa-key mr-2"></i> Change Password
                        </button>
                    </nav>
                </div>
                <div class="p-8">
                    <div id="profile-content" class="tab-content">
                        <form action="my_account.php" method="POST" class="space-y-6">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="action" value="update_profile">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="first_name" class="block text-sm font-black text-secondary uppercase tracking-widest mb-3">First Name</label>
                                    <input type="text" name="first_name" id="first_name" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 text-lg" value="<?php echo htmlspecialchars($client_data['first_name'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-black text-secondary uppercase tracking-widest mb-3">Last Name</label>
                                    <input type="text" name="last_name" id="last_name" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 text-lg" value="<?php echo htmlspecialchars($client_data['last_name'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-black text-secondary uppercase tracking-widest mb-3">Email (Read-Only)</label>
                                    <input type="email" name="email" id="email" disabled class="w-full bg-slate-100 border border-slate-200 rounded-2xl px-4 py-3 text-slate-500 cursor-not-allowed text-lg" value="<?php echo htmlspecialchars($client_data['email'] ?? ''); ?>">
                                </div>
                                 <div>
                                    <label for="phone_number" class="block text-sm font-black text-secondary uppercase tracking-widest mb-3">Phone Number</label>
                                    <input type="text" name="phone_number" id="phone_number" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 text-lg" value="<?php echo htmlspecialchars($client_data['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="pt-8 border-t border-slate-200">
                                 <h3 class="text-lg font-black text-secondary mb-6 uppercase tracking-widest">Mailing Address</h3>
                                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2">
                                        <label for="street_address" class="block text-sm font-black text-secondary uppercase tracking-widest mb-3">Street Address</label>
                                        <input type="text" name="street_address" id="street_address" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 text-lg" value="<?php echo htmlspecialchars($client_data['address'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="city" class="block text-sm font-black text-secondary uppercase tracking-widest mb-3">City</label>
                                        <input type="text" name="city" id="city" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 text-lg" value="<?php echo htmlspecialchars($client_data['city'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="state_province" class="block text-sm font-black text-secondary uppercase tracking-widest mb-3">State / Province</label>
                                        <input type="text" name="state_province" id="state_province" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 text-lg" value="<?php echo htmlspecialchars($client_data['state_province'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="postal_code" class="block text-sm font-black text-secondary uppercase tracking-widest mb-3">Postal Code</label>
                                        <input type="text" name="postal_code" id="postal_code" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 text-lg" value="<?php echo htmlspecialchars($client_data['postal_code'] ?? ''); ?>">
                                    </div>
                                     <div>
                                        <label for="country" class="block text-sm font-black text-secondary uppercase tracking-widest mb-3">Country</label>
                                        <input type="text" name="country" id="country" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 text-lg" value="<?php echo htmlspecialchars($client_data['country'] ?? ''); ?>">
                                    </div>
                                 </div>
                            </div>
                            <div class="pt-6 text-right">
                                 <button type="submit" class="btn-primary text-lg font-black py-4 px-8 rounded-2xl flex items-center gap-3 ml-auto">
                                    <i class="fas fa-save"></i> Save Profile
                                </button>
                            </div>
                        </form>
                    </div>

                    <div id="password-content" class="tab-content hidden">
                         <form action="my_account.php" method="POST" class="space-y-6">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="action" value="change_password">
                            <div>
                                <label for="current_password" class="block text-sm font-black text-secondary uppercase tracking-widest mb-3">Current Password</label>
                                <input type="password" name="current_password" id="current_password" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 text-lg">
                            </div>
                            <div>
                                <label for="new_password" class="block text-sm font-black text-secondary uppercase tracking-widest mb-3">New Password</label>
                                <input type="password" name="new_password" id="new_password" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 text-lg">
                            </div>
                            <div>
                                <label for="confirm_new_password" class="block text-sm font-black text-secondary uppercase tracking-widest mb-3">Confirm New Password</label>
                                <input type="password" name="confirm_new_password" id="confirm_new_password" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-secondary focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-300 text-lg">
                            </div>
                            <div class="pt-4 text-right">
                                <button type="submit" class="btn-primary text-lg font-black py-4 px-8 rounded-2xl flex items-center gap-3 ml-auto">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="lg:col-span-1">
            <div class="card-premium animate-fade-in-up" style="animation-delay: 0.2s">
                 <h3 class="text-lg font-black text-secondary border-b border-slate-200 pb-4 mb-6 uppercase tracking-widest px-6 pt-6">Account Overview</h3>
                 <div class="px-6 pb-6 space-y-6 text-sm">
                    <div class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl border border-blue-200">
                        <p class="text-slate-500 text-xs uppercase font-bold tracking-widest mb-2">Client Since</p>
                        <p class="text-secondary font-black text-lg"><?php echo date("F j, Y", strtotime($client_data['created_at'])); ?></p>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-2xl border border-green-200">
                        <p class="text-slate-500 text-xs uppercase font-bold tracking-widest mb-2">Last Login</p>
                        <p class="text-secondary font-black text-lg"><?php echo $client_data['last_login_at'] ? date("M j, Y", strtotime($client_data['last_login_at'])) : 'Never'; ?></p>
                    </div>
                 </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        document.querySelectorAll('.tab-button').forEach(b => {
            b.classList.remove('active-tab', 'text-primary', 'border-primary');
            b.classList.add('text-slate-500', 'hover:text-primary', 'hover:border-primary', 'border-transparent');
        });
        document.getElementById(tabName + '-content').classList.remove('hidden');
        document.getElementById('tab-btn-' + tabName).classList.add('active-tab', 'text-primary', 'border-primary');
    }

    // Preserve active tab on form submission error
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($errors)) {
        $active_tab = $_POST['action'] === 'change_password' ? 'password' : 'profile';
        echo "document.addEventListener('DOMContentLoaded', () => showTab('$active_tab'));";
    }
    ?>
</script>


<?php include_once 'client_footer.php'; ?>