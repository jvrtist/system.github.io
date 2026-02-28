<?php
/**
 * ISS Investigations - Premium Services Page
 * Features alternating layouts, high-impact imagery, and specialized technical grids.
 */
$page_title = "Professional Investigation Services | Corporate Intelligence, Surveillance Gauteng | ISS Investigations";
include_once 'includes/public_header.php';
?>

<section class="relative bg-slate-900 pt-32 pb-24 overflow-hidden border-b-4 border-primary">
    <div class="container mx-auto px-4 relative z-10 text-center">
        <span class="inline-block py-1 px-4 rounded-full bg-primary/20 text-primary text-xs font-black tracking-widest uppercase mb-6">Expert Solutions</span>
        <h1 class="text-5xl md:text-7xl font-black text-white leading-tight">
            Comprehensive <br>
            <span class="text-primary text-transparent bg-clip-text bg-gradient-to-r from-primary to-orange-500">Investigative Capabilities.</span>
        </h1>
        <p class="mt-8 max-w-2xl mx-auto text-lg md:text-xl text-slate-400 leading-relaxed font-light">
            From high-stakes corporate fraud to sensitive personal matters, we provide the legal evidence and clarity you need to move forward.
        </p>
    </div>
    <div class="absolute inset-0 opacity-10 pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-primary/10 rounded-full blur-[120px]" aria-hidden="true"></div>
</section>

<section class="bg-white py-24">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto space-y-32">

            <div class="flex flex-col lg:flex-row gap-16 items-center">
                <div class="w-full lg:w-1/2 relative group">
                    <div class="relative z-10 overflow-hidden rounded-[2.5rem] shadow-2xl aspect-[4/3]">
                        <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=80" alt="Corporate Investigations" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700">
                    </div>
                    <div class="absolute -top-6 -left-6 text-8 font-black text-slate-100 text-9xl -z-0 select-none">01</div>
                </div>
                <div class="w-full lg:w-1/2">
                    <h2 class="text-primary font-bold tracking-widest uppercase text-sm mb-4">Risk Management</h2>
                    <h3 class="text-4xl font-black text-secondary mb-6 leading-tight">Corporate & Business Intelligence</h3>
                    <p class="text-slate-600 mb-8 leading-relaxed">Protect your organization's integrity and assets with data-driven investigations and field intelligence designed for the South African business landscape.</p>
                    <ul class="grid grid-cols-1 gap-4">
                        <?php 
                        $corp = [
                            'Due Diligence' => 'Verification of partners and high-level vetting.',
                            'Fraud & Theft' => 'Internal financial crimes and asset misappropriation.',
                            'Employee Screening' => 'Mitigating risk through comprehensive background checks.',
                            'Competitive Intel' => 'Ethical gathering of strategic market information.'
                        ];
                        foreach ($corp as $title => $desc): ?>
                        <li class="flex items-start gap-4 p-4 rounded-2xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100">
                            <span class="bg-primary/10 text-primary p-2 rounded-lg mt-1"><i class="fas fa-shield-check"></i></span>
                            <div>
                                <span class="block font-bold text-secondary"><?= htmlspecialchars($title) ?></span>
                                <span class="text-sm text-slate-500"><?= htmlspecialchars($desc) ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row-reverse gap-16 items-center">
                <div class="w-full lg:w-1/2 relative group text-right">
                    <div class="relative z-10 overflow-hidden rounded-[2.5rem] shadow-2xl aspect-[4/3]">
                        <img src="https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=800&q=80" alt="Legal Support" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700">
                    </div>
                    <div class="absolute -top-6 -right-6 text-8 font-black text-slate-100 text-9xl -z-0 select-none">02</div>
                </div>
                <div class="w-full lg:w-1/2">
                    <h2 class="text-primary font-bold tracking-widest uppercase text-sm mb-4">Litigation Support</h2>
                    <h3 class="text-4xl font-black text-secondary mb-6 leading-tight">Legal & Insurance Verification</h3>
                    <p class="text-slate-600 mb-8 leading-relaxed">We serve as the bridge between suspicion and court-admissible proof, assisting legal teams and insurers with meticulous fact-finding.</p>
                    <ul class="grid grid-cols-1 gap-4">
                        <?php 
                        $legal = [
                            'Evidence Gathering' => 'Witness location, interviews, and physical evidence.',
                            'Claims Verification' => 'In-depth investigation of suspicious insurance claims.',
                            'Process Serving' => 'Timely, professional serving of legal documentation.',
                            'Affidavit Preparation' => 'Detailed, professional reporting ready for litigation.'
                        ];
                        foreach ($legal as $title => $desc): ?>
                        <li class="flex items-start gap-4 p-4 rounded-2xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100">
                            <span class="bg-primary/10 text-primary p-2 rounded-lg mt-1"><i class="fas fa-gavel"></i></span>
                            <div>
                                <span class="block font-bold text-secondary"><?= htmlspecialchars($title) ?></span>
                                <span class="text-sm text-slate-500"><?= htmlspecialchars($desc) ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-16 items-center">
                <div class="w-full lg:w-1/2 relative group">
                    <div class="relative z-10 overflow-hidden rounded-[2.5rem] shadow-2xl aspect-[4/3]">
                        <img src="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=800&q=80" alt="Private Services" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700">
                    </div>
                    <div class="absolute -top-6 -left-6 text-8 font-black text-slate-100 text-9xl -z-0 select-none">03</div>
                </div>
                <div class="w-full lg:w-1/2">
                    <h2 class="text-primary font-bold tracking-widest uppercase text-sm mb-4">Personal Matters</h2>
                    <h3 class="text-4xl font-black text-secondary mb-6 leading-tight">Private Client Services</h3>
                    <p class="text-slate-600 mb-8 leading-relaxed">Handling sensitive personal matters with unmatched empathy and total discretion. Your privacy is our absolute priority.</p>
                    <ul class="grid grid-cols-1 gap-4">
                        <?php 
                        $private = [
                            'Infidelity Investigations' => 'Discreet surveillance for matrimonial clarity.',
                            'Missing Persons' => 'Locating family members, debtors, or witnesses.',
                            'Domestic Staff Vetting' => 'In-depth security checks for household employees.',
                            'Mobile Surveillance' => 'Static and active tracking of individuals.'
                        ];
                        foreach ($private as $title => $desc): ?>
                        <li class="flex items-start gap-4 p-4 rounded-2xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100">
                            <span class="bg-primary/10 text-primary p-2 rounded-lg mt-1"><i class="fas fa-user-secret"></i></span>
                            <div>
                                <span class="block font-bold text-secondary"><?= htmlspecialchars($title) ?></span>
                                <span class="text-sm text-slate-500"><?= htmlspecialchars($desc) ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Service Tiers & Details -->
