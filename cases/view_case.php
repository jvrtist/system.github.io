<?php
/**
 * ISS Investigations - Case Details Viewer
 * Comprehensive case management interface with notes, tasks, documents, and client communication.
 */
require_once '../config.php';
require_login();

$page_title = "View Case Details";
$case_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($case_id <= 0) {
    $_SESSION['error_message'] = "Invalid case ID specified.";
    redirect('cases/');
}

$conn = get_db_connection();
$case = null;
$case_notes = [];
$case_tasks = [];
$case_documents = [];
$client_messages = [];
$users_for_task_assignment_list = [];
$note_templates = [];
$total_invoiced = 0.00;

if ($conn) {
    $stmt_case = $conn->prepare("
        SELECT cs.*, cl.client_id as client_id_fk, cl.first_name as client_first_name, cl.last_name as client_last_name, 
               u_assigned.full_name as assigned_to_full_name
        FROM cases cs
        JOIN clients cl ON cs.client_id = cl.client_id
        LEFT JOIN users u_assigned ON cs.assigned_to_user_id = u_assigned.user_id
        WHERE cs.case_id = ?
    ");
    $stmt_case->bind_param("i", $case_id);
    $stmt_case->execute();
    $case = $stmt_case->get_result()->fetch_assoc();
    $stmt_case->close();

    if (!$case) {
        $_SESSION['error_message'] = "Case not found (ID: $case_id).";
        redirect('cases/');
    }

    // Fetch related data
    $stmt_notes = $conn->prepare("SELECT cn.*, u.full_name as noted_by_full_name FROM case_notes cn LEFT JOIN users u ON cn.user_id = u.user_id WHERE cn.case_id = ? ORDER BY cn.created_at DESC");
    $stmt_notes->bind_param("i", $case_id);
    $stmt_notes->execute();
    $case_notes = $stmt_notes->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_notes->close();

    $stmt_tasks = $conn->prepare("SELECT t.*, u.full_name as task_assigned_to_name FROM tasks t LEFT JOIN users u ON t.assigned_to_user_id = u.user_id WHERE t.case_id = ? ORDER BY t.status ASC, t.due_date ASC");
    $stmt_tasks->bind_param("i", $case_id);
    $stmt_tasks->execute();
    $case_tasks = $stmt_tasks->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_tasks->close();

    $stmt_docs = $conn->prepare("SELECT d.*, u.full_name as uploaded_by_full_name FROM documents d LEFT JOIN users u ON d.uploaded_by_user_id = u.user_id WHERE d.case_id = ? ORDER BY d.uploaded_at DESC");
    $stmt_docs->bind_param("i", $case_id);
    $stmt_docs->execute();
    $case_documents = $stmt_docs->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_docs->close();
    
    // Fetch Client Messages
    $stmt_msgs = $conn->prepare("
        SELECT cm.*, 
               cl.first_name as client_first_name, cl.last_name as client_last_name,
               usr.full_name as staff_full_name
        FROM client_messages cm
        LEFT JOIN clients cl ON cm.client_id = cl.client_id AND cm.sent_by_client = TRUE
        LEFT JOIN users usr ON cm.user_id = usr.user_id AND cm.sent_by_client = FALSE
        WHERE cm.case_id = ?
        ORDER BY cm.sent_at ASC
    ");
    $stmt_msgs->bind_param("i", $case_id);
    $stmt_msgs->execute();
    $client_messages = $stmt_msgs->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_msgs->close();

    // Calculate Total Invoiced
    $stmt_inv = $conn->prepare("SELECT SUM(total_amount) as total FROM invoices WHERE case_id = ? AND status != 'Draft'");
    $stmt_inv->bind_param("i", $case_id);
    $stmt_inv->execute();
    $total_invoiced = $stmt_inv->get_result()->fetch_assoc()['total'] ?? 0.00;
    $stmt_inv->close();

    $user_task_result = $conn->query("SELECT user_id, full_name FROM users WHERE role IN ('investigator', 'admin') ORDER BY full_name");
    if ($user_task_result) {
        $users_for_task_assignment_list = $user_task_result->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch Note Templates
    $template_result = $conn->query("SELECT title, content FROM note_templates ORDER BY title ASC");
    if ($template_result) {
        $note_templates = $template_result->fetch_all(MYSQLI_ASSOC);
    }
    
    $conn->close();
}

// Retainer Logic
$retainer_amount = $case['retainer_amount'] ?? 0.00;
$retainer_remaining = max(0, $retainer_amount - $total_invoiced);
$retainer_percent = ($retainer_amount > 0) ? ($retainer_remaining / $retainer_amount) * 100 : 0;
$retainer_color = $retainer_percent > 50 ? 'bg-green-500' : ($retainer_percent > 20 ? 'bg-yellow-500' : 'bg-red-500');

$task_statuses = ['Pending', 'In Progress', 'Completed', 'Deferred'];

include_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/5 pb-6">
        <div>
            <a href="index.php" class="text-xs text-slate-400 hover:text-primary transition-colors mb-3 inline-block font-semibold flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Back to Case List
            </a>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">
                Case File <span class="text-primary">#<?= htmlspecialchars($case['case_number']) ?></span>
            </h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">
                Investigation Management Dashboard
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="edit_case.php?id=<?= $case_id ?>" class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold uppercase tracking-widest px-4 py-2 rounded-lg transition-all">
                <i class="fas fa-edit mr-2"></i>Edit Case
            </a>
            <button onclick="openModal('addNoteModal')" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-widest px-4 py-2 rounded-lg transition-all">
                <i class="fas fa-plus-circle mr-2"></i>Add Note
            </button>
            <button onclick="openModal('addTaskModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-widest px-4 py-2 rounded-lg transition-all">
                <i class="fas fa-tasks mr-2"></i>Add Task
            </button>
            <button onclick="openModal('uploadDocModal')" class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold uppercase tracking-widest px-4 py-2 rounded-lg transition-all">
                <i class="fas fa-upload mr-2"></i>Upload Doc
            </button>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-slate-900 border border-white/5 rounded-2xl p-6">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Case Details</h3>
                <div class="space-y-3 text-sm">
                    <div><p class="text-slate-500 text-xs">Status</p><p><?= get_status_badge($case['status']) ?></p></div>
                    <div><p class="text-slate-500 text-xs">Priority</p><p class="font-bold text-white"><?= htmlspecialchars($case['priority']) ?></p></div>
                    <div><p class="text-slate-500 text-xs">Client</p><p class="font-semibold text-primary hover:underline"><a href="../clients/view_client.php?id=<?= $case['client_id_fk'] ?>"><?= htmlspecialchars($case['client_first_name'] . ' ' . $case['client_last_name']) ?></a></p></div>
                    <div><p class="text-slate-500 text-xs">Assigned Agent</p><p class="text-white"><?= htmlspecialchars($case['assigned_to_full_name'] ?? 'Unassigned') ?></p></div>
                    <div><p class="text-slate-500 text-xs">Date Opened</p><p class="text-white"><?= date("M j, Y", strtotime($case['date_opened'])) ?></p></div>
                </div>
            </div>

            <!-- Retainer Tracker -->
            <div class="bg-slate-900 border border-white/5 rounded-2xl p-6">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Retainer Funds</h3>
                <div class="flex justify-between items-end mb-2">
                    <span class="text-2xl font-bold text-white">R<?= number_format($retainer_remaining, 2) ?></span>
                    <span class="text-xs text-slate-500">of R<?= number_format($retainer_amount, 2) ?></span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-2.5 mb-1">
                    <div class="<?= $retainer_color ?> h-2.5 rounded-full transition-all duration-500" style="width: <?= $retainer_percent ?>%"></div>
                </div>
                <p class="text-[10px] text-slate-500 text-right"><?= number_format($retainer_percent, 0) ?>% Remaining</p>
            </div>

            <div class="bg-slate-900 border border-white/5 rounded-2xl p-6">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Case Description</h3>
                <p class="text-sm text-slate-300 whitespace-pre-wrap"><?= htmlspecialchars($case['description']) ?></p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2">
            <div class="bg-slate-900 border border-white/5 rounded-2xl">
                <div class="border-b border-white/5">
                    <nav class="-mb-px flex space-x-4 px-6" aria-label="Tabs">
                        <button id="tab-btn-notes" onclick="showTab('notes')" class="tab-button active-tab group inline-flex items-center py-3 px-1 border-b-2 font-medium text-sm text-primary border-primary">Notes</button>
                        <button id="tab-btn-tasks" onclick="showTab('tasks')" class="tab-button group inline-flex items-center py-3 px-1 border-b-2 font-medium text-sm text-slate-400 hover:text-primary hover:border-primary border-transparent">Tasks</button>
                        <button id="tab-btn-documents" onclick="showTab('documents')" class="tab-button group inline-flex items-center py-3 px-1 border-b-2 font-medium text-sm text-slate-400 hover:text-primary hover:border-primary border-transparent">Documents</button>
                        <button id="tab-btn-messages" onclick="showTab('messages')" class="tab-button group inline-flex items-center py-3 px-1 border-b-2 font-medium text-sm text-slate-400 hover:text-primary hover:border-primary border-transparent">Messages</button>
                    </nav>
                </div>
                <div class="p-6">
                    <div id="notes-content" class="tab-content space-y-4">
                        <?php foreach($case_notes as $note): ?>
                            <div class="bg-slate-800/50 p-4 rounded-lg">
                                <p class="text-xs text-slate-400 mb-2"><?= htmlspecialchars($note['noted_by_full_name']) ?> on <?= date("M j, Y H:i", strtotime($note['created_at'])) ?></p>
                                <p class="text-sm text-slate-200 whitespace-pre-wrap"><?= htmlspecialchars($note['note_text']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="tasks-content" class="tab-content hidden space-y-3">
                        <?php foreach($case_tasks as $task): ?>
                            <div class="bg-slate-800/50 p-3 rounded-lg flex items-center justify-between">
                                <p class="text-sm text-slate-200"><?= htmlspecialchars($task['description']) ?></p>
                                <select class="task-status-select bg-slate-700 text-xs text-white rounded p-1" data-task-id="<?= $task['task_id'] ?>">
                                    <?php foreach($task_statuses as $status): ?>
                                        <option value="<?= $status ?>" <?= $task['status'] == $status ? 'selected' : '' ?>><?= $status ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="documents-content" class="tab-content hidden space-y-3">
                        <?php foreach($case_documents as $doc): ?>
                            <div class="bg-slate-800/50 p-3 rounded-lg flex items-center justify-between">
                                <div>
                                    <a href="../uploads/<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="text-sm text-primary hover:underline"><?= htmlspecialchars($doc['file_name']) ?></a>
                                    <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($doc['description']) ?></p>
                                    <?php if(!empty($doc['file_hash'])): ?>
                                        <p class="text-[9px] tech-mono text-slate-500 mt-1" title="SHA-256 Hash for Chain of Custody">
                                            <i class="fas fa-fingerprint"></i> <?= substr($doc['file_hash'], 0, 16) ?>...
                                        </p>
                                    <?php endif; ?>
                                    <p class="text-xs mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                            <?php echo ($doc['visibility'] ?? 'Client Visible') === 'Client Visible' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <i class="fas <?php echo ($doc['visibility'] ?? 'Client Visible') === 'Client Visible' ? 'fa-eye' : 'fa-eye-slash'; ?> mr-1"></i>
                                            <?php echo htmlspecialchars($doc['visibility'] ?? 'Client Visible'); ?>
                                        </span>
                                    </p>
                                </div>
                                <span class="text-xs text-slate-500"><?= date("M j, Y", strtotime($doc['uploaded_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="messages-content" class="tab-content hidden space-y-4">
                        <div class="flex justify-end mb-4">
                            <button onclick="openModal('replyMessageModal')" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase px-4 py-2 rounded-lg transition-all">
                                <i class="fas fa-reply mr-2"></i>Reply to Client
                            </button>
                        </div>
                        <?php if (!empty($client_messages)): ?>
                            <?php foreach($client_messages as $msg): 
                                $is_client = $msg['sent_by_client'];
                                $align = $is_client ? 'mr-auto bg-slate-800' : 'ml-auto bg-blue-900/30 border border-blue-500/30';
                            ?>
                                <div class="max-w-[80%] p-4 rounded-xl <?= $align ?>">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-bold <?= $is_client ? 'text-slate-300' : 'text-blue-300' ?>">
                                            <?= htmlspecialchars($is_client ? 'Client' : 'Staff') ?>
                                        </span>
                                        <span class="text-[10px] text-slate-500"><?= date("M j, H:i", strtotime($msg['sent_at'])) ?></span>
                                    </div>
                                    <?php if($msg['message_subject']): ?>
                                        <p class="text-xs font-bold text-white mb-1"><?= htmlspecialchars($msg['message_subject']) ?></p>
                                    <?php endif; ?>
                                    <p class="text-sm text-slate-200 whitespace-pre-wrap"><?= htmlspecialchars($msg['message_content']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-slate-500 text-sm py-8">No messages exchanged yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="addNoteModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 hidden"><div class="flex items-center justify-center min-h-screen"><div class="bg-slate-800 p-6 rounded-2xl shadow-2xl w-full max-w-lg border border-white/10"><h3 class="text-lg font-bold text-white mb-4">Add Case Note</h3><form action="actions/add_note_action.php" method="POST"><?= csrf_input() ?><input type="hidden" name="case_id" value="<?= $case_id ?>">
<div class="mb-4">
    <label for="note_template" class="block text-xs font-bold text-slate-400 mb-1">Quick Template</label>
    <select id="note_template" class="w-full bg-slate-900 border border-white/10 rounded-lg p-2.5 text-sm text-white" onchange="applyTemplate(this.value)">
        <option value="">-- Select Template --</option>
        <?php foreach ($note_templates as $tpl): ?>
            <option value="<?= htmlspecialchars($tpl['content']) ?>"><?= htmlspecialchars($tpl['title']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<textarea name="note_text" id="note_text" rows="5" class="w-full bg-slate-900 border border-white/10 rounded-lg p-3 text-sm text-white" placeholder="Type your note..."></textarea><div class="mt-4 flex justify-end gap-3"><button type="button" onclick="closeModal('addNoteModal')" class="bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold uppercase px-4 py-2 rounded-lg">Cancel</button><button type="submit" class="bg-primary hover:bg-orange-600 text-white text-xs font-bold uppercase px-4 py-2 rounded-lg">Add Note</button></div></form></div></div></div>
<div id="addTaskModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 hidden"><div class="flex items-center justify-center min-h-screen"><div class="bg-slate-800 p-6 rounded-2xl shadow-2xl w-full max-w-lg border border-white/10"><h3 class="text-lg font-bold text-white mb-4">Add New Task</h3><form action="actions/add_task_action.php" method="POST"><?= csrf_input() ?><input type="hidden" name="case_id" value="<?= $case_id ?>"><div class="space-y-4"><textarea name="task_description" rows="3" class="w-full bg-slate-900 border border-white/10 rounded-lg p-3 text-sm text-white" placeholder="Task description..."></textarea><div class="grid grid-cols-2 gap-4"><select name="task_priority" class="w-full bg-slate-900 border border-white/10 rounded-lg p-2.5 text-sm text-white"><option>Low</option><option selected>Medium</option><option>High</option></select><input type="date" name="task_due_date" class="w-full bg-slate-900 border border-white/10 rounded-lg p-2.5 text-sm text-white"></div><select name="task_assigned_to" class="w-full bg-slate-900 border border-white/10 rounded-lg p-2.5 text-sm text-white"><option value="">Unassigned</option><?php foreach($users_for_task_assignment_list as $user): ?><option value="<?= $user['user_id'] ?>"><?= htmlspecialchars($user['full_name']) ?></option><?php endforeach; ?></select></div><div class="mt-4 flex justify-end gap-3"><button type="button" onclick="closeModal('addTaskModal')" class="bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold uppercase px-4 py-2 rounded-lg">Cancel</button><button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase px-4 py-2 rounded-lg">Add Task</button></div></form></div></div></div>
<div id="uploadDocModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 hidden"><div class="flex items-center justify-center min-h-screen"><div class="bg-slate-800 p-6 rounded-2xl shadow-2xl w-full max-w-lg border border-white/10"><h3 class="text-lg font-bold text-white mb-4">Upload Document</h3><form action="actions/upload_document_action.php" method="POST" enctype="multipart/form-data"><?= csrf_input() ?><input type="hidden" name="case_id" value="<?= $case_id ?>"><div class="space-y-4"><input type="file" name="document_file" required class="w-full text-sm text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-orange-600"><textarea name="document_description" rows="2" class="w-full bg-slate-900 border border-white/10 rounded-lg p-3 text-sm text-white" placeholder="Document description..."></textarea><div><label for="document_visibility" class="block text-xs font-bold text-slate-400 mb-1">Visibility</label><select name="document_visibility" id="document_visibility" class="w-full bg-slate-900 border border-white/10 rounded-lg p-2.5 text-sm text-white"><option value="Client Visible" selected>Client Visible</option><option value="Staff Only">Staff Only</option></select></div></div><div class="mt-4 flex justify-end gap-3"><button type="button" onclick="closeModal('uploadDocModal')" class="bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold uppercase px-4 py-2 rounded-lg">Cancel</button><button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold uppercase px-4 py-2 rounded-lg">Upload</button></div></form></div></div></div>
<div id="replyMessageModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 hidden"><div class="flex items-center justify-center min-h-screen"><div class="bg-slate-800 p-6 rounded-2xl shadow-2xl w-full max-w-lg border border-white/10"><h3 class="text-lg font-bold text-white mb-4">Reply to Client</h3><form action="actions/staff_reply_message_action.php" method="POST"><?= csrf_input() ?><input type="hidden" name="case_id" value="<?= $case_id ?>"><input type="hidden" name="client_id" value="<?= $case['client_id_fk'] ?>"><input type="hidden" name="staff_user_id" value="<?= $_SESSION['user_id'] ?>"><div class="space-y-4"><input type="text" name="reply_message_subject" class="w-full bg-slate-900 border border-white/10 rounded-lg p-3 text-sm text-white" placeholder="Subject (Optional)"><textarea name="reply_message_content" rows="5" required class="w-full bg-slate-900 border border-white/10 rounded-lg p-3 text-sm text-white" placeholder="Type your message..."></textarea></div><div class="mt-4 flex justify-end gap-3"><button type="button" onclick="closeModal('replyMessageModal')" class="bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold uppercase px-4 py-2 rounded-lg">Cancel</button><button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase px-4 py-2 rounded-lg">Send Reply</button></div></form></div></div></div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.querySelectorAll('.tab-button').forEach(b => {
        b.classList.remove('text-primary', 'border-primary');
        b.classList.add('text-slate-400', 'border-transparent');
    });
    document.getElementById(tabName + '-content').classList.remove('hidden');
    document.getElementById('tab-btn-' + tabName).classList.add('text-primary', 'border-primary');
}

function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function applyTemplate(content) {
    if (content) {
        const noteText = document.getElementById('note_text');
        // Replace placeholders with current time if needed, or just append
        const now = new Date();
        const timeString = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        content = content.replace('[TIME]', timeString);
        
        if (noteText.value) {
            noteText.value += "\n\n" + content;
        } else {
            noteText.value = content;
        }
    }
}

document.querySelectorAll('.task-status-select').forEach(select => {
    select.addEventListener('change', function() {
        const taskId = this.dataset.taskId;
        const newStatus = this.value;

        fetch('actions/update_task_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?= $_SESSION[CSRF_TOKEN_NAME] ?>'
            },
            body: JSON.stringify({ task_id: taskId, status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Error updating task: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An unexpected error occurred.');
        });
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>
