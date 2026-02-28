<?php
/**
 * ISS Investigations - Reviews Management
 * Admin interface for approving/rejecting client reviews
 */

$page_title = "Review Management";
require_once '../config.php';
require_login();

// Check if user has permission to manage reviews (admin or manager role)
if (!user_has_role('admin') && !user_has_role('manager')) {
    $_SESSION['admin_error_message'] = "You don't have permission to access this page.";
    redirect('dashboard.php');
}

// Handle review actions (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    $conn = get_db_connection();

    $review_id = (int)$_POST['review_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE reviews SET is_approved = TRUE, approved_by = ?, approved_at = NOW() WHERE review_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $review_id);
        $stmt->execute();
        $stmt->close();

        log_audit_action($_SESSION['user_id'], null, 'approve_review', 'review', $review_id, 'Review approved for public display');
        $_SESSION['admin_success_message'] = "Review approved and published.";
    } elseif ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        $stmt = $conn->prepare("UPDATE reviews SET is_approved = FALSE, rejected_by = ?, rejected_at = NOW(), rejection_reason = ? WHERE review_id = ?");
        $stmt->bind_param("isi", $_SESSION['user_id'], $reason, $review_id);
        $stmt->execute();
        $stmt->close();

        log_audit_action($_SESSION['user_id'], null, 'reject_review', 'review', $review_id, 'Review rejected: ' . $reason);
        $_SESSION['admin_success_message'] = "Review rejected.";
    } elseif ($action === 'feature') {
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $stmt = $conn->prepare("UPDATE reviews SET is_featured = ? WHERE review_id = ?");
        $stmt->bind_param("ii", $is_featured, $review_id);
        $stmt->execute();
        $stmt->close();

        $action_text = $is_featured ? 'featured' : 'unfeatured';
        log_audit_action($_SESSION['user_id'], null, 'feature_review', 'review', $review_id, "Review $action_text");
        $_SESSION['admin_success_message'] = "Review " . ($is_featured ? 'featured' : 'unfeatured') . ".";
    }

    redirect('reviews.php');
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'pending';
$rating_filter = $_GET['rating'] ?? 'all';

// Build query based on filters
$where_conditions = [];
$params = [];
$param_types = '';

if ($status_filter === 'approved') {
    $where_conditions[] = "is_approved = TRUE";
} elseif ($status_filter === 'rejected') {
    $where_conditions[] = "rejected_by IS NOT NULL";
} elseif ($status_filter === 'pending') {
    $where_conditions[] = "is_approved = FALSE AND rejected_by IS NULL";
}

