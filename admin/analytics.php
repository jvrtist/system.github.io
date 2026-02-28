<?php
// admin/analytics.php - Advanced Analytics Dashboard
require_once '../config.php';
require_login();

if (!user_has_role('admin')) {
    $_SESSION['error_message'] = "Access denied. Admin privileges required.";
    redirect('dashboard.php');
}

$page_title = "Analytics Dashboard";
$conn = get_db_connection();

// Date range for analytics (default to last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Analytics data
$analytics = [
    'case_metrics' => [],
    'financial_metrics' => [],
    'client_metrics' => [],
    'performance_metrics' => [],
    'trends' => []
];

if ($conn) {
    // Case Metrics
    $stmt_cases = $conn->prepare("
        SELECT
            COUNT(*) as total_cases,
            SUM(CASE WHEN status IN ('Closed', 'Resolved') THEN 1 ELSE 0 END) as closed_cases,
            AVG(CASE WHEN status IN ('Closed', 'Resolved') THEN DATEDIFF(updated_at, created_at) END) as avg_resolution_days,
            COUNT(CASE WHEN created_at >= ? AND created_at <= ? THEN 1 END) as new_cases_period,
            COUNT(CASE WHEN status IN ('Closed', 'Resolved') AND updated_at >= ? AND updated_at <= ? THEN 1 END) as closed_cases_period
        FROM cases
    ");
    $stmt_cases->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
    $stmt_cases->execute();
    $analytics['case_metrics'] = $stmt_cases->get_result()->fetch_assoc();

    // Financial Metrics
    $stmt_financial = $conn->prepare("
        SELECT
            SUM(amount_paid) as total_revenue,
            SUM(total_amount - amount_paid) as outstanding_balance,
            COUNT(CASE WHEN status = 'Paid' THEN 1 END) as paid_invoices,
            COUNT(CASE WHEN status IN ('Sent', 'Partially Paid', 'Overdue') THEN 1 END) as pending_invoices,
            AVG(amount_paid) as avg_invoice_value
        FROM invoices
        WHERE created_at >= ? AND created_at <= ?
    ");
    $stmt_financial->bind_param("ss", $start_date, $end_date);
    $stmt_financial->execute();
    $analytics['financial_metrics'] = $stmt_financial->get_result()->fetch_assoc();

    // Client Metrics
    $stmt_clients = $conn->prepare("
        SELECT
            COUNT(DISTINCT c.client_id) as active_clients,
            COUNT(DISTINCT CASE WHEN ca.created_at >= ? AND ca.created_at <= ? THEN c.client_id END) as new_clients_period,
            AVG(CASE WHEN ca.status IN ('Closed', 'Resolved') THEN DATEDIFF(ca.updated_at, ca.created_at) END) as avg_case_duration
        FROM clients c
        LEFT JOIN cases ca ON c.client_id = ca.client_id
    ");
    $stmt_clients->bind_param("ss", $start_date, $end_date);
    $stmt_clients->execute();
    $analytics['client_metrics'] = $stmt_clients->get_result()->fetch_assoc();

    // Performance Metrics
    $stmt_performance = $conn->prepare("
        SELECT
            COUNT(DISTINCT t.task_id) as total_tasks,
            COUNT(CASE WHEN t.status = 'Completed' THEN 1 END) as completed_tasks,
            COUNT(CASE WHEN t.due_date < CURDATE() AND t.status != 'Completed' THEN 1 END) as overdue_tasks,
            AVG(CASE WHEN t.status = 'Completed' THEN DATEDIFF(t.completed_at, t.created_at) END) as avg_task_completion_days
        FROM tasks t
        WHERE t.created_at >= ? AND t.created_at <= ?
    ");
    $stmt_performance->bind_param("ss", $start_date, $end_date);
    $stmt_performance->execute();
    $analytics['performance_metrics'] = $stmt_performance->get_result()->fetch_assoc();

    // Trends Data (for charts)
    // Monthly case creation trend
    $stmt_trends = $conn->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as cases_created,
            COUNT(CASE WHEN status IN ('Closed', 'Resolved') THEN 1 END) as cases_closed
        FROM cases
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $stmt_trends->execute();
    $analytics['trends']['monthly_cases'] = $stmt_trends->get_result()->fetch_all(MYSQLI_ASSOC);

    // Revenue trend
    $stmt_revenue_trend = $conn->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(amount_paid) as revenue
        FROM invoices
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND status = 'Paid'
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $stmt_revenue_trend->execute();
    $analytics['trends']['monthly_revenue'] = $stmt_revenue_trend->get_result()->fetch_all(MYSQLI_ASSOC);

    $conn->close();
}

include_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/5 pb-6">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Analytics <span class="text-primary">Dashboard</span></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em] mt-1">
                Intelligence & Performance Metrics
            </p>
        </div>
        <div class="flex items-center gap-4">
            <!-- Date Range Filter -->
            <form method="GET" class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-slate-400">From:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>"
                           class="bg-slate-800 border border-white/10 rounded px-3 py-2 text-sm text-white focus:outline-none focus:border-primary/50">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-slate-400">To:</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>"
                           class="bg-slate-800 border border-white/10 rounded px-3 py-2 text-sm text-white focus:outline-none focus:border-primary/50">
                </div>
                <button type="submit" class="bg-primary hover:bg-orange-600 text-white text-xs font-bold uppercase tracking-widest py-2 px-4 rounded-lg transition-all">
                    <i class="fas fa-filter mr-2"></i>Apply
                </button>
            </form>
        </div>
    </header>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Case Success Rate -->
        <div class="bg-slate-900 p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-green-500/30 transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-chart-line fa-4x text-green-500"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Case Success Rate</p>
            <div class="flex items-baseline gap-2">
                <p class="text-4xl font-black text-white leading-none">
                    <?php echo $analytics['case_metrics']['total_cases'] > 0 ? round(($analytics['case_metrics']['closed_cases'] / $analytics['case_metrics']['total_cases']) * 100, 1) : 0; ?>%
                </p>
                <span class="text-[10px] font-bold text-green-500 uppercase">Resolution</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">
                <?php echo $analytics['case_metrics']['closed_cases']; ?>/<?php echo $analytics['case_metrics']['total_cases']; ?> cases resolved
            </p>
        </div>

        <!-- Average Resolution Time -->
        <div class="bg-slate-900 p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-blue-500/30 transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-clock fa-4x text-blue-500"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Avg Resolution Time</p>
            <div class="flex items-baseline gap-2">
                <p class="text-4xl font-black text-white leading-none"><?php echo round($analytics['case_metrics']['avg_resolution_days'] ?? 0, 1); ?></p>
                <span class="text-[10px] font-bold text-blue-500 uppercase">Days</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">From case creation to closure</p>
        </div>

        <!-- Revenue Generated -->
        <div class="bg-slate-900 p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-yellow-500/30 transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-dollar-sign fa-4x text-yellow-500"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Revenue Generated</p>
            <div class="flex items-baseline gap-2">
                <p class="text-3xl font-black text-white leading-none">R<?php echo number_format($analytics['financial_metrics']['total_revenue'] ?? 0, 0); ?></p>
                <span class="text-[10px] font-bold text-yellow-500 uppercase">Earned</span>
            </div>
            <p class="text-xs text-slate-400 mt-2"><?php echo $analytics['financial_metrics']['paid_invoices'] ?? 0; ?> invoices paid</p>
        </div>

        <!-- Task Completion Rate -->
        <div class="bg-slate-900 p-6 rounded-2xl border border-white/5 relative overflow-hidden group hover:border-purple-500/30 transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <i class="fas fa-tasks fa-4x text-purple-500"></i>
            </div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Task Completion</p>
            <div class="flex items-baseline gap-2">
                <p class="text-4xl font-black text-white leading-none">
                    <?php echo $analytics['performance_metrics']['total_tasks'] > 0 ? round(($analytics['performance_metrics']['completed_tasks'] / $analytics['performance_metrics']['total_tasks']) * 100, 1) : 0; ?>%
                </p>
                <span class="text-[10px] font-bold text-purple-500 uppercase">Rate</span>
            </div>
            <p class="text-xs text-slate-400 mt-2"><?php echo $analytics['performance_metrics']['overdue_tasks'] ?? 0; ?> tasks overdue</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Case Trends Chart -->
        <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl p-6">
            <div class="flex items-center gap-3 mb-6">
                <i class="fas fa-chart-bar text-primary"></i>
                <h2 class="text-sm font-black uppercase tracking-widest text-slate-300">Case Trends (12 Months)</h2>
            </div>
            <canvas id="caseTrendsChart" width="400" height="300"></canvas>
        </section>

        <!-- Revenue Trends Chart -->
        <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl p-6">
            <div class="flex items-center gap-3 mb-6">
                <i class="fas fa-chart-line text-green-500"></i>
                <h2 class="text-sm font-black uppercase tracking-widest text-slate-300">Revenue Trends (12 Months)</h2>
            </div>
            <canvas id="revenueTrendsChart" width="400" height="300"></canvas>
        </section>
    </div>

    <!-- Detailed Metrics Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Case Status Breakdown -->
        <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
            <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02]">
                <h2 class="text-xs font-black uppercase tracking-widest text-slate-400">Case Status Breakdown</h2>
            </div>
            <div class="p-6">
                <canvas id="caseStatusBreakdownChart" width="300" height="300"></canvas>
            </div>
        </section>

        <!-- Financial Summary -->
        <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
            <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02]">
                <h2 class="text-xs font-black uppercase tracking-widest text-slate-400">Financial Summary</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-400">Total Revenue</span>
                    <span class="text-lg font-bold text-green-500">R<?php echo number_format($analytics['financial_metrics']['total_revenue'] ?? 0, 2); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-400">Outstanding Balance</span>
                    <span class="text-lg font-bold text-yellow-500">R<?php echo number_format($analytics['financial_metrics']['outstanding_balance'] ?? 0, 2); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-400">Avg Invoice Value</span>
                    <span class="text-lg font-bold text-blue-500">R<?php echo number_format($analytics['financial_metrics']['avg_invoice_value'] ?? 0, 2); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-400">Paid Invoices</span>
                    <span class="text-lg font-bold text-green-400"><?php echo $analytics['financial_metrics']['paid_invoices'] ?? 0; ?></span>
                </div>
            </div>
        </section>

        <!-- Performance Metrics -->
        <section class="bg-slate-900 rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
            <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02]">
                <h2 class="text-xs font-black uppercase tracking-widest text-slate-400">Performance Metrics</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-400">Active Clients</span>
                    <span class="text-lg font-bold text-purple-500"><?php echo $analytics['client_metrics']['active_clients'] ?? 0; ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-400">New Clients (Period)</span>
                    <span class="text-lg font-bold text-green-500"><?php echo $analytics['client_metrics']['new_clients_period'] ?? 0; ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-400">Avg Case Duration</span>
                    <span class="text-lg font-bold text-blue-500"><?php echo round($analytics['client_metrics']['avg_case_duration'] ?? 0, 1); ?> days</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-400">Task Completion Rate</span>
                    <span class="text-lg font-bold text-orange-500">
                        <?php echo $analytics['performance_metrics']['total_tasks'] > 0 ? round(($analytics['performance_metrics']['completed_tasks'] / $analytics['performance_metrics']['total_tasks']) * 100, 1) : 0; ?>%
                    </span>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
// Case Trends Chart
const caseTrendsCtx = document.getElementById('caseTrendsChart').getContext('2d');
const caseTrendsData = <?php echo json_encode($analytics['trends']['monthly_cases']); ?>;
new Chart(caseTrendsCtx, {
    type: 'line',
    data: {
        labels: caseTrendsData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Cases Created',
            data: caseTrendsData.map(item => item.cases_created),
            borderColor: '#f78f18',
            backgroundColor: 'rgba(247, 143, 24, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Cases Closed',
            data: caseTrendsData.map(item => item.cases_closed),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
                labels: { color: '#94a3b8' }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { color: '#94a3b8' },
                grid: { color: 'rgba(255, 255, 255, 0.1)' }
            },
            x: {
                ticks: { color: '#94a3b8' },
                grid: { color: 'rgba(255, 255, 255, 0.1)' }
            }
        }
    }
});

