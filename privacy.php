<?php
/**
 * ISS Investigations - Privacy Policy (POPIA Compliant)
 * A professional, high-authority layout for legal transparency.
 */
$page_title = "Privacy Policy & POPIA Compliance | ISS Investigations";
include_once 'includes/public_header.php';
?>

<section class="relative bg-slate-900 pt-32 pb-16 overflow-hidden border-b-4 border-primary">
    <div class="container mx-auto px-4 relative z-10 text-center">
        <span class="inline-block py-1 px-4 rounded-full bg-primary/20 text-primary text-xs font-black tracking-widest uppercase mb-6">Data Sovereignty</span>
        <h1 class="text-4xl md:text-6xl font-black text-white leading-tight">
            Privacy <span class="text-primary">& Protection.</span>
        </h1>
        <p class="mt-6 max-w-2xl mx-auto text-slate-400 font-light">
            In strict accordance with the Protection of Personal Information Act (POPIA) of South Africa.
        </p>
    </div>
    <div class="absolute inset-0 opacity-10 pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
</section>

<section class="bg-white py-20">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            
            <p class="text-slate-500 mb-12 italic text-sm text-right">Last Updated: <span class="text-secondary font-bold"><?php echo date("F j, Y"); ?></span></p>

            <div class="mb-16">
                <h3 class="text-2xl font-black text-secondary mb-6 flex items-center gap-3">
                    <span class="text-primary">01.</span> Introduction and Definitions
                </h3>
                <div class="prose prose-slate max-w-none text-slate-600 leading-relaxed">
                    <p>ISS Investigations ("we", "us", "our") is a private investigation firm operating in South Africa. We are committed to protecting the privacy and ensuring the lawful processing of Personal Information of our clients and visitors.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <strong class="text-secondary block mb-1 uppercase text-xs tracking-tighter">Responsible Party</strong>
                            <p class="text-xs">ISS Investigations determines the purpose and means for processing Personal Information.</p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <strong class="text-secondary block mb-1 uppercase text-xs tracking-tighter">Data Subject</strong>
                            <p class="text-xs">The natural or juristic person to whom the Personal Information relates.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-primary/5 border-l-4 border-primary p-6 rounded-r-2xl my-10">
                    <h4 class="font-bold text-secondary flex items-center gap-2 mb-2">
                        <i class="fas fa-gavel"></i> Legal Disclaimer
                    </h4>
                    <p class="text-sm text-slate-600">This document does not constitute legal advice. We ensure it is accurate and compliant with the specific nature of investigative tradecraft and South African jurisdiction.</p>
                </div>
            </div>

            <hr class="border-slate-100 my-12">

            <div class="mb-16">
                <h3 class="text-2xl font-black text-secondary mb-6 flex items-center gap-3">
                    <span class="text-primary">02.</span> Compliance Oversight
                </h3>
                <div class="bg-secondary p-8 rounded-[2rem] text-white flex flex-col md:flex-row items-center gap-8 shadow-xl">
                    <div class="w-20 h-20 bg-primary/20 rounded-full flex items-center justify-center shrink-0">
                        <i class="fas fa-user-shield text-primary text-3xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xl font-bold mb-1 uppercase tracking-wider">Information Officer</h4>
                        <p class="text-slate-400 text-sm mb-4">In charge of POPIA compliance and data security audits.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm font-medium">
                            <span class="flex items-center gap-2"><i class="fas fa-user text-primary"></i> Jonathan van Rensburg</span>
                            <a href="mailto:jonathan@iss-investigations.co.za" class="flex items-center gap-2 hover:text-primary transition-colors"><i class="fas fa-envelope text-primary"></i> jonathan@iss-investigations.co.za</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-16">
                <h3 class="text-2xl font-black text-secondary mb-8 flex items-center gap-3">
                    <span class="text-primary">03.</span> Information We Collect
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <?php
                    $data_types = [
                        ['icon' => 'fa-id-card', 'title' => 'Identity Data', 'desc' => 'Names, ID numbers, and physical addresses.'],
                        ['icon' => 'fa-lock', 'title' => 'Account Data', 'desc' => 'Hashed passwords and portal access logs.'],
                        ['icon' => 'fa-briefcase', 'title' => 'Case Data', 'desc' => 'Special information provided for legal defense.'],
                        ['icon' => 'fa-microchip', 'title' => 'Technical Data', 'desc' => 'IP addresses and browser fingerprinting for security.']
                    ];
                    foreach ($data_types as $item): ?>
                    <div class="p-6 border border-slate-100 rounded-2xl bg-white hover:shadow-lg transition-all">
                        <i class="fas <?= htmlspecialchars($item['icon']) ?> text-primary mb-4 block text-xl"></i>
                        <h4 class="font-bold text-secondary mb-2"><?= htmlspecialchars($item['title']) ?></h4>
                        <p class="text-sm text-slate-500 leading-relaxed"><?= htmlspecialchars($item['desc']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-16">
                <h3 class="text-2xl font-black text-secondary mb-8 flex items-center gap-3">
                    <span class="text-primary">04.</span> Your Rights (Data Subject)
                </h3>
                <div class="space-y-4">
                    <?php
                    $rights = [
                        "Right to access information we hold about you.",
                        "Right to correct or delete inaccurate Personal Information.",
                        "Right to object to processing for direct marketing.",
                        "Right to submit a complaint to the Information Regulator."
                    ];
                    foreach ($rights as $right): ?>
                    <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-xl">
                        <i class="fas fa-check-circle text-primary"></i>
                        <span class="text-slate-700 text-sm font-medium"><?= htmlspecialchars($right) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-8 text-center bg-slate-100 p-6 rounded-2xl">
                    <p class="text-xs text-slate-500 uppercase font-black tracking-widest mb-2">South African Information Regulator</p>
                    <a href="mailto:complaints.IR@justice.gov.za" class="text-secondary hover:text-primary font-bold transition-colors underline decoration-primary">complaints.IR@justice.gov.za</a>
                </div>
            </div>

            <div class="prose prose-slate max-w-none text-slate-600 
                prose-h4:text-secondary prose-h4:font-black prose-h4:uppercase prose-h4:text-xs prose-h4:tracking-[0.2em]
                prose-hr:border-slate-100 prose-hr:my-10">
                
                <hr>
                <h4>Data Security tradecraft</h4>
                <p>We implement industrial-grade security measures: CSRF protection, password hashing, and encrypted database silos. While no transmission is 100% secure, our protocols meet PSIRA and POPIA standards for sensitive intelligence handling.</p>
                
                <hr>
                <h4>Trans-Border Flows</h4>
                <p>Information is primarily stored within South Africa. We do not transfer data internationally unless essential for case performance and only to jurisdictions with equivalent protection laws.</p>
            </div>

        </div>
    </div>
</section>

<section class="py-12 bg-slate-900 border-t border-primary/20">
    <div class="container mx-auto px-4 text-center">
        <p class="text-slate-400 text-sm">Concerned about your data footprints?</p>
        <a href="contact.php" class="text-primary font-bold hover:text-white transition-colors mt-2 inline-block">Consult our Privacy Desk &rarr;</a>
    </div>
</section>

<?php include_once 'includes/public_footer.php'; ?>
