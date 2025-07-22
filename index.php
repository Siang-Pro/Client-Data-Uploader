<?php
// 設置字符編碼
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

$error = null;

// 獲取系統設定
$sql = "SELECT setting_name, setting_value FROM system_settings";
$result = $conn->query($sql);
$settings = [];

while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_name']] = $row['setting_value'];
}

$siteName = $settings['site_name'] ?? 'Siang.Pro資料上傳系統';
$contactUrl = $settings['contact_url'] ?? 'https://www.siang.pro';

// 如果有提交表單
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_id'])) {
    $linkId = $_POST['link_id'];
    
    // 檢查此 ID 是否存在且啟用
    $sql = "SELECT * FROM upload_links WHERE link_id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $linkId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // ID 有效，轉向上傳頁面
        header("Location: upload.php?id=" . $linkId);
        exit;
    } else {
        $error = "您輸入的連結 ID 無效或已關閉";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($siteName); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="upload-container">
        <h2><?php echo htmlspecialchars($siteName); ?></h2>
        <p class="notice">如需申請上傳服務，請聯繫：<a href="<?php echo htmlspecialchars($contactUrl); ?>" target="_blank"><?php echo htmlspecialchars($contactUrl); ?></a></p>
        
        <div class="link-entry-form">
            <h3>輸入上傳連結 ID</h3>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" name="link_id" placeholder="請輸入連結 ID" required>
                </div>
                <button type="submit">進入上傳頁面</button>
            </form>
            
            <div class="quick-tips">
                <p>提示：您也可以直接使用以下格式的網址進行訪問：</p>
                <code><?php echo $_SERVER['HTTP_HOST']; ?>/upload.php?id=您的連結ID</code>
            </div>
        </div>
        
        <div class="admin-link">
            <a href="admin_login.php">管理員登入</a>
        </div>
        
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> <a href="https://siang.pro" target="_blank">Siang.Pro</a>. All rights reserved.</p>
        </footer>
    </div>
</body>
</html> 