// Revenue Trends Chart
const revenueTrendsCtx = document.getElementById('revenueTrendsChart').getContext('2d');
const revenueTrendsData = <?php echo json_encode($analytics['trends']['monthly_revenue']); ?>;
new Chart(revenueTrendsCtx, {
    type: 'bar',
    data: {
        labels: revenueTrendsData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Revenue (R)',
            data: revenueTrendsData.map(item => item.revenue),
            backgroundColor: 'rgba(34, 197, 94, 0.8)',
            borderColor: '#22c55e',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
                labels: { color: '#94a3b8' }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#94a3b8',
                    callback: function(value) {
                        return 'R' + value.toLocaleString();
                    }
                },
                grid: { color: 'rgba(255, 255, 255, 0.1)' }
            },
            x: {
                ticks: { color: '#94a3b8' },
                grid: { color: 'rgba(255, 255, 255, 0.1)' }
            }
        }
    }
});

// Case Status Breakdown Chart
const caseStatusCtx = document.getElementById('caseStatusBreakdownChart').getContext('2d');

// Get case status counts from the database
<?php
$case_status_data = [];
if ($conn) {
    $conn = get_db_connection();
    $stmt_status = $conn->query("SELECT status, COUNT(*) as count FROM cases GROUP BY status");
    while ($row = $stmt_status->fetch_assoc()) {
        $case_status_data[] = $row;
    }
    $conn->close();
}
?>

const caseStatusData = <?php echo json_encode($case_status_data); ?>;
new Chart(caseStatusCtx, {
    type: 'doughnut',
    data: {
        labels: caseStatusData.map(item => item.status),
        datasets: [{
            data: caseStatusData.map(item => item.count),
            backgroundColor: [
                '#f78f18', '#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ef4444'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: '#94a3b8' }
            }
        }
    }
});
</script>

<?php include_once '../includes/footer.php'; ?>
