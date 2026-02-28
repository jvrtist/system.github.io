<?php
/**
 * ISS Investigations - Enhanced Homepage
 * Improvements: Refined Copy, Advanced Tailwind Transitions, and Semantic SEO.
 */

$page_title = "Private Investigator Gauteng | Professional Investigations Johannesburg | ISS Investigations";
require_once 'config.php'; 

// --- Database Logic ---
$posts = [];
$approved_reviews = [];
$conn = get_db_connection();

if ($conn) {
    $sql = "SELECT post_id, title, content, created_at, slug, featured_image, view_count, category
            FROM posts
            WHERE status = 'Published'
            AND (publish_at IS NULL OR publish_at <= NOW())
            ORDER BY created_at DESC
            LIMIT 3";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $posts = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }

    // Get approved reviews for display
    $review_result = $conn->query("SELECT * FROM reviews WHERE is_approved = TRUE ORDER BY is_featured DESC, created_at DESC LIMIT 3");
    if ($review_result) {
        $approved_reviews = $review_result->fetch_all(MYSQLI_ASSOC);
    }

    $conn->close();
}

/**
 * Clean truncation with Word-Break Protection
 */
function getSnippet($text, $limit = 110) {
    $text = strip_tags($text);
    if (strlen($text) > $limit) {
        $text = substr($text, 0, strrpos(substr($text, 0, $limit), ' ')) . '...';
    }
    return $text;
}

include_once 'includes/public_header.php';
?>

<section class="relative bg-gradient-secondary overflow-hidden text-white border-b-4 border-primary">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="py-28 sm:py-40 text-center lg:text-left flex flex-col lg:flex-row items-center justify-between">
            <div class="lg:w-2/3 animate-fade-in-up">
                <span class="inline-block py-2 px-4 rounded-full bg-primary/20 text-primary text-sm font-bold tracking-widest uppercase mb-6 border border-primary/30 backdrop-blur-sm">Trusted Across South Africa</span>
                <h1 class="text-5xl md:text-7xl font-black tracking-tight leading-tight mb-6">
                    Find the Truth.<br> <span class="text-gradient-primary text-shadow-lg">Secure the Evidence.</span>
                </h1>
                <p class="mt-6 text-lg md:text-2xl text-slate-300 max-w-2xl leading-relaxed font-light text-shadow">
                    South Africa's premier investigative firm. We provide the clarity and legal proof you need to make informed decisions for your business or personal life.
                </p>
                <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="contact.php" class="btn-primary inline-flex items-center justify-center font-bold py-4 px-10 rounded-full text-lg shadow-glow hover:shadow-glow-lg transform hover:scale-105 transition-all duration-300">
                        Get a Free Consultation
                    </a>
                    <a href="services.php" class="border-2 border-slate-400 hover:border-primary text-white font-bold py-4 px-10 rounded-full text-lg transition-all duration-300 hover:bg-primary/10 backdrop-blur-sm transform hover:scale-105">
                        View Our Services
                    </a>
                </div>
            </div>
            <div class="hidden lg:flex w-1/3 justify-end animate-float">
                <div class="bg-white/10 backdrop-blur-md p-8 rounded-3xl border border-white/20 text-center shadow-elevated">
                    <div class="text-primary text-5xl font-bold mb-2 animate-bounce-subtle">100%</div>
                    <div class="text-slate-100 font-semibold uppercase tracking-widest text-sm">Discretion Guaranteed</div>
                    <hr class="my-4 border-white/20">
                    <div class="text-slate-400 text-xs italic">POPIA & PSIRA Compliant Operations</div>
                </div>
            </div>
        </div>
    </div>
    <div class="absolute inset-0 opacity-5 pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
    <div class="absolute -top-24 -right-24 w-128 h-128 bg-primary/10 rounded-full blur-[120px] animate-pulse-slow" aria-hidden="true"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-primary/5 rounded-full blur-[100px] animate-float" aria-hidden="true"></div>
</section>