if ($rating_filter !== 'all') {
    $where_conditions[] = "rating = ?";
    $params[] = (int)$rating_filter;
    $param_types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$conn = get_db_connection();

$query = "SELECT r.*, u.username as approved_by_name, ru.username as rejected_by_name
          FROM reviews r
          LEFT JOIN users u ON r.approved_by = u.user_id
          LEFT JOIN users ru ON r.rejected_by = ru.user_id
          $where_clause
          ORDER BY r.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$reviews = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get statistics
$stats_query = "SELECT
    COUNT(*) as total_reviews,
    SUM(CASE WHEN is_approved = TRUE THEN 1 ELSE 0 END) as approved_reviews,
    SUM(CASE WHEN rejected_by IS NOT NULL THEN 1 ELSE 0 END) as rejected_reviews,
    SUM(CASE WHEN is_approved = FALSE AND rejected_by IS NULL THEN 1 ELSE 0 END) as pending_reviews,
    AVG(rating) as avg_rating
    FROM reviews";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

include_once '../includes/header.php';
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8">
        <div>
            <h1 class="text-3xl font-black text-secondary mb-2">Review Management</h1>
            <p class="text-slate-600">Approve, reject, and manage client reviews</p>
        </div>
        <div class="mt-4 lg:mt-0">
            <a href="../submit_review.php" target="_blank" class="btn-primary inline-flex items-center gap-2">
                <i class="fas fa-external-link-alt"></i> View Public Form
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-2xl font-black text-secondary mb-1"><?php echo $stats['total_reviews'] ?? 0; ?></div>
            <div class="text-sm text-slate-500 uppercase tracking-widest">Total Reviews</div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-2xl font-black text-green-600 mb-1"><?php echo $stats['approved_reviews'] ?? 0; ?></div>
            <div class="text-sm text-slate-500 uppercase tracking-widest">Approved</div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-2xl font-black text-orange-600 mb-1"><?php echo $stats['pending_reviews'] ?? 0; ?></div>
            <div class="text-sm text-slate-500 uppercase tracking-widest">Pending</div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-2xl font-black text-red-600 mb-1"><?php echo $stats['rejected_reviews'] ?? 0; ?></div>
            <div class="text-sm text-slate-500 uppercase tracking-widest">Rejected</div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-2xl font-black text-primary mb-1"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?>★</div>
            <div class="text-sm text-slate-500 uppercase tracking-widest">Avg Rating</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-8">
        <div class="flex flex-col md:flex-row gap-4">
            <div>
                <label class="block text-sm font-bold text-secondary mb-2">Status Filter</label>
                <select onchange="updateFilters()" id="status_filter" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Reviews</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-secondary mb-2">Rating Filter</label>
                <select onchange="updateFilters()" id="rating_filter" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="all" <?php echo $rating_filter === 'all' ? 'selected' : ''; ?>>All Ratings</option>
                    <option value="5" <?php echo $rating_filter === '5' ? 'selected' : ''; ?>>5 Stars</option>
                    <option value="4" <?php echo $rating_filter === '4' ? 'selected' : ''; ?>>4 Stars</option>
                    <option value="3" <?php echo $rating_filter === '3' ? 'selected' : ''; ?>>3 Stars</option>
                    <option value="2" <?php echo $rating_filter === '2' ? 'selected' : ''; ?>>2 Stars</option>
                    <option value="1" <?php echo $rating_filter === '1' ? 'selected' : ''; ?>>1 Star</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Reviews List -->
    <div class="space-y-6">
        <?php if (empty($reviews)): ?>
            <div class="bg-white p-12 rounded-2xl border border-slate-200 shadow-sm text-center">
                <i class="fas fa-comments fa-4x text-slate-200 mb-4"></i>
                <h3 class="text-xl font-bold text-secondary mb-2">No Reviews Found</h3>
                <p class="text-slate-500">No reviews match the current filters.</p>
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-6">
                        <!-- Review Content -->
                        <div class="flex-1">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="flex items-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star text-lg <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-slate-200'; ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ml-2 text-sm text-slate-500">(<?php echo $review['rating']; ?>/5)</span>
                                </div>
                                <?php if ($review['is_featured']): ?>
                                    <span class="px-3 py-1 bg-primary text-white text-xs font-bold rounded-full">FEATURED</span>
                                <?php endif; ?>
                            </div>

                            <h3 class="text-xl font-bold text-secondary mb-2">
                                <?php echo htmlspecialchars($review['review_title'] ?: 'Review from ' . htmlspecialchars($review['client_name'])); ?>
                            </h3>

                            <p class="text-slate-600 mb-4 leading-relaxed"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>

                            <div class="text-sm text-slate-500 space-y-1">
                                <p><strong>From:</strong> <?php echo htmlspecialchars($review['client_name']); ?></p>
                                <?php if ($review['service_type']): ?>
                                    <p><strong>Service:</strong> <?php echo htmlspecialchars($review['service_type']); ?></p>
                                <?php endif; ?>
                                <?php if ($review['case_type']): ?>
                                    <p><strong>Case Type:</strong> <?php echo htmlspecialchars($review['case_type']); ?></p>
                                <?php endif; ?>
                                <p><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($review['created_at'])); ?></p>
                            </div>

                            <?php if ($review['is_approved'] && $review['approved_by_name']): ?>
                                <div class="mt-4 text-sm text-green-600">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Approved by <?php echo htmlspecialchars($review['approved_by_name']); ?> on <?php echo date('M j, Y', strtotime($review['approved_at'])); ?>
                                </div>
                            <?php elseif ($review['rejected_by_name']): ?>
                                <div class="mt-4 text-sm text-red-600">
                                    <i class="fas fa-times-circle mr-1"></i>
                                    Rejected by <?php echo htmlspecialchars($review['rejected_by_name']); ?> on <?php echo date('M j, Y', strtotime($review['rejected_at'])); ?>
                                    <?php if ($review['rejection_reason']): ?>
                                        <br><strong>Reason:</strong> <?php echo htmlspecialchars($review['rejection_reason']); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col gap-3 min-w-[200px]">
                            <?php if (!$review['is_approved'] && !$review['rejected_by']): ?>
                                <!-- Pending Review Actions -->
                                <form method="POST" class="inline-block">
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                    <button type="submit" name="action" value="approve" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition-colors">
                                        <i class="fas fa-check mr-2"></i> Approve
                                    </button>
                                </form>

                                <button onclick="rejectReview(<?php echo $review['review_id']; ?>)" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition-colors">
                                    <i class="fas fa-times mr-2"></i> Reject
                                </button>
                            <?php else: ?>
                                <!-- Approved/Rejected Review Actions -->
                                <form method="POST" class="inline-block">
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="is_featured" value="1"
                                               <?php echo $review['is_featured'] ? 'checked' : ''; ?>
                                               onchange="this.form.submit()" class="rounded">
                                        <input type="hidden" name="action" value="feature">
                                        <span class="text-sm font-medium">Featured Review</span>
                                    </label>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Reject Review Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full">
            <h3 class="text-xl font-bold text-secondary mb-4">Reject Review</h3>
            <form method="POST">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="review_id" id="reject_review_id">
                <div class="mb-4">
                    <label class="block text-sm font-bold text-secondary mb-2">Reason for Rejection (Optional)</label>
                    <textarea name="rejection_reason" rows="4" class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Please provide a reason for rejecting this review..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeRejectModal()" class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-3 px-6 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="action" value="reject" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition-colors">
                        Reject Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateFilters() {
    const status = document.getElementById('status_filter').value;
    const rating = document.getElementById('rating_filter').value;

    const url = new URL(window.location);
    url.searchParams.set('status', status);
    url.searchParams.set('rating', rating);
    window.location.href = url.toString();
}

function rejectReview(reviewId) {
    document.getElementById('reject_review_id').value = reviewId;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('reject_review_id').value = '';
}

// Close modal when clicking outside
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});
</script>

<?php include_once '../includes/footer.php'; ?>
