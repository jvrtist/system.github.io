<?php
/**
 * ISS Investigations - Blog/Journal Feed
 * Purpose: Display a comprehensive list of all published investigative insights.
 */

$page_title = "Investigation & Fraud Prevention Blog | Corporate Intelligence Insights | ISS Gauteng";
require_once 'config.php';

// --- Fetch All Published Posts ---
$posts = [];
$conn = get_db_connection();

if ($conn) {
    // We fetch all columns needed for the cards
    $sql = "SELECT post_id, title, content, created_at, slug, featured_image
            FROM posts
            WHERE status = 'Published'
            AND (publish_at IS NULL OR publish_at <= NOW())
            ORDER BY created_at DESC";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $posts = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }
    $conn->close();
}

/**
 * Clean truncation for excerpt
 */
function getExcerpt($text, $limit = 150) {
    $text = strip_tags($text);
    if (strlen($text) > $limit) {
        // Break at the last full word
        $text = substr($text, 0, strrpos(substr($text, 0, $limit), ' ')) . '...';
    }
    return $text;
}

include_once 'includes/public_header.php';
?>

<header class="bg-secondary py-20 border-b border-white/10">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight">
            The <span class="text-primary">Intelligence</span> Journal
        </h1>
        <p class="mt-4 text-slate-400 max-w-2xl mx-auto text-lg">
            Expert analysis on corporate fraud, private surveillance, and South African legal standards. Stay informed on how to protect your assets and interests.
        </p>
    </div>
</header>

<main class="bg-slate-50 py-16 min-h-screen">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if (!empty($posts)): ?>
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($posts as $index => $post): 
                    $post_url = "view_post.php?slug=" . urlencode($post['slug']);
                    $date = date('F j, Y', strtotime($post['created_at']));
                ?>
                    <article class="card-premium flex flex-col rounded-3xl overflow-hidden shadow-card hover:shadow-card-hover transition-all duration-500 group animate-fade-in-up" style="animation-delay: <?= $index * 0.1 ?>s">
                        <div class="h-56 bg-gradient-to-br from-slate-200 to-slate-300 relative overflow-hidden">
                            <?php if (!empty($post['featured_image'])): ?>
                                <img src="<?= htmlspecialchars($post['featured_image']); ?>"
                                     alt="<?= htmlspecialchars($post['title']); ?>"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1507208773393-40d9fc670acf?auto=format&fit=crop&w=800&q=80"
                                     alt="<?= htmlspecialchars($post['title']); ?>"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="absolute top-4 left-4">
                                <span class="bg-gradient-accent text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full shadow-glow animate-pulse-slow">
                                    Intelligence
                                </span>
                            </div>
                        </div>

                        <div class="p-8 flex-1 flex flex-col">
                            <div class="flex items-center text-xs text-slate-400 mb-3 font-semibold uppercase tracking-tighter">
                                <i class="far fa-clock mr-2 text-primary"></i> <?= $date ?>
                                <span class="mx-2 text-slate-300">&middot;</span>
                                <i class="far fa-user mr-2 text-primary"></i> Senior Investigator
                            </div>

                            <h2 class="text-2xl font-bold text-slate-900 leading-tight mb-4 group-hover:text-primary transition-colors duration-300">
                                <a href="<?= $post_url ?>">
                                    <?= htmlspecialchars($post['title']); ?>
                                </a>
                            </h2>

                            <p class="text-slate-600 text-sm leading-relaxed mb-6">
                                <?= htmlspecialchars(getExcerpt($post['content'])); ?>
                            </p>

                            <div class="mt-auto pt-6 border-t border-slate-200">
                                <a href="<?= $post_url ?>" class="inline-flex items-center text-primary font-bold text-sm hover:text-orange-600 transition-all duration-300 group-hover:translate-x-2 transform">
                                    Analyze Report <i class="fas fa-chevron-right ml-2 text-[10px] transition-transform duration-300 group-hover:translate-x-1"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="max-w-2xl mx-auto text-center py-20">
                <div class="bg-white p-12 rounded-3xl shadow-sm border border-dashed border-slate-300">
                    <i class="fas fa-folder-open fa-4x text-slate-200 mb-6"></i>
                    <h3 class="text-2xl font-bold text-slate-900 mb-2">No Insights Published Yet</h3>
                    <p class="text-slate-500 mb-8">Our investigators are currently in the field. New reports and insights will be published here soon.</p>
                    <a href="index.php" class="btn-primary inline-block py-3 px-8 rounded-full shadow-glow hover:shadow-glow-lg transform hover:scale-105 transition-all duration-300">
                        Return to Homepage
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<section class="py-24 bg-white relative overflow-hidden">
    <div class="container mx-auto px-4 relative z-10 text-center">
        <div class="max-w-4xl mx-auto bg-secondary p-12 md:p-20 rounded-[3rem] shadow-2xl">
                <h2 class="text-3xl md:text-5xl font-black text-white mb-8">Stay Informed on Local Security</h2>
                <p class="text-slate-300 text-lg mb-12 max-w-2xl mx-auto">
                    Need professional advice on a specific matter? Get in touch for a confidential discussion regarding your unique requirements.
                </p>
                <a href="contact.php" class="inline-block bg-primary hover:bg-orange-600 text-white font-black py-4 px-12 rounded-full text-lg shadow-xl transition-all transform hover:scale-105">
                    Speak with an Investigator
                </a>
            </div>
            <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        </div>
    </div>
</section>

<?php include_once 'includes/public_footer.php'; ?>

            <h2 class="">Discretion is our Foundation.</h2>
            <p class="text-slate-300 text-lg mb-12 max-w-2xl mx-auto">
                No matter how complex or sensitive your situation, our lead investigators are ready to provide a confidential strategy to find the truth.
            </p>
            <a href="contact.php" class="">
                Begin Your Consultation
            </a>
        </div>
    </div>
</section>