<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// 建立新連結 - 只在表單提交時執行
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_name'])) {
    $clientName = $_POST['client_name'];
    
    // 生成新的短連結ID (s + 8位隨機數字)
    do {
        $randomNum = str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $linkId = 's' . $randomNum;
        
        // 檢查ID是否已存在
        $checkSql = "SELECT COUNT(*) as count FROM upload_links WHERE link_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $linkId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $exists = $result->fetch_assoc()['count'] > 0;
    } while ($exists);
    
    $sql = "INSERT INTO upload_links (client_name, link_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $clientName, $linkId);
    $stmt->execute();
    
    header('Location: admin_dashboard.php');
    exit;
}

// 獲取所有連結和檔案
$sql = "SELECT 
            l.*,
            COUNT(f.id) as file_count,
            GROUP_CONCAT(
                CONCAT(f.original_name, '|', f.file_name, '|', f.uploaded_at)
                SEPARATOR ';;'
            ) as files
        FROM upload_links l
        LEFT JOIN uploaded_files f ON l.link_id = f.link_id
        GROUP BY l.id
        ORDER BY l.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>檔案上傳管理系統</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="admin-container">
        <h2>建立上傳連結</h2>
        <div class="admin-nav">
            <a href="settings.php" class="settings-link">系統設定</a>
            <a href="admin_login.php?logout=1" class="logout-link">登出</a>
        </div>
        <form method="POST" class="create-link-form">
            <input type="text" name="client_name" placeholder="客戶名稱" required>
            <button type="submit">建立連結</button>
        </form>

        <h3>已建立的連結</h3>
        <table class="links-table">
            <tr>
                <th>客戶名稱</th>
                <th>上傳連結 ID</th>
                <th>狀態</th>
                <th>已上傳檔案</th>
                <th>操作</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                <td>
                    <?php if ($row['is_active']): ?>
                        <a href="upload.php?id=<?php echo $row['link_id']; ?>" target="_blank">
                            <?php echo $row['link_id']; ?>
                        </a>
                    <?php else: ?>
                        <span class="inactive-link">連結已關閉</span>
                    <?php endif; ?>
                </td>
                <td class="status-cell" data-link-id="<?php echo $row['link_id']; ?>">
                    <?php echo $row['is_active'] ? '啟用中' : '已關閉'; ?>
                </td>
                <td>
                    <?php if ($row['file_count'] > 0): ?>
                        <button onclick="showFiles('<?php echo $row['link_id']; ?>')" class="view-files-btn">
                            查看檔案 (<?php echo $row['file_count']; ?>)
                        </button>
                    <?php else: ?>
                        <span class="no-files">尚未上傳檔案</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button onclick="toggleLink('<?php echo $row['link_id']; ?>')" 
                            class="toggle-btn <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                        <?php echo $row['is_active'] ? '關閉' : '開啟'; ?>
                    </button>
                    <button onclick="deleteLink('<?php echo $row['link_id']; ?>', '<?php echo addslashes(htmlspecialchars($row['client_name'])); ?>')" 
                            class="delete-link-btn">
                        刪除
                    </button>
                </td>
            </tr>
            <?php if ($row['file_count'] > 0): ?>
            <tr class="files-row" id="files-<?php echo $row['link_id']; ?>" style="display: none;">
                <td colspan="5" class="files-list">
                    <?php if ($row['files']): ?>
                        <div class="files-grid">
                            <?php
                            $files = explode(';;', $row['files']);
                            foreach ($files as $file) {
                                list($originalName, $fileName, $uploadedAt) = explode('|', $file);
                                $uploadTime = date('Y-m-d H:i:s', strtotime($uploadedAt));
                                echo "<div class='file-item'>";
                                echo "<div class='file-details'>";
                                echo "<div class='file-name'>" . htmlspecialchars($originalName) . "</div>";
                                echo "<div class='file-time'>上傳時間：{$uploadTime}</div>";
                                echo "</div>";
                                echo "<div class='file-actions'>";
                                echo "<a href='download.php?file=" . urlencode($fileName) . "' class='download-btn'>下載</a>";
                                echo "<button onclick='deleteFile(\"" . $fileName . "\", \"" . $row['link_id'] . "\", \"" . addslashes(htmlspecialchars($originalName)) . "\")' class='delete-file-btn'>刪除</button>";
                                echo "</div>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php endwhile; ?>
        </table>
    </div>

    <script>
    function showFiles(linkId) {
        const filesRow = document.getElementById(`files-${linkId}`);
        const allFilesRows = document.querySelectorAll('.files-row');
        
        // 關閉其他打開的檔案列表
        allFilesRows.forEach(row => {
            if (row.id !== `files-${linkId}`) {
                row.style.display = 'none';
            }
        });
        
        // 切換當前檔案列表的顯示狀態
        filesRow.style.display = filesRow.style.display === 'none' ? 'table-row' : 'none';
    }
    
    function deleteFile(fileName, linkId, originalName) {
        if (confirm(`確定要刪除檔案 "${originalName}" 嗎？此操作無法復原。`)) {
            fetch('delete_file.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    file_name: fileName,
                    link_id: linkId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('檔案已成功刪除！');
                    // 重新載入頁面以更新檔案列表
                    window.location.reload();
                } else {
                    alert('刪除失敗：' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('操作失敗，請稍後再試');
            });
        }
    }
    
    function deleteLink(linkId, clientName) {
        if (confirm(`確定要刪除 "${clientName}" 的連結嗎？此操作將一併刪除該連結下的所有檔案，且無法復原。`)) {
            fetch('delete_link.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    link_id: linkId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('連結及相關檔案已成功刪除！');
                    // 重新載入頁面以更新連結列表
                    window.location.reload();
                } else {
                    alert('刪除失敗：' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('操作失敗，請稍後再試');
            });
        }
    }
    </script>
    <script src="js/admin.js"></script>
    
    <footer class="footer admin-footer">
        <p>&copy; <?php echo date('Y'); ?> <a href="https://siang.pro" target="_blank">Siang.Pro</a>. All rights reserved.</p>
    </footer>
</body>
</html> 