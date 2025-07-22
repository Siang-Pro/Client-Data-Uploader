<?php
session_start();
require_once 'config.php';

// 檢查管理員是否已登入
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 確定是哪個表單被提交了
    if (isset($_POST['update_settings'])) {
        // 更新網站設定
        $siteName = $_POST['site_name'] ?? '';
        $contactEmail = $_POST['contact_email'] ?? '';
        $contactPhone = $_POST['contact_phone'] ?? '';
        $contactLine = $_POST['contact_line'] ?? '';
        $contactUrl = $_POST['contact_url'] ?? '';
        $maxFileSize = $_POST['max_file_size'] ?? '10';
        $allowedFileTypes = $_POST['allowed_file_types'] ?? '';
        
        // 更新每個設定
        $settings = [
            'site_name' => $siteName,
            'contact_email' => $contactEmail,
            'contact_phone' => $contactPhone,
            'contact_line' => $contactLine,
            'contact_url' => $contactUrl,
            'max_file_size' => $maxFileSize,
            'allowed_file_types' => $allowedFileTypes
        ];
        
        foreach ($settings as $name => $value) {
            $sql = "INSERT INTO system_settings (setting_name, setting_value) 
                   VALUES (?, ?) 
                   ON DUPLICATE KEY UPDATE setting_value = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $name, $value, $value);
            $stmt->execute();
        }
        
        $settingsUpdated = true;
    } elseif (isset($_POST['update_password'])) {
        // 更新管理員密碼
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // 確認新密碼和確認密碼是否匹配
        if ($newPassword !== $confirmPassword) {
            $passwordError = "新密碼與確認密碼不符";
        } else {
            // 檢查當前密碼是否正確
            $sql = "SELECT password FROM admin_users WHERE username = 'admin'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            
            if (password_verify($currentPassword, $row['password'])) {
                // 更新密碼
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $sql = "UPDATE admin_users SET password = ? WHERE username = 'admin'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $hashedPassword);
                $stmt->execute();
                
                $passwordUpdated = true;
            } else {
                $passwordError = "當前密碼不正確";
            }
        }
    }
}

// 獲取當前設定
$sql = "SELECT * FROM system_settings";
$result = $conn->query($sql);
$settings = [];

