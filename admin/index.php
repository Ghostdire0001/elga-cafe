<?php
session_start();

// If already logged in as admin, redirect to dashboard
if(isset($_SESSION['user_id']) && in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: admin/dashboard.php');
    exit();
}

require_once '../includes/config.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role IN ('admin', 'manager') AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        header('Location: admin/dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Elga Cafe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-orange-custom { background-color: #F97316; }
        .hover\:bg-orange-custom:hover { background-color: #F97316; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl p-6 md:p-8 w-full max-w-md">
            <div class="text-center mb-8">
                <div class="bg-orange-custom text-white w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-utensils text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Elga Cafe Admin</h2>
                <p class="text-gray-600 mt-2">Please login to access the dashboard</p>
            </div>
            
            <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-user mr-2"></i> Username
                    </label>
                    <input type="text" name="username" required autofocus
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom focus:ring-1 focus:ring-orange-custom">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-lock mr-2"></i> Password
                    </label>
                    <input type="password" name="password" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom focus:ring-1 focus:ring-orange-custom">
                </div>
                
                <button type="submit" 
                        class="w-full bg-orange-custom text-white font-bold py-2 px-4 rounded-lg hover:bg-orange-600 transition duration-300">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login
                </button>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-500">
                <i class="fas fa-shield-alt mr-1"></i> Secure Admin Area
            </div>
        </div>
    </div>
</body>
</html>
