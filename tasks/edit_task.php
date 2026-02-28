<?php
/**
 * ISS Investigations - Task Modification
 * Admin interface for editing existing operational tasks with assignment and status updates.
 */
require_once '../config.php'; // Adjust path to root config.php
require_login();

$page_title = "Edit Task";
$task_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($task_id_to_edit <= 0) {
    $_SESSION['error_message'] = "Invalid task ID specified for editing.";
    redirect('tasks/');
}

$conn = get_db_connection();
$task_data = null;

// Fetch existing task data
if ($conn) {
    $stmt_fetch = $conn->prepare("SELECT * FROM tasks WHERE task_id = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $task_id_to_edit);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows === 1) {
            $task_data = $result_fetch->fetch_assoc();
            $current_user_id = $_SESSION['user_id'];
            $is_task_owner = ((int)$task_data['created_by_user_id'] === (int)$current_user_id);
            $is_task_assignee = (!empty($task_data['assigned_to_user_id']) && (int)$task_data['assigned_to_user_id'] === (int)$current_user_id);
            if (!user_has_role('admin') && !$is_task_owner && !$is_task_assignee) {
                $_SESSION['error_message'] = "You do not have permission to edit this task.";
                $stmt_fetch->close();
                redirect('tasks/');
            }
        } else {
            $_SESSION['error_message'] = "Task not found for editing (ID: $task_id_to_edit).";
            $stmt_fetch->close();
            // $conn->close(); // Let config handle static connection
            redirect('tasks/');
        }
        $stmt_fetch->close();
    } else {
        $_SESSION['error_message'] = "Error preparing statement to fetch task: " . $conn->error;
        // $conn->close();
        redirect('tasks/');
    }
} else {
    $_SESSION['error_message'] = "Database connection failed.";
    redirect('tasks/');
}

if (!$task_data) { // Should be caught above
    redirect('tasks/');
}

// Fetch data for dropdowns
$cases_list = [];
$users_list = [];
$task_statuses_available = ['Pending', 'In Progress', 'Completed', 'Deferred'];
$task_priorities_available = ['Low', 'Medium', 'High'];

if ($conn) { // Connection might have been closed if initial fetch failed
    $conn = get_db_connection(); // Ensure connection is open

    $case_result = $conn->query("SELECT case_id, case_number, title FROM cases WHERE status NOT IN ('Closed', 'Archived', 'Resolved') ORDER BY case_number ASC");
    if ($case_result) $cases_list = $case_result->fetch_all(MYSQLI_ASSOC);

    $user_result = $conn->query("SELECT user_id, full_name FROM users WHERE role IN ('investigator', 'admin') ORDER BY full_name");
    if ($user_result) $users_list = $user_result->fetch_all(MYSQLI_ASSOC);
}