while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_name']] = $row['setting_value'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>系統設定</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .settings-container {
            max-width: 850px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .settings-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .settings-header h2 {
            margin: 0;
            color: #2c3e50;
        }
        
        .nav-links {
            display: flex;
            gap: 10px;
        }
        
        .nav-links a {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            background-color: #f8f9fa;
        }
        
        .nav-links a:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }
        
        .settings-section {
            margin-bottom: 35px;
            padding: 22px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
        }
        
        .settings-section:nth-child(1) {
            border-left-color: #007bff;
        }
        
        .settings-section:nth-child(2) {
            border-left-color: #dc3545;
        }
        
        .settings-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #343a40;
            font-size: 18px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #ddd;
        }
        
        .form-row {
            margin-bottom: 20px;
        }
        
        .form-row label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 15px;
        }
        
        .form-row input[type="text"],
        .form-row input[type="email"],
        .form-row input[type="password"],
        .form-row input[type="number"],
        .form-row textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 15px;
            transition: border-color 0.2s;
        }
        
        .form-row input:focus,
        .form-row textarea:focus {
            outline: none;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        
        .form-row textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        button[type="submit"] {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-block;
            margin-top: 10px;
        }
        
        button[type="submit"]:hover {
            background-color: #218838;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            border-left: 4px solid #28a745;
        }
        
        .success-message:before {
            content: '✓';
            font-weight: bold;
            margin-right: 10px;
            font-size: 20px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            border-left: 4px solid #dc3545;
        }
        
        .error-message:before {
            content: '✗';
            font-weight: bold;
            margin-right: 10px;
            font-size: 20px;
        }
        
        .setting-group {
            margin-bottom: 25px;
        }
        
        .setting-group h4 {
            color: #495057;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .input-hint {
            color: #6c757d;
            font-size: 13px;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .settings-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .nav-links {
                width: 100%;
                justify-content: space-between;
            }
            
            .settings-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <div class="settings-header">
            <h2>系統設定</h2>
            
            <div class="nav-links">
                <a href="admin_dashboard.php">返回管理後台</a>
            </div>
        </div>
        
        <?php if (isset($settingsUpdated) && $settingsUpdated): ?>
            <div class="success-message">設定已更新成功！系統變更已立即生效。</div>
        <?php endif; ?>
        
        <?php if (isset($passwordUpdated) && $passwordUpdated): ?>
            <div class="success-message">密碼已更新成功！下次登入時請使用新密碼。</div>
        <?php endif; ?>
        
        <?php if (isset($passwordError)): ?>
            <div class="error-message"><?php echo $passwordError; ?></div>
        <?php endif; ?>
        
        <div class="settings-section">
            <h3>系統基本設定</h3>
            <form method="POST">
                <div class="setting-group">
                    <h4>網站資訊</h4>
                    <div class="form-row">
                        <label for="site_name">網站名稱</label>
                        <input type="text" name="site_name" id="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                        <div class="input-hint">顯示於網頁標題和頁面頂部</div>
                    </div>
                </div>
                
                <div class="setting-group">
                    <h4>聯絡資訊</h4>
                    <div class="form-row">
                        <label for="contact_email">電子郵件</label>
                        <input type="email" name="contact_email" id="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                        <div class="input-hint">用於接收通知和客戶聯絡</div>
                    </div>
                    
                    <div class="form-row">
                        <label for="contact_phone">電話號碼</label>
                        <input type="text" name="contact_phone" id="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <label for="contact_line">LINE ID</label>
                        <input type="text" name="contact_line" id="contact_line" value="<?php echo htmlspecialchars($settings['contact_line'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <label for="contact_url">網站連結</label>
                        <input type="text" name="contact_url" id="contact_url" value="<?php echo htmlspecialchars($settings['contact_url'] ?? ''); ?>">
                        <div class="input-hint">格式：https://www.example.com</div>
                    </div>
                </div>
                
                <div class="setting-group">
                    <h4>上傳設定</h4>
                    <div class="form-row">
                        <label for="max_file_size">最大檔案大小（MB）</label>
                        <input type="number" name="max_file_size" id="max_file_size" value="<?php echo htmlspecialchars($settings['max_file_size'] ?? '10'); ?>" min="1" max="100" required>
                        <div class="input-hint">建議值：10-50 MB，視伺服器配置而定</div>
                    </div>
                    
                    <div class="form-row">
                        <label for="allowed_file_types">允許的檔案類型（以逗號分隔）</label>
                        <textarea name="allowed_file_types" id="allowed_file_types" placeholder="例如：jpg,png,pdf,docx"><?php echo htmlspecialchars($settings['allowed_file_types'] ?? ''); ?></textarea>
                        <div class="input-hint">留空表示允許所有檔案類型（不建議）</div>
                    </div>
                </div>
                
                <button type="submit" name="update_settings">儲存設定</button>
            </form>
        </div>
        
        <div class="settings-section">
            <h3>管理員帳號設定</h3>
            <form method="POST">
                <div class="form-row">
                    <label for="current_password">當前密碼</label>
                    <input type="password" name="current_password" id="current_password" required>
                </div>
                
                <div class="form-row">
                    <label for="new_password">新密碼</label>
                    <input type="password" name="new_password" id="new_password" required>
                    <div class="input-hint">建議使用至少8位英數字混合密碼，包含大小寫和特殊符號</div>
                </div>
                
                <div class="form-row">
                    <label for="confirm_password">確認新密碼</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
                
                <button type="submit" name="update_password">更改密碼</button>
            </form>
        </div>
        
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> <a href="https://siang.pro" target="_blank">Siang.Pro</a>. All rights reserved.</p>
        </footer>
    </div>
</body>
</html> 