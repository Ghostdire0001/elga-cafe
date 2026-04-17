            </div>
            <!-- Footer -->
            <div class="border-t border-gray-200 mt-8 pt-6 pb-4 text-center" style="border-color: var(--border-color);">
                <div class="footer-brand">
                    <i class="fas fa-mug-hot text-orange-custom"></i>
                    <span><?php echo t('site_name'); ?> <?php echo t('admin_panel'); ?></span>
                    <span class="mx-2">•</span>
                    <span><?php echo t('version'); ?> 1.0</span>
                </div>
                <div class="footer-links">
                    <a href="../../index.php" target="_blank">
                        <i class="fas fa-external-link-alt mr-1"></i> <?php echo t('view_website'); ?>
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="https://cloudinary.com" target="_blank">
                        <i class="fas fa-cloud-upload-alt mr-1"></i> Cloudinary
                    </a>
                    <span class="text-gray-300">|</span>
                    <span>
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
        if(window.innerWidth <= 767) {
            document.querySelectorAll('.sidebar-nav a').forEach(link => {
                link.addEventListener('click', () => {
                    if(window.innerWidth <= 767) toggleSidebar();
                });
            });
        }
        window.addEventListener('resize', function() {
            if(window.innerWidth > 767) {
                document.getElementById('sidebar')?.classList.remove('active');
                document.querySelector('.overlay')?.classList.remove('active');
            }
        });
    </script>
    <?php echo getThemeScript(); ?>
    <?php echo getLanguageScript(); ?>
</body>
</html>
