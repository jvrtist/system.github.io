<?php
// client_portal/invoices.php
require_once '../config.php';
require_once 'client_auth.php'; 

$page_title = "My Invoices";
$client_id = $_SESSION[CLIENT_ID_SESSION_VAR];
$conn = get_db_connection();

// --- Filters ---
$filter_status = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'all'; // all, unpaid, paid

// Build Query
$sql = "SELECT invoice_id, invoice_number, invoice_date, due_date, total_amount, amount_paid, status 
        FROM invoices 
        WHERE client_id = ? AND status != 'Draft'";

if ($filter_status === 'unpaid') {
    $sql .= " AND status IN ('Sent', 'Partially Paid', 'Overdue')";
} elseif ($filter_status === 'paid') {
    $sql .= " AND status = 'Paid'";
}

$sql .= " ORDER BY invoice_date DESC";

$invoices = [];
if ($conn) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoices = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
}

function get_client_invoice_status_badge($status) {
    $base = "px-2.5 py-1 inline-flex text-xs leading-4 font-bold uppercase tracking-wide rounded-full border";
    switch (strtolower($status)) {
        case 'paid': return "$base bg-green-100 text-green-800 border-green-200";
        case 'sent': return "$base bg-blue-100 text-blue-800 border-blue-200";
        case 'overdue': return "$base bg-red-100 text-red-800 border-red-200";
        case 'partially paid': return "$base bg-orange-100 text-orange-800 border-orange-200";
        default: return "$base bg-slate-100 text-slate-800 border-slate-200";
    }
}

include_once 'client_header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header Section -->
    <header class="border-l-4 border-primary pl-6 animate-fade-in-up">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-secondary mb-2">Invoices & Billing</h1>
            <p class="text-[10px] tech-mono text-slate-600 uppercase tracking-[0.2em] mt-1">View and manage your billing history and outstanding payments.</p>
        </div>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="btn-primary inline-flex items-center gap-2 text-sm font-semibold">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </header>
                   
    <!-- Controls Section -->
	
    <div class="p-4 rounded-xl shadow-sm border border-slate-200 animate-fade-in-up">
        <form action="invoices.php" method="GET" class="flex flex-col md:flex-row gap-6">
            <div class="flex-grow relative">
            </div>
            <div class="flex gap-3 pb-2 md:pb-0">
                <button type="submit" name="status" value="all" 
                        class="px-2 rounded-2xl text-sm font-black whitespace-nowrap transition-all duration-300 transform hover:scale-105 <?php echo $filter_status === 'all' ? 'bg-gradient-primary text-white shadow-glow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 shadow-card hover:shadow-card-hover'; ?>">
                    All Invoices
                </button>
                <button type="submit" name="status" value="unpaid" 
                        class="px-6 py-4 rounded-2xl text-sm font-black whitespace-nowrap transition-all duration-300 transform hover:scale-105 <?php echo $filter_status === 'unpaid' ? 'bg-gradient-secondary text-white shadow-glow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 shadow-card hover:shadow-card-hover'; ?>">
                    Unpaid / Due
                </button>
                <button type="submit" name="status" value="paid" 
                        class="px-6 py-4 rounded-2xl text-sm font-black whitespace-nowrap transition-all duration-300 transform hover:scale-105 <?php echo $filter_status === 'paid' ? 'bg-gradient-accent text-white shadow-glow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 shadow-card hover:shadow-card-hover'; ?>">
                    Paid History
                </button>
            </div>
        </form>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-slate-200 animate-fade-in-up">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Invoice #</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Date Issued</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Total Amount</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Balance Due</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    <?php if (!empty($invoices)): ?>
                        <?php foreach ($invoices as $invoice): 
                            $balance = $invoice['total_amount'] - $invoice['amount_paid'];
                            $is_overdue = ($balance > 0 && strtotime($invoice['due_date']) < time());
                        ?>
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="view_invoice.php?id=<?php echo $invoice['invoice_id']; ?>" class="text-sm font-bold text-blue-600 hover:text-blue-800">
                                        <?php echo htmlspecialchars($invoice['invoice_number']); ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    <?php echo date("M j, Y", strtotime($invoice['invoice_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $is_overdue ? 'text-red-600 font-semibold' : 'text-slate-600'; ?>">
                                    <?php echo $invoice['due_date'] ? date("M j, Y", strtotime($invoice['due_date'])) : 'N/A'; ?>
                                    <?php if ($is_overdue): ?>
                                        <i class="fas fa-exclamation-circle ml-1 text-xs" title="Overdue"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-800 text-right font-mono">
                                    R<?php echo number_format($invoice['total_amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-mono <?php echo $balance > 0 ? 'text-orange-600 font-bold' : 'text-slate-400'; ?>">
                                    R<?php echo number_format($balance, 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="<?php echo htmlspecialchars(get_client_invoice_status_badge($invoice['status'])); ?>">
                                        <?php echo htmlspecialchars(ucwords($invoice['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <a href="view_invoice.php?id=<?php echo $invoice['invoice_id']; ?>" 
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-blue-100 hover:text-blue-600 transition-colors" 
                                       title="View Invoice">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-file-invoice text-2xl text-slate-300"></i>
                                    </div>
                                    <p class="text-slate-500 font-medium">No invoices found.</p>
                                    <p class="text-xs text-slate-400 mt-1">Try adjusting your filters.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'client_footer.php'; ?>
