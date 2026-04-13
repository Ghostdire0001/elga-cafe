<?php
// Check if user is logged in (to be included in all admin pages)
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo $page_title ?? 'Admin Dashboard'; ?> - Elga Cafe Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Mobile sidebar styles */
        .sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            width: 280px;
            height: 100vh;
            transition: left 0.3s ease-in-out;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar.active {
            left: 0;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .overlay.active {
            display: block;
        }
        
        .menu-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #F97316;
            color: white;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            display: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        /* Desktop styles */
        @media (min-width: 768px) {
            .sidebar {
                position: relative;
                left: 0;
                width: 260px;
            }
            
            .menu-toggle {
                display: none !important;
            }
            
            .overlay {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Mobile styles */
        @media (max-width: 767px) {
            .menu-toggle {
                display: block;
            }
            
            .main-content {
                width: 100%;
            }
            
            body {
                padding-top: 70px;
            }
        }
        
        /* Sidebar styling */
        .sidebar {
            background-color: #1f2937;
            color: white;
        }
        
        .sidebar a {
            transition: all 0.3s ease;
        }
        
        .sidebar a:hover {
            background-color: #374151;
            padding-left: 1.5rem;
        }
        
        .sidebar a.active {
            background-color: #F97316;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Mobile Menu Toggle Button -->
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars text-xl"></i>
    </button>
    
    <!-- Overlay -->
    <div class="overlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="p-4 border-b border-gray-700">
            <h2 class="text-xl font-bold">Elga Cafe Admin</h2>
            <p class="text-sm text-gray-400 mt-1">Welcome, <?php echo $_SESSION['username']; ?></p>
        </div>
        <nav class="mt-4">
            <a href="index.php" class="flex items-center py-3 px-4 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-orange-custom' : 'hover:bg-gray-700'; ?>">
                <i class="fas fa-tachometer-alt w-5 mr-3"></i> Dashboard
            </a>
            <a href="meals.php" class="flex items-center py-3 px-4 <?php echo basename($_SERVER['PHP_SELF']) == 'meals.php' ? 'bg-orange-custom' : 'hover:bg-gray-700'; ?>">
                <i class="fas fa-utensils w-5 mr-3"></i> Meals
            </a>
            <a href="categories.php" class="flex items-center py-3 px-4 <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'bg-orange-custom' : 'hover:bg-gray-700'; ?>">
                <i class="fas fa-tags w-5 mr-3"></i> Categories
            </a>
            <a href="dietary-labels.php" class="flex items-center py-3 px-4 <?php echo basename($_SERVER['PHP_SELF']) == 'dietary-labels.php' ? 'bg-orange-custom' : 'hover:bg-gray-700'; ?>">
                <i class="fas fa-leaf w-5 mr-3"></i> Dietary Labels
            </a>
            <a href="discounts.php" class="flex items-center py-3 px-4 <?php echo basename($_SERVER['PHP_SELF']) == 'discounts.php' ? 'bg-orange-custom' : 'hover:bg-gray-700'; ?>">
                <i class="fas fa-tag w-5 mr-3"></i> Discounts
            </a>
            <div class="border-t border-gray-700 mt-4 pt-4">
                <a href="logout.php" class="flex items-center py-3 px-4 hover:bg-gray-700 text-red-400">
                    <i class="fas fa-sign-out-alt w-5 mr-3"></i> Logout
                </a>
            </div>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" style="transition: margin-left 0.3s ease;">
        <div class="p-4 md:p-8">
            <div class="md:hidden mb-4">
                <!-- Spacer for mobile menu button -->
                <div class="h-10"></div>
            </div>
            
            <script>
                function toggleSidebar() {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.querySelector('.overlay');
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                }
                
                // Close sidebar when clicking a link on mobile
                if(window.innerWidth <= 767) {
                    document.querySelectorAll('.sidebar a').forEach(link => {
                        link.addEventListener('click', () => {
                            if(window.innerWidth <= 767) {
                                toggleSidebar();
                            }
                        });
                    });
                }
                
                // Handle window resize
                window.addEventListener('resize', function() {
                    if(window.innerWidth > 767) {
                        const sidebar = document.getElementById('sidebar');
                        const overlay = document.querySelector('.overlay');
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                    }
                });
            </script>
