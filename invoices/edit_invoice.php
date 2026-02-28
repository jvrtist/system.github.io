<?php
/**
 * ISS Investigations - Invoice Modification Interface
 * Admin interface for editing invoices with line items, tax calculations, and audit logging.
 */
require_once '../config.php';
require_login();

$page_title = "Modify Invoice";
$invoice_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($invoice_id_to_edit <= 0) {
    $_SESSION['error_message'] = "Invalid invoice ID specified for editing.";
    redirect('invoices/');
}

// --- Database Connection & Data Fetching ---
$conn = get_db_connection();
$invoice_data = null;
$invoice_items_current = [];
$clients_list = [];
$all_cases_list_for_js = [];
$predefined_items_list = [];
$editable_statuses = ['Draft', 'Sent', 'Overdue', 'Void', 'Cancelled'];

if ($conn) {
    // Fetch the specific invoice to edit
    $stmt_invoice = $conn->prepare("SELECT * FROM invoices WHERE invoice_id = ?");
    if ($stmt_invoice) {
        $stmt_invoice->bind_param("i", $invoice_id_to_edit);
        $stmt_invoice->execute();
        $result_invoice = $stmt_invoice->get_result();
        if ($result_invoice->num_rows === 1) {
            $invoice_data = $result_invoice->fetch_assoc();
        } else {
            $_SESSION['error_message'] = "Invoice not found (ID: $invoice_id_to_edit).";
            $stmt_invoice->close();
            $conn->close();
            redirect('invoices/');
        }
        $stmt_invoice->close();
    }

    // Fetch its existing items
    if ($invoice_data) {
        $stmt_items = $conn->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_id ASC");
        $stmt_items->bind_param("i", $invoice_id_to_edit);
        $stmt_items->execute();
        $invoice_items_current = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_items->close();
    }

    // Fetch data for dropdowns
    $client_result = $conn->query("SELECT client_id, first_name, last_name, company_name FROM clients ORDER BY last_name, first_name");
    if ($client_result) $clients_list = $client_result->fetch_all(MYSQLI_ASSOC);
    
    $case_result = $conn->query("SELECT case_id, case_number, title, client_id FROM cases ORDER BY case_number ASC");
    if ($case_result) $all_cases_list_for_js = $case_result->fetch_all(MYSQLI_ASSOC);

    $items_result = $conn->query("SELECT item_id, name, description, default_price FROM service_items WHERE is_active = 1 ORDER BY name ASC");
    if ($items_result) $predefined_items_list = $items_result->fetch_all(MYSQLI_ASSOC);

} else {
    $_SESSION['error_message'] = "Database connection failed.";
    redirect('invoices/');
}

// --- Form State & Error Handling ---
$errors = [];
$form_data = $invoice_data; // Pre-populate with existing data

