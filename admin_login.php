<?php
session_start();
require_once 'config.php';

// 處理登出
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM admin_users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_logged_in'] = true;
            header('Location: admin_dashboard.php');
            exit;
        }
    }
    $error = "登入失敗";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>管理員登入</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>管理員登入</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="帳號" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="密碼" required>
            </div>
            <button type="submit">登入</button>
        </form>
        
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> <a href="https://siang.pro" target="_blank">Siang.Pro</a>. All rights reserved.</p>
        </footer>
    </div>
</body>
</html> 