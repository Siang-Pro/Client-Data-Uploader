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
        // 設置適當的標頭
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $row['original_name'] . '"');
        header('Content-Length: ' . filesize($filePath));
        
        // 輸出檔案內容
        readfile($filePath);
        exit;
    }
}

die('檔案不存在'); 