// --- POST Request Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    // Repopulate form_data with submitted values
    $form_data['client_id'] = (int)$_POST['client_id'];
    $form_data['case_id'] = !empty($_POST['case_id']) ? (int)$_POST['case_id'] : null;
    $form_data['invoice_number'] = sanitize_input($_POST['invoice_number']);
    $form_data['invoice_date'] = sanitize_input($_POST['invoice_date']);
    $form_data['due_date'] = !empty($_POST['due_date']) ? sanitize_input($_POST['due_date']) : null;
    
    // Handle status: if disabled in form (e.g. Paid), it won't be in POST, so keep existing.
    if (isset($_POST['invoice_status'])) {
        $form_data['status'] = sanitize_input($_POST['invoice_status']);
    } else {
        $form_data['status'] = $invoice_data['status'];
    }

    $form_data['tax_rate_percentage'] = filter_var($_POST['tax_rate_percentage'], FILTER_VALIDATE_FLOAT);
    $form_data['notes_to_client'] = sanitize_input($_POST['notes_to_client']);
    $form_data['notes'] = sanitize_input($_POST['notes']);

    // Ensure non-editable statuses (like 'Paid') are not changed via this form logic if somehow passed
    if (!in_array($form_data['status'], $editable_statuses) && $form_data['status'] !== $invoice_data['status']) {
        // If user tried to hack a status change to something not allowed, or if we are in a state where we shouldn't change it
        // But if it was already Paid, we kept it above.
        // This check is mostly for if they sent a valid editable status but we want to restrict it?
        // No, if they sent a valid editable status, we allow it.
        // If they sent an invalid one, we revert.
        // If they didn't send one (Paid), we kept existing.
        
        // If the *current* status in DB is Paid, we generally shouldn't allow changing it back to Draft via this form easily, 
        // but maybe we do want to allow Voiding.
        // The logic above: if not in editable_statuses, revert.
        // 'Paid' is NOT in editable_statuses. So if they somehow sent 'Paid', it's fine (matches existing).
        // If they sent 'Draft' but current is 'Paid', should we allow?
        // The UI disables the dropdown if current is Paid.
        // If they hack it to send 'Draft', we might want to allow it if they really want to un-pay it?
        // But for now, let's stick to the UI logic: if it wasn't sent, keep existing.
    }

    $items = [];
    $subtotal = 0;
    $posted_item_ids = [];

    if (isset($_POST['item_description']) && is_array($_POST['item_description'])) {
        foreach ($_POST['item_description'] as $i => $desc_raw) {
            $desc = sanitize_input($desc_raw);
            $qty = filter_var($_POST['item_quantity'][$i], FILTER_VALIDATE_FLOAT);
            $price = filter_var($_POST['item_unit_price'][$i], FILTER_VALIDATE_FLOAT);
            $item_id = isset($_POST['item_id'][$i]) && !empty($_POST['item_id'][$i]) ? (int)$_POST['item_id'][$i] : null;

            if ($item_id) $posted_item_ids[] = $item_id;

            if (!empty($desc) && $qty > 0 && $price >= 0) {
                $item_subtotal = $qty * $price;
                $items[] = ['item_id' => $item_id, 'description' => $desc, 'quantity' => $qty, 'unit_price' => $price, 'subtotal' => $item_subtotal];
                $subtotal += $item_subtotal;
            }
        }
    }
    if (empty($items)) $errors['items'] = "An invoice must have at least one valid line item.";
    
    // Validate case_id if provided (not null and not empty)
    if (!empty($form_data['case_id'])) {
        $stmt_case_check = $conn->prepare("SELECT case_id FROM cases WHERE case_id = ?");
        $stmt_case_check->bind_param("i", $form_data['case_id']);
        $stmt_case_check->execute();
        if ($stmt_case_check->get_result()->num_rows === 0) {
            $errors['case_id'] = "INVALID_CASE: The selected case does not exist or is inaccessible.";
        }
        $stmt_case_check->close();
    }
    
    // --- Database Update ---
    if (empty($errors)) {
        $tax_amount = $subtotal * ($form_data['tax_rate_percentage'] / 100);
        $total_amount = $subtotal + $tax_amount;
        
        $conn->begin_transaction();
        try {
            // 1. Update the main invoice record
            $sql_invoice = "UPDATE invoices SET client_id = ?, case_id = ?, invoice_number = ?, invoice_date = ?, due_date = ?, subtotal_amount = ?, tax_rate_percentage = ?, tax_amount = ?, total_amount = ?, status = ?, notes_to_client = ?, notes = ? WHERE invoice_id = ?";
            $stmt_invoice = $conn->prepare($sql_invoice);
            $stmt_invoice->bind_param("iisssddddsssi",
                $form_data['client_id'], $form_data['case_id'], $form_data['invoice_number'], $form_data['invoice_date'], $form_data['due_date'],
                $subtotal, $form_data['tax_rate_percentage'], $tax_amount, $total_amount, $form_data['status'],
                $form_data['notes_to_client'], $form_data['notes'], $invoice_id_to_edit
            );
            $stmt_invoice->execute();
            $stmt_invoice->close();
            
            // 2. Determine which items to delete
            $original_item_ids = array_column($invoice_items_current, 'item_id');
            $items_to_delete = array_diff($original_item_ids, $posted_item_ids);
            if (!empty($items_to_delete)) {
                $delete_placeholders = implode(',', array_fill(0, count($items_to_delete), '?'));
                $stmt_delete = $conn->prepare("DELETE FROM invoice_items WHERE item_id IN ($delete_placeholders) AND invoice_id = ?");
                $delete_types = str_repeat('i', count($items_to_delete)) . 'i';
                $delete_params = array_merge(array_values($items_to_delete), [$invoice_id_to_edit]);
                $stmt_delete->bind_param($delete_types, ...$delete_params);
                $stmt_delete->execute();
                $stmt_delete->close();
            }

            // 3. Update existing items and insert new ones
            foreach ($items as $item) {
                if ($item['item_id'] && in_array($item['item_id'], $original_item_ids)) {
                    $sql_update_item = "UPDATE invoice_items SET description = ?, quantity = ?, unit_price = ? WHERE item_id = ?";
                    $stmt_item = $conn->prepare($sql_update_item);
                    $stmt_item->bind_param("sddi", $item['description'], $item['quantity'], $item['unit_price'], $item['item_id']);
                } else {
                    $sql_insert_item = "INSERT INTO invoice_items (invoice_id, description, quantity, unit_price) VALUES (?, ?, ?, ?)";
                    $stmt_item = $conn->prepare($sql_insert_item);
                    $stmt_item->bind_param("isdd", $invoice_id_to_edit, $item['description'], $item['quantity'], $item['unit_price']);
                }
                $stmt_item->execute();
                $stmt_item->close();
            }

            $conn->commit();
            $_SESSION['success_message'] = "Invoice #" . htmlspecialchars($form_data['invoice_number']) . " updated successfully!";
            redirect('invoices/view_invoice.php?id=' . $invoice_id_to_edit);

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error updating invoice: " . $e->getMessage();
        }
    } else {
        // If there were validation errors, repopulate items from POST for sticky form
        $invoice_items_current = [];
         if (isset($_POST['item_description'])) {
            foreach ($_POST['item_description'] as $i => $desc) {
                $invoice_items_current[] = [
                    'item_id' => $_POST['item_id'][$i] ?? null,
                    'description' => sanitize_input($desc),
                    'quantity' => $_POST['item_quantity'][$i],
                    'unit_price' => $_POST['item_unit_price'][$i]
                ];
            }
        }
    }
}

