<?php
/**
 * ISS Investigations - Public Review Submission Form
 * Allows clients to submit reviews for admin approval
 */

$page_title = "Submit a Review | Share Your Experience | ISS Investigations";
include_once 'includes/public_header.php';
require_once 'config.php'; 
$conn = get_db_connection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Security token validation failed. Please try again.";
        redirect('submit_review.php');
    }

    // Get form data
    $client_name = trim($_POST['client_name'] ?? '');
    $client_email = trim($_POST['client_email'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $review_title = trim($_POST['review_title'] ?? '');
    $review_text = trim($_POST['review_text'] ?? '');
    $service_type = trim($_POST['service_type'] ?? '');
    $case_type = trim($_POST['case_type'] ?? '');

    // Validation
    $errors = [];

    if (empty($client_name)) {
        $errors[] = "Please provide your name.";
    }

    if ($rating < 1 || $rating > 5) {
        $errors[] = "Please select a valid rating.";
    }

    if (empty($review_text)) {
        $errors[] = "Please provide your review.";
    }

    if (strlen($review_text) < 10) {
        $errors[] = "Please provide a more detailed review (minimum 10 characters).";
    }

    // Basic spam protection
    if (str_word_count($review_text) < 3) {
        $errors[] = "Please provide a more detailed review.";
    }

    // Check for suspicious content
    $suspicious_words = ['http', 'www', 'viagra', 'casino', 'lottery', 'winner'];
    $review_lower = strtolower($review_text);
    foreach ($suspicious_words as $word) {
        if (strpos($review_lower, $word) !== false) {
            $errors[] = "Your review contains inappropriate content. Please review and resubmit.";
            break;
        }
    }

    if (empty($errors)) {
        // Insert review into database
        $stmt = $conn->prepare("INSERT INTO reviews (client_name, client_email, rating, review_title, review_text, service_type, case_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->bind_param("ssissss",
            $client_name,
            $client_email ?: null,
            $rating,
            $review_title ?: null,
            $review_text,
            $service_type ?: null,
            $case_type ?: null
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Thank you for your review! It has been submitted for approval and will be published once reviewed by our team.";
            redirect('submit_review.php');
        } else {
            $_SESSION['error_message'] = "There was an error submitting your review. Please try again.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }

    redirect('submit_review.php');
}

// Get approved reviews for display
$approved_reviews = [];
$result = $conn->query("SELECT * FROM reviews WHERE is_approved = TRUE ORDER BY is_featured DESC, created_at DESC LIMIT 6");
if ($result) {
    $approved_reviews = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<section class="relative bg-slate-900 pt-32 pb-16 overflow-hidden border-b-4 border-primary">
    <div class="container mx-auto px-4 relative z-10 text-center">
        <span class="inline-block py-1 px-4 rounded-full bg-primary/20 text-primary text-xs font-black tracking-widest uppercase mb-6">Client Feedback</span>
        <h1 class="text-4xl md:text-6xl font-black text-white leading-tight">
            Share Your <span class="text-primary">Experience.</span>
        </h1>
        <p class="mt-6 max-w-2xl mx-auto text-slate-400 font-light">
            Your feedback helps us improve our services and guides other clients. All reviews are reviewed by our team before publication.
        </p>
    </div>
    <div class="absolute inset-0 opacity-10 pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
</section>

<section class="py-24 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">

            <!-- Review Submission Form -->
            <div class="bg-slate-50 p-8 md:p-12 rounded-[3rem] border border-slate-200 shadow-xl mb-16">
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-black text-secondary mb-4">Submit Your Review</h2>
                    <p class="text-slate-600">Help others understand what to expect from our investigative services.</p>
                </div>

                <form action="submit_review.php" method="POST" class="space-y-8">
                    <?php echo csrf_input(); ?>

                    <!-- Rating -->
                    <div>
                        <label class="block text-sm font-black uppercase tracking-widest text-slate-400 mb-4">Overall Rating</label>
                        <div class="flex items-center gap-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" class="sr-only peer" required>
                                    <i class="fas fa-star text-3xl text-slate-300 peer-checked:text-yellow-400 transition-colors hover:text-yellow-400"></i>
                                </label>
                            <?php endfor; ?>
                            <span class="ml-4 text-sm text-slate-500">(Required)</span>
                        </div>
                    </div>

                    <!-- Name and Email -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-black uppercase tracking-widest text-slate-400 mb-2">Your Name</label>
                            <input type="text" name="client_name" required placeholder="John Doe"
                                class="w-full px-6 py-4 bg-white border border-slate-200 rounded-2xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                            <p class="text-xs text-slate-500 mt-1">You can use a pseudonym if you prefer to remain anonymous.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-black uppercase tracking-widest text-slate-400 mb-2">Email (Optional)</label>
                            <input type="email" name="client_email" placeholder="john@example.com"
                                class="w-full px-6 py-4 bg-white border border-slate-200 rounded-2xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                            <p class="text-xs text-slate-500 mt-1">For verification purposes only - not displayed publicly.</p>
                        </div>
                    </div>

                    <!-- Review Title -->
                    <div>
                        <label class="block text-sm font-black uppercase tracking-widest text-slate-400 mb-2">Review Title (Optional)</label>
                        <input type="text" name="review_title" placeholder="Professional and Discreet Service"
                            class="w-full px-6 py-4 bg-white border border-slate-200 rounded-2xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>

                    <!-- Service and Case Type -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-black uppercase tracking-widest text-slate-400 mb-2">Service Type (Optional)</label>
                            <select name="service_type" class="w-full px-6 py-4 bg-white border border-slate-200 rounded-2xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                <option value="">Select Service Type</option>
                                <option value="Corporate Investigation">Corporate Investigation</option>
                                <option value="Domestic & Family Cases">Domestic & Family Cases</option>
                                <option value="Personal Security">Personal Security</option>
                                <option value="Legal Support Services">Legal Support Services</option>
                                <option value="Financial Investigations">Financial Investigations</option>
                                <option value="Insurance Investigations">Insurance Investigations</option>
                                <option value="Emergency Investigations">Emergency Investigations</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-black uppercase tracking-widest text-slate-400 mb-2">Case Type (Optional)</label>
                            <select name="case_type" class="w-full px-6 py-4 bg-white border border-slate-200 rounded-2xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                <option value="">Select Case Type</option>
                                <option value="Fraud Investigation">Fraud Investigation</option>
                                <option value="Infidelity Investigation">Infidelity Investigation</option>
                                <option value="Missing Persons">Missing Persons</option>
                                <option value="Asset Tracing">Asset Tracing</option>
                                <option value="Background Checks">Background Checks</option>
                                <option value="Insurance Claims">Insurance Claims</option>
                                <option value="Legal Support">Legal Support</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Review Text -->
                    <div>
                        <label class="block text-sm font-black uppercase tracking-widest text-slate-400 mb-2">Your Review</label>
                        <textarea name="review_text" rows="8" required placeholder="Please share your experience with our investigative services. What was the outcome? How did our team handle your case? What would you tell other potential clients?"
                            class="w-full px-6 py-4 bg-white border border-slate-200 rounded-2xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all resize-vertical"
                            minlength="10"></textarea>
                        <p class="text-xs text-slate-500 mt-1">Minimum 10 characters. Be specific about your experience to help other clients.</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center pt-6">
                        <button type="submit" class="btn-primary px-12 py-5 text-lg font-bold rounded-full shadow-glow hover:shadow-glow-lg transform hover:scale-105 transition-all duration-300">
                            <i class="fas fa-paper-plane mr-3"></i> Submit Review for Approval
                        </button>
                        <p class="text-sm text-slate-500 mt-4">
                            <i class="fas fa-shield-alt mr-1"></i> All reviews are reviewed by our team before publication. We reserve the right to edit for clarity and professionalism.
                        </p>
                    </div>
                </form>
            </div>

            <!-- Recent Approved Reviews -->
            <?php if (!empty($approved_reviews)): ?>
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-black text-secondary mb-4">What Our Clients Say</h2>
                    <p class="text-slate-600">Recent reviews from satisfied clients</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($approved_reviews as $review): ?>
                        <div class="card-premium p-8 rounded-3xl shadow-card hover:shadow-card-hover transition-all duration-500">
                            <div class="flex items-center mb-6">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star text-lg <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-slate-200'; ?> mr-1"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if ($review['review_title']): ?>
                                <h4 class="text-xl font-bold text-secondary mb-3"><?php echo htmlspecialchars($review['review_title']); ?></h4>
                            <?php endif; ?>
                            <p class="text-slate-600 mb-4 leading-relaxed italic">"<?php echo htmlspecialchars(substr($review['review_text'], 0, 150)) . (strlen($review['review_text']) > 150 ? '..."' : '"'); ?></p>
                            <div class="border-t border-slate-200 pt-4">
                                <div class="font-bold text-secondary"><?php echo htmlspecialchars($review['client_name']); ?></div>
                                <div class="text-sm text-slate-500"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<script>
// Star rating interaction
document.querySelectorAll('input[name="rating"]').forEach((radio, index) => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.fa-star').forEach((star, starIndex) => {
            if (starIndex < index + 1) {
                star.classList.add('text-yellow-400');
                star.classList.remove('text-slate-300');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-slate-300');
            }
        });
    });
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const rating = document.querySelector('input[name="rating"]:checked');
    const reviewText = document.querySelector('textarea[name="review_text"]').value.trim();

    if (!rating) {
        e.preventDefault();
        alert('Please select a rating for your review.');
        return;
    }

    if (reviewText.length < 10) {
        e.preventDefault();
        alert('Please provide a more detailed review (minimum 10 characters).');
        return;
    }
});
</script>

<?php include_once 'includes/public_footer.php'; ?>
