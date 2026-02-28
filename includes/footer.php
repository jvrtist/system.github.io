<?php
/**
 * ISS Investigations - Unified Footer
 * Handles layout closing, site-map navigation, and notification logic.
 */
?>
    </main>

    <footer class="bg-slate-900 text-slate-400 border-t border-white/5">
        <div class="bg-black/30 py-6 border-t border-white/5">
            <div class="container mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-4 text-[10px] font-bold uppercase tracking-widest">
                <div class="flex items-center gap-3">
                    <img src="images/logo.png" alt="ISS Investigations Lion Logo" class="h-6 w-6 object-contain" title="ISS Investigations">
                    <div class="text-slate-500">
                        &copy; <?php echo date("Y"); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.
                        <?php if (defined('APP_VERSION')): ?>
                            <span class="mx-2 text-slate-800">|</span> <span class="text-slate-600">Secure Node v<?php echo APP_VERSION; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-2 text-slate-500">
                        <i class="fas fa-lock text-[8px]"></i> SSL Encrypted
                    </span>
                    <span class="flex items-center gap-2 text-slate-500">
                        <i class="fas fa-server text-[8px]"></i> RSA Protected
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <?php if (is_logged_in()): ?>
        </div> <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /**
             * Notification Toast Controller
             */
            const dismissToast = (element) => {
                if (!element) return;
                element.classList.add('translate-y-2', 'opacity-0');
                setTimeout(() => {
                    element.style.display = 'none';
                }, 500);
            };

            // Auto-dismiss logic for existing toasts
            ['toast-success', 'toast-error'].forEach(id => {
                const toast = document.getElementById(id);
                if (toast) {
                    setTimeout(() => dismissToast(toast), 6000);
                }
            });

            // Manual dismiss listener
            document.querySelectorAll('[data-dismiss-target]').forEach(button => {
                button.addEventListener('click', function() {
                    const target = document.querySelector(this.getAttribute('data-dismiss-target'));
                    dismissToast(target);
                });
            });

            /**
             * Smooth scroll for anchor links
             */
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if(target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
        });

        // TinyMCE initialization for post content
        tinymce.init({
            selector: '#content',
            height: 400,
            menubar: false,
            plugins: [
      'advlist', 'autolink', 'link', 'image', 'lists', 'charmap', 'preview', 'anchor', 'pagebreak',
      'searchreplace', 'wordcount', 'visualblocks', 'visualchars', 'code', 'fullscreen', 'insertdatetime',
      'media', 'table', 'emoticons', 'help'
    ],
    toolbar: 'undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | ' +
      'bullist numlist outdent indent | link image | print preview media fullscreen | ' +
      'forecolor backcolor emoticons | help',
                  content_style: 'body { font-family: Inter, sans-serif; font-size: 14px; color: #e2e8f0; background: #1e293b; }',
            skin: 'oxide-dark',
            content_css: 'dark'
        });
    </script>

</body>
</html>