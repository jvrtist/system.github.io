<?php
/**
 * ISS Investigations - Premium About Page
 * Matches the high-impact styling and interactive depth of index.php
 */
$page_title = "About ISS Investigations | 15+ Years Private Detective Gauteng";
include_once 'includes/public_header.php';
?>

<section class="relative bg-slate-900 pt-32 pb-24 overflow-hidden border-b-4 border-primary">
    <div class="container mx-auto px-4 relative z-10 text-center">
        <span class="inline-block py-1 px-4 rounded-full bg-primary/20 text-primary text-xs font-black tracking-widest uppercase mb-6">Established & Licensed</span>
        <h1 class="text-5xl md:text-7xl font-black text-white leading-tight">
            The Intersection of <br>
            <span class="text-primary text-transparent bg-clip-text bg-gradient-to-r from-primary to-orange-500">Truth & Technology.</span>
        </h1>
        <p class="mt-8 max-w-2xl mx-auto text-lg md:text-xl text-slate-400 leading-relaxed font-light">
            ISS Investigations is more than a surveillance firm. We are a boutique intelligence agency providing the clarity needed to navigate South Africa's most complex challenges.
        </p>
    </div>
    <div class="absolute inset-0 opacity-10 pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary/10 rounded-full blur-[100px]" aria-hidden="true"></div>
</section>

<section class="bg-white py-12 border-b border-slate-100">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
            <div class="p-4">
                <span class="block text-4xl font-black text-secondary">15+</span>
                <span class="text-xs uppercase tracking-widest font-bold text-slate-400">Years Experience</span>
            </div>
            <div class="p-4">
                <span class="block text-4xl font-black text-secondary">100%</span>
                <span class="text-xs uppercase tracking-widest font-bold text-slate-400">POPIA Compliant</span>
            </div>
            <div class="p-4">
                <span class="block text-4xl font-black text-secondary">500+</span>
                <span class="text-xs uppercase tracking-widest font-bold text-slate-400">Cases Resolved</span>
            </div>
            <div class="p-4">
                <span class="block text-4xl font-black text-secondary">PSIRA</span>
                <span class="text-xs uppercase tracking-widest font-bold text-slate-400">Registered Firm</span>
            </div>
        </div>
    </div>
</section>

<section class="py-24 bg-white overflow-hidden">
    <div class="container mx-auto px-4">
        <div class="flex flex-col lg:flex-row items-center gap-16">
            <div class="w-full lg:w-1/2 relative group">
                <div class="relative z-10 overflow-hidden rounded-3xl shadow-2xl">
                    <img src="https://images.unsplash.com/photo-1553877522-43269d4ea984?auto=format&fit=crop&w=800&q=80" alt="Investigative Strategy" class="w-full h-auto transform group-hover:scale-105 transition-transform duration-700">
                </div>
                <div class="absolute -bottom-8 -right-8 w-48 h-48 bg-primary/20 rounded-full blur-3xl -z-0"></div>
                <div class="absolute -top-8 -left-8 w-48 h-48 bg-secondary/10 rounded-full blur-3xl -z-0"></div>
            </div>
            <div class="w-full lg:w-1/2">
                <h2 class="text-primary font-bold tracking-widest uppercase text-sm mb-4">Our Narrative</h2>
                <h3 class="text-4xl font-black text-secondary mb-6 leading-tight">Bridging the Gap Between <br>Suspicion and Proof.</h3>
                <div class="space-y-4 text-slate-600 leading-relaxed">
                    <p>
                        Founded by veterans of high-stakes law enforcement and corporate risk management, ISS Investigations addresses the critical need for <strong>legally admissible intelligence</strong> in South Africa.
                    </p>
                    <p>
                        We understand that our clients come to us during moments of high stress. Our role is to provide a calm, calculated, and professional service that prioritizes your peace of mind above all else.
                    </p>
                    <p>
                        Operating from our secure Gauteng headquarters, we serve clients across South Africa, from corporate boardrooms in Sandton to family matters in Pretoria, delivering the same level of professionalism and discretion regardless of case complexity or sensitivity.
                    </p>
                </div>
                <div class="mt-10 flex items-center gap-6">
                    <img src="https://via.placeholder.com/60" alt="Signature" class="opacity-40 grayscale">
                    <div>
                        <p class="text-secondary font-bold italic">The ISS Leadership Team</p>
                        <p class="text-xs text-slate-400 uppercase tracking-widest">Gauteng Headquarters</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Detailed Services Section -->
