<?php
session_start();
require_once 'config.php';

// 檢查管理員登入
if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['success' => false, 'message' => '未授權的操作']));
}

// 獲取 POST 數據
$data = json_decode(file_get_contents('php://input'), true);
$linkId = $data['link_id'] ?? '';

if (empty($linkId)) {
    die(json_encode(['success' => false, 'message' => '參數錯誤']));
}

// 開始事務
$conn->begin_transaction();

try {
    // 獲取連結資訊
    $sql = "SELECT * FROM upload_links WHERE link_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $linkId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($link = $result->fetch_assoc()) {
        // 獲取該連結下的所有檔案
        $sql = "SELECT * FROM uploaded_files WHERE link_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $linkId);
        $stmt->execute();
        $files = $stmt->get_result();
        
        // 刪除所有相關檔案
        while ($file = $files->fetch_assoc()) {
            $filePath = __DIR__ . '/uploads/' . $linkId . '/' . $file['file_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // 嘗試刪除目錄
        $dirPath = __DIR__ . '/uploads/' . $linkId;
        if (is_dir($dirPath)) {
            rmdir($dirPath);
        }
        
        // 從資料庫刪除檔案記錄
        $sql = "DELETE FROM uploaded_files WHERE link_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $linkId);
        $stmt->execute();
        
        // 從資料庫刪除連結
        $sql = "DELETE FROM upload_links WHERE link_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $linkId);
        $stmt->execute();
        
        // 提交事務
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => '連結及相關檔案已成功刪除']);
    } else {
        throw new Exception("找不到指定連結");
    }
} catch (Exception $e) {
    // 回滾事務
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => '刪除失敗：' . $e->getMessage()]);
}
?> 