<section class="bg-white py-24">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-primary font-bold tracking-widest uppercase text-sm">Elite Standards</h2>
            <p class="mt-2 text-4xl font-extrabold text-slate-900 sm:text-5xl">Unmatched Investigative Integrity</p>
            <div class="h-1 w-20 bg-primary mx-auto mt-6"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
            <?php
            $advantages = [
                ['icon' => 'fa-shield-check', 'title' => 'Ironclad Secrecy', 'desc' => 'We utilize encrypted communications to ensure your case remains strictly between us.'],
                ['icon' => 'fa-gavel', 'title' => 'Court-Ready Proof', 'desc' => 'Every piece of evidence is gathered under strict chain-of-custody protocols for legal admissibility.'],
                ['icon' => 'fa-file-signature', 'title' => 'POPIA Compliant', 'desc' => 'Expert navigation of South African privacy laws to protect you from legal blowback.'],
                ['icon' => 'fa-microscope', 'title' => 'Forensic Precision', 'desc' => 'From digital footprints to physical surveillance, we leave no stone unturned.']
            ];
            foreach ($advantages as $adv): ?>
            <div class="group p-8 rounded-3xl border border-slate-100 bg-slate-50 hover:bg-white hover:shadow-2xl hover:border-primary/20 transition-all duration-300">
                <div class="w-14 h-14 bg-white text-primary rounded-2xl shadow-sm flex items-center justify-center mb-6 group-hover:bg-primary group-hover:text-white transition-colors">
                    <i class="fas <?= htmlspecialchars($adv['icon']) ?> fa-xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3"><?= htmlspecialchars($adv['title']) ?></h3>
                <p class="text-slate-600 leading-relaxed text-sm"><?= htmlspecialchars($adv['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Service Preview Section -->
<section class="py-24 bg-slate-50">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">Comprehensive Investigation Services</h2>
            <p class="text-slate-600 text-lg">From corporate intelligence to personal matters, we deliver results that matter.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <?php
            $services = [
                [
                    'icon' => 'fa-briefcase',
                    'title' => 'Corporate Investigations',
                    'desc' => 'Fraud detection, due diligence, employee investigations, and competitive intelligence.',
                    'features' => ['Financial forensics', 'Background checks', 'Asset tracing']
                ],
                [
                    'icon' => 'fa-home',
                    'title' => 'Domestic & Family Cases',
                    'desc' => 'Custody disputes, infidelity investigations, asset concealment, and child welfare.',
                    'features' => ['Surveillance', 'Witness interviews', 'Court reports']
                ],
                [
                    'icon' => 'fa-user-secret',
                    'title' => 'Personal Security',
                    'desc' => 'Threat assessments, stalker investigations, and personal protection intelligence.',
                    'features' => ['Risk assessment', 'Security consulting', 'Emergency response']
                ],
                [
                    'icon' => 'fa-gavel',
                    'title' => 'Legal Support Services',
                    'desc' => 'Evidence gathering for litigation, witness location, and insurance fraud detection.',
                    'features' => ['Evidence collection', 'Witness location', 'Expert testimony']
                ],
                [
                    'icon' => 'fa-search-dollar',
                    'title' => 'Financial Investigations',
                    'desc' => 'Asset tracing, money laundering detection, and financial due diligence.',
                    'features' => ['Asset tracing', 'Financial analysis', 'Regulatory compliance']
                ],
                [
                    'icon' => 'fa-shield-alt',
                    'title' => 'Insurance Investigations',
                    'desc' => 'Claims verification, arson investigations, and accident reconstruction.',
                    'features' => ['Claims verification', 'Fraud detection', 'Expert reports']
                ]
            ];
            foreach ($services as $index => $service): ?>
            <div class="card-premium p-8 rounded-2xl shadow-card hover:shadow-card-hover transition-all duration-500 group animate-fade-in-up" style="animation-delay: <?= $index * 0.1 ?>s">
                <div class="w-16 h-16 bg-gradient-primary text-white rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-glow">
                    <i class="fas <?= htmlspecialchars($service['icon']) ?> fa-xl"></i>
                </div>
                <h4 class="text-xl font-bold text-secondary mb-3 group-hover:text-primary transition-colors duration-300"><?= htmlspecialchars($service['title']) ?></h4>
                <p class="text-slate-600 text-sm leading-relaxed mb-4"><?= htmlspecialchars($service['desc']) ?></p>
                <ul class="text-xs text-slate-500 space-y-2 mb-6">
                    <?php foreach ($service['features'] as $feature): ?>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-primary text-xs animate-scale-in"></i>
                            <?= htmlspecialchars($feature) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="services.php" class="inline-flex items-center gap-2 text-primary hover:text-orange-600 transition-all duration-300 text-sm font-semibold group-hover:translate-x-2 transform">
                    Learn More <i class="fas fa-arrow-right text-xs transition-transform duration-300 group-hover:translate-x-1"></i>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center">
            <a href="services.php" class="btn-primary inline-block py-4 px-10 rounded-full text-lg shadow-glow hover:shadow-glow-lg transform hover:scale-105 transition-all duration-300 animate-bounce-subtle">
                View All Services
            </a>
        </div>
    </div>
</section>

<section class="py-24 bg-secondary text-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-20">
            <h2 class="text-4xl font-bold mb-4">The Pathway to Clarity</h2>
            <p class="text-slate-400">Our structured approach ensures efficiency and total transparency.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-0 relative">
            <div class="hidden md:block absolute top-12 left-0 w-full h-0.5 bg-white/10 z-0"></div>
            
            <?php 
            $steps = [
                'Case Briefing' => 'Initial high-level discussion of the facts.',
                'Tactical Strategy' => 'Customized roadmap and scope approval.',
                'Field Intelligence' => 'Active surveillance and data recovery.',
                'Final Resolution' => 'The delivery of the comprehensive report.'
            ];
            $i = 1;
            foreach ($steps as $title => $desc): ?>
            <div class="relative z-10 p-6 text-center">
                <div class="w-24 h-24 rounded-full bg-secondary border-4 border-primary text-primary flex items-center justify-center mx-auto text-3xl font-black mb-6">
                    0<?= $i++ ?>
                </div>
                <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($title) ?></h3>
                <p class="text-slate-400 text-sm"><?= htmlspecialchars($desc) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Trust Indicators & Statistics -->
<section class="py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">Trusted by South African Businesses</h2>
            <p class="text-slate-600 text-lg">Join hundreds of satisfied clients who trust us with their most sensitive investigations.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-16">
            <?php
            $stats = [
                ['number' => '15+', 'label' => 'Years Experience', 'icon' => 'fa-calendar-alt'],
                ['number' => '500+', 'label' => 'Cases Resolved', 'icon' => 'fa-check-circle'],
                ['number' => '98%', 'label' => 'Client Satisfaction', 'icon' => 'fa-star'],
                ['number' => '100%', 'label' => 'POPIA Compliant', 'icon' => 'fa-shield-alt']
            ];
            foreach ($stats as $stat): ?>
            <div class="text-center">
                <div class="w-16 h-16 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas <?= htmlspecialchars($stat['icon']) ?> fa-2x"></i>
                </div>
                <div class="text-3xl font-black text-secondary mb-1"><?= htmlspecialchars($stat['number']) ?></div>
                <div class="text-sm text-slate-500 uppercase tracking-widest font-bold"><?= htmlspecialchars($stat['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Certifications -->
        <div class="bg-slate-50 rounded-3xl p-8 md:p-12">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold text-secondary mb-4">Professional Certifications & Compliance</h3>
                <p class="text-slate-600">Licensed, certified, and committed to the highest professional standards in South Africa.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php
                $certifications = [
                    ['name' => 'PSIRA Grade A', 'desc' => 'Licensed Private Investigators'],
                    ['name' => 'POPIA Compliant', 'desc' => 'Data Protection Certified'],
                    ['name' => 'Court Approved', 'desc' => 'Evidence Admissible'],
                    ['name' => 'ISO Certified', 'desc' => 'Quality Management']
                ];
                foreach ($certifications as $cert): ?>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 text-center hover:border-primary/30 transition-colors">
                    <div class="w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-certificate fa-lg"></i>
                    </div>
                    <h4 class="font-bold text-secondary mb-2"><?= htmlspecialchars($cert['name']) ?></h4>
                    <p class="text-sm text-slate-600"><?= htmlspecialchars($cert['desc']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Client Testimonials -->
<section class="py-24 bg-slate-50">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">What Our Clients Say</h2>
            <p class="text-slate-600 text-lg">Real feedback from clients across South Africa.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php
            if (!empty($approved_reviews)):
                foreach ($approved_reviews as $index => $review): ?>
                <div class="card-premium p-8 rounded-3xl shadow-card hover:shadow-card-hover transition-all duration-500 animate-fade-in-up" style="animation-delay: <?= $index * 0.2 ?>s">
                    <div class="flex items-center mb-6">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star text-yellow-400 text-sm mr-1 animate-pulse-slow" style="animation-delay: <?= $i * 0.1 ?>s"></i>
                        <?php endfor; ?>
                    </div>
                    <blockquote class="text-slate-600 italic mb-6 leading-relaxed text-shadow group-hover:text-slate-700 transition-colors">
                        "<?= htmlspecialchars(substr($review['review_text'], 0, 200)) . (strlen($review['review_text']) > 200 ? '..."' : '"'); ?>
                    </blockquote>
                    <div class="border-t border-slate-200 pt-4">
                        <div class="font-bold text-secondary text-lg mb-1 group-hover:text-primary transition-colors"><?= htmlspecialchars($review['client_name']); ?></div>
                        <div class="text-sm text-slate-500 font-medium">
                            <?php if ($review['service_type']): ?>
                                <?= htmlspecialchars($review['service_type']); ?> -
                            <?php endif; ?>
                            <?= date('M j, Y', strtotime($review['created_at'])); ?>
                        </div>
                    </div>
                    <div class="absolute top-4 right-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="fas fa-quote-right text-4xl text-primary"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else:
                // Fallback to static testimonials if no approved reviews
                $testimonials = [
                    [
                        'quote' => 'ISS Investigations provided the evidence we needed to recover millions in fraudulent transactions. Their professionalism and discretion were exceptional.',
                        'author' => 'Corporate CEO',
                        'company' => 'Johannesburg Financial Services',
                        'rating' => 5
                    ],
                    [
                        'quote' => 'During our divorce proceedings, their asset investigation revealed hidden accounts that completely changed the settlement. Highly recommend.',
                        'author' => 'Legal Client',
                        'company' => 'Pretoria Law Firm',
                        'rating' => 5
                    ],
                    [
                        'quote' => 'Their insurance fraud investigation saved us significant costs. The evidence was court-ready and led to a successful prosecution.',
                        'author' => 'Claims Manager',
                        'company' => 'Durban Insurance Company',
                        'rating' => 5
                    ]
                ];
                foreach ($testimonials as $index => $testimonial): ?>
                <div class="card-premium p-8 rounded-3xl shadow-card hover:shadow-card-hover transition-all duration-500 animate-fade-in-up group" style="animation-delay: <?= $index * 0.2 ?>s">
                    <div class="flex items-center mb-6">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star text-yellow-400 text-sm mr-1 animate-pulse-slow" style="animation-delay: <?= $i * 0.1 ?>s"></i>
                        <?php endfor; ?>
                    </div>
                    <blockquote class="text-slate-600 italic mb-6 leading-relaxed text-shadow group-hover:text-slate-700 transition-colors">
                        "<?= htmlspecialchars($testimonial['quote']) ?>"
                    </blockquote>
                    <div class="border-t border-slate-200 pt-4">
                        <div class="font-bold text-secondary text-lg mb-1 group-hover:text-primary transition-colors"><?= htmlspecialchars($testimonial['author']) ?></div>
                        <div class="text-sm text-slate-500 font-medium"><?= htmlspecialchars($testimonial['company']) ?></div>
                    </div>
                    <div class="absolute top-4 right-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i class="fas fa-quote-right text-4xl text-primary"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-12">
            <p class="text-slate-600 mb-6 text-lg font-medium">Ready to experience the ISS difference?</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="contact.php" class="btn-primary inline-block py-4 px-10 rounded-full text-lg shadow-glow hover:shadow-glow-lg transform hover:scale-105 transition-all duration-300 animate-bounce-subtle">
                    Start Your Consultation
                </a>
                <a href="submit_review.php" class="inline-flex items-center gap-2 text-primary hover:text-orange-600 transition-colors text-sm font-semibold">
                    <i class="fas fa-star mr-1"></i> Share Your Experience
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Emergency Services Callout -->
<section class="py-16 bg-gradient-secondary relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/dark-mosaic.png')] opacity-5"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto text-center">
            <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-8 md:p-12 border border-white/20 shadow-elevated animate-fade-in-up">
                <div class="flex items-center justify-center mb-6">
                    <div class="w-20 h-20 bg-gradient-accent text-white rounded-3xl flex items-center justify-center shadow-glow animate-bounce-subtle">
                        <i class="fas fa-exclamation-triangle fa-2xl"></i>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-white mb-6 text-shadow-lg">Emergency Investigations Available 24/7</h3>
                <p class="text-slate-300 mb-10 leading-relaxed text-lg text-shadow">
                    Time-sensitive situations require immediate response. Our emergency investigation services provide rapid deployment
                    within 24 hours anywhere in South Africa for urgent cases including missing persons, immediate threats,
                    and time-critical evidence gathering.
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <a href="tel:+27653087750" class="btn-primary text-white font-bold inline-flex items-center gap-3 py-4 px-8 rounded-full transition-all transform hover:scale-105 shadow-glow hover:shadow-glow-lg">
                        <i class="fas fa-phone-alt"></i>
                        Emergency Hotline
                    </a>
                    <a href="contact.php" class="inline-flex items-center gap-3 bg-white/20 hover:bg-white/30 text-white font-bold py-4 px-8 rounded-full transition-all backdrop-blur-sm border border-white/30 transform hover:scale-105">
                        <i class="fas fa-envelope"></i>
                        Emergency Contact
                    </a>
                </div>
                <div class="mt-8 text-center">
                    <p class="text-slate-400 text-sm">Response guaranteed within 24 hours nationwide</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="latest-insights" class="py-24 bg-slate-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-end mb-12">
            <div class="text-left">
                <h2 class="text-primary font-bold uppercase tracking-widest text-sm">Intelligence Journal</h2>
                <p class="text-4xl font-extrabold text-slate-900 mt-2">Latest Insights & Case Studies</p>
                <p class="text-slate-600 mt-2 max-w-md">Stay informed with our expert analysis, investigation techniques, and industry updates.</p>
            </div>
            <a href="blog.php" class="bg-primary hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-lg transition-all hover:-translate-y-0.5 shadow-lg shadow-primary/20 flex items-center">
                <i class="fas fa-newspaper mr-2"></i> Browse All Articles
            </a>
        </div>
        
        <div class="grid gap-10 lg:grid-cols-3 max-w-lg mx-auto lg:max-w-none">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $index => $post): ?>
                <article class="card-premium group flex flex-col rounded-3xl overflow-hidden shadow-card hover:shadow-card-hover transition-all duration-500 animate-fade-in-up" style="animation-delay: <?= $index * 0.15 ?>s">
                    <div class="h-48 bg-gradient-to-br from-slate-200 to-slate-300 overflow-hidden relative">
                        <?php if (!empty($post['featured_image'])): ?>
                            <img src="<?= htmlspecialchars($post['featured_image']); ?>" alt="<?= htmlspecialchars($post['title']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        <?php else: ?>
                            <img src="https://images.unsplash.com/photo-1453723122520-6478290e4ecb?auto=format&fit=crop&w=600&q=80" alt="ISS Insight" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <div class="flex-1 p-8 flex flex-col justify-between relative">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs font-bold text-primary tracking-widest uppercase bg-primary/10 px-3 py-1 rounded-full">
                                    <?= !empty($post['category']) ? htmlspecialchars($post['category']) : 'Investigation Case Study'; ?>
                                </span>
                                <div class="flex items-center gap-3">
                                    <?php if (strtotime($post['created_at']) > strtotime('-7 days')): ?>
                                        <span class="px-2 py-1 bg-gradient-accent text-white text-xs font-bold rounded-full animate-pulse-slow">NEW</span>
                                    <?php endif; ?>
                                    <span class="text-xs text-slate-400 flex items-center bg-slate-100 px-2 py-1 rounded-full">
                                        <i class="fas fa-eye mr-1"></i>
                                        <?= number_format($post['view_count'] ?? 0); ?>
                                    </span>
                                </div>
                            </div>
                            <a href="view_post.php?id=<?= $post['post_id']; ?>" class="block group-hover:text-primary transition-colors duration-300">
                                <h3 class="text-2xl font-bold text-slate-900 leading-tight mb-3">
                                    <?= htmlspecialchars($post['title']); ?>
                                </h3>
                                <p class="text-slate-600 leading-relaxed italic text-shadow">
                                    "<?= htmlspecialchars(getSnippet($post['content'])); ?>"
                                </p>
                            </a>
                        </div>
                        <div class="mt-8 pt-6 border-t border-slate-200 flex justify-between items-center">
                            <span class="text-slate-400 text-xs uppercase font-semibold flex items-center">
                                <i class="far fa-calendar-alt mr-2 text-primary"></i><?= date('M d, Y', strtotime($post['created_at'])) ?>
                            </span>
                            <a href="view_post.php?id=<?= $post['post_id']; ?>" class="w-12 h-12 rounded-full bg-gradient-primary text-white flex items-center justify-center hover:scale-110 transition-all duration-300 shadow-glow hover:shadow-glow">
                                <i class="fas fa-arrow-right text-sm"></i>
                            </a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="lg:col-span-3 bg-white p-12 rounded-3xl text-center border border-dashed border-slate-300">
                    <p class="text-slate-500 italic">We are currently drafting new insights. Check back soon for updates.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include_once 'includes/public_footer.php'; ?>
