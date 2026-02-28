<?php
/**
 * ISS Investigations - Refund & Retainer Policy
 * Professional financial disclosure layout matching the premium brand identity.
 */
$page_title = "Refund & Retainer Policy | ISS Investigations";
include_once 'includes/public_header.php';
?>

<section class="relative bg-slate-900 pt-32 pb-16 overflow-hidden border-b-4 border-primary">
    <div class="container mx-auto px-4 relative z-10 text-center">
        <span class="inline-block py-1 px-4 rounded-full bg-primary/20 text-primary text-xs font-black tracking-widest uppercase mb-6">Financial Transparency</span>
        <h1 class="text-4xl md:text-6xl font-black text-white leading-tight">
            Refund & <span class="text-primary">Retainers.</span>
        </h1>
        <p class="mt-6 max-w-2xl mx-auto text-slate-400 font-light">
            Clear terms for professional engagements, ensuring mutual accountability.
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
                    <span class="text-primary">01.</span> Overview
                </h3>
                <div class="prose prose-slate max-w-none text-slate-600">
                    <p>This policy outlines the terms related to fees, retainers, and refunds for the investigative services provided by ISS Investigations. Our commitment is to deliver professional, high-quality results while ensuring clarity and fairness for both our clients and our agency.</p>
                </div>
                
                <div class="bg-primary/5 border-l-4 border-primary p-6 rounded-r-2xl my-8">
                    <h4 class="font-bold text-secondary flex items-center gap-2 mb-2 text-sm uppercase tracking-wider">
                        <i class="fas fa-file-contract"></i> Service Agreement Precedence
                    </h4>
                    <p class="text-xs text-slate-500 italic">Please note: The specific terms of any individual engagement will be formally detailed in a signed Service Agreement which takes precedence over this general policy.</p>
                </div>
            </div>

            <hr class="border-slate-100 my-12">

            <div class="mb-16">
                <h3 class="text-2xl font-black text-secondary mb-8 flex items-center gap-3">
                    <span class="text-primary">02.</span> The Retainer System
                </h3>
                <div class="bg-slate-50 p-8 rounded-[2rem] border border-slate-100 relative overflow-hidden">
                    <div class="relative z-10">
                        <p class="text-slate-600 mb-6">Most investigative services require an upfront <strong>Retainer</strong>. This is not a flat fee, but a deposit against which billable hours and expenses are charged.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                                <span class="text-primary font-black block mb-2 uppercase text-xs tracking-widest">Purpose</span>
                                <p class="text-sm text-slate-500">Secures investigator availability and covers initial case setup, preliminary research, and database access.</p>
                            </div>
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                                <span class="text-primary font-black block mb-2 uppercase text-xs tracking-widest">Non-Refundable Portion</span>
                                <p class="text-sm text-slate-500">Administrative costs incurred immediately upon case initiation are deemed non-refundable once work commences.</p>
                            </div>
                        </div>
                    </div>
                    <i class="fas fa-vault absolute -bottom-10 -right-10 text-slate-200/50 text-9xl"></i>
                </div>
            </div>

            <div class="mb-16">
                <h3 class="text-2xl font-black text-secondary mb-8 flex items-center gap-3">
                    <span class="text-primary">03.</span> Billing for Services
                </h3>
                <p class="text-slate-600 mb-8 font-medium italic">"You are paying for professional expertise and diligent effort, not a specific guaranteed outcome."</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                    <div class="p-6 rounded-2xl border-t-4 border-secondary bg-slate-50">
                        <i class="fas fa-clock text-primary mb-3"></i>
                        <h4 class="font-bold text-secondary text-sm mb-1 uppercase">Hourly Rates</h4>
                        <p class="text-xs text-slate-500">Surveillance, Interviews, Research, and Report Writing.</p>
                    </div>
                    <div class="p-6 rounded-2xl border-t-4 border-secondary bg-slate-50">
                        <i class="fas fa-gas-pump text-primary mb-3"></i>
                        <h4 class="font-bold text-secondary text-sm mb-1 uppercase">Direct Expenses</h4>
                        <p class="text-xs text-slate-500">Travel, Database Fees, and Third-Party Costs.</p>
                    </div>
                    <div class="p-6 rounded-2xl border-t-4 border-secondary bg-slate-50">
                        <i class="fas fa-microchip text-primary mb-3"></i>
                        <h4 class="font-bold text-secondary text-sm mb-1 uppercase">Tech Surcharges</h4>
                        <p class="text-xs text-slate-500">Specialized forensic or TSCM equipment utilization.</p>
                    </div>
                </div>
            </div>

            <div class="mb-16">
                <h3 class="text-2xl font-black text-secondary mb-8 flex items-center gap-3">
                    <span class="text-primary">04.</span> Cancellation Protocols
                </h3>
                <div class="space-y-6">
                    <div class="flex gap-6 p-6 rounded-2xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-100">
                        <div class="shrink-0 w-12 h-12 bg-secondary text-white rounded-xl flex items-center justify-center font-black">A</div>
                        <div>
                            <h4 class="font-bold text-secondary text-lg">Client-Initiated Cancellation</h4>
                            <p class="text-sm text-slate-500 mt-1">Written notice required. Work ceases immediately. You are billed for hours worked up to that moment. Unused retainer funds are refunded within 14 business days.</p>
                        </div>
                    </div>
                    <div class="flex gap-6 p-6 rounded-2xl hover:bg-red-50 transition-colors border border-transparent hover:border-red-100">
                        <div class="shrink-0 w-12 h-12 bg-red-600 text-white rounded-xl flex items-center justify-center font-black">B</div>
                        <div>
                            <h4 class="font-bold text-red-900 text-lg">Firm-Initiated Cancellation</h4>
                            <p class="text-sm text-red-800/70 mt-1">We reserve the right to terminate if a case involves illegal activities, false information, or unethical requests. Retainer balance will be returned after final billing.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-16 p-10 bg-secondary rounded-[3rem] text-white relative overflow-hidden shadow-2xl">
                <h3 class="text-2xl font-black mb-6">Finality of Rendered Services</h3>
                <p class="text-slate-300 mb-6 leading-relaxed">
                    Due to the nature of investigative work, we do not offer refunds for services that have already been rendered. Results (or lack thereof) cannot be used as a basis for a refund. Time spent on surveillance where no activity is observed is still a billable professional service.
                </p>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 rounded-full border border-white/20 text-xs font-bold uppercase tracking-widest">
                    <i class="fas fa-shield-alt text-primary"></i> Guaranteed Diligence, Not Outcome
                </div>
            </div>

            <div class="text-center bg-slate-50 p-10 rounded-[2.5rem] border border-slate-100">
                <h3 class="text-xl font-bold text-secondary mb-4">Financial Inquiries</h3>
                <p class="text-slate-500 mb-8 text-sm">Should you have questions regarding an invoice or your retainer balance, contact our billing department:</p>
                <div class="flex flex-col md:flex-row justify-center gap-6">
                    <a href="mailto:info@iss-investigations.co.za" class="flex items-center justify-center gap-3 px-6 py-3 bg-white rounded-2xl border border-slate-200 hover:border-primary transition-all group shadow-sm">
                        <i class="fas fa-envelope text-primary"></i>
                        <span class="text-sm font-bold text-secondary">info@iss-investigations.co.za</span>
                    </a>
                    <a href="tel:+27653087750" class="flex items-center justify-center gap-3 px-6 py-3 bg-white rounded-2xl border border-slate-200 hover:border-primary transition-all group shadow-sm">
                        <i class="fas fa-phone-alt text-primary"></i>
                        <span class="text-sm font-bold text-secondary">+27 65 308 7750</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<?php include_once 'includes/public_footer.php'; ?>