<?php
// client_portal/view_case_details.php
require_once '../config.php';
require_once 'client_sensitive_auth.php';

$client_id = $_SESSION[CLIENT_ID_SESSION_VAR];
$case_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($case_id <= 0) {
    redirect('client_portal/cases.php');
}

$conn = get_db_connection();
// Initialize all data arrays
$case = null;
$client_visible_notes = [];
$client_visible_documents = [];
$case_messages = [];
$messages_by_id = [];
$case_invoices = [];
$case_tasks = [];
$financial_summary = ['total_invoiced' => 0.00, 'total_paid' => 0.00, 'balance' => 0.00];

if ($conn) {
    // Fetch Case Details
    $stmt_case = $conn->prepare("SELECT c.*, u.full_name as assigned_to_full_name FROM cases c LEFT JOIN users u ON c.assigned_to_user_id = u.user_id WHERE c.case_id = ? AND c.client_id = ?");
    $stmt_case->bind_param("ii", $case_id, $client_id);
    $stmt_case->execute();
    $result_case = $stmt_case->get_result();
    if ($result_case->num_rows !== 1) {
        $_SESSION['client_error_message'] = "Case not found or you do not have permission to view it.";
        redirect('client_portal/cases.php');
    }
    $case = $result_case->fetch_assoc();
    $stmt_case->close();

    // Fetch Client Visible Case Notes
    $stmt_notes = $conn->prepare("SELECT cn.*, u.full_name as noted_by_full_name FROM case_notes cn LEFT JOIN users u ON cn.user_id = u.user_id WHERE cn.case_id = ? AND cn.visibility = 'Client Visible' ORDER BY cn.created_at DESC");
    $stmt_notes->bind_param("i", $case_id);
    $stmt_notes->execute();
    $client_visible_notes = $stmt_notes->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_notes->close();

    // Fetch Client Visible Case Documents
    $stmt_docs = $conn->prepare("SELECT d.document_id, d.file_name, d.description, d.uploaded_at, u.full_name as uploaded_by_full_name FROM documents d LEFT JOIN users u ON d.uploaded_by_user_id = u.user_id WHERE d.case_id = ? AND d.visibility = 'Client Visible' ORDER BY d.uploaded_at DESC");
    $stmt_docs->bind_param("i", $case_id);
    $stmt_docs->execute();
    $client_visible_documents = $stmt_docs->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_docs->close();
    
    // Fetch All Messages
    $stmt_messages = $conn->prepare("SELECT cm.*, u.full_name as staff_full_name FROM client_messages cm LEFT JOIN users u ON cm.user_id = u.user_id AND cm.sent_by_client = FALSE WHERE cm.case_id = ? ORDER BY cm.sent_at ASC");
    $stmt_messages->bind_param("i", $case_id);
    $stmt_messages->execute();
    $case_messages = $stmt_messages->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_messages->close();
    
    // Create a lookup map for message replies
    foreach ($case_messages as $message) {
        $messages_by_id[$message['message_id']] = $message;
    }

    // Mark messages from staff as read
    $stmt_mark_read = $conn->prepare("UPDATE client_messages SET is_read_by_client = TRUE WHERE case_id = ? AND client_id = ? AND sent_by_client = FALSE");
    $stmt_mark_read->bind_param("ii", $case_id, $client_id);
    $stmt_mark_read->execute();
    $stmt_mark_read->close();

    // Fetch Invoices for this case
    $stmt_invoices = $conn->prepare("SELECT invoice_id, invoice_number, status, total_amount, amount_paid, invoice_date FROM invoices WHERE case_id = ? AND client_id = ? AND status != 'Draft' ORDER BY invoice_date DESC");
    $stmt_invoices->bind_param("ii", $case_id, $client_id);
    $stmt_invoices->execute();
    $case_invoices = $stmt_invoices->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_invoices->close();
    
    // Calculate financial summary for this case
    foreach ($case_invoices as $invoice) {
        $financial_summary['total_invoiced'] += $invoice['total_amount'];
        $financial_summary['total_paid'] += $invoice['amount_paid'];
    }
    $financial_summary['balance'] = $financial_summary['total_invoiced'] - $financial_summary['total_paid'];

    // Fetch Tasks for this case
    $stmt_tasks = $conn->prepare("SELECT description, status, due_date FROM tasks WHERE case_id = ? ORDER BY due_date ASC");
    $stmt_tasks->bind_param("i", $case_id);
    $stmt_tasks->execute();
    $case_tasks = $stmt_tasks->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_tasks->close();

    $conn->close();
}

