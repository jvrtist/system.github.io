<?php
/**
 * ISS Investigations - Premium Contact Page
 * Designed for conversion, trust, and mobile-first accessibility.
 */
$page_title = "Contact Private Investigator Gauteng | Confidential Consultation | ISS Investigations";
include_once 'includes/public_header.php';
?>

<section class="relative bg-slate-900 pt-32 pb-20 overflow-hidden border-b-4 border-primary">
    <div class="container mx-auto px-4 relative z-10 text-center">
        <span class="inline-block py-1 px-4 rounded-full bg-primary/20 text-primary text-xs font-black tracking-widest uppercase mb-6">Secure Channel</span>
        <h1 class="text-5xl md:text-7xl font-black text-white leading-tight">
            Initiate a <span class="text-primary">Briefing.</span>
        </h1>
        <p class="mt-8 max-w-2xl mx-auto text-lg text-slate-400 leading-relaxed font-light">
            Your inquiry is handled with absolute discretion. Reach out through our secure channels to begin a confidential assessment of your case.
        </p>
    </div>
    <div class="absolute inset-0 opacity-10 pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
</section>

<section class="bg-slate-50 py-24">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-12">
            
            <div class="lg:col-span-1 space-y-8">
                <div>
                    <h2 class="text-3xl font-black text-secondary mb-6">Direct Channels</h2>
                    <p class="text-slate-500 mb-8">Our investigators are available for urgent consultations across Gauteng and nationwide.</p>
                </div>

                <div class="space-y-6">
                    <div class="group flex items-start gap-5 p-6 bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                        <div class="bg-slate-900 text-primary p-4 rounded-2xl group-hover:bg-primary group-hover:text-white transition-colors">
                            <i class="fas fa-map-marker-alt fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-secondary text-lg">HQ Location</h3>
                            <p class="text-slate-500 text-sm leading-relaxed mt-1">
                                95 Houtkop Road, Duncanville<br>
                                Vereeniging, Gauteng, 1900
                            </p>
                        </div>
                    </div>

                    <a href="tel:+27653087750" class="group flex items-start gap-5 p-6 bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                        <div class="bg-slate-900 text-primary p-4 rounded-2xl group-hover:bg-primary group-hover:text-white transition-colors">
                            <i class="fas fa-phone-alt fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-secondary text-lg">Secure Line</h3>
                            <p class="text-slate-500 text-sm mt-1">+27 65 308 7750</p>
                        </div>
                    </a>

                    <a href="mailto:info@iss-investigations.co.za" class="group flex items-start gap-5 p-6 bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                        <div class="bg-slate-900 text-primary p-4 rounded-2xl group-hover:bg-primary group-hover:text-white transition-colors">
                            <i class="fas fa-envelope fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-secondary text-lg">Email Intelligence</h3>
                            <p class="text-slate-500 text-sm mt-1">info@iss-investigations.co.za</p>
                        </div>
                    </a>
                </div>

                <div class="p-8 rounded-3xl bg-secondary text-white relative overflow-hidden">
                    <h4 class="font-bold mb-2 flex items-center gap-2">
                        <i class="fas fa-user-shield text-primary"></i> Data Privacy
                    </h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        In accordance with POPIA, your details are encrypted and used solely for the purpose of your investigative inquiry.
                    </p>
                </div>

                <div class="p-6 rounded-3xl bg-primary/10 border border-primary/20">
                    <h4 class="font-bold text-secondary mb-3 flex items-center gap-2">
                        <i class="fas fa-star text-primary"></i> Share Your Experience
                    </h4>
                    <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                        Your feedback helps us improve our services and guides other clients in their decision-making process.
                    </p>
                    <a href="submit_review.php" class="inline-flex items-center gap-2 text-primary hover:text-orange-600 transition-colors text-sm font-semibold">
                        <i class="fas fa-arrow-right"></i> Submit a Review
                    </a>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white p-8 md:p-12 rounded-[2.5rem] shadow-xl border border-slate-100">
                    <div class="mb-10">
                        <h2 class="text-3xl font-black text-secondary mb-2">Consultation Request</h2>
                        <p class="text-slate-500">Provide a brief overview of your requirements. An investigator will contact you shortly.</p>
                    </div>

                    <form action="contact_form_handler.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php echo csrf_input(); ?>
                        <div class="col-span-1">
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Full Name</label>
                            <input type="text" name="name" required placeholder="John Doe" 
                                class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Email Address</label>
                            <input type="email" name="email" required placeholder="john@example.com" 
                                class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Subject / Service Type</label>
                            <select name="service" class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all appearance-none">
                                <option>Corporate Investigation</option>
                                <option>Private Surveillance</option>
                                <option>Technical / Bug Sweep</option>
                                <option>Legal Support</option>
                                <option>Other Inquiry</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Case Briefing</label>
                            <textarea name="message" rows="6" required placeholder="Please describe your situation discreetly..." 
                                class="w-full px-6 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"></textarea>
                        </div>
                        <div class="col-span-2 mt-4">
                            <button type="submit" class="btn-primary w-full py-5 px-8 rounded-2xl shadow-glow hover:shadow-glow-lg transform hover:scale-[1.02] transition-all duration-300 flex items-center justify-center gap-3">
                                <i class="fas fa-paper-plane"></i>
                                Submit Secure Inquiry
                            </button>
                            <p class="text-center text-[10px] text-slate-400 mt-4 uppercase tracking-[0.2em]">
                                <i class="fas fa-lock mr-1"></i> End-to-End Encrypted Submission
                            </p>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

<?php include_once 'includes/public_footer.php'; ?>
