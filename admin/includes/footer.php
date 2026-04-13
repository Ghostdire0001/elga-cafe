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
