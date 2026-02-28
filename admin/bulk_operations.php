<?php
// admin/bulk_operations.php - Bulk Operations Interface
require_once '../config.php';
require_login();

if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "Access denied. Admin privileges required.";
    redirect('dashboard.php');
}

$page_title = "Bulk Operations";
$conn = get_db_connection();

// Handle bulk operations
$results = [];
$operation = $_POST['operation'] ?? '';
$entity_type = $_POST['entity_type'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($operation) && !empty($entity_type)) {
    $ids = $_POST['selected_ids'] ?? [];

    if (empty($ids)) {
        $_SESSION['error_message'] = "No items selected for operation.";
        redirect('bulk_operations.php');
    }

    $conn = get_db_connection();
    $success_count = 0;
    $error_count = 0;

    try {
        switch ($entity_type) {
            case 'cases':
                switch ($operation) {
                    case 'status_update':
                        $new_status = $_POST['new_status'] ?? '';
                        if (empty($new_status)) {
                            throw new Exception("New status is required.");
                        }

                        foreach ($ids as $id) {
                            $stmt = $conn->prepare("UPDATE cases SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
                            $stmt->bind_param("si", $new_status, $id);
                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $error_count++;
                            }
                        }
                        break;

                    case 'assign_investigator':
                        $user_id = $_POST['assigned_user_id'] ?? '';
                        if (empty($user_id)) {
                            throw new Exception("Investigator selection is required.");
                        }

                        foreach ($ids as $id) {
                            $stmt = $conn->prepare("UPDATE cases SET assigned_to_user_id = ?, updated_at = CURRENT_TIMESTAMP WHERE case_id = ?");
                            $stmt->bind_param("ii", $user_id, $id);
                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $error_count++;
                            }
                        }
                        break;

                    case 'delete':
                        foreach ($ids as $id) {
                            $stmt = $conn->prepare("DELETE FROM cases WHERE case_id = ?");
                            $stmt->bind_param("i", $id);
                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $error_count++;
                            }
                        }
                        break;
                }
                break;

            case 'clients':
                switch ($operation) {
                    case 'delete':
                        foreach ($ids as $id) {
                            $stmt = $conn->prepare("DELETE FROM clients WHERE client_id = ?");
                            $stmt->bind_param("i", $id);
                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $error_count++;
                            }
                        }
                        break;
                }
                break;

            case 'posts':
                switch ($operation) {
                    case 'status_update':
                        $new_status = $_POST['new_status'] ?? '';
                        if (empty($new_status)) {
                            throw new Exception("New status is required.");
                        }

                        foreach ($ids as $id) {
                            $stmt = $conn->prepare("UPDATE posts SET status = ? WHERE post_id = ?");
                            $stmt->bind_param("si", $new_status, $id);
                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $error_count++;
                            }
                        }
                        break;

                    case 'delete':
                        foreach ($ids as $id) {
                            $stmt = $conn->prepare("DELETE FROM posts WHERE post_id = ?");
                            $stmt->bind_param("i", $id);
                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $error_count++;
                            }
                        }
                        break;
                }
                break;
        }

        $_SESSION['success_message'] = "Bulk operation completed: {$success_count} successful, {$error_count} failed.";

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Bulk operation failed: " . $e->getMessage();
    }

    $conn->close();
    redirect('bulk_operations.php');
}

// Get data for bulk operations interface
$cases = [];
$clients = [];
$posts = [];

