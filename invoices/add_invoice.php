<?php
/**
 * ISS Investigations - Fiscal Transaction Console
 * Secure generation of client invoices and service reconciliation.
 */
require_once '../config.php';
require_login();

$page_title = "Fiscal Generation";
$conn = get_db_connection();

// --- 1. Tactical Data Harvesting ---
$clients_list = [];
$all_cases_list_for_js = [];
$predefined_items_list = [];

if ($conn) {
    $clients_list = $conn->query("SELECT client_id, first_name, last_name, company_name FROM clients ORDER BY last_name ASC")->fetch_all(MYSQLI_ASSOC);
    $all_cases_list_for_js = $conn->query("SELECT case_id, case_number, title, client_id FROM cases WHERE status NOT IN ('Closed', 'Resolved', 'Archived')")->fetch_all(MYSQLI_ASSOC);
    $predefined_items_list = $conn->query("SELECT item_id, name, description, default_price FROM service_items WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);
}

// Helpers
if (!defined('DEFAULT_TAX_RATE')) define('DEFAULT_TAX_RATE', 15.00);
$invoice_statuses = ['Draft', 'Sent'];

function generate_invoice_number($conn): string {
    $year = date('Y');
    $prefix = "INV-" . $year . "-";
    $stmt = $conn->prepare("SELECT invoice_number FROM invoices WHERE invoice_number LIKE ? ORDER BY invoice_id DESC LIMIT 1");
    $like = $prefix . '%';
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $last = $stmt->get_result()->fetch_assoc();
    $seq = $last ? (int)substr($last['invoice_number'], -4) + 1 : 1;
    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

// Sticky State
$errors = [];
$items = [];
$form = [
    'client_id' => $_POST['client_id'] ?? ($_GET['client_id'] ?? ''),
    'case_id' => $_POST['case_id'] ?? ($_GET['case_id'] ?? ''),
    'invoice_number' => $_POST['invoice_number'] ?? generate_invoice_number($conn),
    'invoice_date' => $_POST['invoice_date'] ?? date('Y-m-d'),
    'due_date' => $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
    'status' => $_POST['invoice_status'] ?? 'Draft',
    'tax_rate' => $_POST['tax_rate_percentage'] ?? DEFAULT_TAX_RATE,
    'notes' => $_POST['notes_to_client'] ?? '',
];

// --- 2. Transaction Processing ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    if (empty($form['client_id'])) $errors[] = "ENTITY_REQUIRED: A client must be assigned to this ledger.";
    
    // Validate case_id if provided
    if (!empty($form['case_id'])) {
        $stmt_case_check = $conn->prepare("SELECT case_id FROM cases WHERE case_id = ?");
        $stmt_case_check->bind_param("i", $form['case_id']);
        $stmt_case_check->execute();
        if ($stmt_case_check->get_result()->num_rows === 0) {
            $errors[] = "INVALID_CASE: The selected case does not exist or is inaccessible.";
        }
        $stmt_case_check->close();
    }
    
    $subtotal = 0;
    if (isset($_POST['item_description'])) {
        foreach ($_POST['item_description'] as $i => $desc) {
            $qty = (float)($_POST['item_quantity'][$i] ?? 0);
            $price = (float)($_POST['item_unit_price'][$i] ?? 0);
            if (!empty($desc) && $qty > 0) {
                $line_total = $qty * $price;
                $items[] = ['desc' => sanitize_input($desc), 'qty' => $qty, 'price' => $price, 'sub' => $line_total];
                $subtotal += $line_total;
            }
        }
    }

    if (empty($items)) $errors[] = "NULL_PAYLOAD: At least one service item is required.";

    if (empty($errors)) {
        $tax_amt = $subtotal * ($form['tax_rate'] / 100);
        $total = $subtotal + $tax_amt;
        
        $case_id_to_insert = !empty($form['case_id']) ? (int)$form['case_id'] : null;
        
        $conn->begin_transaction();
        try {
            $sql = "INSERT INTO invoices (client_id, case_id, invoice_number, invoice_date, due_date, subtotal_amount, tax_rate_percentage, tax_amount, total_amount, status, notes_to_client, created_by_user_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisssddddssi", $form['client_id'], $case_id_to_insert, $form['invoice_number'], $form['invoice_date'], $form['due_date'], $subtotal, $form['tax_rate'], $tax_amt, $total, $form['status'], $form['notes'], $_SESSION['user_id']);
            
            $stmt->execute();
            $inv_id = $stmt->insert_id;

            $item_stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price) VALUES (?, ?, ?, ?)");
            foreach ($items as $it) {
                $item_stmt->bind_param("isdd", $inv_id, $it['desc'], $it['qty'], $it['price']);
                $item_stmt->execute();
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "FISCAL_COMMITTED: Invoice #{$form['invoice_number']} generated.";
            redirect('invoices/view_invoice.php?id=' . $inv_id);
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "DB_FAILURE: " . $e->getMessage();
        }
    }
}

