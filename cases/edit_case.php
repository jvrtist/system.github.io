<?php
/**
 * ISS Investigations - Case Modification
 * Admin interface for editing existing case details and investigation parameters.
 */
require_once '../config.php';
require_login();
require_once '../includes/notifications.php';

$page_title = "Edit Case Details";
$case_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($case_id <= 0) {
    $_SESSION['error_message'] = "Invalid case ID specified for editing.";
    redirect('cases/');
}

$conn = get_db_connection();
$case_data = null;

// Fetch existing case data
$stmt_fetch = $conn->prepare("SELECT * FROM cases WHERE case_id = ?");
$stmt_fetch->bind_param("i", $case_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
if ($result_fetch->num_rows === 1) {
    $case_data = $result_fetch->fetch_assoc();
} else {
    $_SESSION['error_message'] = "Case not found for editing (ID: $case_id).";
    redirect('cases/');
}
$stmt_fetch->close();

// Fetch data for dropdowns
$clients_list = $conn->query("SELECT client_id, first_name, last_name, company_name FROM clients ORDER BY last_name, first_name")->fetch_all(MYSQLI_ASSOC);
$users_list = $conn->query("SELECT user_id, full_name FROM users WHERE role IN ('investigator', 'admin') ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
$statuses = ['New', 'Open', 'In Progress', 'Pending Client Input', 'On Hold', 'Resolved', 'Closed', 'Archived'];
$priorities = ['Low', 'Medium', 'High', 'Urgent'];

// Initialize variables with existing case data
$client_id = $case_data['client_id'];
$case_title = $case_data['title'];
$case_number = $case_data['case_number'];
$description = $case_data['description'];
$status = $case_data['status'];
$priority = $case_data['priority'];
$date_opened = $case_data['date_opened'];
$date_closed = $case_data['date_closed'];
$assigned_to_user_id = $case_data['assigned_to_user_id'];
$retainer_amount = $case_data['retainer_amount'];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    // Sanitize and retrieve form data
    $client_id = (int)$_POST['client_id'];
    $case_title = sanitize_input($_POST['title']);
    $case_number = sanitize_input($_POST['case_number']);
    $description = sanitize_input($_POST['description']);
    $status = sanitize_input($_POST['status']);
    $priority = sanitize_input($_POST['priority']);
    $date_opened = sanitize_input($_POST['date_opened']);
    $date_closed = !empty($_POST['date_closed']) ? sanitize_input($_POST['date_closed']) : NULL;
    $assigned_to_user_id = !empty($_POST['assigned_to_user_id']) ? (int)$_POST['assigned_to_user_id'] : NULL;
    $retainer_amount = (float)$_POST['retainer_amount'];

    // Validation...
    if (empty($case_title)) $errors['title'] = "Case title is required.";
    // ... other validations from original file

    if (empty($errors)) {
        $sql = "UPDATE cases SET 
                    client_id = ?, title = ?, case_number = ?, description = ?, 
                    status = ?, priority = ?, date_opened = ?, date_closed = ?, 
                    assigned_to_user_id = ?, retainer_amount = ?, updated_at = CURRENT_TIMESTAMP
                WHERE case_id = ?";
        $stmt_update = $conn->prepare($sql);
        $stmt_update->bind_param("issssssiidi",
            $client_id, $case_title, $case_number, $description,
            $status, $priority, $date_opened, $date_closed,
            $assigned_to_user_id, $retainer_amount, $case_id
        );

        if ($stmt_update->execute()) {
            // Check if status changed and create notification
            if ($status !== $case_data['status']) {
                notify_case_updates($case_id, $case_data['status'], $status);
            }

            $_SESSION['success_message'] = "Case '" . htmlspecialchars($case_title) . "' updated successfully!";
            redirect('cases/view_case.php?id=' . $case_id);
        } else {
            $errors['db'] = "Error updating case: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}

$conn->close();

include_once '../includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Edit Case <span class="text-primary">#<?= htmlspecialchars($case_number) ?></span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em]">Modify Investigation Parameters</p>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-shake">
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

    <form action="edit_case.php?id=<?php echo $case_id; ?>" method="POST" class="space-y-6">
        <?php echo csrf_input(); ?>

        <!-- Section 1: Case Identification -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">01. Case Identification</h2>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="client_id" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Client / Subject <span class="text-red-500">*</span></label>
                        <select id="client_id" name="client_id" required class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <?php foreach ($clients_list as $c): ?>
                                <option value="<?php echo $c['client_id']; ?>" <?php echo $client_id == $c['client_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['last_name'] . ", " . $c['first_name'] . ($c['company_name'] ? " [{$c['company_name']}]" : "")); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="case_number" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Case Number <span class="text-red-500">*</span></label>
                        <input type="text" id="case_number" name="case_number" value="<?php echo htmlspecialchars($case_number); ?>" required class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 font-mono text-primary focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label for="title" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Case Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($case_title); ?>" required class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <?php if (isset($errors['title'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['title']); ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Case Description</label>
                    <textarea id="description" name="description" rows="5" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($description); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Section 2: Case Configuration -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">02. Case Configuration</h2>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="status" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Status</label>
                        <select id="status" name="status" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="priority" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Priority Level</label>
                        <select id="priority" name="priority" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <?php foreach ($priorities as $p): ?>
                                <option value="<?php echo htmlspecialchars($p); ?>" <?php echo $priority === $p ? 'selected' : ''; ?>><?php echo htmlspecialchars($p); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="assigned_to_user_id" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Assigned Investigator</label>
                        <select id="assigned_to_user_id" name="assigned_to_user_id" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <option value="">Unassigned</option>
                            <?php foreach ($users_list as $u): ?>
                                <option value="<?php echo $u['user_id']; ?>" <?php echo $assigned_to_user_id == $u['user_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="date_opened" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Date Opened</label>
                        <input type="date" id="date_opened" name="date_opened" value="<?php echo htmlspecialchars($date_opened); ?>" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    </div>
                    <div>
                        <label for="date_closed" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Date Closed</label>
                        <input type="date" id="date_closed" name="date_closed" value="<?php echo htmlspecialchars($date_closed ?? ''); ?>" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    </div>
                    <div>
                        <label for="retainer_amount" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Retainer Amount (R)</label>
                        <input type="number" id="retainer_amount" name="retainer_amount" value="<?php echo htmlspecialchars($retainer_amount ?? 0.00); ?>" step="0.01" min="0" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-4">
            <a href="view_case.php?id=<?php echo $case_id; ?>" class="w-full sm:w-auto text-center px-6 py-2.5 border border-slate-600 hover:bg-slate-800/50 text-slate-300 hover:text-slate-100 rounded-lg transition-colors duration-200 font-semibold text-sm">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="w-full sm:w-auto bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider py-2.5 px-8 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5 text-sm">
                <i class="fas fa-save mr-2"></i>Update Case
            </button>
        </div>
    </form>
</div>

<?php include_once '../includes/footer.php'; ?>
