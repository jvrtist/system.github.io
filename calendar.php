<?php
/**
 * ISS Investigations - Operational Calendar
 * Visual timeline of case milestones and task deadlines.
 */
require_once 'config.php';
require_login();

$page_title = "Operational Calendar";
$conn = get_db_connection();

// Get current month/year
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Navigation logic
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

// Fetch Events (Tasks & Case Openings)
$events = [];
if ($conn) {
    $start_date = "$year-$month-01";
    $end_date = date("Y-m-t", strtotime($start_date));

    // Tasks
    $stmt_tasks = $conn->prepare("
        SELECT t.task_id, t.description, t.due_date, t.priority, c.case_number 
        FROM tasks t 
        JOIN cases c ON t.case_id = c.case_id 
        WHERE t.due_date BETWEEN ? AND ? AND t.status != 'Completed'
        AND (t.assigned_to_user_id = ? OR ? = 1) -- Show own tasks or all if admin (assuming admin is role check, simplified here)
    ");
    // Simplified permission: Show all for now, or filter by user. Let's filter by user unless admin.
    $is_admin = user_has_role('admin');
    $user_id = $_SESSION['user_id'];
    
    // Fix query for permissions
    $sql_tasks = "
        SELECT t.task_id, t.description, t.due_date, t.priority, c.case_number, c.case_id
        FROM tasks t 
        JOIN cases c ON t.case_id = c.case_id 
        WHERE t.due_date BETWEEN ? AND ? AND t.status != 'Completed'
    ";
    if (!$is_admin) {
        $sql_tasks .= " AND t.assigned_to_user_id = ?";
    }
    
    $stmt_tasks = $conn->prepare($sql_tasks);
    if ($is_admin) {
        $stmt_tasks->bind_param("ss", $start_date, $end_date);
    } else {
        $stmt_tasks->bind_param("ssi", $start_date, $end_date, $user_id);
    }
    
    $stmt_tasks->execute();
    $result_tasks = $stmt_tasks->get_result();
    while ($row = $result_tasks->fetch_assoc()) {
        $day = (int)date('j', strtotime($row['due_date']));
        $events[$day][] = [
            'type' => 'task',
            'title' => $row['description'],
            'ref' => $row['case_number'],
            'priority' => $row['priority'],
            'link' => "cases/view_case.php?id=" . $row['case_id'] . "#tasks-content"
        ];
    }
    $stmt_tasks->close();
    
    $conn->close();
}

// Calendar Grid Logic
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day_of_week = date('w', strtotime("$year-$month-01")); // 0 (Sun) - 6 (Sat)

include_once 'includes/header.php';
?>

<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">Operational <span class="text-primary">Calendar</span></h1>
            <p class="text-[10px] tech-mono text-slate-500 uppercase tracking-[0.2em]">Schedule & Deadlines</p>
        </div>
        <div class="flex items-center gap-4 bg-slate-900 p-2 rounded-xl border border-white/5">
            <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="p-2 text-slate-400 hover:text-white transition-colors"><i class="fas fa-chevron-left"></i></a>
            <span class="text-sm font-bold text-white uppercase tracking-widest w-32 text-center"><?= date("F Y", strtotime("$year-$month-01")) ?></span>
            <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="p-2 text-slate-400 hover:text-white transition-colors"><i class="fas fa-chevron-right"></i></a>
        </div>
    </div>

    <div class="bg-slate-900 border border-white/5 rounded-2xl shadow-2xl overflow-hidden">
        <!-- Days Header -->
        <div class="grid grid-cols-7 border-b border-white/5 bg-slate-950">
            <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day_name): ?>
                <div class="py-3 text-center text-[10px] font-black uppercase tracking-widest text-slate-500"><?= $day_name ?></div>
            <?php endforeach; ?>
        </div>

        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 auto-rows-fr bg-slate-900">
            <?php
            // Empty cells for previous month
            for ($i = 0; $i < $first_day_of_week; $i++) {
                echo '<div class="min-h-[120px] border-b border-r border-white/5 bg-slate-950/30"></div>';
            }

            // Days of the month
            for ($day = 1; $day <= $days_in_month; $day++) {
                $is_today = ($day == date('j') && $month == date('m') && $year == date('Y'));
                $day_events = $events[$day] ?? [];
                
                echo '<div class="min-h-[120px] border-b border-r border-white/5 p-2 relative group hover:bg-white/[0.02] transition-colors">';
                
                // Date Number
                echo '<span class="text-xs font-bold ' . ($is_today ? 'text-white bg-primary w-6 h-6 rounded-full flex items-center justify-center shadow-lg shadow-primary/50' : 'text-slate-500') . '">' . $day . '</span>';
                
                // Events
                echo '<div class="mt-2 space-y-1">';
                foreach ($day_events as $event) {
                    $color = 'bg-slate-700 text-slate-300';
                    if ($event['priority'] === 'High') $color = 'bg-orange-500/20 text-orange-300 border border-orange-500/30';
                    if ($event['priority'] === 'Urgent') $color = 'bg-red-500/20 text-red-300 border border-red-500/30';
                    
                    echo '<a href="' . $event['link'] . '" class="block text-[9px] p-1.5 rounded ' . $color . ' hover:brightness-110 transition-all truncate" title="' . htmlspecialchars($event['title']) . '">';
                    echo '<span class="font-bold opacity-75">' . htmlspecialchars($event['ref']) . '</span> ';
                    echo htmlspecialchars($event['title']);
                    echo '</a>';
                }
                echo '</div>';
                
                echo '</div>';
            }

            // Empty cells for next month to fill grid
            $remaining_cells = 7 - (($days_in_month + $first_day_of_week) % 7);
            if ($remaining_cells < 7) {
                for ($i = 0; $i < $remaining_cells; $i++) {
                    echo '<div class="min-h-[120px] border-b border-r border-white/5 bg-slate-950/30"></div>';
                }
            }
            ?>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
