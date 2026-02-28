<?php
/**
 * ISS Investigations - Comprehensive Terms of Service (National Coverage)
 * Version: 2.1 (South African National Compliance)
 * Designed for readability, legal clarity, and professional brand consistency.
 * * Client: Jonathan van Rensburg
 * Service Area: National (South Africa)
 */
$page_title = "Terms of Service | ISS Investigations";
include_once 'includes/public_header.php';
?>

<section class="relative bg-slate-900 pt-32 pb-16 overflow-hidden border-b-4 border-primary">
    <div class="container mx-auto px-4 relative z-10 text-center">
        <span class="inline-block py-1 px-4 rounded-full bg-primary/20 text-primary text-xs font-black tracking-widest uppercase mb-6">Legal Agreement</span>
        <h1 class="text-4xl md:text-6xl font-black text-white leading-tight">
            Terms of <span class="text-primary">Service.</span>
        </h1>
        <p class="mt-6 max-w-2xl mx-auto text-slate-400 font-light">
            Comprehensive legal terms governing our professional investigative services across the Republic of South Africa and access to the Secure Client Portal.
        </p>
    </div>
    <div class="absolute inset-0 opacity-10 pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
</section>

<section class="bg-white py-20">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">

            <p class="text-slate-500 mb-12 italic text-sm text-right">Last Updated: <span class="text-secondary font-bold"><?php echo date("F j, Y"); ?></span></p>
            
            <div class="mb-16 p-8 bg-slate-50 rounded-3xl border border-slate-100">
                <h2 class="text-sm font-black uppercase tracking-[0.2em] text-secondary mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-primary"></i> Executive Summary
                </h2>
                <p class="text-slate-600 text-sm leading-relaxed">
                    By accessing ISS Investigations' services or Client Portal, you enter into a legally binding agreement. This document outlines professional standards under <strong>PSIRA</strong>, national data handling under <strong>POPIA</strong>, and the strict confidentiality protocols essential to private investigation within the Republic of South Africa.
                </p>
            </div>

            <div class="prose prose-slate max-w-none">

                <div class="mb-16">
                    <h3 class="text-2xl font-black text-secondary mb-6 flex items-center gap-3">
                        <span class="text-primary">01.</span> Agreement to Terms</h3>
                    <div class="text-slate-600">
                        <p>
                            These Terms constitute a legally binding agreement made between you, whether personally or on behalf of an entity (“you”) and <strong>ISS Investigations</strong> (“we,” “us,” or “our”), concerning your access to and use of the website and the <strong>Secure Client Portal</strong>.
                        </p>
                        <p>
                            You agree that by accessing the Service, you have read, understood, and agreed to be bound by all of these Terms of Service. If you do not agree, you are expressly prohibited from using the Service and must discontinue use immediately.
                        </p>
                    </div>
                </div>

                <hr class="border-slate-100 my-12">

                <div class="mb-16">
                    <h3 class="text-2xl font-black text-secondary mb-6 flex items-center gap-3">
                        <span class="text-primary">02.</span> Professional Regulation & National Compliance</h3>
                    <div class="text-slate-600">
                        <p>
                            ISS Investigations operates under the <strong>Private Security Industry Regulation Act 56 of 2001 (PSIRA)</strong> and maintains the right to conduct operations across all nine provinces of South Africa.
                        </p>
                        <ul class="list-disc pl-5 space-y-2 mt-4">
                            <li>All investigations are performed by registered practitioners in compliance with South African national law.</li>
                            <li>We reserve the right to decline any mandate that we believe would involve illegal activity, ethical violations, or a conflict of interest in any South African jurisdiction.</li>
                            <li>The Client warrants that the investigation is requested for a lawful purpose (e.g., litigation support, internal corporate fraud, or domestic matters).</li>
                        </ul>
                    </div>
                </div>

                <hr class="border-slate-100 my-12">

                <div class="mb-16">
                    <h3 class="text-2xl font-black text-secondary mb-6 flex items-center gap-3">
                        <span class="text-primary">03.</span> National Deployment & Disbursements</h3>
                    <div class="text-slate-600">
                        <p>
                            ISS Investigations provides services nationwide. For investigations requiring travel outside of our primary hubs, the following apply:
                        </p>
                        <ul class="list-disc pl-5 space-y-2 mt-4">
                            <li><strong>Travel Expenses:</strong> Kilometre rates are charged according to current AA rates or as per our professional fee structure.</li>
                            <li><strong>Substantial Disbursements:</strong> Accommodation, airfare, and specialized equipment costs required for national deployment will be invoiced to the Client.</li>
                            <li><strong>Local Compliance:</strong> While we operate nationally, we adhere to all local municipal by-laws relevant to surveillance and evidence gathering in specific districts.</li>
                        </ul>
                    </div>
                </div>

                <hr class="border-slate-100 my-12">

                <div class="mb-16">
                    <h3 class="text-2xl font-black text-secondary mb-6 flex items-center gap-3">
                        <span class="text-primary">04.</span> Client Portal & Account Responsibility</h3>
                    <div class="text-slate-600">
                        <p>To view case progress and download secure reports, you must use the credentials provided. You are responsible for:</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                            <?php
                            $requirements = [
                                "Maintaining absolute secrecy of access tokens.",
                                "Restricting case viewing to authorized legal/corporate personnel.",
                                "Reporting any security anomalies immediately.",
                                "Ensuring your devices are free from spyware or loggers."
                            ];
                            foreach ($requirements as $req): ?>
                            <div class="flex items-start gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100">
                                <i class="fas fa-check-shield text-primary mt-1"></i>
                                <span class="text-slate-700 text-sm font-medium"><?= htmlspecialchars($req) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-100 my-12">

                <div class="mb-16">
                    <h3 class="text-2xl font-black text-secondary mb-6 flex items-center gap-3">
                        <span class="text-primary">05.</span> Absolute Confidentiality</h3>
                    <div class="p-6 bg-secondary rounded-2xl text-slate-300 border-l-4 border-primary italic mb-6">
                        "Investigative integrity relies on total discretion. Disclosure of surveillance methods, operative identities, or unredacted reports can compromise national operations and lead to immediate termination of service."
                    </div>
                    <p class="text-slate-600">
                        All materials within the Client Portal are the proprietary property of ISS Investigations. You agree not to distribute or publish these materials—specifically to social media or public forums—without express written consent, except for use in formal South African legal proceedings (CCMA, Magistrates, or High Court).
                    </p>
                </div>

                <hr class="border-slate-100 my-12">

                <div class="mb-16">
                    <h3 class="text-2xl font-black text-secondary mb-6 flex items-center gap-3">
                        <span class="text-primary">06.</span> Financial Terms</h3>
                    <div class="text-slate-600">
                        <p><strong>Retainers:</strong> Upfront retainers are required to secure operative time and resources for national deployment.</p>
                        <p><strong>Outcome Disclaimer:</strong> Fees are paid for <em>professional expertise and investigative hours</em>. We gather evidence as it exists in reality; we do not guarantee specific outcomes, "guilty" findings, or the location of individuals who have successfully evaded detection.</p>
                        <p><strong>Refunds:</strong> Once an investigation has commenced, retainers are non-refundable to cover mobilized costs. Balances are returned within 14 business days of file closure.</p>
                    </div>
                </div>

                <hr class="border-slate-100 my-12">

                <div class="mb-16">
                    <h3 class="text-2xl font-black text-secondary mb-6 flex items-center gap-3">
                        <span class="text-primary">07.</span> National Data Protection (POPIA)</h3>
                    <div class="text-slate-600">
                        <p>
                            In accordance with the <strong>Protection of Personal Information Act (POPIA)</strong>, ISS Investigations acts as the "Operator" in the processing of personal data. 
                        </p>
                        <ul class="list-disc pl-5 space-y-2 mt-4">
                            <li>Data is stored in secure South African data centres with end-to-end encryption.</li>
                            <li>Information is processed only for the specified mandate.</li>
                            <li>Evidence is retained only as required by PSIRA or the Prescription Act.</li>
                        </ul>
                    </div>
                </div>

                <hr class="border-slate-100 my-12">

                <div class="mb-16">
                    <h3 class="text-2xl font-black text-secondary mb-6 flex items-center gap-3">
                        <span class="text-primary">08.</span> Limitation of Liability</h3>
                    <div class="bg-slate-900 p-8 rounded-2xl border border-slate-700 uppercase text-xs leading-loose tracking-wider text-slate-400">
                        IN NO EVENT WILL ISS INVESTIGATIONS, ITS DIRECTORS, OR OPERATIVES BE LIABLE TO YOU OR ANY THIRD PARTY FOR INDIRECT, CONSEQUENTIAL, OR INCIDENTAL DAMAGES (INCLUDING LOSS OF REVENUE OR REPUTATIONAL DAMAGE) ARISING FROM INVESTIGATIVE FINDINGS. TOTAL LIABILITY IS LIMITED TO THE ACTUAL FEES PAID FOR THE SERVICE IN QUESTION.
                    </div>
                </div>

                <hr class="border-slate-100 my-12">

                <div class="mb-16">
                    <h3 class="text-2xl font-black text-secondary mb-6 flex items-center gap-3">
                        <span class="text-primary">09.</span> Governing Law & Jurisdiction</h3>
                    <div class="text-slate-600">
                        <p>
                            These Terms and your use of the Service are governed by the laws of the <strong>Republic of South Africa</strong>. While we operate nationally, any legal disputes shall be adjudicated within the jurisdiction of the High Court of South Africa, Gauteng Division.
                        </p>
                    </div>
                </div>

                <hr class="border-slate-100 my-12">

                <div class="mb-16">
                    <h3 class="text-2xl font-black text-secondary mb-6 flex items-center gap-3">
                        <span class="text-primary">10.</span> Contact Information</h3>
                    <div class="text-slate-600">
                        <p>
                            For queries regarding these Terms or to report a security concern, please contact:
                        </p>
                        <div class="mt-4 p-6 bg-slate-50 rounded-2xl border border-slate-200 inline-block">
                            <p class="font-bold text-secondary">ISS Investigations Legal Compliance</p>
                            <p>Email: <a href="mailto:info@iss-investigations.co.za" class="text-primary hover:underline">info@iss-investigations.co.za</a></p>
                            <p>National Contact Nr: 065 308 7750</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<section class="py-12 bg-slate-50 border-t border-slate-100">
    <div class="container mx-auto px-4 text-center">
        <p class="text-slate-500 text-sm">Need a signed Service Level Agreement for a corporate mandate elsewhere in SA?</p>
        <a href="contact.php" class="text-primary font-bold hover:text-secondary transition-colors mt-2 inline-block">Request Formal SLA &rarr;</a>
    </div>
</section>

<?php include_once 'includes/public_footer.php'; ?>