if ($conn) {
    // Get recent cases for bulk operations
    $cases = $conn->query("SELECT case_id, case_number, title, status, client_id FROM cases ORDER BY updated_at DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);

    // Get clients
    $clients = $conn->query("SELECT client_id, company_name, email FROM clients ORDER BY company_name LIMIT 50")->fetch_all(MYSQLI_ASSOC);

    // Get posts
    $posts = $conn->query("SELECT post_id, title, status, created_at FROM posts ORDER BY created_at DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);

    // Get users for assignment
    $users = $conn->query("SELECT user_id, full_name FROM users WHERE role IN ('investigator', 'admin') ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

    $conn->close();
}

include_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Bulk <span class="text-primary">Operations</span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Perform actions on multiple items simultaneously</p>
    </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-500/10 border border-green-500/50 rounded-xl p-4 flex items-start gap-3 animate-fade-in">
            <i class="fas fa-check-circle text-green-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-green-200"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-fade-in">
            <i class="fas fa-exclamation-circle text-red-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-red-200"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Operations Tabs -->
    <div class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
        <div class="border-b border-white/5">
            <nav class="flex">
                <button onclick="showTab('cases')" id="cases-tab" class="tab-button active flex-1 px-6 py-4 text-center text-sm font-bold uppercase tracking-widest border-b-2 border-primary text-primary">
                    <i class="fas fa-folder-tree mr-2"></i>Cases
                </button>
                <button onclick="showTab('clients')" id="clients-tab" class="tab-button flex-1 px-6 py-4 text-center text-sm font-bold uppercase tracking-widest text-slate-500 hover:text-white transition-colors">
                    <i class="fas fa-users mr-2"></i>Clients
                </button>
                <button onclick="showTab('posts')" id="posts-tab" class="tab-button flex-1 px-6 py-4 text-center text-sm font-bold uppercase tracking-widest text-slate-500 hover:text-white transition-colors">
                    <i class="fas fa-blog mr-2"></i>Posts
                </button>
            </nav>
        </div>

        <!-- Cases Tab -->
        <div id="cases-content" class="tab-content">
            <form method="POST" onsubmit="return confirmBulkOperation('cases')">
                <input type="hidden" name="entity_type" value="cases">

                <!-- Bulk Actions Bar -->
                <div class="bg-slate-800/50 px-6 py-4 border-b border-white/5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-slate-400">Selected: <span id="cases-selected-count">0</span></span>
                            <select name="operation" id="cases-operation" class="bg-slate-700 border border-white/10 rounded px-3 py-2 text-sm text-white focus:outline-none focus:border-primary/50" onchange="showOperationFields('cases')">
                                <option value="">Choose Operation</option>
                                <option value="status_update">Update Status</option>
                                <option value="assign_investigator">Assign Investigator</option>
                                <option value="delete">Delete Cases</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="selectAll('cases')" class="text-xs text-slate-400 hover:text-white transition-colors">Select All</button>
                            <button type="button" onclick="clearSelection('cases')" class="text-xs text-slate-400 hover:text-white transition-colors">Clear</button>
                            <button type="submit" id="cases-submit" class="bg-primary hover:bg-orange-600 text-white text-xs font-bold uppercase tracking-widest py-2 px-4 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                Execute
                            </button>
                        </div>
                    </div>

                    <!-- Dynamic Fields -->
                    <div id="cases-fields" class="mt-4 hidden">
                        <div id="cases-status-field" class="hidden">
                            <label class="block text-xs font-bold uppercase text-slate-400 mb-2">New Status:</label>
                            <select name="new_status" class="bg-slate-700 border border-white/10 rounded px-3 py-2 text-sm text-white focus:outline-none focus:border-primary/50">
                                <option value="New">New</option>
                                <option value="Open">Open</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Pending Client Input">Pending Client Input</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Closed">Closed</option>
                                <option value="Archived">Archived</option>
                            </select>
                        </div>
                        <div id="cases-assign-field" class="hidden">
                            <label class="block text-xs font-bold uppercase text-slate-400 mb-2">Assign to Investigator:</label>
                            <select name="assigned_user_id" class="bg-slate-700 border border-white/10 rounded px-3 py-2 text-sm text-white focus:outline-none focus:border-primary/50">
                                <option value="">Select Investigator</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['user_id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Cases List -->
                <div class="divide-y divide-white/5">
                    <?php foreach ($cases as $case): ?>
                        <div class="flex items-center gap-4 px-6 py-4 hover:bg-white/[0.02] transition-colors">
                            <input type="checkbox" name="selected_ids[]" value="<?php echo $case['case_id']; ?>" class="cases-checkbox w-4 h-4 text-primary bg-slate-700 border-white/10 rounded focus:ring-primary focus:ring-2">
                            <div class="flex-grow min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] tech-mono text-slate-500">#<?php echo htmlspecialchars($case['case_number']); ?></span>
                                    <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded border
                                        <?php
                                        if ($case['status'] == 'Active') echo 'bg-green-500/10 border-green-500/20 text-green-400';
                                        elseif ($case['status'] == 'Pending') echo 'bg-amber-500/10 border-amber-500/20 text-amber-400';
                                        else echo 'bg-slate-500/10 border-slate-500/20 text-slate-400';
                                        ?>">
                                        <?php echo htmlspecialchars($case['status']); ?>
                                    </span>
                                </div>
                                <h3 class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($case['title']); ?></h3>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <!-- Clients Tab -->
        <div id="clients-content" class="tab-content hidden">
            <form method="POST" onsubmit="return confirmBulkOperation('clients')">
                <input type="hidden" name="entity_type" value="clients">
                <input type="hidden" name="operation" value="delete">

                <!-- Bulk Actions Bar -->
                <div class="bg-slate-800/50 px-6 py-4 border-b border-white/5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-slate-400">Selected: <span id="clients-selected-count">0</span></span>
                            <span class="text-sm font-bold text-red-500">DELETE OPERATION</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="selectAll('clients')" class="text-xs text-slate-400 hover:text-white transition-colors">Select All</button>
                            <button type="button" onclick="clearSelection('clients')" class="text-xs text-slate-400 hover:text-white transition-colors">Clear</button>
                            <button type="submit" id="clients-submit" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-widest py-2 px-4 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                Delete Selected
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Clients List -->
                <div class="divide-y divide-white/5">
                    <?php foreach ($clients as $client): ?>
                        <div class="flex items-center gap-4 px-6 py-4 hover:bg-white/[0.02] transition-colors">
                            <input type="checkbox" name="selected_ids[]" value="<?php echo $client['client_id']; ?>" class="clients-checkbox w-4 h-4 text-primary bg-slate-700 border-white/10 rounded focus:ring-primary focus:ring-2">
                            <div class="flex-grow min-w-0">
                                <h3 class="text-sm font-bold text-white"><?php echo htmlspecialchars($client['company_name']); ?></h3>
                                <p class="text-xs text-slate-400"><?php echo htmlspecialchars($client['email']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <!-- Posts Tab -->
        <div id="posts-content" class="tab-content hidden">
            <form method="POST" onsubmit="return confirmBulkOperation('posts')">
                <input type="hidden" name="entity_type" value="posts">

                <!-- Bulk Actions Bar -->
                <div class="bg-slate-800/50 px-6 py-4 border-b border-white/5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-slate-400">Selected: <span id="posts-selected-count">0</span></span>
                            <select name="operation" id="posts-operation" class="bg-slate-700 border border-white/10 rounded px-3 py-2 text-sm text-white focus:outline-none focus:border-primary/50" onchange="showOperationFields('posts')">
                                <option value="">Choose Operation</option>
                                <option value="status_update">Update Status</option>
                                <option value="delete">Delete Posts</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="selectAll('posts')" class="text-xs text-slate-400 hover:text-white transition-colors">Select All</button>
                            <button type="button" onclick="clearSelection('posts')" class="text-xs text-slate-400 hover:text-white transition-colors">Clear</button>
                            <button type="submit" id="posts-submit" class="bg-primary hover:bg-orange-600 text-white text-xs font-bold uppercase tracking-widest py-2 px-4 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                Execute
                            </button>
                        </div>
                    </div>

                    <!-- Dynamic Fields -->
                    <div id="posts-fields" class="mt-4 hidden">
                        <div id="posts-status-field" class="hidden">
                            <label class="block text-xs font-bold uppercase text-slate-400 mb-2">New Status:</label>
                            <select name="new_status" class="bg-slate-700 border border-white/10 rounded px-3 py-2 text-sm text-white focus:outline-none focus:border-primary/50">
                                <option value="Draft">Draft</option>
                                <option value="Published">Published</option>
                                <option value="Archived">Archived</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Posts List -->
                <div class="divide-y divide-white/5">
                    <?php foreach ($posts as $post): ?>
                        <div class="flex items-center gap-4 px-6 py-4 hover:bg-white/[0.02] transition-colors">
                            <input type="checkbox" name="selected_ids[]" value="<?php echo $post['post_id']; ?>" class="posts-checkbox w-4 h-4 text-primary bg-slate-700 border-white/10 rounded focus:ring-primary focus:ring-2">
                            <div class="flex-grow min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded border
                                        <?php echo $post['status'] == 'Published' ? 'bg-green-500/10 border-green-500/20 text-green-400' : 'bg-slate-500/10 border-slate-500/20 text-slate-400'; ?>">
                                        <?php echo htmlspecialchars($post['status']); ?>
                                    </span>
                                </div>
                                <h3 class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($post['title']); ?></h3>
                                <p class="text-xs text-slate-400"><?php echo date('M j, Y', strtotime($post['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Tab switching
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active', 'border-primary', 'text-primary');
        btn.classList.add('text-slate-500');
    });

    // Show selected tab
    document.getElementById(tabName + '-content').classList.remove('hidden');
    document.getElementById(tabName + '-tab').classList.add('active', 'border-primary', 'text-primary');
    document.getElementById(tabName + '-tab').classList.remove('text-slate-500');
}

// Operation field toggling
function showOperationFields(entityType) {
    const operation = document.getElementById(entityType + '-operation').value;
    const fieldsContainer = document.getElementById(entityType + '-fields');
    const submitBtn = document.getElementById(entityType + '-submit');

    // Hide all fields
    fieldsContainer.querySelectorAll('div').forEach(div => div.classList.add('hidden'));

    // Show relevant fields
    if (operation === 'status_update') {
        document.getElementById(entityType + '-status-field').classList.remove('hidden');
        submitBtn.disabled = false;
    } else if (operation === 'assign_investigator' && entityType === 'cases') {
        document.getElementById(entityType + '-assign-field').classList.remove('hidden');
        submitBtn.disabled = false;
    } else if (operation === 'delete') {
        submitBtn.disabled = false;
    } else {
        submitBtn.disabled = true;
    }

    fieldsContainer.classList.toggle('hidden', operation === '');
}

// Checkbox handling
function updateSelectedCount(entityType) {
    const checkboxes = document.querySelectorAll('.' + entityType + '-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById(entityType + '-selected-count').textContent = count;
    document.getElementById(entityType + '-submit').disabled = count === 0;
}

function selectAll(entityType) {
    document.querySelectorAll('.' + entityType + '-checkbox').forEach(cb => cb.checked = true);
    updateSelectedCount(entityType);
}

function clearSelection(entityType) {
    document.querySelectorAll('.' + entityType + '-checkbox').forEach(cb => cb.checked = false);
    updateSelectedCount(entityType);
}

// Add event listeners to checkboxes
document.querySelectorAll('.cases-checkbox').forEach(cb => {
    cb.addEventListener('change', () => updateSelectedCount('cases'));
});

document.querySelectorAll('.clients-checkbox').forEach(cb => {
    cb.addEventListener('change', () => updateSelectedCount('clients'));
});

document.querySelectorAll('.posts-checkbox').forEach(cb => {
    cb.addEventListener('change', () => updateSelectedCount('posts'));
});

// Confirmation dialog
function confirmBulkOperation(entityType) {
    const count = document.querySelectorAll('.' + entityType + '-checkbox:checked').length;
    const operation = document.getElementById(entityType + '-operation').value;

    let message = `Are you sure you want to perform "${operation}" on ${count} ${entityType}?`;

    if (operation === 'delete') {
        message += '\n\nThis action cannot be undone!';
    }

    return confirm(message);
}
</script>

<?php include_once '../includes/footer.php'; ?>