<section class="py-24 bg-slate-50">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">Investigation Service Tiers</h2>
            <p class="text-slate-600 text-lg">Professional investigation services tailored to your specific needs with comprehensive reporting and legal consultation.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <?php
            $pricing = [
                [
                    'tier' => 'Essential',
                    'duration' => '1-2 Weeks',
                    'features' => [
                        'Background investigations',
                        'Basic surveillance (48 hours)',
                        'Standard reporting',
                        'Email/phone support',
                        'POPIA compliance'
                    ],
                    'suitable' => 'Personal matters, basic due diligence'
                ],
                [
                    'tier' => 'Professional',
                    'duration' => '2-6 Weeks',
                    'features' => [
                        'Comprehensive surveillance',
                        'Digital forensics',
                        'Witness interviews',
                        'Detailed forensic reports',
                        'Priority support',
                        'Court preparation assistance'
                    ],
                    'suitable' => 'Corporate investigations, complex cases'
                ],
                [
                    'tier' => 'Enterprise',
                    'duration' => '6-12 Weeks',
                    'features' => [
                        'Multi-jurisdictional investigations',
                        'Advanced forensic analysis',
                        'Expert witness testimony',
                        'Ongoing monitoring',
                        'Dedicated case manager',
                        'International coordination'
                    ],
                    'suitable' => 'Major corporate fraud, international cases'
                ]
            ];
            foreach ($pricing as $plan): ?>
            <div class="card-premium p-8 rounded-3xl shadow-card hover:shadow-card-hover transition-all duration-500 group">
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-secondary mb-2 group-hover:text-primary transition-colors duration-300"><?= htmlspecialchars($plan['tier']) ?></h3>
                    <div class="text-sm text-slate-500 uppercase tracking-widest font-bold bg-primary/10 text-primary px-3 py-1 rounded-full inline-block group-hover:bg-primary group-hover:text-white transition-all duration-300"><?= htmlspecialchars($plan['duration']) ?></div>
                </div>

                <ul class="space-y-3 mb-6">
                    <?php foreach ($plan['features'] as $feature): ?>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-check text-primary text-sm animate-scale-in"></i>
                            <span class="text-sm text-slate-600 group-hover:text-slate-700 transition-colors duration-300"><?= htmlspecialchars($feature) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="pt-6 border-t border-slate-200 group-hover:border-primary/30 transition-colors duration-300">
                    <p class="text-xs text-slate-500 mb-4 group-hover:text-slate-600 transition-colors duration-300">Suitable for:</p>
                    <p class="text-sm font-medium text-secondary group-hover:text-primary transition-colors duration-300"><?= htmlspecialchars($plan['suitable']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Additional Services -->
        <div class="bg-white rounded-3xl p-8 md:p-12 shadow-sm border border-slate-100">
            <h3 class="text-2xl font-bold text-secondary text-center mb-8">Additional Specialized Services</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php
                $additional_services = [
                    [
                        'name' => 'Emergency Investigations',
                        'desc' => '24/7 rapid response for urgent situations including missing persons, immediate threats, or time-sensitive evidence gathering.',
                        'timeline' => 'Same day response'
                    ],
                    [
                        'name' => 'Corporate Due Diligence',
                        'desc' => 'Comprehensive background checks on partners, executives, and investment targets including financial analysis and reputation assessment.',
                        'timeline' => '2-4 weeks'
                    ],
                    [
                        'name' => 'Insurance Fraud Investigation',
                        'desc' => 'Specialized investigation of suspicious claims including arson, workers compensation fraud, and accident reconstruction.',
                        'timeline' => '1-3 weeks'
                    ],
                    [
                        'name' => 'Cyber Investigations',
                        'desc' => 'Digital forensics, online harassment tracing, data breach analysis, and cyber evidence recovery for litigation.',
                        'timeline' => '2-6 weeks'
                    ],
                    [
                        'name' => 'Asset Tracing',
                        'desc' => 'Locating hidden assets, offshore accounts, and concealed wealth in divorce, fraud, or recovery cases.',
                        'timeline' => '4-8 weeks'
                    ],
                    [
                        'name' => 'Executive Protection Intelligence',
                        'desc' => 'Threat assessment, security consulting, and protective intelligence for high-profile individuals and executives.',
                        'timeline' => 'Ongoing monitoring available'
                    ]
                ];
                foreach ($additional_services as $service): ?>
                <div class="border border-slate-200 rounded-2xl p-6 hover:border-primary/30 hover:shadow-md transition-all">
                    <h4 class="text-xl font-bold text-secondary mb-3"><?= htmlspecialchars($service['name']) ?></h4>
                    <p class="text-slate-600 text-sm leading-relaxed mb-4"><?= htmlspecialchars($service['desc']) ?></p>
                    <div class="text-sm text-slate-600">
                        <span class="font-semibold">Timeline:</span> <?= htmlspecialchars($service['timeline']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Service Process Timeline -->
<section class="py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">Our Investigation Process</h2>
            <p class="text-slate-600 text-lg">A systematic, evidence-based approach ensuring reliable results and court admissibility.</p>
        </div>

        <div class="max-w-4xl mx-auto">
            <div class="space-y-8">
                <?php
                $process_steps = [
                    [
                        'step' => '01',
                        'title' => 'Initial Consultation & Assessment',
                        'desc' => 'Confidential discussion of your case requirements, objectives, and constraints. We assess feasibility and provide preliminary recommendations.',
                        'duration' => '1-2 hours',
                        'deliverables' => 'Case assessment report, preliminary strategy, timeline estimate'
                    ],
                    [
                        'step' => '02',
                        'title' => 'Contract & Planning Phase',
                        'desc' => 'Formal agreement, detailed investigation plan development, resource allocation, and legal compliance review.',
                        'duration' => '1-3 days',
                        'deliverables' => 'Investigation contract, detailed plan, timeline, retainer agreement'
                    ],
                    [
                        'step' => '03',
                        'title' => 'Intelligence Gathering',
                        'desc' => 'Active field investigation, surveillance, interviews, document collection, and digital evidence recovery.',
                        'duration' => '1-8 weeks',
                        'deliverables' => 'Progress reports, interim findings, evidence documentation'
                    ],
                    [
                        'step' => '04',
                        'title' => 'Analysis & Verification',
                        'desc' => 'Evidence analysis, witness verification, forensic examination, and pattern identification.',
                        'duration' => '3-7 days',
                        'deliverables' => 'Analysis reports, evidence validation, preliminary conclusions'
                    ],
                    [
                        'step' => '05',
                        'title' => 'Final Report & Presentation',
                        'desc' => 'Comprehensive report preparation, executive summary, and formal presentation of findings.',
                        'duration' => '3-5 days',
                        'deliverables' => 'Final investigation report, evidence packages, court-ready documentation'
                    ],
                    [
                        'step' => '06',
                        'title' => 'Post-Investigation Support',
                        'desc' => 'Court testimony support, additional evidence requests, and follow-up investigations if needed.',
                        'duration' => 'Ongoing',
                        'deliverables' => 'Expert testimony, additional evidence, case consultation'
                    ]
                ];
                foreach ($process_steps as $step): ?>
                <div class="flex items-start gap-6 p-6 rounded-2xl border border-slate-100 hover:border-primary/20 hover:shadow-md transition-all">
                    <div class="flex-shrink-0 w-16 h-16 bg-primary text-white rounded-xl flex items-center justify-center font-bold text-lg">
                        <?= htmlspecialchars($step['step']) ?>
                    </div>
                    <div class="flex-grow">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xl font-bold text-secondary"><?= htmlspecialchars($step['title']) ?></h3>
                            <span class="text-sm font-semibold text-primary bg-primary/10 px-3 py-1 rounded-full">
                                <?= htmlspecialchars($step['duration']) ?>
                            </span>
                        </div>
                        <p class="text-slate-600 leading-relaxed mb-4"><?= htmlspecialchars($step['desc']) ?></p>
                        <div class="text-sm">
                            <span class="font-semibold text-slate-700">Deliverables:</span>
                            <span class="text-slate-600 ml-2"><?= htmlspecialchars($step['deliverables']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Technology Showcase -->
<section class="py-24 bg-secondary">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-white sm:text-4xl mb-4">Advanced Investigative Technology</h2>
            <p class="text-slate-300 text-lg">We leverage cutting-edge technology to enhance traditional investigative methods while maintaining strict legal compliance.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php
            $technologies = [
                [
                    'icon' => 'fa-video-camera',
                    'title' => 'HD Surveillance Systems',
                    'desc' => '4K cameras, night vision, drone technology, GPS tracking, and real-time streaming with encrypted transmission.'
                ],
                [
                    'icon' => 'fa-laptop-code',
                    'title' => 'Digital Forensics Lab',
                    'desc' => 'Advanced data recovery, email analysis, social media investigation, and cyber evidence collection tools.'
                ],
                [
                    'icon' => 'fa-search-plus',
                    'title' => 'OSINT Research Platform',
                    'desc' => 'Proprietary open-source intelligence tools, database access, and automated research capabilities.'
                ],
                [
                    'icon' => 'fa-shield-alt',
                    'title' => 'Counter-Surveillance',
                    'desc' => 'TSCM equipment, bug detection, signal analysis, and secure communication platforms.'
                ],
                [
                    'icon' => 'fa-fingerprint',
                    'title' => 'Forensic Analysis Suite',
                    'desc' => 'Document examination, fingerprint analysis, chemical testing, and biometric verification.'
                ],
                [
                    'icon' => 'fa-satellite',
                    'title' => 'Remote Monitoring',
                    'desc' => 'Automated surveillance systems, motion detection, and instant alert notifications.'
                ],
                [
                    'icon' => 'fa-database',
                    'title' => 'Evidence Management',
                    'desc' => 'Secure digital evidence storage, chain of custody tracking, and court-ready presentation tools.'
                ],
                [
                    'icon' => 'fa-lock',
                    'title' => 'Encrypted Communication',
                    'desc' => 'End-to-end encrypted reporting, secure client portals, and confidential data transmission.'
                ]
            ];
            foreach ($technologies as $tech): ?>
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-primary/50 transition-all group">
                <div class="w-12 h-12 bg-primary text-white rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas <?= htmlspecialchars($tech['icon']) ?> fa-lg"></i>
                </div>
                <h4 class="text-white font-bold text-lg mb-3 group-hover:text-primary transition-colors"><?= htmlspecialchars($tech['title']) ?></h4>
                <p class="text-slate-300 text-sm leading-relaxed"><?= htmlspecialchars($tech['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Case Studies / Success Stories -->
<section class="py-24 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">Success Stories</h2>
            <p class="text-slate-600 text-lg">Real results for our clients (identities protected for confidentiality).</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php
            $case_studies = [
                [
                    'type' => 'Corporate Fraud',
                    'challenge' => 'Multi-million rand embezzlement scheme within a Johannesburg-based corporation',
                    'approach' => 'Undercover investigation, financial forensics, and digital evidence recovery',
                    'result' => 'Full asset recovery, criminal prosecution, strengthened internal controls',
                    'value' => 'R12 million recovered'
                ],
                [
                    'type' => 'Insurance Investigation',
                    'challenge' => 'Suspicious arson claim on an industrial property in Durban',
                    'approach' => 'Scene reconstruction, witness interviews, and forensic fire analysis',
                    'result' => 'Fraud exposed, claim denied, perpetrator prosecuted under insurance fraud legislation',
                    'value' => 'R8.5 million saved'
                ],
                [
                    'type' => 'Matrimonial Investigation',
                    'challenge' => 'Hidden asset discovery during a high-net-worth divorce proceeding',
                    'approach' => 'International asset tracing, financial analysis, and witness testimony',
                    'result' => 'Complete asset disclosure achieved, fair settlement negotiated',
                    'value' => 'R25 million additional assets discovered'
                ]
            ];
            foreach ($case_studies as $study): ?>
            <div class="bg-slate-50 rounded-3xl p-8 border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                <div class="text-primary font-bold text-sm uppercase tracking-widest mb-3 bg-primary/10 inline-block px-3 py-1 rounded-full">
                    <?= htmlspecialchars($study['type']) ?>
                </div>
                <h4 class="text-xl font-bold text-secondary mb-4">Challenge: <?= htmlspecialchars($study['challenge']) ?></h4>
                <p class="text-slate-600 text-sm mb-4">
                    <strong>Approach:</strong> <?= htmlspecialchars($study['approach']) ?>
                </p>
                <p class="text-slate-600 text-sm mb-4">
                    <strong>Result:</strong> <?= htmlspecialchars($study['result']) ?>
                </p>
                <div class="pt-4 border-t border-slate-200">
                    <div class="text-lg font-bold text-green-600">
                        Value Delivered: <?= htmlspecialchars($study['value']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Geographic Coverage & Response Times -->
<section class="py-24 bg-slate-50">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-extrabold text-secondary sm:text-4xl mb-4">Nationwide Coverage</h2>
            <p class="text-slate-600 text-lg">Professional investigative services across South Africa with rapid response capabilities.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Coverage Map -->
            <div>
                <h3 class="text-2xl font-bold text-secondary mb-6">Service Areas</h3>
                <div class="space-y-4">
                    <?php
                    $regions = [
                        ['area' => 'Gauteng Province', 'cities' => 'Johannesburg, Pretoria, Sandton, Midrand', 'response' => 'Same-day', 'coverage' => 'Complete'],
                        ['area' => 'Western Cape', 'cities' => 'Cape Town, Stellenbosch, Paarl', 'response' => '24 hours', 'coverage' => 'Full service'],
                        ['area' => 'KwaZulu-Natal', 'cities' => 'Durban, Pietermaritzburg, Richards Bay', 'response' => '24-48 hours', 'coverage' => 'Full service'],
                        ['area' => 'Eastern Cape', 'cities' => 'Port Elizabeth, East London', 'response' => '48-72 hours', 'coverage' => 'Partner network'],
                        ['area' => 'Mpumalanga', 'cities' => 'Nelspruit, Witbank, Middelburg', 'response' => '24-48 hours', 'coverage' => 'Full service'],
                        ['area' => 'Limpopo', 'cities' => 'Polokwane, Tzaneen, Phalaborwa', 'response' => '48-72 hours', 'coverage' => 'Partner network'],
                        ['area' => 'North West', 'cities' => 'Rustenburg, Potchefstroom, Klerksdorp', 'response' => '24-48 hours', 'coverage' => 'Full service'],
                        ['area' => 'Free State', 'cities' => 'Bloemfontein, Welkom, Kroonstad', 'response' => '48-72 hours', 'coverage' => 'Partner network']
                    ];
                    foreach ($regions as $region): ?>
                    <div class="bg-white p-4 rounded-xl border border-slate-200 hover:border-primary/30 transition-colors">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-bold text-secondary"><?= htmlspecialchars($region['area']) ?></h4>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full
                                <?php echo $region['response'] === 'Same-day' ? 'bg-green-100 text-green-700' :
                                           ($region['response'] === '24 hours' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700'); ?>">
                                <?= htmlspecialchars($region['response']) ?>
                            </span>
                        </div>
                        <p class="text-sm text-slate-600 mb-2"><?= htmlspecialchars($region['cities']) ?></p>
                        <p class="text-xs text-slate-500">Coverage: <?= htmlspecialchars($region['coverage']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Service Guarantees -->
            <div>
                <h3 class="text-2xl font-bold text-secondary mb-6">Service Guarantees</h3>
                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-2xl border border-slate-200">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-secondary">Rapid Response Guarantee</h4>
                                <p class="text-sm text-slate-600">Emergency investigations within 24 hours anywhere in South Africa</p>
                            </div>
                        </div>
                        <ul class="text-sm text-slate-600 space-y-1 ml-16">
                            <li>• Gauteng: Same-day response</li>
                            <li>• Major cities: Within 24 hours</li>
                            <li>• Remote areas: Within 72 hours</li>
                        </ul>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-slate-200">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-shield-alt fa-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-secondary">Quality Assurance</h4>
                                <p class="text-sm text-slate-600">Every investigation meets court admissibility standards</p>
                            </div>
                        </div>
                        <ul class="text-sm text-slate-600 space-y-1 ml-16">
                            <li>• POPIA compliant data handling</li>
                            <li>• PSIRA registered investigators</li>
                            <li>• Chain of custody documentation</li>
                            <li>• Independent peer review</li>
                        </ul>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-slate-200">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-handshake fa-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-secondary">Client Satisfaction</h4>
                                <p class="text-sm text-slate-600">98% client retention rate with guaranteed confidentiality</p>
                            </div>
                        </div>
                        <ul class="text-sm text-slate-600 space-y-1 ml-16">
                            <li>• Transparent pricing, no hidden fees</li>
                            <li>• Regular progress updates</li>
                            <li>• Dedicated case management</li>
                            <li>• Post-investigation support</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- FAQ Schema Markup for SEO -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "What types of investigations does ISS Investigations offer in Gauteng?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "We offer comprehensive investigation services including corporate fraud investigations, surveillance, TSCM bug sweeps, digital forensics, background screening, employee investigations, and legal intelligence. All services are POPIA-compliant and conducted by PSIRA-registered investigators."
            }
        },
        {
            "@type": "Question",
            "name": "Are ISS Investigations services POPIA-compliant?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Yes. All of our investigation services strictly comply with the Protection of Personal Information Act (POPIA) of South Africa. We prioritize data protection and handle all personal information with absolute confidentiality and legal precision."
            }
        },
        {
            "@type": "Question",
            "name": "How long does a typical investigation take?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Investigation timelines vary depending on the scope and complexity of the case. Corporate investigations may take 2-8 weeks, while surveillance projects are typically 1-4 weeks. We provide estimated timelines during the initial consultation."
            }
        },
        {
            "@type": "Question",
            "name": "Is my investigation confidential?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Absolute confidentiality is guaranteed. All communications, evidence, and findings are protected by attorney-client privilege where applicable, and handled with strict operational security protocols."
            }
        },
        {
            "@type": "Question",
            "name": "Can I use evidence from an ISS investigation in court?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Yes. All our investigations are conducted to ensure findings are legally admissible in South African courts. We maintain strict evidence handling procedures and chain of custody documentation."
            }
        },
        {
            "@type": "Question",
            "name": "What is PSIRA registration and why does it matter?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "PSIRA (Private Security Industry Regulatory Authority) registration certifies that our investigators meet South African professional standards for security and investigative services. This ensures competence, compliance, and accountability."
            }
        },
        {
            "@type": "Question",
            "name": "How much do investigation services cost?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Costs depend on the investigation type, scope, duration, and resources required. We provide transparent quotes during the consultation phase with no hidden fees. Emergency investigations may include rush fees."
            }
        },
        {
            "@type": "Question",
            "name": "Do you operate only in Gauteng?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "While headquartered in Gauteng, we operate nationwide across South Africa. Our network of investigators and field operatives covers major cities including Johannesburg, Pretoria, Cape Town, Durban, and beyond."
            }
        }
    ]
}
</script>

