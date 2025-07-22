<?php
session_start();
require_once 'config.php';

// 檢查管理員登入狀態
if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['success' => false, 'message' => '未授權的操作']));
}

// 獲取 POST 數據
$data = json_decode(file_get_contents('php://input'), true);
$linkId = $data['linkId'] ?? '';

if (empty($linkId)) {
    die(json_encode(['success' => false, 'message' => '參數錯誤']));
}

// 切換連結狀態
$sql = "UPDATE upload_links SET is_active = NOT is_active WHERE link_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $linkId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '操作失敗']);
} 