<?php
session_start();
require_once 'config.php';

// 檢查管理員登入
if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['success' => false, 'message' => '未授權的操作']));
}

// 獲取 POST 數據
$data = json_decode(file_get_contents('php://input'), true);
$fileName = $data['file_name'] ?? '';
$linkId = $data['link_id'] ?? '';

if (empty($fileName) || empty($linkId)) {
    die(json_encode(['success' => false, 'message' => '參數錯誤']));
}

// 開始事務
$conn->begin_transaction();

try {
    // 獲取檔案資訊
    $sql = "SELECT * FROM uploaded_files WHERE file_name = ? AND link_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fileName, $linkId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($file = $result->fetch_assoc()) {
        // 刪除實際檔案
        $filePath = __DIR__ . '/uploads/' . $linkId . '/' . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // 從資料庫刪除記錄
        $sql = "DELETE FROM uploaded_files WHERE file_name = ? AND link_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $fileName, $linkId);
        $stmt->execute();
        
        // 提交事務
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => '檔案已成功刪除']);
    } else {
        throw new Exception("找不到指定檔案");
    }
} catch (Exception $e) {
    // 回滾事務
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => '刪除失敗：' . $e->getMessage()]);
}
?> 