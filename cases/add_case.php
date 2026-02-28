<?php
/**
 * ISS Investigations - Case Initiation
 * Logic for establishing new investigative files.
 */
require_once '../config.php';
require_login();

$page_title = "Initialize Case";
$conn = get_db_connection();

// --- 1. Load System Requirements ---
$clients_list = [];
$users_list = [];
$statuses = ['New', 'Open', 'In Progress', 'Pending Client Input', 'On Hold', 'Resolved', 'Closed', 'Archived'];
$priorities = ['Low', 'Medium', 'High', 'Urgent'];

if ($conn) {
    $clients_list = $conn->query("SELECT client_id, first_name, last_name, company_name FROM clients ORDER BY last_name ASC")->fetch_all(MYSQLI_ASSOC);
    $users_list = $conn->query("SELECT user_id, full_name FROM users WHERE role IN ('investigator', 'admin') ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);
}

// --- 2. State Initialization ---
$client_id = (int)($_GET['client_id'] ?? 0);
$case_title = $description = $case_num = '';
$status = 'New';
$priority = 'Medium';
$date_opened = date('Y-m-d');
$assigned_to = '';
$retainer_amount = 0.00;
$errors = [];

// --- 3. Processing Logic ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $client_id   = (int)$_POST['client_id'];
    $case_title  = sanitize_input($_POST['title']);
    $case_num    = sanitize_input($_POST['case_number']);
    $description = sanitize_input($_POST['description']);
    $status      = sanitize_input($_POST['status']);
    $priority    = sanitize_input($_POST['priority']);
    $date_opened = sanitize_input($_POST['date_opened']);
    $assigned_to = !empty($_POST['assigned_to_user_id']) ? (int)$_POST['assigned_to_user_id'] : NULL;
    $retainer_amount = (float)$_POST['retainer_amount'];

    // Validation
    if (!$client_id) $errors['client_id'] = "Valid client selection required.";
    if (empty($case_title)) $errors['title'] = "Protocol requires a case title.";
    if (empty($case_num)) $errors['case_number'] = "Unique case identifier required.";

    // Check for Duplicate Case Number
    $check_stmt = $conn->prepare("SELECT case_id FROM cases WHERE case_number = ?");
    $check_stmt->bind_param("s", $case_num);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $errors['case_number'] = "Identifier conflict: $case_num is already assigned.";
    }

    if (empty($errors)) {
        $sql = "INSERT INTO cases (client_id, case_number, title, description, status, priority, date_opened, assigned_to_user_id, retainer_amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("issssssid", 
                $client_id, $case_num, $case_title, $description, 
                $status, $priority, $date_opened, $assigned_to, $retainer_amount
            );

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Case $case_num successfully initialized.";
                redirect('cases/view_case.php?id=' . $stmt->insert_id);
            } else {
                $_SESSION['error_message'] = "Critical Database Failure: " . $stmt->error;
            }
        }
    }
}

$suggested_num = "ISS-" . date('Y') . "-" . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

include_once '../includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Initialize <span class="text-primary">New Case</span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em]">Deployment Phase: Documentation & Assignment</p>
    </header>

    <form action="add_case.php" method="POST" class="space-y-6">
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
                        <select id="client_id" name="client_id" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['client_id']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <option value="">Select a client...</option>
                            <?php foreach ($clients_list as $c): ?>
                                <option value="<?php echo $c['client_id']; ?>" <?php echo $client_id == $c['client_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['last_name'] . ", " . $c['first_name'] . ($c['company_name'] ? " [{$c['company_name']}]" : "")); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['client_id'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['client_id']); ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="case_number" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Case Number <span class="text-red-500">*</span></label>
                        <input type="text" id="case_number" name="case_number" value="<?php echo htmlspecialchars($case_num ?: $suggested_num); ?>" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['case_number']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 font-mono text-primary focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <?php if (isset($errors['case_number'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['case_number']); ?></p><?php endif; ?>
                    </div>
                </div>

                <div>
                    <label for="title" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Case Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($case_title); ?>" placeholder="e.g., Surveillance Operation - Project Phoenix" required class="w-full px-4 py-2.5 bg-slate-800 border <?php echo isset($errors['title']) ? 'border-red-500' : 'border-slate-700'; ?> rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    <?php if (isset($errors['title'])): ?><p class="text-red-400 text-xs mt-1.5 flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['title']); ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Case Description / Brief</label>
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
                        <label for="status" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Initial Status</label>
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
                        <label for="date_opened" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Date Opened</label>
                        <input type="date" id="date_opened" name="date_opened" value="<?php echo htmlspecialchars($date_opened); ?>" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="assigned_to_user_id" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Assign Primary Investigator</label>
                        <select id="assigned_to_user_id" name="assigned_to_user_id" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <option value="">Unassigned</option>
                            <?php foreach ($users_list as $u): ?>
                                <option value="<?php echo $u['user_id']; ?>" <?php echo $assigned_to == $u['user_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="retainer_amount" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Retainer Amount (R)</label>
                        <input type="number" id="retainer_amount" name="retainer_amount" value="<?php echo htmlspecialchars($retainer_amount); ?>" step="0.01" min="0" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
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
                <i class="fas fa-plus mr-2"></i>Initialize Case
            </button>
        </div>
    </form>
</div>

<?php include_once '../includes/footer.php'; ?>
