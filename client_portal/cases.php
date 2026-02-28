<?php
// client_portal/cases.php
require_once '../config.php';
require_once 'client_auth.php';

$page_title = "My Cases";
$client_id = $_SESSION[CLIENT_ID_SESSION_VAR];
$conn = get_db_connection();

// --- Filters & Search ---
$filter_status = isset($_GET['status']) ? sanitize_input($_GET['status']) : 'active'; // active, closed, all
$search_query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';

// Build Query
$sql = "SELECT case_id, case_number, title, status, priority, created_at, updated_at 
        FROM cases 
        WHERE client_id = ?";

$params = [$client_id];
$types = "i";

if ($filter_status === 'active') {
    $sql .= " AND status NOT IN ('Closed', 'Resolved', 'Archived')";
} elseif ($filter_status === 'closed') {
    $sql .= " AND status IN ('Closed', 'Resolved', 'Archived')";
}

if (!empty($search_query)) {
    $sql .= " AND (case_number LIKE ? OR title LIKE ?)";
    $search_term = "%" . $search_query . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$sql .= " ORDER BY updated_at DESC";

$cases = [];
if ($conn) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $cases = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
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

include_once 'client_header.php';
?>

<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header Section -->
    <header class="border-l-4 border-primary pl-6 animate-fade-in-up">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-secondary mb-2">Case Management</h1>
               <p class="text-[10px] tech-mono text-slate-600 uppercase tracking-[0.2em] mt-1">Track the progress and details of your investigations.</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="btn-primary inline-flex items-center gap-2 text-sm font-semibold">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Controls Section -->
    <div class="p-4 rounded-xl shadow-sm border border-slate-200">
        <form action="cases.php" method="GET" class="flex flex-col md:flex-row gap-6">
            <div class="flex-grow relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-primary text-lg"></i>
                </div>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" 
                       placeholder="Search by Case # or Title..." 
                       class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-lg">
            </div>
            <div class="flex gap-3 pb-2 md:pb-0">
                <button type="submit" name="status" value="active" 
                        class="px-2 rounded-2xl text-sm font-black whitespace-nowrap transition-all duration-300 transform hover:scale-105 <?php echo $filter_status === 'active' ? 'bg-gradient-primary text-white shadow-glow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 shadow-card hover:shadow-card-hover'; ?>">
                    Active Cases
                </button>
                <button type="submit" name="status" value="closed" 
                        class="px-6 py-4 rounded-2xl text-sm font-black whitespace-nowrap transition-all duration-300 transform hover:scale-105 <?php echo $filter_status === 'closed' ? 'bg-gradient-secondary text-white shadow-glow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 shadow-card hover:shadow-card-hover'; ?>">
                    <i class="fas fa-check-circle"></i>Closed / Archived
                </button>
                <button type="submit" name="status" value="all" 
                        class="px-6 py-4 rounded-2xl text-sm font-black whitespace-nowrap transition-all duration-300 transform hover:scale-105 <?php echo $filter_status === 'all' ? 'bg-gradient-accent text-white shadow-glow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 shadow-card hover:shadow-card-hover'; ?>">
                    <i class="fas fa-list"></i>All Records
                </button>
            </div>
        </form>
    </section>

    <!-- Cases Grid -->
    <?php if (!empty($cases)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($cases as $index => $case): ?>
                <a href="view_case_details.php?id=<?php echo $case['case_id']; ?>" 
                   class="group card-premium hover:shadow-card-hover transition-all duration-500 animate-fade-in-up overflow-hidden flex flex-col h-full" style="animation-delay: <?= $index * 0.1 ?>s">
                    
                    <div class="p-8 flex-grow">
                        <div class="flex justify-between items-start mb-6">
                            <span class="px-4 py-2 rounded-2xl text-xs font-black uppercase tracking-wider border-2 
                                <?php 
                                $status_class = get_status_badge_color($case['status']);
                                echo str_replace(['bg-', 'text-', 'border-'], ['bg-gradient-to-r from-', 'text-', 'border-'], $status_class);
                                ?>">
                                <?php echo htmlspecialchars($case['status']); ?>
                            </span>
                            <span class="text-sm font-mono text-slate-400 bg-slate-100 px-3 py-1 rounded-full font-semibold">#<?php echo htmlspecialchars($case['case_number']); ?></span>
                        </div>
                        
                        <h3 class="text-xl font-black text-secondary group-hover:text-primary transition-colors mb-4 line-clamp-2 leading-tight">
                            <?php echo htmlspecialchars($case['title']); ?>
                        </h3>
                        
                        <div class="flex items-center gap-4 text-sm text-slate-500 mt-6">
                            <div class="flex items-center gap-2">
                                <i class="far fa-calendar-alt text-primary"></i>
                                <span class="font-semibold">Opened: <?php echo date("M j, Y", strtotime($case['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-8 py-6 border-t border-slate-200 flex justify-between items-center">
                        <span class="text-sm text-slate-500 font-medium">
                            Updated <?php echo date("M j, Y", strtotime($case['updated_at'])); ?>
                        </span>
                        <span class="text-sm font-black text-primary group-hover:translate-x-1 transition-transform flex items-center gap-2">
                            View Details <i class="fas fa-arrow-right"></i>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card-premium p-16 text-center animate-scale-in">
            <div class="w-24 h-24 bg-gradient-to-br from-slate-100 to-slate-200 rounded-3xl flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-folder-open text-4xl text-slate-400"></i>
            </div>
            <h3 class="text-2xl font-black text-secondary mb-3">No cases found</h3>
            <p class="text-slate-600 text-lg mb-6">No cases match your current filter or search criteria.</p>
            <?php if ($filter_status !== 'active' || !empty($search_query)): ?>
                <a href="cases.php" class="btn-primary inline-flex items-center gap-2">
                    <i class="fas fa-refresh"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'client_footer.php'; ?>
