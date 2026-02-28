<?php
/**
 * ISS Investigations - Operational Task Creation
 * Interface for creating new investigative tasks linked to cases with assignment capabilities.
 */
require_once '../config.php';
require_login();

$page_title = "Create Operational Task";

// Fetch data for dropdowns
$conn = get_db_connection();
$cases_list = [];
$users_list = []; // Investigators/Admin for assignment
$task_statuses_available = ['Pending', 'In Progress', 'Completed', 'Deferred']; // From tasks table schema
$task_priorities_available = ['Low', 'Medium', 'High', 'Urgent']; // From tasks table schema
$task_categories_available = ['General', 'Client Management', 'Investigation', 'Research', 'Documentation', 'Legal', 'Field Work', 'Administrative'];

if ($conn) {
    // Fetch cases for dropdown
    $case_result = $conn->query("SELECT case_id, case_number, title FROM cases WHERE status NOT IN ('Closed', 'Archived', 'Resolved') ORDER BY case_number ASC");
    if ($case_result) {
        $cases_list = $case_result->fetch_all(MYSQLI_ASSOC);
    }
    // Fetch users (investigators/admins) for assignment dropdown
    $user_result = $conn->query("SELECT user_id, full_name FROM users WHERE role IN ('investigator', 'admin') ORDER BY full_name");
    if ($user_result) {
        $users_list = $user_result->fetch_all(MYSQLI_ASSOC);
    }
    // Fetch task templates
    $template_result = $conn->query("SELECT template_id, name, description, category, estimated_hours, priority FROM task_templates WHERE is_active = TRUE ORDER BY category, name");
    if ($template_result) {
        $task_templates = $template_result->fetch_all(MYSQLI_ASSOC);
    }
}