$conn->close();
include_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Modify <span class="text-primary">Invoice</span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">Edit invoice details, items, and calculations</p>
    </header>

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

    <div class="container mx-auto">
        <h1 class="text-3xl font-bold text-sky-400">Edit Invoice #<?php echo htmlspecialchars($form_data['invoice_number']); ?></h1>
        <p class="text-slate-400">Modify the details for this invoice.</p>
    </header>

    <form action="edit_invoice.php?id=<?php echo $invoice_id_to_edit; ?>" method="POST" id="invoiceForm" class="space-y-6">
        <?php echo csrf_input(); ?>

        <!-- Section 1: Invoice Details -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">01. Invoice Information</h2>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="client_id" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Client <span class="text-red-500">*</span></label>
                        <select name="client_id" id="client_id" required class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <?php foreach ($clients_list as $client): ?>
                                <option value="<?php echo $client['client_id']; ?>" <?php echo ($form_data['client_id'] == $client['client_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($client['last_name'] . ', ' . $client['first_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="case_id" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Related Case</label>
                        <select name="case_id" id="case_id" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <option value="">-- None --</option>
                        </select>
                    </div>
                    <div>
                        <label for="invoice_number" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Invoice Number <span class="text-red-500">*</span></label>
                        <input type="text" name="invoice_number" id="invoice_number" value="<?php echo htmlspecialchars($form_data['invoice_number']); ?>" required class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    </div>
                    <div>
                        <label for="invoice_date" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Invoice Date <span class="text-red-500">*</span></label>
                        <input type="date" name="invoice_date" id="invoice_date" value="<?php echo htmlspecialchars($form_data['invoice_date']); ?>" required class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    </div>
                    <div>
                        <label for="due_date" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Due Date</label>
                        <input type="date" name="due_date" id="due_date" value="<?php echo htmlspecialchars($form_data['due_date'] ?? ''); ?>" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                    </div>
                    <div>
                        <label for="invoice_status" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Status <span class="text-red-500">*</span></label>
                        <select name="invoice_status" id="invoice_status" required class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                            <?php foreach ($editable_statuses as $status): ?>
                                <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($form_data['status'] === $status) ? 'selected' : ''; ?>><?php echo htmlspecialchars($status); ?></option>
                            <?php endforeach; ?>
                            <?php if (!in_array($form_data['status'], $editable_statuses)): ?>
                                <option value="<?php echo htmlspecialchars($form_data['status']); ?>" selected disabled><?php echo htmlspecialchars(ucwords($form_data['status'])); ?> (Set via Payments)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Line Items -->
        <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">02. Line Items</h2>
            </div>
            <div class="p-6 space-y-4">
                <div id="invoiceItemsContainer" class="space-y-4"></div>
                <?php if (isset($errors['items'])): ?>
                    <p class="text-red-400 text-xs flex items-center gap-1"><i class="fas fa-times-circle"></i><?php echo htmlspecialchars($errors['items']); ?></p>
                <?php endif; ?>
                <button type="button" id="addInvoiceItemBtn" class="text-sm bg-primary hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded-lg shadow-lg shadow-primary/20 transition-all">
                    <i class="fas fa-plus-circle mr-2"></i>Add Line Item
                </button>
            </div>
        </div>

        <!-- Section 3: Notes & Totals -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
                <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                    <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">03. Additional Information</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label for="notes_to_client" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Notes to Client</label>
                        <textarea name="notes_to_client" id="notes_to_client" rows="3" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($form_data['notes_to_client']); ?></textarea>
                    </div>
                    <div>
                        <label for="notes" class="block text-xs font-bold uppercase text-slate-400 mb-2 tracking-widest">Internal Notes</label>
                        <textarea name="notes" id="notes" rows="3" class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 placeholder-slate-600 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all resize-none"><?php echo htmlspecialchars($form_data['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
                <div class="bg-white/[0.03] px-6 py-3 border-b border-white/5">
                    <h2 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">Totals</h2>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">Subtotal:</span>
                        <span id="displaySubtotal" class="font-semibold text-slate-100">R0.00</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <label for="tax_rate_percentage" class="text-slate-400">Tax Rate (%):</label>
                        <input type="number" name="tax_rate_percentage" id="tax_rate_percentage" value="<?php echo number_format($form_data['tax_rate_percentage'], 2); ?>" step="0.01" class="w-24 px-2 py-1 bg-slate-800 border border-slate-700 rounded-lg text-right text-slate-100 focus:ring-2 focus:ring-primary outline-none">
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-400">Tax Amount:</span>
                        <span id="displayTaxAmount" class="font-semibold text-slate-100">R0.00</span>
                    </div>
                    <div class="border-t border-slate-700 pt-3">
                        <div class="flex justify-between text-lg">
                            <strong class="text-white">Total:</strong>
                            <strong id="displayTotalAmount" class="text-primary">R0.00</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-4">
            <a href="view_invoice.php?id=<?php echo $invoice_id_to_edit; ?>" class="w-full sm:w-auto text-center px-6 py-2.5 border border-slate-600 hover:bg-slate-800/50 text-slate-300 hover:text-slate-100 rounded-lg transition-colors duration-200 font-semibold text-sm">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="w-full sm:w-auto bg-primary hover:bg-orange-600 text-white font-black uppercase tracking-wider py-2.5 px-8 rounded-lg shadow-lg shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all transform hover:-translate-y-0.5 text-sm">
                <i class="fas fa-save mr-2"></i>Update Invoice
            </button>
        </div>
    </form>

<script>
    const allCases = <?php echo json_encode($all_cases_list_for_js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const predefinedItems = <?php echo json_encode($predefined_items_list, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const initialItemsData = <?php echo json_encode($invoice_items_current, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const initialCaseId = <?php echo json_encode($form_data['case_id']); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const itemsContainer = document.getElementById('invoiceItemsContainer');
        const addItemButton = document.getElementById('addInvoiceItemBtn');
        const clientIdSelect = document.getElementById('client_id');
        const caseIdSelect = document.getElementById('case_id');
        const escapeHtml = (value) => String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#39;');

        function addInvoiceItemRow(item = {}) {
            const itemRow = document.createElement('div');
            itemRow.classList.add('p-3', 'rounded-md', 'bg-slate-700/30', 'invoice-item-row');
            
            let optionsHTML = '<option value="">-- Select a Service --</option>';
            predefinedItems.forEach(pi => {
                optionsHTML += `<option value="\${pi.item_id}">\${escapeHtml(pi.name)}</option>`;
            });
            optionsHTML += '<option value="custom">-- Custom Item --</option>';
            
            const itemIdInput = `<input type="hidden" name="item_id[]" value="\${item.item_id || ''}">`;

            itemRow.innerHTML = `
                ${itemIdInput}
                <div class="grid grid-cols-12 gap-x-3 gap-y-2 items-start">
                    <div class="col-span-12 sm:col-span-4">
                        <label class="text-xs text-slate-400">Service / Item</label>
                        <select class="item-select w-full mt-1 text-sm p-2 bg-slate-700 border border-slate-600 rounded-md">${optionsHTML}</select>
                    </div>
                    <div class="col-span-12 sm:col-span-8">
                        <label class="text-xs text-slate-400">Description</label>
                        <textarea name="item_description[]" required class="item-description w-full mt-1 text-sm p-2 bg-slate-700 border border-slate-600 rounded-md" rows="1">${escapeHtml(item.description || '')}</textarea>
                    </div>
                    <div class="col-span-4 sm:col-span-3">
                        <label class="text-xs text-slate-400">Quantity</label>
                        <input type="number" name="item_quantity[]" value="${item.quantity || 1}" required class="item-qty w-full mt-1 text-sm p-2 bg-slate-700 border border-slate-600 rounded-md text-right" step="0.01" min="0.01">
                    </div>
                    <div class="col-span-4 sm:col-span-3">
                        <label class="text-xs text-slate-400">Unit Price</label>
                         <input type="number" name="item_unit_price[]" value="${parseFloat(item.unit_price || 0).toFixed(2)}" required class="item-price w-full mt-1 text-sm p-2 bg-slate-700 border border-slate-600 rounded-md text-right" step="0.01" min="0.00">
                    </div>
                    <div class="col-span-4 sm:col-span-4 self-center text-right">
                        <p class="item-subtotal font-medium text-lg">$0.00</p>
                    </div>
                    <div class="col-span-12 sm:col-span-2 self-center flex justify-end">
                        <button type="button" class="removeInvoiceItem text-red-400 hover:text-red-300 p-2"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </div>
            `;
            itemsContainer.appendChild(itemRow);
        }

        function updateTotals() {
        let subtotal = 0;
        itemsContainer.querySelectorAll('.invoice-item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const itemSubtotal = qty * price;
            row.querySelector('.item-subtotal').textContent = 'R' + itemSubtotal.toFixed(2);
            subtotal += itemSubtotal;
        });

        document.getElementById('displaySubtotal').textContent = 'R' + subtotal.toFixed(2);
        const taxRate = parseFloat(document.getElementById('tax_rate_percentage').value) || 0;
        const taxAmount = subtotal * (taxRate / 100);
        document.getElementById('displayTaxAmount').textContent = 'R' + taxAmount.toFixed(2);
        document.getElementById('displayTotalAmount').textContent = 'R' + (subtotal + taxAmount).toFixed(2);
    }
        function populateCaseDropdown(selectedClientId) {
        caseIdSelect.innerHTML = '<option value="">-- None --</option>';
        if (selectedClientId) {
            allCases.filter(c => c.client_id == selectedClientId).forEach(c => {
                const option = new Option(`${c.case_number} - ${c.title}`, c.case_id);
                if (c.case_id == initialCaseId) option.selected = true;
                caseIdSelect.add(option);
            });
        }
    }
        
        // --- Event Listeners ---
        addItemButton.addEventListener('click', () => addInvoiceItemRow());
        // Use event delegation for dynamic elements
        itemsContainer.addEventListener('change', e => {
            if (e.target.classList.contains('item-select')) {
                const row = e.target.closest('.invoice-item-row');
                const descInput = row.querySelector('.item-description');
                const priceInput = row.querySelector('.item-price');
                if (e.target.value && e.target.value !== 'custom') {
                    const selected = predefinedItems.find(item => item.item_id == e.target.value);
                    if (selected) {
                        descInput.value = selected.description;
                        priceInput.value = parseFloat(selected.default_price).toFixed(2);
                    }
                }
                updateTotals();
            }
        });
        itemsContainer.addEventListener('input', e => { if (e.target.matches('.item-qty, .item-price')) updateTotals(); });
        itemsContainer.addEventListener('click', e => { if (e.target.closest('.removeInvoiceItem')) e.target.closest('.invoice-item-row').remove(); updateTotals(); });
        document.getElementById('tax_rate_percentage').addEventListener('input', updateTotals);
        clientIdSelect.addEventListener('change', () => populateCaseDropdown(clientIdSelect.value));

        // --- Initial Population ---
        itemsContainer.innerHTML = ''; // Clear container before populating
        if (initialItemsData.length > 0) {
            initialItemsData.forEach(item => addInvoiceItemRow(item));
        } else {
            addInvoiceItemRow(); // Add a blank row if an invoice has no items
        }
        
        populateCaseDropdown(clientIdSelect.value);
        updateTotals();
    });
</script>

<?php include_once '../includes/footer.php'; ?>