<section class="py-24 bg-slate-50">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-black text-secondary mb-12 text-center">Frequently Asked Questions</h2>
            
            <div class="space-y-6">
                <?php
                $faqs = [
                    ["What types of investigations does ISS Investigations offer in Gauteng?", "We offer comprehensive investigation services including corporate fraud investigations, surveillance, TSCM bug sweeps, digital forensics, background screening, employee investigations, and legal intelligence. All services are POPIA-compliant and conducted by PSIRA-registered investigators."],
                    ["Are ISS Investigations services POPIA-compliant?", "Yes. All of our investigation services strictly comply with the Protection of Personal Information Act (POPIA) of South Africa. We prioritize data protection and handle all personal information with absolute confidentiality and legal precision."],
                    ["How long does a typical investigation take?", "Investigation timelines vary depending on the scope and complexity of the case. Corporate investigations may take 2-8 weeks, while surveillance projects are typically 1-4 weeks. We provide estimated timelines during the initial consultation."],
                    ["Is my investigation confidential?", "Absolute confidentiality is guaranteed. All communications, evidence, and findings are protected by attorney-client privilege where applicable, and handled with strict operational security protocols."],
                    ["Can I use evidence from an ISS investigation in court?", "Yes. All our investigations are conducted to ensure findings are legally admissible in South African courts. We maintain strict evidence handling procedures and chain of custody documentation."],
                    ["What is PSIRA registration and why does it matter?", "PSIRA (Private Security Industry Regulatory Authority) registration certifies that our investigators meet South African professional standards for security and investigative services. This ensures competence, compliance, and accountability."],
                    ["What factors determine the scope of an investigation?", "The scope of an investigation depends on the case complexity, number of subjects involved, geographic requirements, and evidence collection needs. During consultation, we assess these factors to determine the most appropriate service tier and timeline."],
                    ["Do you operate only in Gauteng?", "While headquartered in Gauteng, we operate nationwide across South Africa. Our network of investigators and field operatives covers major cities including Johannesburg, Pretoria, Cape Town, Durban, and beyond."]
                ];
                foreach ($faqs as $index => $faq):
                ?>
                <details class="group bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                    <summary class="flex items-center justify-between cursor-pointer p-6 font-bold text-secondary text-lg">
                        <span><?php echo htmlspecialchars($faq[0]); ?></span>
                        <i class="fas fa-chevron-down group-open:rotate-180 transition-transform text-primary"></i>
                    </summary>
                    <div class="px-6 pb-6 border-t border-slate-100 text-slate-600 leading-relaxed">
                        <?php echo htmlspecialchars($faq[1]); ?>
                    </div>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="py-24 bg-white relative overflow-hidden">
    <div class="container mx-auto px-4 relative z-10 text-center">
        <div class="max-w-4xl mx-auto bg-secondary p-12 md:p-20 rounded-[3rem] shadow-2xl">
            <h2 class="text-3xl md:text-5xl font-black text-white mb-8">Discretion is our Foundation.</h2>
            <p class="text-slate-300 text-lg mb-12 max-w-2xl mx-auto">
                No matter how complex or sensitive your situation, our lead investigators are ready to provide a confidential strategy to find the truth.
            </p>
            <a href="contact.php" class="inline-block bg-primary hover:bg-orange-600 text-white font-black py-4 px-12 rounded-full text-lg shadow-xl transition-all transform hover:scale-105">
                Begin Your Consultation
            </a>
        </div>
    </div>
</section>

<?php include_once 'includes/public_footer.php'; ?>
