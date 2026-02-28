<?php
/**
 * ISS Investigations - Intelligence Reports
 * Central hub for generating operational and financial reports.
 */
require_once '../config.php';
require_login();

$page_title = "Intelligence Reports";

// Define available reports
$available_reports = [
    [
        'id' => 'case_summary',
        'name' => 'Case Summary',
        'description' => 'Comprehensive overview of all active and closed cases, filtered by status and priority.',
        'url' => 'generate_case_summary_report.php',
        'icon' => 'fas fa-briefcase',
        'roles' => ['admin', 'investigator']
    ],
    [
        'id' => 'task_status',
        'name' => 'Task Status',
        'description' => 'Detailed breakdown of task assignments, completion rates, and overdue items.',
        'url' => 'generate_task_status_report.php',
        'icon' => 'fas fa-list-check',
        'roles' => ['admin', 'investigator']
    ],
    [
        'id' => 'client_activity',
        'name' => 'Client Activity',
        'description' => 'Analysis of client interactions, case volume, and engagement history.',
        'url' => 'generate_client_activity_report.php',
        'icon' => 'fas fa-users',
        'roles' => ['admin']
    ],
    [
        'id' => 'user_performance',
        'name' => 'Agent Performance',
        'description' => 'Metrics on investigator caseload, task efficiency, and closure rates.',
        'url' => 'generate_user_performance_report.php',
        'icon' => 'fas fa-chart-line',
        'roles' => ['admin']
    ],
    [
        'id' => 'invoice_aging',
        'name' => 'Financial Aging',
        'description' => 'Report on outstanding invoices, payment delays, and revenue projections.',
        'url' => 'generate_invoice_aging_report.php',
        'icon' => 'fas fa-file-invoice-dollar',
        'roles' => ['admin']
    ],
    [
        'id' => 'case_trends',
        'name' => 'Case Trends',
        'description' => 'Longitudinal analysis of case intake and resolution over time.',
        'url' => 'generate_case_status_trends_report.php',
        'icon' => 'fas fa-chart-area',
        'roles' => ['admin', 'investigator']
    ]
];

// Filter reports by role
$user_role = $_SESSION['user_role'] ?? 'investigator';
$accessible_reports = array_filter($available_reports, function($report) use ($user_role) {
    return in_array($user_role, $report['roles']);
});

include_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <header class="border-l-4 border-primary pl-6">
        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Intelligence <span class="text-primary">Reports</span></h1>
        <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em]">Data Analysis & Export Center</p>
    </header>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 flex items-start gap-3 animate-shake">
            <i class="fas fa-exclamation-triangle text-red-400 mt-0.5 flex-shrink-0"></i>
            <p class="text-sm font-bold text-red-200"><?= htmlspecialchars($_SESSION['error_message']) ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($accessible_reports)): ?>
            <?php foreach ($accessible_reports as $report): ?>
                <div class="bg-slate-900 border border-white/5 rounded-2xl p-6 shadow-lg hover:shadow-primary/20 hover:border-primary/30 transition-all duration-300 flex flex-col group">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-slate-800 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                            <i class="<?= htmlspecialchars($report['icon']) ?> text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-white uppercase tracking-tight"><?= htmlspecialchars($report['name']) ?></h3>
                            <span class="text-[9px] tech-mono text-slate-500 uppercase tracking-widest">Module</span>
                        </div>
                    </div>
                    
                    <p class="text-xs text-slate-400 leading-relaxed mb-6 flex-grow">
                        <?= htmlspecialchars($report['description']) ?>
                    </p>
                    
                    <a href="<?= htmlspecialchars($report['url']) ?>" class="w-full bg-slate-950 hover:bg-primary text-slate-300 hover:text-white text-[10px] font-black uppercase tracking-widest py-3 rounded-xl text-center transition-all border border-white/5 hover:border-primary/50">
                        Generate Report
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-20 bg-slate-900 border border-white/5 rounded-2xl">
                <i class="fas fa-lock text-5xl text-slate-700 mb-4"></i>
                <h3 class="text-lg font-bold text-white">Access Restricted</h3>
                <p class="text-sm text-slate-500 mt-2">No reports are available for your current clearance level.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
