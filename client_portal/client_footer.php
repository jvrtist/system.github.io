</div>
    </main>
    <footer class="bg-gradient-secondary mt-auto py-16 border-t-4 border-primary no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <!-- Company Info -->
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center shadow-glow">
                            <i class="fas fa-shield-alt text-2xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-white">ISS Investigations</h3>
                            <p class="text-sm text-slate-300">Client Portal</p>
                        </div>
                    </div>
                    <p class="text-slate-300 mb-6 leading-relaxed max-w-md">
                        Professional investigative services with complete discretion and integrity. 
                        Your trusted partner in resolving complex matters.
                    </p>
                    <div class="flex items-center gap-4 text-sm text-slate-400">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-envelope text-primary"></i>
                            info@iss-investigations.co.za
                        </span>
                        <span class="flex items-center gap-2">
                            <i class="fas fa-phone text-primary"></i>
                            +27 65 308 7750
                        </span>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-bold text-white mb-6">Quick Links</h4>
                    <ul class="space-y-3">
                        <li><a href="dashboard.php" class="text-slate-300 hover:text-primary transition-colors flex items-center gap-2">
                            <i class="fas fa-tachometer-alt text-primary"></i> Dashboard
                        </a></li>
                        <li><a href="cases.php" class="text-slate-300 hover:text-primary transition-colors flex items-center gap-2">
                            <i class="fas fa-folder-open text-primary"></i> My Cases
                        </a></li>
                        <li><a href="invoices.php" class="text-slate-300 hover:text-primary transition-colors flex items-center gap-2">
                            <i class="fas fa-file-invoice-dollar text-primary"></i> Invoices
                        </a></li>
                        <li><a href="posts.php" class="text-slate-300 hover:text-primary transition-colors flex items-center gap-2">
                            <i class="fas fa-newspaper text-primary"></i> News & Updates
                        </a></li>
                        <li><a href="my_account.php" class="text-slate-300 hover:text-primary transition-colors flex items-center gap-2">
                            <i class="fas fa-user-cog text-primary"></i> My Account
                        </a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="text-lg font-bold text-white mb-6">Support</h4>
                    <ul class="space-y-3">
                        <li><a href="mailto:support@iss-investigations.co.za" class="text-slate-300 hover:text-primary transition-colors flex items-center gap-2">
                            <i class="fas fa-life-ring text-primary"></i> Contact Support
                        </a></li>
                        <li><a href="../privacy.php" target="_blank" class="text-slate-300 hover:text-primary transition-colors flex items-center gap-2">
                            <i class="fas fa-shield-alt text-primary"></i> Privacy Policy
                        </a></li>
                        <li><a href="../terms.php" target="_blank" class="text-slate-300 hover:text-primary transition-colors flex items-center gap-2">
                            <i class="fas fa-file-contract text-primary"></i> Terms of Service
                        </a></li>
                        <li><a href="../contact.php" target="_blank" class="text-slate-300 hover:text-primary transition-colors flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-primary"></i> Office Location
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="pt-8 border-t border-white/10">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-slate-400 text-sm">
                        &copy; <?php echo date("Y"); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.
                    </p>
                    <div class="flex items-center gap-6 text-xs text-slate-400">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-lock text-primary"></i>
                            Secure & Encrypted
                        </span>
                        <span class="flex items-center gap-2">
                            <i class="fas fa-user-shield text-primary"></i>
                            POPIA Compliant
                        </span>
                        <span class="flex items-center gap-2">
                            <i class="fas fa-certificate text-primary"></i>
                            PSIRA Licensed
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // JavaScript for the client portal mobile menu
        document.addEventListener('DOMContentLoaded', function() {
            const menuButton = document.getElementById('client-mobile-menu-button');
            const mobileMenu = document.getElementById('client-mobile-menu');

            if (menuButton && mobileMenu) {
                menuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html>