function get_status_badge_color($status) {
    switch (strtolower($status)) {
        case 'active': return 'bg-green-100 text-green-800 border-green-200';
        case 'open': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'closed': return 'bg-slate-100 text-slate-800 border-slate-200';
        case 'resolved': return 'bg-teal-100 text-teal-800 border-teal-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
}

$page_title = "Case: " . htmlspecialchars($case['title']);
include_once 'client_header.php';
?>

<div class="max-w-7xl mx-auto space-y-6">
    <!-- Breadcrumb & Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 no-print">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-blue-600">
                        <i class="fas fa-home mr-2"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-slate-400 mx-1"></i>
                        <a href="cases.php" class="ml-1 text-sm font-medium text-slate-500 hover:text-blue-600">My Cases</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-slate-400 mx-1"></i>
                        <span class="ml-1 text-sm font-medium text-slate-800 md:ml-2">Case #<?php echo htmlspecialchars($case['case_number']); ?></span>
                    </div>
                </li>
            </ol>
        </nav>
        <div class="flex items-center gap-2">
            <button type="button" onclick="openMessageModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm text-sm flex items-center transition-colors">
                <i class="fas fa-paper-plane mr-2"></i> Send Message
            </button>
            <button type="button" onclick="window.print()" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium py-2 px-4 rounded-lg shadow-sm text-sm flex items-center transition-colors">
                <i class="fas fa-print mr-2"></i> Print
            </button>
        </div>
    </div>
 <!-- Print Header (Hidden on screen, visible when printing) -->
    <div class="hidden print:block print:mb-8">
        <div class="text-center border-slate-800 pb-6 mb-8">
            <div class="flex items-center justify-center mb-4">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 mb-1">ISS Investigations</h1>
                    <p class="text-lg text-slate-600">Professional Investigation Services</p>
                </div>
            </div>
            <div class="text-center">
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Case Report</h2>
                <p class="text-lg text-slate-600">Case Number: <?php echo htmlspecialchars($case['case_number']); ?></p>
                <p class="text-sm text-slate-500 mt-2">Generated on <?php echo date("F j, Y \a\\t g:i A"); ?></p>
            </div>
        </div>
    </div>

    <!-- Case Header Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 no-print">
        <div class="flex flex-col md:flex-row justify-between items-start gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider border <?php echo get_status_badge_color($case['status']); ?>">
                        <?php echo htmlspecialchars($case['status']); ?>
                    </span>
                    <span class="text-sm text-slate-500 font-mono">#<?php echo htmlspecialchars($case['case_number']); ?></span>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-slate-800"><?php echo htmlspecialchars($case['title']); ?></h1>
            </div>
            <div class="flex flex-col items-end gap-1 text-sm text-slate-500">
                <p>Opened: <span class="font-medium text-slate-800"><?php echo date("M j, Y", strtotime($case['created_at'])); ?></span></p>
                <p>Last Updated: <span class="font-medium text-slate-800"><?php echo date("M j, Y", strtotime($case['updated_at'])); ?></span></p>
                <p>Investigator: <span class="font-medium text-slate-800"><?php echo htmlspecialchars($case['assigned_to_full_name'] ?: 'Unassigned'); ?></span></p>
            </div>
        </div>
    </div>

   

    <!-- Print-Only Content Layout (Hidden on screen, optimized for printing) -->
    <div class="hidden print:block print:space-y-8">
	 <!-- Case Summary Sidebar -->
        <section class="print-section">
            <h2 class="text-2xl font-bold text-black mb-6 border-b-2 border-black pb-2">Case Summary</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-gray-600 text-sm uppercase font-bold">Status</dt>
                    <dd class="font-medium text-black"><?php echo htmlspecialchars(ucwords($case['status'])); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 text-sm uppercase font-bold">Priority</dt>
                    <dd class="font-medium text-black"><?php echo htmlspecialchars(ucwords($case['priority'])); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 text-sm uppercase font-bold">Assigned To</dt>
                    <dd class="font-medium text-black"><?php echo htmlspecialchars($case['assigned_to_full_name'] ?: 'Unassigned'); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 text-sm uppercase font-bold">Opened</dt>
                    <dd class="font-medium text-black"><?php echo date("M j, Y", strtotime($case['created_at'])); ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-600 text-sm uppercase font-bold">Last Updated</dt>
                    <dd class="font-medium text-black"><?php echo date("M j, Y", strtotime($case['updated_at'])); ?></dd>
                </div>
            </dl>
        </section>

        <!-- Notes Section -->
        <section class="print-section">
            <h2 class="text-2xl font-bold text-black mb-6 border-b-2 border-black pb-2">Case Notes & Updates</h2>
            <div class="space-y-4">
                <?php if (!empty($client_visible_notes)): ?>
                    <?php foreach($client_visible_notes as $note): ?>
                        <div class="border border-gray-300 rounded-lg p-4 bg-white">
                            <div class="flex justify-between items-start mb-2">
                                <strong class="text-black"><?php echo htmlspecialchars($note['noted_by_full_name'] ?: 'Staff'); ?></strong>
                                <span class="text-gray-600 text-sm"><?php echo date("M j, Y, g:i a", strtotime($note['created_at'])); ?></span>
                            </div>
                            <div class="text-black leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($note['note_text'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-black font-medium">No updates available</p>
                    </div>
                <?php endif; ?>
            </div>
			
			 <!-- Tasks List -->
            <div class="mt-6">
                <h3 class="text-lg font-bold text-black mb-4">Tasks & Milestones</h3>
                <?php if (!empty($case_tasks)): ?>
                    <ul class="space-y-2">
                        <?php foreach($case_tasks as $task): 
                            $is_complete = strtolower($task['status']) === 'completed';
                        ?>
                            <li class="flex items-start border border-gray-300 rounded-lg p-3 bg-white">
                                <div class="flex-shrink-0 mt-0.5 mr-3">
                                    <?php if ($is_complete): ?>
                                        <span class="text-green-600 text-lg">✓</span>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-lg">○</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium <?php echo $is_complete ? 'text-gray-500 line-through' : 'text-black'; ?>">
                                        <?php echo htmlspecialchars($task['description']); ?>
                                    </p>
                                    <div class="flex gap-3 mt-1 text-sm text-gray-600">
                                        <span>Status: <?php echo htmlspecialchars($task['status']); ?></span>
                                        <?php if($task['due_date']): ?>
                                            <span>Due: <?php echo date("M j, Y", strtotime($task['due_date'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-black italic">No tasks visible.</p>
                <?php endif; ?>
            </div>
			
        </section>
 <!-- Messages Section -->
        <section class="print-section page-break">
            <h2 class="text-2xl font-bold text-black mb-6 border-b-2 border-black pb-2">Messages & Communication</h2>
            <div class="space-y-4">
                <?php if (!empty($case_messages)): ?>
                    <?php foreach($case_messages as $message):
                        $is_client_sender = $message['sent_by_client'];
                        $sender_name = $is_client_sender ? 'You' : htmlspecialchars($message['staff_full_name'] ?: 'ISS Staff');
                    ?>
                        <div class="border border-gray-300 rounded-lg p-4 bg-white">
                            <div class="flex justify-between items-start mb-2">
                                <strong class="text-black"><?php echo $sender_name; ?></strong>
                                <span class="text-gray-600 text-sm"><?php echo date("M j, Y, g:i a", strtotime($message['sent_at'])); ?></span>
                            </div>
                            <?php if (!empty($message['message_subject'])): ?>
                                <h4 class="font-semibold text-black mb-2"><?php echo htmlspecialchars($message['message_subject']); ?></h4>
                            <?php endif; ?>
                            <div class="text-black leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($message['message_content'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-black">No messages have been exchanged for this case.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Documents Section -->
        <section class="print-section">
            <h2 class="text-2xl font-bold text-black mb-6 border-b-2 border-black pb-2">Case Documents & Files</h2>
            <?php if (!empty($client_visible_documents)): ?>
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach($client_visible_documents as $doc): ?>
                        <div class="border border-gray-300 rounded-lg p-4 bg-white">
                            <h3 class="font-semibold text-black mb-1"><?php echo htmlspecialchars($doc['file_name']); ?></h3>
                            <p class="text-gray-700 mb-2"><?php echo htmlspecialchars($doc['description']); ?></p>
                            <div class="text-sm text-gray-600">
                                <span>Uploaded: <?php echo date("M j, Y", strtotime($doc['uploaded_at'])); ?></span>
                                <span class="ml-4">By: <?php echo htmlspecialchars($doc['uploaded_by_full_name'] ?: 'Staff'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-black font-medium">No documents shared</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Financials Section -->
        <section class="print-section">
            <h2 class="text-2xl font-bold text-black mb-6 border-b-2 border-black pb-2">Billing & Financial Information</h2>
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="border border-gray-300 rounded-lg p-4 bg-white text-center">
                    <p class="text-sm text-gray-600 uppercase font-bold mb-1">Total Invoiced</p>
                    <p class="text-2xl font-black text-black">R<?php echo number_format($financial_summary['total_invoiced'], 2); ?></p>
                </div>
                <div class="border border-gray-300 rounded-lg p-4 bg-white text-center">
                    <p class="text-sm text-gray-600 uppercase font-bold mb-1">Total Paid</p>
                    <p class="text-2xl font-black text-black">R<?php echo number_format($financial_summary['total_paid'], 2); ?></p>
                </div>
                <div class="border border-gray-300 rounded-lg p-4 bg-white text-center">
                    <p class="text-sm text-gray-600 uppercase font-bold mb-1">Balance Due</p>
                    <p class="text-2xl font-black text-black">R<?php echo number_format($financial_summary['balance'], 2); ?></p>
                </div>
            </div>

            <!-- Invoices List -->
            <div>
                <h3 class="text-lg font-bold text-black mb-4">Invoices</h3>
                <?php if (!empty($case_invoices)): ?>
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-300 px-4 py-2 text-left text-black font-bold">Invoice #</th>
                                <th class="border border-gray-300 px-4 py-2 text-left text-black font-bold">Date</th>
                                <th class="border border-gray-300 px-4 py-2 text-left text-black font-bold">Status</th>
                                <th class="border border-gray-300 px-4 py-2 text-right text-black font-bold">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($case_invoices as $invoice): ?>
                                <tr>
                                    <td class="border border-gray-300 px-4 py-2 text-black"><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                    <td class="border border-gray-300 px-4 py-2 text-black"><?php echo date("M j, Y", strtotime($invoice['invoice_date'])); ?></td>
                                    <td class="border border-gray-300 px-4 py-2 text-black"><?php echo htmlspecialchars($invoice['status']); ?></td>
                                    <td class="border border-gray-300 px-4 py-2 text-black text-right">R<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-black italic">No invoices generated for this case.</p>
                <?php endif; ?>
            </div>

           
        </section>

       
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content Area -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Tabs Navigation -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden no-print">
                <nav class="flex divide-x divide-slate-200 bg-slate-50" aria-label="Tabs">
                    <button id="tab-btn-messages" onclick="showTab('messages')" class="tab-button active-tab flex-1 py-4 px-4 text-center text-sm font-medium text-blue-600 bg-white border-b-2 border-blue-600 hover:bg-white transition-colors">
                        <i class="fas fa-comments mr-2"></i> Messages
                    </button>
                    <button id="tab-btn-notes" onclick="showTab('notes')" class="tab-button flex-1 py-4 px-4 text-center text-sm font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-100 border-b-2 border-transparent transition-colors">
                        <i class="fas fa-sticky-note mr-2"></i> Notes
                    </button>
                    <button id="tab-btn-documents" onclick="showTab('documents')" class="tab-button flex-1 py-4 px-4 text-center text-sm font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-100 border-b-2 border-transparent transition-colors">
                        <i class="fas fa-folder-open mr-2"></i> Files
                    </button>
                    <button id="tab-btn-financials" onclick="showTab('financials')" class="tab-button flex-1 py-4 px-4 text-center text-sm font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-100 border-b-2 border-transparent transition-colors">
                        <i class="fas fa-file-invoice-dollar mr-2"></i> Billing
                    </button>
                </nav>
                
                <div class="p-6">
                    <!-- Messages Tab -->
                    <div id="messages-content" class="tab-content">
                        <h3 class="text-xl font-bold text-slate-800 mb-6 print:text-2xl">Messages & Communication</h3>
                        <?php if (!empty($case_messages)): ?>
                            <?php foreach($case_messages as $message):
                                $is_client_sender = $message['sent_by_client'];
                                $sender_name = $is_client_sender ? 'You' : htmlspecialchars($message['staff_full_name'] ?: 'ISS Staff');
                                $replied_to_msg = null;
                                if (!empty($message['replied_to_message_id']) && isset($messages_by_id[$message['replied_to_message_id']])) {
                                    $replied_to_msg = $messages_by_id[$message['replied_to_message_id']];
                                }
                            ?>
                                <div class="flex gap-4 <?php echo $is_client_sender ? 'flex-row-reverse' : ''; ?>">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-sm <?php echo $is_client_sender ? 'bg-blue-600' : 'bg-slate-500'; ?>">
                                            <?php echo $is_client_sender ? 'ME' : 'S'; ?>
                                        </div>
                                    </div>
                                    <div class="flex-1 max-w-2xl">
                                        <div class="rounded-2xl p-5 shadow-sm <?php echo $is_client_sender ? 'bg-blue-50 border border-blue-100 rounded-tr-none' : 'bg-white border border-slate-200 rounded-tl-none'; ?>">
                                            <div class="flex justify-between items-start mb-2">
                                                <span class="font-bold text-sm <?php echo $is_client_sender ? 'text-blue-800' : 'text-slate-800'; ?>">
                                                    <?php echo $sender_name; ?>
                                                </span>
                                                <span class="text-xs text-slate-400">
                                                    <?php echo date("M j, g:i a", strtotime($message['sent_at'])); ?>
                                                </span>
                                            </div>

                                            <?php if ($replied_to_msg): ?>
                                                <div class="mb-3 p-3 bg-white/50 border-l-2 border-slate-300 rounded text-xs text-slate-500 italic">
                                                    Replying to: "<?php echo htmlspecialchars(substr($replied_to_msg['message_content'], 0, 60)) . (strlen($replied_to_msg['message_content']) > 60 ? '...' : ''); ?>"
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($message['message_subject']) && !$replied_to_msg): ?>
                                                <h4 class="font-semibold text-sm mb-2 text-slate-700"><?php echo htmlspecialchars($message['message_subject']); ?></h4>
                                            <?php endif; ?>

                                            <div class="prose prose-sm max-w-none text-slate-600">
                                                <?php echo nl2br(htmlspecialchars($message['message_content'])); ?>
                                            </div>

                                            <?php if (!$is_client_sender): ?>
                                                <div class="mt-3 pt-3 border-t border-slate-100 flex justify-end no-print">
                                                    <button onclick="openMessageModal(<?php echo (int)$message['message_id']; ?>, <?php echo json_encode($message['message_content']); ?>, <?php echo json_encode($sender_name); ?>)" class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                                        <i class="fas fa-reply"></i> Reply
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center py-8">No messages have been exchanged for this case.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Notes Tab -->
                    <div id="notes-content" class="tab-content hidden">
                        <h3 class="text-xl font-bold text-slate-800 mb-6 print:text-2xl">Case Notes & Updates</h3>
                        <?php if (!empty($client_visible_notes)): ?>
                            <?php foreach($client_visible_notes as $note): ?>
                                <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-5 shadow-sm relative overflow-hidden">
                                    <div class="absolute top-0 right-0 w-16 h-16 bg-yellow-100 rounded-bl-full -mr-8 -mt-8 opacity-50"></div>
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-8 h-8 rounded-full bg-yellow-200 flex items-center justify-center text-yellow-700">
                                            <i class="fas fa-sticky-note text-xs"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($note['noted_by_full_name'] ?: 'Staff'); ?></p>
                                            <p class="text-xs text-slate-500"><?php echo date("M j, Y, g:i a", strtotime($note['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="prose prose-sm text-slate-700 pl-11">
                                        <?php echo nl2br(htmlspecialchars($note['note_text'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                                <i class="fas fa-sticky-note fa-3x text-slate-300 mb-3"></i>
                                <p class="text-slate-500 font-medium">No updates available</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Documents Tab -->
                    <div id="documents-content" class="tab-content hidden">
                        <h3 class="text-xl font-bold text-slate-800 mb-6 print:text-2xl">Case Documents & Files</h3>
                        <?php if (!empty($client_visible_documents)): ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php foreach($client_visible_documents as $doc): ?>
                                    <div class="group bg-white border border-slate-200 rounded-xl p-4 hover:border-blue-300 hover:shadow-md transition-all">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                                                <i class="fas fa-file-alt fa-lg"></i>
                                            </div>
                                            <a href="download_document.php?id=<?php echo $doc['document_id']; ?>" class="text-slate-400 hover:text-blue-600 transition-colors">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                        <h3 class="font-semibold text-slate-800 truncate mb-1" title="<?php echo htmlspecialchars($doc['file_name']); ?>">
                                            <?php echo htmlspecialchars($doc['file_name']); ?>
                                        </h3>
                                        <p class="text-xs text-slate-500 mb-3 line-clamp-2"><?php echo htmlspecialchars($doc['description']); ?></p>
                                        <div class="flex justify-between items-center text-xs text-slate-400 border-t border-slate-100 pt-3">
                                            <span><?php echo date("M j, Y", strtotime($doc['uploaded_at'])); ?></span>
                                            <span><?php echo htmlspecialchars($doc['uploaded_by_full_name'] ?: 'Staff'); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                                <i class="fas fa-folder-open fa-3x text-slate-300 mb-3"></i>
                                <p class="text-slate-500 font-medium">No documents shared</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Financials Tab -->
                    <div id="financials-content" class="tab-content hidden">
                        <h3 class="text-xl font-bold text-slate-800 mb-6 print:text-2xl">Billing & Financial Information</h3>
                        <!-- Summary Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                                <p class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-1">Total Invoiced</p>
                                <p class="text-2xl font-black text-slate-800">R<?php echo number_format($financial_summary['total_invoiced'], 2); ?></p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-xl border border-green-200">
                                <p class="text-xs text-green-600 uppercase font-bold tracking-wider mb-1">Total Paid</p>
                                <p class="text-2xl font-black text-green-700">R<?php echo number_format($financial_summary['total_paid'], 2); ?></p>
                            </div>
                            <div class="bg-orange-50 p-4 rounded-xl border border-orange-200">
                                <p class="text-xs text-orange-600 uppercase font-bold tracking-wider mb-1">Balance Due</p>
                                <p class="text-2xl font-black text-orange-700">R<?php echo number_format($financial_summary['balance'], 2); ?></p>
                            </div>
                        </div>

                        <!-- Invoices List -->
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-4">Invoices</h3>
                            <?php if (!empty($case_invoices)): ?>
                                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                                    <table class="min-w-full divide-y divide-slate-200">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Invoice #</th>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                                                <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-slate-200">
                                            <?php foreach($case_invoices as $invoice): ?>
                                                <tr class="hover:bg-slate-50 transition-colors">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                                        <?php echo htmlspecialchars($invoice['invoice_number']); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                                        <?php echo date("M j, Y", strtotime($invoice['invoice_date'])); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-800">
                                                            <?php echo htmlspecialchars($invoice['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-800 text-right font-mono">
                                                        R<?php echo number_format($invoice['total_amount'], 2); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <a href="view_invoice.php?id=<?php echo $invoice['invoice_id']; ?>" class="text-blue-600 hover:text-blue-900">View</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-sm text-slate-500 italic">No invoices generated for this case.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Tasks List -->
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-4">Tasks & Milestones</h3>
                            <?php if (!empty($case_tasks)): ?>
                                <ul class="space-y-3">
                                    <?php foreach($case_tasks as $task): 
                                        $is_complete = strtolower($task['status']) === 'completed';
                                    ?>
                                    <li class="flex items-start p-3 bg-slate-50 rounded-lg border border-slate-100">
                                        <div class="flex-shrink-0 mt-0.5 mr-3">
                                            <?php if ($is_complete): ?>
                                                <i class="fas fa-check-circle text-green-500"></i>
                                            <?php else: ?>
                                                <i class="far fa-circle text-slate-400"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium <?php echo $is_complete ? 'text-slate-500 line-through' : 'text-slate-800'; ?>">
                                                <?php echo htmlspecialchars($task['description']); ?>
                                            </p>
                                            <div class="flex gap-3 mt-1 text-xs text-slate-400">
                                                <span>Status: <?php echo htmlspecialchars($task['status']); ?></span>
                                                <?php if($task['due_date']): ?>
                                                    <span>Due: <?php echo date("M j", strtotime($task['due_date'])); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-sm text-slate-500 italic">No tasks visible.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6 no-print">
            <!-- Case Info Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 no-print">
                <h3 class="text-lg font-bold text-slate-800 mb-4 print:text-xl print:border-b print:border-slate-300 print:pb-2">Case Summary</h3>
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-slate-500 text-xs uppercase">Status</dt>
                        <dd class="font-medium text-slate-800 mt-0.5"><?php echo htmlspecialchars(ucwords($case['status'])); ?></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 text-xs uppercase">Priority</dt>
                        <dd class="font-medium text-slate-800 mt-0.5"><?php echo htmlspecialchars(ucwords($case['priority'])); ?></dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 text-xs uppercase">Assigned To</dt>
                        <dd class="font-medium text-slate-800 mt-0.5 flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-xs text-slate-600 font-bold">
                                <?php echo strtoupper(substr($case['assigned_to_full_name'] ?: 'U', 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($case['assigned_to_full_name'] ?: 'Unassigned'); ?>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Support Card -->
            <div class="bg-slate-800 rounded-xl shadow-sm p-6 text-white print:hidden">
                <h3 class="font-bold text-lg mb-2">Need Help?</h3>
                <p class="text-slate-300 text-sm mb-4">If you have urgent questions about this case, please contact our support team directly.</p>
                <a href="mailto:support@iss-investigations.co.za" class="block w-full bg-blue-600 hover:bg-blue-500 text-white text-center font-bold py-2 rounded-lg transition-colors text-sm">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
    </div>

    <!-- Print Footer (Hidden on screen, visible when printing) -->
    <div class="hidden print:block print-footer">
        <p>ISS Investigations Case Report - Generated on <?php echo date("M j, Y \a\\t g:i a"); ?> - Case #<?php echo htmlspecialchars($case['case_number']); ?> - Confidential</p>
    </div>

<!-- Message Modal -->
<div id="sendMessageModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 hidden p-4 transition-opacity duration-300 opacity-0 no-print">
    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-lg border border-slate-200 transform transition-all duration-300 scale-95" id="modal-panel">
        <div class="flex justify-between items-center mb-6">
            <h4 id="messageModalTitle" class="text-xl font-bold text-slate-800">Send New Message</h4>
            <button onclick="closeModal('sendMessageModal')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="formSendMessage" action="actions/send_message_action.php" method="POST">
            <?php echo csrf_input(); ?>
            <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
            <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
            <input type="hidden" name="replied_to_message_id" id="replied_to_message_id" value="">
            
            <div id="replyingToBlock" class="hidden mb-4 p-3 bg-blue-50 border-l-4 border-blue-500 rounded-r-md">
                <p class="text-xs font-bold text-blue-800 uppercase mb-1">Replying to</p>
                <p id="replyingToText" class="text-sm text-blue-900 italic"></p>
            </div>

            <div class="space-y-4">
                <div id="subject-field">
                    <label for="message_subject" class="block text-sm font-bold text-slate-700 mb-1">Subject</label>
                    <input type="text" name="message_subject" id="message_subject" class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>
                <div>
                    <label for="message_content" class="block text-sm font-bold text-slate-700 mb-1">Message <span class="text-red-500">*</span></label>
                    <textarea name="message_content" id="message_content" rows="5" required class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all resize-none" placeholder="Type your message here..."></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal('sendMessageModal')" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">Cancel</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md hover:shadow-lg transition-all transform active:scale-95">
                    <i class="fas fa-paper-plane mr-2"></i> Send Message
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function showTab(tabName) {
        // Hide all content
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        
        // Reset all buttons
        document.querySelectorAll('.tab-button').forEach(b => {
            b.classList.remove('active-tab', 'text-blue-600', 'border-blue-600', 'bg-white');
            b.classList.add('text-slate-500', 'hover:text-slate-700', 'hover:bg-slate-100', 'border-transparent');
        });
        
        // Show selected content
        document.getElementById(tabName + '-content').classList.remove('hidden');
        
        // Activate selected button
        const button = document.getElementById('tab-btn-' + tabName);
        button.classList.add('active-tab', 'text-blue-600', 'border-blue-600', 'bg-white');
        button.classList.remove('text-slate-500', 'hover:text-slate-700', 'hover:bg-slate-100', 'border-transparent');
        
        // Update URL hash without scrolling
        history.replaceState(null, null, '#' + tabName + '-content');
    }

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('hidden');
        // Small delay to allow display:block to apply before opacity transition
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('#modal-panel').classList.remove('scale-95');
        });
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.add('opacity-0');
        modal.querySelector('#modal-panel').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            // Reset form state if needed
            if(modalId === 'sendMessageModal') {
                document.getElementById('replyingToBlock').classList.add('hidden');
            }
        }, 300);
    }
    
    function openMessageModal(messageId = null, messageContent = '', senderName = '') {
        const modalTitle = document.getElementById('messageModalTitle');
        const repliedToInput = document.getElementById('replied_to_message_id');
        const subjectField = document.getElementById('subject-field');
        const contentTextarea = document.getElementById('message_content');
        const replyingToBlock = document.getElementById('replyingToBlock');
        const replyingToText = document.getElementById('replyingToText');

        contentTextarea.value = '';

        if (messageId) {
            modalTitle.textContent = 'Reply to Message';
            repliedToInput.value = messageId;
            subjectField.classList.add('hidden');
            
            const preview = messageContent.length > 80 ? `${messageContent.substring(0, 80)}...` : messageContent;
            replyingToText.textContent = `"${preview}" - ${senderName}`;
            replyingToBlock.classList.remove('hidden');
        } else {
            modalTitle.textContent = 'Send New Message';
            repliedToInput.value = '';
            subjectField.classList.remove('hidden');
            replyingToBlock.classList.add('hidden');
        }

        openModal('sendMessageModal');
        setTimeout(() => contentTextarea.focus(), 100);
    }

    document.addEventListener('keydown', e => {
        if (e.key === "Escape") closeModal('sendMessageModal');
    });

    // Check URL hash on load to open correct tab
    document.addEventListener('DOMContentLoaded', () => {
        const hash = window.location.hash;
        if (hash) {
            const tabName = hash.replace('-content', '').replace('#', '');
            if (document.getElementById(tabName + '-content')) {
                showTab(tabName);
            }
        }
    });
</script>

<style>
@media print {
    @page {
        margin: 0;
        size: A4;
    }
	.no-print{
		display:none;
	}

    body {
        font-family: 'Times New Roman', serif;
        font-size: 10pt;
        line-height: 1;
        color: #1f2937;
    }

    /* Print header styling */
    .print-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        border-bottom: 1pt solid #e5e7eb;
        padding: 8pt 0;
        font-size: 9pt;
        background-color: white;
        z-index: 1000;
        text-align: center;
    }

    .print-header h1 {
        font-size: 14pt;
        margin-bottom: 4pt;
        color: #1e40af;
    }

    .print-header p {
        font-size: 10pt;
        color: #6b7280;
    }

    /* Hide screen-only elements */
    .no-print {
        display: none !important;
    }

    /* Improve table styling for print */
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 10pt 0;
        font-size: 10pt;
    }

    th {
        background-color: #f9fafb;
        font-weight: bold;
        font-size: 9pt;
    }

    /* Improve heading hierarchy */
    h1, h2, h3, h4 {
        color: #1e40af;
        page-break-after: avoid;
        margin-bottom: 5pt;
    }

    h1 { font-size: 14pt; }
    h2 { font-size: 12pt; }
    h3 { font-size: 11pt; }
    h4 { font-size: 10pt; }

    /* Better spacing and layout */
    .print-section {
        margin-bottom: 8pt;
    }

    /* Status badges */
    .status-badge {
        display: inline-block;
        padding: 2pt 6pt;
        border-radius: 3pt;
        font-size: 8pt;
        font-weight: bold;
        text-transform: uppercase;
        border: 1pt solid #d1d5db;
    }

    /* Financial summary cards styling */
    .financial-summary {
        display: table;
        width: 100%;
        margin: 10pt 0;
        border: 1pt solid #e5e7eb;
    }

    .financial-summary > div {
        display: table-cell;
        padding: 8pt;
        text-align: center;
        border-right: 1pt solid #e5e7eb;
    }

    .financial-summary > div:last-child {
        border-right: none;
    }

    .financial-summary .label {
        font-size: 8pt;
        color: #6b7280;
        text-transform: uppercase;
        font-weight: bold;
        margin-bottom: 4pt;
    }

    .financial-summary .amount {
        font-size: 10pt;
        font-weight: bold;
        color: #1f2937;
    }

    /* Print footer */
    .print-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        border-top: 1pt solid #e5e7eb;
        padding: 8pt 0;
        font-size: 8pt;
        color: #6b7280;
        background-color: white;
        text-align: center;
    }

    /* Page breaks */
    .page-break {
        page-break-before: always;
    }

    .no-page-break {
        page-break-inside: avoid;
    }

    /* Message styling for print */
    .message-thread {
        margin: 5pt 0;
        padding: 5pt;
        border-left: 2pt solid #3b82f6;
        background-color: #f8fafc;
        page-break-inside: avoid;
    }

    .message-from-client {
        border-left-color: #10b981;
        background-color: #f0fdf4;
    }

    /* Document item styling */
    .document-item {
        margin: 6pt 0;
        padding: 6pt;
        border: 1pt solid #e5e7eb;
        border-radius: 3pt;
        page-break-inside: avoid;
    }

    /* Improve spacing */
    .print-mt { margin-top: 5pt; }
    .print-mb { margin-bottom: 5pt; }
}

</style>

<?php include_once 'client_footer.php'; ?>
