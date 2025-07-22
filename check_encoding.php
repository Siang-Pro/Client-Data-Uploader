<?php
// 設置字符編碼
header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

echo "<html><head><meta charset='UTF-8'><title>字符編碼檢查</title></head><body>";
echo "<h1>字符編碼檢查工具</h1>";

// 檢查PHP版本和配置
echo "<h2>PHP 環境設定:</h2>";
echo "<ul>";
echo "<li>PHP 版本: " . PHP_VERSION . "</li>";
echo "<li>default_charset: " . ini_get('default_charset') . "</li>";
echo "<li>mbstring.internal_encoding: " . ini_get('mbstring.internal_encoding') . "</li>";
echo "<li>mbstring.language: " . ini_get('mbstring.language') . "</li>";
echo "</ul>";

// 檢查資料庫編碼
echo "<h2>資料庫設定:</h2>";
echo "<ul>";
$result = $conn->query("SHOW VARIABLES LIKE 'character_set%'");
while ($row = $result->fetch_assoc()) {
    echo "<li>" . $row['Variable_name'] . ": " . $row['Value'] . "</li>";
}
echo "</ul>";

// 檢查表編碼
echo "<h2>資料表設定:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>表名</th><th>字符集</th><th>排序規則</th></tr>";

$result = $conn->query("SHOW TABLE STATUS");
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Name'] . "</td>";
    echo "<td>" . $row['Collation'] . "</td>";
    $collationParts = explode('_', $row['Collation']);
    echo "<td>" . $collationParts[0] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 檢查欄位編碼
echo "<h2>欄位設定:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>表名</th><th>欄位名</th><th>類型</th><th>字符集</th><th>排序規則</th></tr>";

$result = $conn->query("SHOW TABLES");
while ($tableRow = $result->fetch_row()) {
    $tableName = $tableRow[0];
    $columnsResult = $conn->query("SHOW FULL COLUMNS FROM `$tableName`");
    
    while ($columnRow = $columnsResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $tableName . "</td>";
        echo "<td>" . $columnRow['Field'] . "</td>";
        echo "<td>" . $columnRow['Type'] . "</td>";
        echo "<td>" . ($columnRow['Collation'] ? $columnRow['Collation'] : 'N/A') . "</td>";
        echo "<td>" . ($columnRow['Collation'] ? explode('_', $columnRow['Collation'])[0] : 'N/A') . "</td>";
        echo "</tr>";
    }
}
echo "</table>";

// 檢查中文樣本
echo "<h2>中文樣本測試:</h2>";
$testString = "測試中文字符 - 檔案名稱.txt";
echo "<p>原始字符串: " . $testString . "</p>";

// 檢查各種編碼下的字符串表示
$encodings = array('UTF-8', 'BIG5', 'GBK', 'ISO-8859-1');
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>編碼</th><th>轉換後</th><th>HEX 值</th></tr>";

foreach ($encodings as $encoding) {
    $converted = mb_convert_encoding($testString, $encoding, 'UTF-8');
    $backToUtf8 = mb_convert_encoding($converted, 'UTF-8', $encoding);
    $hex = bin2hex($converted);
    
    echo "<tr>";
    echo "<td>" . $encoding . "</td>";
    echo "<td>" . $backToUtf8 . "</td>";
    echo "<td>" . $hex . "</td>";
    echo "</tr>";
}
echo "</table>";

// 顯示已上傳的文件及編碼信息
echo "<h2>已上傳檔案的檔名編碼:</h2>";

$sql = "SELECT link_id, original_name, file_name FROM uploaded_files ORDER BY uploaded_at DESC LIMIT 20";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>原始檔名</th><th>UTF-8 檢查</th><th>HEX 值</th><th>儲存檔名</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['link_id'] . "</td>";
        echo "<td>" . $row['original_name'] . "</td>";
        
        // 檢查原始檔名是否為有效的 UTF-8
        $isUtf8 = mb_check_encoding($row['original_name'], 'UTF-8') ? '是' : '否';
        echo "<td>" . $isUtf8 . "</td>";
        
        // 顯示 HEX 值
        echo "<td>" . bin2hex($row['original_name']) . "</td>";
        
        echo "<td>" . $row['file_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>尚未有已上傳的檔案</p>";
}

echo "</body></html>"; 