// Initialize variables with existing task data
$case_id_selected = $task_data['case_id'];
$task_description = $task_data['description'];
$task_priority_selected = $task_data['priority'];
$task_due_date = $task_data['due_date'];
$assigned_to_user_id_selected = $task_data['assigned_to_user_id'];
$task_status_selected = $task_data['status'];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    // Sanitize and retrieve form data
    $case_id_selected = isset($_POST['case_id']) ? (int)$_POST['case_id'] : $case_id_selected;
    $task_description = sanitize_input($_POST['description']);
    $task_priority_selected = sanitize_input($_POST['priority']);
    $task_due_date = sanitize_input($_POST['due_date']);
    $assigned_to_user_id_selected = isset($_POST['assigned_to_user_id']) && !empty($_POST['assigned_to_user_id']) ? (int)$_POST['assigned_to_user_id'] : NULL;
    $task_status_selected = sanitize_input($_POST['status']);


    // --- Validation ---
    if (empty($case_id_selected)) $errors['case_id'] = "A case must be selected.";
    if (empty(trim($task_description))) $errors['description'] = "Task description is required.";
    if (!in_array($task_priority_selected, $task_priorities_available)) $errors['priority'] = "Invalid priority selected.";
    if (!in_array($task_status_selected, $task_statuses_available)) $errors['status'] = "Invalid status selected.";
    
    if (!empty($task_due_date)) {
        $d = DateTime::createFromFormat('Y-m-d', $task_due_date);
        if (!$d || $d->format('Y-m-d') !== $task_due_date) {
            $errors['due_date'] = "Invalid due date format (YYYY-MM-DD).";
        }
    } else {
        $task_due_date = NULL; // Ensure it's NULL if empty
    }
     if ($assigned_to_user_id_selected !== NULL && !filter_var($assigned_to_user_id_selected, FILTER_VALIDATE_INT)) {
         $errors['assigned_to_user_id'] = "Invalid user selected for assignment.";
    }


    // If no validation errors, proceed to update
    if (empty($errors)) {
        $conn_update = get_db_connection();
        if ($conn_update) {
            $sql = "UPDATE tasks SET 
                        case_id = ?, assigned_to_user_id = ?, description = ?, 
                        due_date = ?, status = ?, priority = ?, 
                        updated_at = CURRENT_TIMESTAMP
                    WHERE task_id = ?";
            $stmt_update = $conn_update->prepare($sql);

            if ($stmt_update) {
                $stmt_update->bind_param("iissssi",
                    $case_id_selected,
                    $assigned_to_user_id_selected,
                    $task_description,
                    $task_due_date,
                    $task_status_selected,
                    $task_priority_selected,
                    $task_id_to_edit
                );

                if ($stmt_update->execute()) {
                    $_SESSION['success_message'] = "Task '" . htmlspecialchars(substr($task_description, 0, 50)) . "...' updated successfully!";
                    
                    // Update the parent case's updated_at timestamp
                    $update_case_stmt = $conn_update->prepare("UPDATE cases SET updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
                     if($update_case_stmt) {
                        $update_case_stmt->bind_param("i", $case_id_selected);
                        $update_case_stmt->execute();
                        $update_case_stmt->close();
                    } else {
                        error_log("Failed to prepare statement to update case updated_at after task edit: " . $conn_update->error);
                    }
                    redirect('tasks/'); // Redirect to task list
                } else {
                    $_SESSION['error_message'] = "Error updating task: " . $stmt_update->error;
                    error_log("Edit Task Error: " . $stmt_update->error . " SQL: " . $sql);
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
    }
}

if ($conn) $conn->close();

include_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">
            Edit <span class="text-primary">Task</span>
        </h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Modify operational task parameters</p>
    </header>

    <?php if (isset($_SESSION['error_message']) && empty($errors) ): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-shake">
            <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-red-200"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
     <?php if (!empty($errors) && isset($_SESSION['error_message']) ): ?>
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

    <form action="edit_task.php?id=<?php echo $task_id_to_edit; ?>" method="POST" class="space-y-6">
        <?php echo csrf_input(); ?>
        <input type="hidden" name="task_id" value="<?php echo $task_id_to_edit; ?>">

        <!-- Section 1: Task Assignment -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">01. Task Assignment</h2>
            </div>
            <div class="p-6">
                <label for="case_id" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Related Case <span class="text-red-500">*</span></label>
                <select name="case_id" id="case_id" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['case_id']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <option value="">-- Select a Case --</option>
                    <?php foreach ($cases_list as $case_opt): ?>
                        <option value="<?php echo $case_opt['case_id']; ?>" <?php echo ($case_id_selected == $case_opt['case_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($case_opt['case_number'] . ' - ' . $case_opt['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['case_id'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['case_id']); ?></p><?php endif; ?>
            </div>
        </div>

        <!-- Section 2: Task Details -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">02. Task Details</h2>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label for="description" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Task Description <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="4" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['description']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($task_description); ?></textarea>
                    <?php if (isset($errors['description'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['description']); ?></p><?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="priority" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Priority <span class="text-red-500">*</span></label>
                        <select name="priority" id="priority" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['priority']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <?php foreach ($task_priorities_available as $priority_opt): ?>
                                <option value="<?php echo htmlspecialchars($priority_opt); ?>" <?php echo ($task_priority_selected === $priority_opt) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucwords($priority_opt)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                         <?php if (isset($errors['priority'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['priority']); ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Status <span class="text-red-500">*</span></label>
                        <select name="status" id="status" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['status']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <?php foreach ($task_statuses_available as $status_opt): ?>
                                <option value="<?php echo htmlspecialchars($status_opt); ?>" <?php echo ($task_status_selected === $status_opt) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucwords($status_opt)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                         <?php if (isset($errors['status'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['status']); ?></p><?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="due_date" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Due Date (Optional)</label>
                        <input type="date" name="due_date" id="due_date" value="<?php echo htmlspecialchars($task_due_date ?: ''); ?>" class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['due_date']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all" min="<?php echo date('Y-m-d'); ?>">
                        <?php if (isset($errors['due_date'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['due_date']); ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="assigned_to_user_id" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Assign To (Optional)</label>
                        <select name="assigned_to_user_id" id="assigned_to_user_id" class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['assigned_to_user_id']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($users_list as $user_opt): ?>
                                <option value="<?php echo $user_opt['user_id']; ?>" <?php echo ($assigned_to_user_id_selected == $user_opt['user_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user_opt['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['assigned_to_user_id'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['assigned_to_user_id']); ?></p><?php endif; ?>
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
                <i class="fas fa-save mr-2"></i>Update Task
            </button>
        </div>
    </form>
</div>

<?php
include_once '../includes/footer.php';
?>