<section class="py-24 bg-slate-50">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">Comprehensive Investigative Services</h2>
            <p class="text-slate-600 text-lg">From corporate intelligence to personal matters, we provide specialized investigative solutions.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            $services = [
                [
                    'icon' => 'fa-briefcase',
                    'title' => 'Corporate Investigations',
                    'desc' => 'Due diligence, fraud detection, employee misconduct, intellectual property protection, and competitive intelligence gathering.',
                    'features' => ['Financial forensics', 'Background checks', 'Asset tracing', 'Due diligence']
                ],
                [
                    'icon' => 'fa-home',
                    'title' => 'Domestic & Family Cases',
                    'desc' => 'Custody disputes, infidelity investigations, asset concealment, child welfare concerns, and matrimonial investigations.',
                    'features' => ['Surveillance', 'Asset investigation', 'Witness interviews', 'Court reports']
                ],
                [
                    'icon' => 'fa-user-secret',
                    'title' => 'Personal Security',
                    'desc' => 'Threat assessments, stalker investigations, harassment cases, and personal protection intelligence.',
                    'features' => ['Risk assessment', 'Threat analysis', 'Security consulting', 'Emergency response']
                ],
                [
                    'icon' => 'fa-gavel',
                    'title' => 'Legal Support Services',
                    'desc' => 'Evidence gathering for litigation, witness location and preparation, accident investigations, and insurance fraud detection.',
                    'features' => ['Evidence collection', 'Witness location', 'Accident reconstruction', 'Expert testimony']
                ],
                [
                    'icon' => 'fa-search-dollar',
                    'title' => 'Financial Investigations',
                    'desc' => 'Asset tracing, money laundering detection, fraud investigations, and financial due diligence for transactions.',
                    'features' => ['Asset tracing', 'Financial analysis', 'Fraud detection', 'Regulatory compliance']
                ],
                [
                    'icon' => 'fa-shield-alt',
                    'title' => 'Insurance Investigations',
                    'desc' => 'Claims verification, arson investigations, workers compensation fraud, and accident reconstruction.',
                    'features' => ['Claims verification', 'Fraud detection', 'Accident analysis', 'Expert reports']
                ]
            ];
            foreach ($services as $index => $service): ?>
            <div class="card-premium p-8 rounded-2xl shadow-card hover:shadow-card-hover transition-all duration-500 group animate-fade-in-up" style="animation-delay: <?= $index * 0.1 ?>s">
                <div class="w-16 h-16 bg-gradient-primary text-white rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-glow">
                    <i class="fas <?= htmlspecialchars($service['icon']) ?> fa-xl"></i>
                </div>
                <h4 class="text-xl font-bold text-secondary mb-3 group-hover:text-primary transition-colors duration-300"><?= htmlspecialchars($service['title']) ?></h4>
                <p class="text-slate-600 text-sm leading-relaxed mb-4"><?= htmlspecialchars($service['desc']) ?></p>
                <ul class="text-xs text-slate-500 space-y-2">
                    <?php foreach ($service['features'] as $feature): ?>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-primary text-xs animate-scale-in"></i>
                            <?= htmlspecialchars($feature) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Team Expertise Section -->
