<?php
session_start();
require_once 'config.php';

// 檢查管理員登入
if (!isset($_SESSION['admin_logged_in'])) {
    die('未授權的訪問');
}

$fileName = $_GET['file'] ?? '';
if (empty($fileName)) {
    die('檔案不存在');
}

// 檢查檔案是否存在於資料庫
$sql = "SELECT f.original_name, f.file_name, f.link_id 
        FROM uploaded_files f 
        WHERE f.file_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $fileName);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $filePath = __DIR__ . '/uploads/' . $row['link_id'] . '/' . $fileName;
    
    if (file_exists($filePath)) {
        // 獲取檔案資訊
        $fileSize = filesize($filePath);
        $originalName = $row['original_name'];
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        
        // 清除輸出緩衝區
        ob_clean();
        
        // 設置適當的標頭
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . rawurlencode($originalName) . '"; filename*=UTF-8\'\'' . rawurlencode($originalName));
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: max-age=0');
        
        // 關閉輸出緩衝區並輸出檔案內容
        flush();
        readfile($filePath);
        exit;
    }
}

die('檔案不存在'); 