// Initialize variables for form fields
$case_id_selected = isset($_GET['case_id']) ? (int)$_GET['case_id'] : ''; // Pre-select case if ID is in URL
$task_description = '';
$task_priority_selected = 'Medium'; // Default priority
$task_due_date = '';
$assigned_to_user_id_selected = ''; // Default to unassigned
$task_status_selected = 'Pending'; // Default status for new tasks
$task_category_selected = 'General';
$estimated_hours = '';
$task_notes = '';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    // Sanitize and retrieve form data
    $case_id_selected = isset($_POST['case_id']) ? (int)$_POST['case_id'] : '';
    $task_description = sanitize_input($_POST['description']);
    $task_priority_selected = sanitize_input($_POST['priority']);
    $task_due_date = sanitize_input($_POST['due_date']);
    $assigned_to_user_id_selected = isset($_POST['assigned_to_user_id']) && !empty($_POST['assigned_to_user_id']) ? (int)$_POST['assigned_to_user_id'] : NULL;
    $task_category_selected = sanitize_input($_POST['category']);
    $estimated_hours = isset($_POST['estimated_hours']) && !empty($_POST['estimated_hours']) ? (float)$_POST['estimated_hours'] : NULL;
    $task_notes = sanitize_input($_POST['notes']);


    // --- Validation ---
    if (empty($case_id_selected)) {
        $errors['case_id'] = "A case must be selected for the task.";
    }
    if (empty(trim($task_description))) {
        $errors['description'] = "Task description is required.";
    }
    if (!in_array($task_priority_selected, $task_priorities_available)) {
        $errors['priority'] = "Invalid priority selected.";
    }
    if (!in_array($task_category_selected, $task_categories_available)) {
        $errors['category'] = "Invalid category selected.";
    }
    if (!empty($task_due_date)) {
        $d = DateTime::createFromFormat('Y-m-d', $task_due_date);
        if (!$d || $d->format('Y-m-d') !== $task_due_date) {
            $errors['due_date'] = "Invalid due date format. Please use YYYY-MM-DD.";
        }
    } else {
        $task_due_date = NULL; // Ensure it's NULL if empty
    }
    if ($estimated_hours !== NULL && ($estimated_hours < 0 || $estimated_hours > 999.99)) {
        $errors['estimated_hours'] = "Estimated hours must be between 0 and 999.99.";
    }

    if ($assigned_to_user_id_selected !== NULL && !filter_var($assigned_to_user_id_selected, FILTER_VALIDATE_INT)) {
         $errors['assigned_to_user_id'] = "Invalid user selected for assignment.";
    }


    // If no validation errors, proceed to insert
    if (empty($errors)) {
        $conn_insert = get_db_connection();
        if ($conn_insert) {
            $sql = "INSERT INTO tasks (case_id, assigned_to_user_id, description, due_date, status, priority, estimated_hours, task_category, notes, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $stmt = $conn_insert->prepare($sql);

            if ($stmt) {
                $default_new_task_status = 'Pending'; // New tasks are always pending

                $stmt->bind_param("isssssdsss",
                    $case_id_selected,
                    $assigned_to_user_id_selected,
                    $task_description,
                    $task_due_date,
                    $default_new_task_status,
                    $task_priority_selected,
                    $estimated_hours,
                    $task_category_selected,
                    $task_notes
                );

                if ($stmt->execute()) {
                    $new_task_id = $stmt->insert_id;
                    log_audit_action($_SESSION['user_id'], null, 'create_task', 'task', $new_task_id, "New Task: " . substr($task_description, 0, 100));
                    $_SESSION['success_message'] = "Task created successfully!";
                    
                    // Update the parent case's updated_at timestamp
                    $update_case_stmt = $conn_insert->prepare("UPDATE cases SET updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
                    if($update_case_stmt) {
                        $update_case_stmt->bind_param("i", $case_id_selected);
                        $update_case_stmt->execute();
                        $update_case_stmt->close();
                    } else {
                        error_log("Failed to prepare statement to update case updated_at after task add: " . $conn_insert->error);
                    }

                    redirect('tasks/');
                } else {
                    $_SESSION['error_message'] = "Error adding task: " . $stmt->error;
                    error_log("Add Task Error: " . $stmt->error . " SQL: " . $sql);
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = "Database statement preparation error: " . $conn_insert->error;
            }
        } else {
            $_SESSION['error_message'] = "Database connection failed.";
        }
    } else {
        $_SESSION['error_message'] = "Please correct the errors in the form.";
    }
}


include_once '../includes/header.php';
?>

?>

<div class="max-w-5xl mx-auto space-y-8">
    <div class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Create <span class="text-primary">Operational Task</span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em]">Assign investigative tasks to cases</p>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-shake">
            <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-red-200"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

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

    <form action="add_task.php<?php echo isset($_GET['case_id']) ? '?case_id='.(int)$_GET['case_id'] : ''; ?>" method="POST" class="space-y-6">
        <?php echo csrf_input(); ?>

        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">01. Task Template (Optional)</h2>
            </div>
            <div class="p-6">
                <label for="task_template" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Use Task Template</label>
                <select id="task_template" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <option value="">-- Select a Template (Optional) --</option>
                    <?php
                    $current_category = '';
                    foreach ($task_templates as $template):
                        if ($current_category !== $template['category']):
                            if ($current_category !== '') echo '</optgroup>';
                            echo '<optgroup label="' . htmlspecialchars($template['category']) . '">';
                            $current_category = $template['category'];
                        endif;
                    ?>
                        <option value="<?php echo $template['template_id']; ?>"
                                data-description="<?php echo htmlspecialchars($template['description']); ?>"
                                data-hours="<?php echo $template['estimated_hours']; ?>"
                                data-priority="<?php echo $template['priority']; ?>">
                            <?php echo htmlspecialchars($template['name']); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if (!empty($task_templates)) echo '</optgroup>'; ?>
                </select>
                <p class="text-xs text-slate-500 mt-2">Selecting a template will auto-fill task details</p>
            </div>
        </div>

        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">02. Case Assignment</h2>
            </div>
            <div class="p-6">
                <label for="case_id" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Related Case <span class="text-red-500">*</span></label>
                <select name="case_id" id="case_id" required
                        class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['case_id']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <option value="">-- Select a Case --</option>
                    <?php foreach ($cases_list as $case_opt): ?>
                        <option value="<?php echo $case_opt['case_id']; ?>" <?php echo ($case_id_selected == $case_opt['case_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($case_opt['case_number'] . ' - ' . $case_opt['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['case_id'])): ?>
                    <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['case_id']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">02. Task Details</h2>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label for="description" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Task Description <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="4" required
                              class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['description']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($task_description); ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['description']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="priority" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Priority <span class="text-red-500">*</span></label>
                        <select name="priority" id="priority" required
                                class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['priority']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <?php foreach ($task_priorities_available as $priority_opt): ?>
                                <option value="<?php echo htmlspecialchars($priority_opt); ?>" <?php echo ($task_priority_selected === $priority_opt) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucwords($priority_opt)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['priority'])): ?>
                            <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['priority']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="category" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Category <span class="text-red-500">*</span></label>
                        <select name="category" id="category" required
                                class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['category']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <?php foreach ($task_categories_available as $category_opt): ?>
                                <option value="<?php echo htmlspecialchars($category_opt); ?>" <?php echo ($task_category_selected === $category_opt) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category_opt); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category'])): ?>
                            <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['category']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="due_date" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Due Date (Optional)</label>
                        <input type="date" name="due_date" id="due_date" value="<?php echo htmlspecialchars($task_due_date); ?>"
                               class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['due_date']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all"
                               min="<?php echo date('Y-m-d'); ?>">
                        <?php if (isset($errors['due_date'])): ?>
                            <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['due_date']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="estimated_hours" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Estimated Hours (Optional)</label>
                        <input type="number" name="estimated_hours" id="estimated_hours" value="<?php echo htmlspecialchars($estimated_hours); ?>" step="0.5" min="0" max="999.99"
                               class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['estimated_hours']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <?php if (isset($errors['estimated_hours'])): ?>
                            <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['estimated_hours']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Additional Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($task_notes); ?></textarea>
                </div>
            </div>
        </div>

        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">03. Assignment</h2>
            </div>
            <div class="p-6">
                <label for="assigned_to_user_id" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Assign To (Optional)</label>
                <select name="assigned_to_user_id" id="assigned_to_user_id"
                        class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['assigned_to_user_id']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($users_list as $user_opt): ?>
                        <option value="<?php echo $user_opt['user_id']; ?>" <?php echo ($assigned_to_user_id_selected == $user_opt['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user_opt['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['assigned_to_user_id'])): ?>
                    <p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['assigned_to_user_id']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-4">
            <a href="index.php" class="w-full sm:w-auto text-center px-6 py-2.5 border border-slate-600 hover:bg-slate-800/50 text-slate-300 hover:text-slate-100 rounded-lg transition-colors duration-200 font-semibold text-sm">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="w-full sm:w-auto bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider py-2.5 px-8 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5 text-sm">
                <i class="fas fa-plus-circle mr-2"></i>Create Task
            </button>
        </div>
    </form>
</div>

<script>
// Task template functionality
document.getElementById('task_template').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (!selectedOption.value) {
        // Clear fields if no template selected
        return;
    }

    const description = selectedOption.getAttribute('data-description');
    const hours = selectedOption.getAttribute('data-hours');
    const priority = selectedOption.getAttribute('data-priority');

    // Auto-fill description if empty
    const descField = document.getElementById('description');
    if (!descField.value.trim()) {
        descField.value = description;
    }

    // Auto-fill estimated hours if empty
    const hoursField = document.getElementById('estimated_hours');
    if (!hoursField.value && hours) {
        hoursField.value = hours;
    }

    // Set priority
    const priorityField = document.getElementById('priority');
    if (priority) {
        priorityField.value = priority;
    }
});
</script>

<?php
include_once '../includes/footer.php';
?>
