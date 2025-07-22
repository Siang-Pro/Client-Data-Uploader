<?php
// 設定時區為台灣時區
date_default_timezone_set('Asia/Taipei');

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'file_upload';

// 確保使用 UTF-8 編碼
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

// 設置資料庫連接編碼為 utf8mb4，支援完整的 Unicode 字符集（包括表情符號）
$conn->set_charset("utf8mb4");

// 設定 PHP 內部字符編碼
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// 設定資料庫連線的時區 (使用會話層級，不需要特殊權限)
$conn->query("SET time_zone = '+08:00'"); 