$sticky_items_json = '[]';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($items)) {
    $js_items = array_map(function($item) {
        return [
            'description' => $item['desc'],
            'quantity' => $item['qty'],
            'unit_price' => $item['price']
        ];
    }, $items);
    $sticky_items_json = json_encode($js_items);
}

include_once '../includes/header.php';
?>

<div class="max-w-6xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Create <span class="text-primary">Invoice</span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Generate new invoice with line items and automatic numbering</p>
    </header>

    <?php if ($errors): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-shake">
            <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-sm font-bold text-red-200">Please correct the errors below:</p>
                <ul class="text-xs text-red-300 mt-2 list-disc list-inside">
                    <?php foreach($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <form action="add_invoice.php" method="POST" id="invoiceForm" class="space-y-6">
        <?= csrf_input() ?>
        
        <div class="bg-slate-900 border border-white/5 rounded-2xl p-8 shadow-2xl">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="space-y-1">
                    <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Client Entity</label>
                    <select name="client_id" id="client_id" required class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-primary outline-none appearance-none">
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients_list as $c): ?>
                            <option value="<?= $c['client_id'] ?>" <?= $form['client_id'] == $c['client_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['last_name']) ?>, <?= htmlspecialchars($c['first_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Operational Case</label>
                    <select name="case_id" id="case_id" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-primary outline-none appearance-none">
                        <option value="">-- Standalone --</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Fiscal Status</label>
                    <select name="invoice_status" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-primary outline-none">
                        <?php foreach ($invoice_statuses as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>" <?= $form['status'] === $s ? 'selected' : '' ?>><?= htmlspecialchars(strtoupper($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Issue Date</label>
                    <input type="date" name="invoice_date" value="<?= htmlspecialchars($form['invoice_date']) ?>" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-primary outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Due Date</label>
                    <input type="date" name="due_date" value="<?= htmlspecialchars($form['due_date']) ?>" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-primary outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Serial Override</label>
                    <input type="text" name="invoice_number" value="<?= htmlspecialchars($form['invoice_number']) ?>" class="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-primary outline-none">
                </div>
            </div>
        </div>

        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-8 py-4 border-b border-white/5 flex justify-between items-center">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Service Reconciliation Grid</h2>
                <button type="button" id="addInvoiceItemBtn" class="text-[9px] font-black uppercase tracking-widest bg-primary/10 text-primary border border-primary/20 px-4 py-2 rounded-lg hover:bg-primary hover:text-white transition-all">
                    + Add Line Item
                </button>
            </div>
            
            <div id="invoiceItemsContainer" class="p-8 space-y-4">
                </div>

            <div class="bg-slate-950/50 p-8 border-t border-white/5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase text-slate-500 tracking-widest ml-1">Notes to Client</label>
                        <textarea name="notes_to_client" rows="4" placeholder="Payment instructions, terms, or service summary..." class="w-full bg-slate-900 border border-white/5 rounded-xl px-4 py-3 text-sm text-slate-400 focus:border-primary outline-none resize-none"><?= htmlspecialchars($form['notes']) ?></textarea>
                    </div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center tech-mono">
                            <span class="text-slate-500 text-[10px] uppercase">Net Subtotal</span>
                            <span id="displaySubtotal" class="text-white font-bold text-sm">R0.00</span>
                        </div>
                        <div class="flex justify-between items-center tech-mono">
                            <span class="text-slate-500 text-[10px] uppercase italic">Tax Rate (%)</span>
                            <input type="number" name="tax_rate_percentage" id="tax_rate_percentage" value="<?= htmlspecialchars($form['tax_rate']) ?>" step="0.01" class="w-20 bg-transparent border-b border-white/10 text-right text-sm text-primary focus:border-primary outline-none">
                        </div>
                        <div class="flex justify-between items-center tech-mono">
                            <span class="text-slate-500 text-[10px] uppercase">Tax Amount</span>
                            <span id="displayTaxAmount" class="text-slate-400 text-sm">R0.00</span>
                        </div>
                        <div class="pt-4 border-t border-white/10 flex justify-between items-center">
                            <span class="text-white text-[12px] font-black uppercase tracking-widest">Total Liability</span>
                            <span id="displayTotalAmount" class="text-primary text-2xl font-black tracking-tighter">R0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4 pt-4">
            <a href="index.php" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-white transition-colors px-8 py-4">Abort</a>
            <button type="submit" class="bg-primary hover:bg-orange-600 text-white text-[10px] font-black uppercase tracking-[0.2em] px-12 py-4 rounded-xl shadow-lg transition-all flex items-center">
                <i class="fas fa-check-double mr-2"></i> Commit to Ledger
            </button>
        </div>
    </form>
</div>

<script>
const allCases = <?= json_encode($all_cases_list_for_js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const predefinedItems = <?= json_encode($predefined_items_list, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const initialCaseId = <?= json_encode($form['case_id']) ?>;
const stickyItems = <?= $sticky_items_json ?>;

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('invoiceItemsContainer');
    const clientSelect = document.getElementById('client_id');
    const caseSelect = document.getElementById('case_id');
    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#39;');

    function createRow(item = null) {
        const div = document.createElement('div');
        div.className = "grid grid-cols-12 gap-4 items-center bg-white/[0.02] border border-white/5 p-4 rounded-xl group animate-fade-in";
        
        let services = predefinedItems.map(i => `<option value="${i.item_id}">${escapeHtml(i.name)}</option>`).join('');

        div.innerHTML = `
            <div class="col-span-12 md:col-span-4">
                <select class="item-select w-full bg-slate-900 border border-white/10 rounded-lg px-3 py-2 text-xs text-slate-300 outline-none focus:border-primary">
                    <option value="">-- Select Service --</option>
                    ${services}
                    <option value="custom">-- Custom Payload --</option>
                </select>
                <textarea name="item_description[]" placeholder="Operational description..." required class="item-description mt-2 w-full bg-transparent border border-white/5 rounded-lg px-3 py-2 text-[11px] text-slate-400 focus:border-primary outline-none resize-none" rows="1"></textarea>
            </div>
            <div class="col-span-4 md:col-span-2">
                <label class="text-[8px] uppercase text-slate-600 tech-mono block mb-1">Quantity</label>
                <input type="number" name="item_quantity[]" value="1" step="0.01" class="item-qty w-full bg-slate-900 border border-white/10 rounded-lg px-3 py-2 text-xs text-white text-right outline-none">
            </div>
            <div class="col-span-4 md:col-span-3">
                <label class="text-[8px] uppercase text-slate-600 tech-mono block mb-1">Unit Rate</label>
                <input type="number" name="item_unit_price[]" value="0.00" step="0.01" class="item-price w-full bg-slate-900 border border-white/10 rounded-lg px-3 py-2 text-xs text-white text-right outline-none">
            </div>
            <div class="col-span-3 md:col-span-2 text-right">
                <label class="text-[8px] uppercase text-slate-600 tech-mono block mb-1">Line Total</label>
                <span class="item-subtotal tech-mono text-xs text-primary font-bold">R0.00</span>
            </div>
            <div class="col-span-1 text-center">
                <button type="button" class="remove-row text-slate-700 hover:text-red-500 transition-colors"><i class="fas fa-times"></i></button>
            </div>
        `;

        if (item) {
            div.querySelector('.item-description').value = item.description;
            div.querySelector('.item-qty').value = item.quantity;
            div.querySelector('.item-price').value = item.unit_price;
        }

        container.appendChild(div);
    }

    function calculate() {
        let sub = 0;
        container.querySelectorAll('div.grid').forEach(row => {
            const q = parseFloat(row.querySelector('.item-qty').value) || 0;
            const p = parseFloat(row.querySelector('.item-price').value) || 0;
            const line = q * p;
            row.querySelector('.item-subtotal').textContent = 'R' + line.toLocaleString(undefined, {minimumFractionDigits: 2});
            sub += line;
        });

        const rate = parseFloat(document.getElementById('tax_rate_percentage').value) || 0;
        const tax = sub * (rate / 100);
        document.getElementById('displaySubtotal').textContent = 'R' + sub.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('displayTaxAmount').textContent = 'R' + tax.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('displayTotalAmount').textContent = 'R' + (sub + tax).toLocaleString(undefined, {minimumFractionDigits: 2});
    }

    // Event Delegation
    document.getElementById('addInvoiceItemBtn').onclick = () => createRow();
    container.addEventListener('input', calculate);
    document.getElementById('tax_rate_percentage').oninput = calculate;
    
    container.addEventListener('click', e => {
        if (e.target.closest('.remove-row')) {
            e.target.closest('div.grid').remove();
            calculate();
        }
    });

    container.addEventListener('change', e => {
        if (e.target.classList.contains('item-select')) {
            const row = e.target.closest('div.grid');
            const item = predefinedItems.find(i => i.item_id == e.target.value);
            if (item) {
                row.querySelector('.item-description').value = item.description;
                row.querySelector('.item-price').value = item.default_price;
            }
            calculate();
        }
    });

    clientSelect.onchange = () => {
        const currentCaseVal = caseSelect.value;
        caseSelect.innerHTML = '<option value="">-- Standalone --</option>';
        allCases.filter(c => c.client_id == clientSelect.value).forEach(c => {
            caseSelect.add(new Option(`${c.case_number} - ${c.title}`, c.case_id));
        });
        caseSelect.value = currentCaseVal;
    };

    // Init
    if (stickyItems && stickyItems.length > 0) {
        stickyItems.forEach(item => createRow(item));
    } else {
        createRow();
    }
    
    if (clientSelect.value) {
        clientSelect.onchange();
        if (initialCaseId) {
            caseSelect.value = initialCaseId;
        }
    }
    calculate();
});
</script>

<?php include_once '../includes/footer.php'; ?>