<section class="py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">Expertise That Commands Respect</h2>
            <p class="text-slate-600 text-lg">Our investigators bring decades of combined experience from law enforcement, military intelligence, and private sector investigations.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
            <?php
            $expertise = [
                ['icon' => 'fa-graduation-cap', 'title' => 'Advanced Training', 'desc' => 'Continuous professional development in modern investigative techniques, digital forensics, and legal procedures.'],
                ['icon' => 'fa-certificate', 'title' => 'Professional Certifications', 'desc' => 'PSIRA Grade A investigators with specialized certifications in fraud examination and corporate investigations.'],
                ['icon' => 'fa-microscope', 'title' => 'Forensic Expertise', 'desc' => 'Advanced forensic analysis capabilities including digital evidence recovery and chain of custody procedures.'],
                ['icon' => 'fa-globe', 'title' => 'International Networks', 'desc' => 'Established relationships with investigative agencies worldwide for cross-border intelligence gathering.']
            ];
            foreach ($expertise as $index => $exp): ?>
            <div class="text-center group animate-fade-in-up" style="animation-delay: <?= $index * 0.15 ?>s">
                <div class="w-20 h-20 bg-gradient-primary text-white rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300 shadow-glow hover:shadow-glow">
                    <i class="fas <?= htmlspecialchars($exp['icon']) ?> fa-2x"></i>
                </div>
                <h4 class="text-lg font-bold text-secondary mb-2 group-hover:text-primary transition-colors duration-300"><?= htmlspecialchars($exp['title']) ?></h4>
                <p class="text-slate-600 text-sm leading-relaxed"><?= htmlspecialchars($exp['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Leadership Profiles -->
        <div class="bg-slate-50 rounded-3xl p-8 md:p-12">
            <h3 class="text-2xl font-bold text-secondary text-center mb-8">Leadership & Senior Investigators</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php
                $leaders = [
                    [
                        'name' => 'Director of Investigations',
                        'credentials' => 'Former SAPS Detective Colonel • 20+ Years Experience • Fraud Investigation Specialist',
                        'focus' => 'Corporate fraud and financial crimes'
                    ],
                    [
                        'name' => 'Senior Surveillance Specialist',
                        'credentials' => 'Military Intelligence Veteran • PSIRA Grade A • Advanced Digital Forensics Certification',
                        'focus' => 'High-tech surveillance and cyber investigations'
                    ],
                    [
                        'name' => 'Legal Intelligence Coordinator',
                        'credentials' => 'LLB Degree • Admitted Attorney • Forensic Accounting Qualification',
                        'focus' => 'Legal support and compliance investigations'
                    ]
                ];
                foreach ($leaders as $leader): ?>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <div class="w-12 h-12 bg-primary/20 text-primary rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h4 class="font-bold text-secondary mb-2"><?= htmlspecialchars($leader['name']) ?></h4>
                    <p class="text-xs text-slate-600 mb-3 leading-relaxed"><?= htmlspecialchars($leader['credentials']) ?></p>
                    <p class="text-sm text-primary font-medium">Focus: <?= htmlspecialchars($leader['focus']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Methodology Section -->
<section class="py-24 bg-secondary">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-white sm:text-4xl mb-4">Our Investigative Methodology</h2>
            <p class="text-slate-300 text-lg">A systematic, evidence-based approach that ensures reliable results and court admissibility.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="space-y-8">
                <?php
                $methodology = [
                    [
                        'step' => '01',
                        'title' => 'Strategic Assessment',
                        'desc' => 'Initial confidential consultation to understand your specific needs, objectives, and constraints.'
                    ],
                    [
                        'step' => '02',
                        'title' => 'Intelligence Planning',
                        'desc' => 'Development of a customized investigative strategy with clear milestones and deliverables.'
                    ],
                    [
                        'step' => '03',
                        'title' => 'Evidence Collection',
                        'desc' => 'Systematic gathering of information using legal and ethical methods with full documentation.'
                    ],
                    [
                        'step' => '04',
                        'title' => 'Analysis & Synthesis',
                        'desc' => 'Thorough analysis of all collected data to identify patterns, connections, and actionable insights.'
                    ],
                    [
                        'step' => '05',
                        'title' => 'Reporting & Presentation',
                        'desc' => 'Comprehensive reports with executive summaries, detailed findings, and recommendations.'
                    ]
                ];
                foreach ($methodology as $step): ?>
                <div class="flex items-start gap-6">
                    <div class="flex-shrink-0 w-12 h-12 bg-primary text-white rounded-xl flex items-center justify-center font-bold text-sm">
                        <?= htmlspecialchars($step['step']) ?>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-white mb-2"><?= htmlspecialchars($step['title']) ?></h4>
                        <p class="text-slate-300 leading-relaxed"><?= htmlspecialchars($step['desc']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="bg-white/10 backdrop-blur-sm rounded-3xl p-8 border border-white/20">
                <h3 class="text-2xl font-bold text-white mb-6">Quality Assurance</h3>
                <div class="space-y-4">
                    <?php
                    $qa_points = [
                        'All evidence collected with proper chain of custody procedures',
                        'Digital timestamps and GPS coordinates on all surveillance',
                        'Regular progress reports and client updates',
                        'Independent peer review of all final reports',
                        'Court-ready documentation and presentation',
                        'Client confidentiality maintained throughout'
                    ];
                    foreach ($qa_points as $point): ?>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle text-primary text-sm"></i>
                        <span class="text-slate-300 text-sm"><?= htmlspecialchars($point) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Technology & Tools Section -->
<section class="py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">Technology-Driven Investigations</h2>
            <p class="text-slate-600 text-lg">We leverage cutting-edge technology to enhance traditional investigative methods.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php
            $technologies = [
                [
                    'icon' => 'fa-video-camera',
                    'title' => 'Advanced Surveillance',
                    'desc' => 'HD cameras, night vision, drone technology, and real-time streaming capabilities.'
                ],
                [
                    'icon' => 'fa-database',
                    'title' => 'Digital Forensics',
                    'desc' => 'Data recovery, email analysis, social media investigation, and cyber evidence collection.'
                ],
                [
                    'icon' => 'fa-search',
                    'title' => 'OSINT Tools',
                    'desc' => 'Open source intelligence gathering with proprietary research databases and tools.'
                ],
                [
                    'icon' => 'fa-shield-alt',
                    'title' => 'Secure Communications',
                    'desc' => 'End-to-end encrypted reporting systems and secure client communication platforms.'
                ]
            ];
            foreach ($technologies as $tech): ?>
            <div class="text-center group">
                <div class="w-20 h-20 bg-slate-50 text-primary rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-primary group-hover:text-white transition-all border border-slate-100">
                    <i class="fas <?= htmlspecialchars($tech['icon']) ?> fa-2x"></i>
                </div>
                <h4 class="text-lg font-bold text-secondary mb-2 group-hover:text-primary transition-colors"><?= htmlspecialchars($tech['title']) ?></h4>
                <p class="text-slate-600 text-sm leading-relaxed"><?= htmlspecialchars($tech['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Geographic Coverage & Industry Specializations -->
<section class="py-24 bg-slate-50">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">

            <!-- Geographic Coverage -->
            <div>
                <h2 class="text-3xl font-extrabold text-secondary mb-8">Geographic Coverage</h2>
                <div class="space-y-6">
                    <div>
                        <h4 class="text-xl font-bold text-secondary mb-3">Primary Service Areas</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <?php
                            $areas = ['Gauteng', 'Mpumalanga', 'Limpopo', 'North West', 'Free State', 'KwaZulu-Natal'];
                            foreach ($areas as $area): ?>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-map-marker-alt text-primary"></i>
                                <span class="text-slate-600"><?= htmlspecialchars($area) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xl font-bold text-secondary mb-3">Extended Coverage</h4>
                        <p class="text-slate-600 leading-relaxed">
                            Through our network of trusted associates, we provide investigative services across South Africa.
                            International investigations coordinated with local partners in neighboring countries.
                        </p>
                    </div>

                    <div>
                        <h4 class="text-xl font-bold text-secondary mb-3">Response Times</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-slate-600">Gauteng</span>
                                <span class="font-bold text-primary">Same day</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Major cities</span>
                                <span class="font-bold text-primary">24-48 hours</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Remote areas</span>
                                <span class="font-bold text-primary">72 hours</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Industry Specializations -->
            <div>
                <h2 class="text-3xl font-extrabold text-secondary mb-8">Industry Specializations</h2>
                <div class="space-y-6">
                    <?php
                    $industries = [
                        'Corporate & Business' => 'Fraud prevention, due diligence, employee investigations, intellectual property protection',
                        'Legal & Insurance' => 'Evidence collection, witness location, accident reconstruction, claims verification',
                        'Family Law' => 'Custody disputes, asset investigations, infidelity cases, child welfare matters',
                        'Financial Services' => 'Money laundering detection, asset tracing, regulatory compliance investigations',
                        'Real Estate' => 'Title fraud, property disputes, tenant screening, development due diligence',
                        'Healthcare' => 'Medical malpractice, insurance fraud, regulatory compliance investigations'
                    ];
                    foreach ($industries as $industry => $services): ?>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                        <h4 class="font-bold text-secondary mb-2"><?= htmlspecialchars($industry) ?></h4>
                        <p class="text-slate-600 text-sm leading-relaxed"><?= htmlspecialchars($services) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Certifications & Partnerships -->
<section class="py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">Credibility & Compliance</h2>
            <p class="text-slate-600 text-lg">Licensed, certified, and committed to the highest professional standards.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-16">
            <?php
            $certifications = [
                ['icon' => 'fa-certificate', 'title' => 'PSIRA Grade A', 'desc' => 'Licensed Private Investigators'],
                ['icon' => 'fa-shield-alt', 'title' => 'POPIA Compliant', 'desc' => 'Data Protection Act Certified'],
                ['icon' => 'fa-gavel', 'title' => 'Court Approved', 'desc' => 'Evidence Admissible Nationwide'],
                ['icon' => 'fa-lock', 'title' => 'ISO 27001', 'desc' => 'Information Security Certified']
            ];
            foreach ($certifications as $cert): ?>
            <div class="text-center">
                <div class="w-16 h-16 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas <?= htmlspecialchars($cert['icon']) ?> fa-2x"></i>
                </div>
                <h4 class="font-bold text-secondary mb-1"><?= htmlspecialchars($cert['title']) ?></h4>
                <p class="text-slate-600 text-xs leading-relaxed"><?= htmlspecialchars($cert['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Professional Partnerships -->
        <div class="bg-slate-50 rounded-3xl p-8 md:p-12">
            <h3 class="text-2xl font-bold text-secondary text-center mb-8">Professional Partnerships</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php
                $partnerships = [
                    [
                        'category' => 'Legal',
                        'partners' => 'South African Bar Association, Legal Practice Council, Association of Certified Fraud Examiners',
                        'services' => 'Court admissibility, legal consultation, expert witness testimony'
                    ],
                    [
                        'category' => 'Law Enforcement',
                        'partners' => 'SAPS, Hawks, Directorate for Priority Crime Investigation, Commercial Crime Units',
                        'services' => 'Information sharing, joint investigations, intelligence coordination'
                    ],
                    [
                        'category' => 'Corporate',
                        'partners' => 'SAICA, IRBA, Banking Association of South Africa, Corporate Forensic Investigation bodies',
                        'services' => 'Financial investigations, regulatory compliance, risk assessment'
                    ]
                ];
                foreach ($partnerships as $partnership): ?>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <h4 class="font-bold text-secondary mb-3 text-center"><?= htmlspecialchars($partnership['category']) ?> Partnerships</h4>
                    <p class="text-xs text-slate-600 mb-3 font-medium">Collaborating with:</p>
                    <p class="text-sm text-slate-600 mb-4 leading-relaxed"><?= htmlspecialchars($partnership['partners']) ?></p>
                    <p class="text-xs text-primary font-medium">Services: <?= htmlspecialchars($partnership['services']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Success Stories (Anonymized) -->
<section class="py-24 bg-secondary">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-white sm:text-4xl mb-4">Success Stories</h2>
            <p class="text-slate-300 text-lg">Real results for our clients (identities protected for confidentiality).</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php
            $success_stories = [
                [
                    'industry' => 'Corporate Fraud',
                    'challenge' => 'Multi-million rand embezzlement scheme',
                    'solution' => 'Undercover investigation revealed internal theft network',
                    'result' => 'Full recovery of funds, criminal prosecution, strengthened internal controls'
                ],
                [
                    'industry' => 'Insurance Claims',
                    'challenge' => 'Suspicious arson claim on industrial property',
                    'solution' => 'Digital forensics and witness interviews',
                    'result' => 'Fraud exposed, claim denied, perpetrator prosecuted'
                ],
                [
                    'industry' => 'Family Law',
                    'challenge' => 'Hidden asset discovery in divorce proceedings',
                    'solution' => 'Comprehensive asset tracing and financial analysis',
                    'result' => 'Full asset disclosure, fair settlement, court approval'
                ]
            ];
            foreach ($success_stories as $story): ?>
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                <div class="text-primary font-bold text-sm uppercase tracking-widest mb-3"><?= htmlspecialchars($story['industry']) ?></div>
                <h4 class="text-white font-bold text-lg mb-3">Challenge: <?= htmlspecialchars($story['challenge']) ?></h4>
                <p class="text-slate-300 text-sm mb-4">Solution: <?= htmlspecialchars($story['solution']) ?></p>
                <div class="border-t border-white/20 pt-4">
                    <p class="text-green-400 text-sm font-medium">Result: <?= htmlspecialchars($story['result']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Why Choose ISS Section -->
<section class="py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">Why Choose ISS Investigations?</h2>
            <p class="text-slate-600 text-lg">What sets us apart in the competitive field of private investigations.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div class="space-y-8">
                <?php
                $advantages = [
                    [
                        'icon' => 'fa-clock',
                        'title' => 'Rapid Response',
                        'desc' => 'Same-day response in Gauteng, 24-48 hours nationwide. Emergency investigations available 24/7.'
                    ],
                    [
                        'icon' => 'fa-eye-slash',
                        'title' => 'Absolute Discretion',
                        'desc' => 'Military-grade confidentiality protocols. Your case details never leave our secure systems.'
                    ],
                    [
                        'icon' => 'fa-balance-scale',
                        'title' => 'Court-Ready Evidence',
                        'desc' => 'All evidence collected with proper chain of custody, admissible in South African courts.'
                    ],
                    [
                        'icon' => 'fa-users',
                        'title' => 'Dedicated Teams',
                        'desc' => 'Each case assigned to a dedicated investigator team, not outsourced or subcontracted.'
                    ],
                    [
                        'icon' => 'fa-graduation-cap',
                        'title' => 'Expert Analysis',
                        'desc' => 'Findings interpreted by qualified professionals, not just raw data collection.'
                    ]
                ];
                foreach ($advantages as $adv): ?>
                <div class="flex items-start gap-6">
                    <div class="flex-shrink-0 w-12 h-12 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                        <i class="fas <?= htmlspecialchars($adv['icon']) ?>"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-secondary mb-2"><?= htmlspecialchars($adv['title']) ?></h4>
                        <p class="text-slate-600 leading-relaxed"><?= htmlspecialchars($adv['desc']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="bg-slate-50 rounded-3xl p-8">
                <h3 class="text-2xl font-bold text-secondary mb-6">Client Satisfaction Guarantee</h3>
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Client Retention Rate</span>
                        <span class="font-bold text-primary text-lg">98%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3">
                        <div class="bg-primary h-3 rounded-full" style="width: 98%"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Average Case Resolution</span>
                        <span class="font-bold text-primary text-lg">14 Days</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3">
                        <div class="bg-green-500 h-3 rounded-full" style="width: 85%"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Court Success Rate</span>
                        <span class="font-bold text-primary text-lg">95%</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3">
                        <div class="bg-blue-500 h-3 rounded-full" style="width: 95%"></div>
                    </div>
                </div>

                <div class="mt-8 p-4 bg-primary/5 rounded-xl border border-primary/20">
                    <p class="text-secondary text-sm italic text-center">
                        "If we're not confident in our ability to deliver results, we won't take your case."
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-24 bg-slate-50">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">Frequently Asked Questions</h2>
                <p class="text-slate-600 text-lg">Common questions about our services and process.</p>
            </div>

            <div class="space-y-6">
                <?php
                $faqs = [
                    [
                        'question' => 'How much do your investigative services cost?',
                        'answer' => 'Costs vary based on case complexity, duration, and required resources. We provide detailed quotes after initial consultation. Many investigations start from R15,000 with retainers available for complex cases.'
                    ],
                    [
                        'question' => 'How long do investigations typically take?',
                        'answer' => 'Simple cases may be resolved in days, while complex investigations can take weeks or months. We provide regular progress updates and work efficiently to meet your timeline requirements.'
                    ],
                    [
                        'question' => 'Is all information kept confidential?',
                        'answer' => 'Absolutely. Confidentiality is paramount in our profession. All case details are encrypted, access is restricted, and we never discuss cases outside our secure systems.'
                    ],
                    [
                        'question' => 'Do you provide reports for court?',
                        'answer' => 'Yes, all our investigations are conducted with court admissibility in mind. Our reports include proper chain of custody, timestamps, and can be presented as expert testimony.'
                    ],
                    [
                        'question' => 'Can you work across different provinces?',
                        'answer' => 'Yes, we operate nationwide and have established networks throughout South Africa. Response times may vary by location, but we maintain the same quality standards everywhere.'
                    ],
                    [
                        'question' => 'What happens if you don\'t find evidence?',
                        'answer' => 'We provide comprehensive reports regardless of findings. If no evidence is uncovered, this conclusion is as valuable as a positive finding, potentially saving you time and resources pursuing fruitless avenues.'
                    ]
                ];
                foreach ($faqs as $index => $faq): ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <button class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 transition-colors faq-toggle"
                            onclick="toggleFAQ(<?= $index ?>)">
                        <h4 class="font-bold text-secondary pr-4"><?= htmlspecialchars($faq['question']) ?></h4>
                        <i class="fas fa-chevron-down text-slate-400 transition-transform faq-icon"></i>
                    </button>
                    <div class="px-6 pb-4 faq-content hidden">
                        <p class="text-slate-600 leading-relaxed pt-2 border-t border-slate-100 mt-4">
                            <?= htmlspecialchars($faq['answer']) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="py-24 bg-secondary">
    <div class="container mx-auto px-4 text-center">
        <div class="max-w-4xl mx-auto bg-white/5 border border-white/10 p-12 md:p-20 rounded-[3rem] backdrop-blur-sm">
            <h2 class="text-3xl md:text-5xl font-black text-white mb-8">Ready to Uncover the Facts?</h2>
            <p class="text-slate-300 text-lg mb-12 max-w-2xl mx-auto leading-relaxed">
                Contact our lead investigators for a confidential, no-obligation assessment. We provide the expertise you need to resolve your situation discreetly.
            </p>
            <div class="flex flex-col sm:flex-row gap-6 justify-center">
                <a href="contact.php" class="bg-primary hover:bg-orange-600 text-white font-black py-4 px-12 rounded-full text-lg shadow-xl transition-all transform hover:scale-105">
                    Request a Briefing
                </a>
                <a href="tel:+27000000000" class="flex items-center justify-center gap-3 text-white font-bold hover:text-primary transition-colors">
                    <i class="fas fa-phone-alt text-primary"></i>
                    Speak With Us Now
                </a>
            </div>
        </div>
    </div>
</section>

<script>
// FAQ toggle functionality
function toggleFAQ(index) {
    const content = document.querySelectorAll('.faq-content')[index];
    const icon = document.querySelectorAll('.faq-icon')[index];

    if (content.classList.contains('hidden')) {
        // Close all other FAQs first
        document.querySelectorAll('.faq-content').forEach((el, i) => {
            if (i !== index) {
                el.classList.add('hidden');
                document.querySelectorAll('.faq-icon')[i].classList.remove('rotate-180');
            }
        });

        // Open this FAQ
        content.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        // Close this FAQ
        content.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}
</script>

<?php include_once 'includes/public_footer.php'; ?>
