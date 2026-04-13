            </div>
            <!-- Brand Footer with Icon -->
            <div class="border-t border-gray-200 mt-8 pt-6 pb-4 text-center">
                <div class="flex items-center justify-center gap-2 text-gray-400 text-sm">
                    <i class="fas fa-mug-hot text-orange-custom"></i>
                    <span>Elga Cafe Admin Panel</span>
                    <span class="mx-2">•</span>
                    <span>v1.0</span>
                </div>
                <div class="flex items-center justify-center gap-3 mt-2">
                    <a href="../index.php" target="_blank" class="text-xs text-gray-400 hover:text-orange-custom transition">
                        <i class="fas fa-external-link-alt mr-1"></i> View Website
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="https://cloudinary.com" target="_blank" class="text-xs text-gray-400 hover:text-orange-custom transition">
                        <i class="fas fa-cloud-upload-alt mr-1"></i> Cloudinary
                    </a>
                    <span class="text-gray-300">|</span>
                    <span class="text-xs text-gray-400">
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
</body>
</html>
