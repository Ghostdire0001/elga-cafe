            </div>
            <div class="border-t mt-8 pt-6 pb-4 text-center" style="border-color: var(--border-color);">
                <div class="footer-brand">
                    <i class="fas fa-mug-hot text-orange-custom"></i>
                    <span><?php echo t('site_name'); ?> <?php echo t('admin_panel'); ?></span>
                    <span class="mx-2">•</span>
                    <span><?php echo t('version'); ?> 1.0</span>
                </div>
                <div class="footer-links">
                    <a href="../../index.php" target="_blank" style="color: var(--text-secondary);">
                        <i class="fas fa-external-link-alt mr-1"></i> <?php echo t('view_website'); ?>
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="https://cloudinary.com" target="_blank" style="color: var(--text-secondary);">
                        <i class="fas fa-cloud-upload-alt mr-1"></i> Cloudinary
                    </a>
                    <span class="text-gray-300">|</span>
                    <span style="color: var(--text-secondary);">
                        <i class="far fa-clock mr-1"></i> <?php echo date('Y'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
        
        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }
        
        // Theme toggle
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                document.cookie = "user_theme=" + newTheme + "; path=/; max-age=" + (30 * 24 * 60 * 60);
                
                const icon = themeToggle.querySelector('i');
                if (newTheme === 'dark') {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
                
                // Update URL without reload
                const url = new URL(window.location.href);
                url.searchParams.set('theme', newTheme);
                window.history.pushState({}, '', url);
            });
        }
        
        // Close sidebar on mobile when clicking a link
        if (window.innerWidth <= 767) {
            document.querySelectorAll('.sidebar-nav a').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 767) toggleSidebar();
                });
            });
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 767) {
                document.getElementById('sidebar')?.classList.remove('active');
                document.querySelector('.overlay')?.classList.remove('active');
            }
        });
        
        // Initialize theme from localStorage
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</body>
</html>
