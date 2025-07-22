<?php
require_once 'config.php';

// 獲取系統設定
$sql = "SELECT setting_name, setting_value FROM system_settings";
$result = $conn->query($sql);
$settings = [];

while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_name']] = $row['setting_value'];
}

$siteName = $settings['site_name'] ?? 'Siang.Pro資料上傳系統';
$contactUrl = $settings['contact_url'] ?? 'https://www.siang.pro';

// 確保基礎的 uploads 目錄存在
$baseUploadDir = __DIR__ . '/uploads/';
if (!file_exists($baseUploadDir)) {
    mkdir($baseUploadDir, 0777, true);
}

$linkId = $_GET['id'] ?? '';

// 先檢查 ID 是否有效
$sql = "SELECT * FROM upload_links WHERE link_id = ? AND is_active = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $linkId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result->num_rows) {
    die('
        <!DOCTYPE html>
        <html>
        <head>
            <title>' . htmlspecialchars($siteName) . '</title>
            <link rel="stylesheet" href="css/style.css">
        </head>
        <body>
            <div class="upload-container">
                <h2>連結已關閉</h2>
                <p class="notice">此上傳連結不存在或已關閉。如需服務請聯繫：<a href="' . htmlspecialchars($contactUrl) . '" target="_blank">' . htmlspecialchars($contactUrl) . '</a></p>
                
                <footer class="footer">
                    <p>&copy; ' . date('Y') . ' <a href="https://siang.pro" target="_blank">Siang.Pro</a>. All rights reserved.</p>
                </footer>
            </div>
        </body>
        </html>
    ');
}

// ID 有效，才為該 link_id 創建專屬資料夾
$uploadDir = $baseUploadDir . $linkId . '/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 獲取已上傳的檔案列表
$sql = "SELECT original_name, uploaded_at 
        FROM uploaded_files 
        WHERE link_id = ? 
        ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $linkId);
$stmt->execute();
$uploadedFiles = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $uploadFilesData = $_FILES['files'];
    $successCount = 0;
    $errorCount = 0;
    $errorMessages = [];
    
    // 設定預設值
    $maxFileSize = isset($settings['max_file_size']) ? (int)$settings['max_file_size'] : 10; // 預設 10MB
    $maxFileSizeBytes = $maxFileSize * 1024 * 1024;
    
    // 解析允許的檔案類型為陣列
    $allowedFileTypes = [];
    if (isset($settings['allowed_file_types']) && !empty($settings['allowed_file_types'])) {
        $allowedFileTypes = array_map('strtolower', array_map('trim', explode(',', $settings['allowed_file_types'])));
    }
    
    for ($i = 0; $i < count($uploadFilesData['name']); $i++) {
        $originalName = $uploadFilesData['name'][$i];
        $fileSize = $uploadFilesData['size'][$i];
        $fileType = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // 驗證檔案大小
        if ($fileSize > $maxFileSizeBytes) {
            $errorCount++;
            $errorMessages[] = "{$originalName} 檔案太大，超過 {$maxFileSize}MB 限制";
            continue;
        }
        
        // 驗證檔案類型（如果有設定限制）
        if (!empty($allowedFileTypes) && !in_array($fileType, $allowedFileTypes)) {
            $errorCount++;
            $errorMessages[] = "{$originalName} 檔案類型不允許上傳";
            continue;
        }
        
        $fileName = time() . '_' . $originalName;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($uploadFilesData['tmp_name'][$i], $uploadPath)) {
            $sql = "INSERT INTO uploaded_files (link_id, original_name, file_name) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $linkId, $originalName, $fileName);
            $stmt->execute();
            $successCount++;
        } else {
            $errorCount++;
            $errorMessages[] = "{$originalName} 檔案上傳失敗";
        }
    }
    
    if ($successCount > 0) {
        $message = "成功上傳 {$successCount} 個檔案";
        if ($errorCount > 0) {
            $message .= "，{$errorCount} 個檔案上傳失敗";
        }
        
        // 重新獲取最新的上傳檔案列表
        $sql = "SELECT original_name, uploaded_at 
                FROM uploaded_files 
                WHERE link_id = ? 
                ORDER BY uploaded_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $linkId);
        $stmt->execute();
        $uploadedFiles = $stmt->get_result();
    } else {
        $error = "檔案上傳失敗，請稍後再試";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($siteName); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="upload-container">
        <h2><?php echo htmlspecialchars($siteName); ?></h2>
        <p class="notice">僅提供上傳資料使用，如有其他服務需求請與我們聯繫</a></p>
        
        <div class="instructions">
            <h3>使用說明：</h3>
            <ol>
                <li>點擊「新增檔案」按鈕選擇要上傳的檔案</li>
                <li>可以多次點擊「新增檔案」來選擇更多檔案</li>
                <li>如要移除已選擇的檔案，點擊檔案右側的 × 按鈕</li>
                <li>確認檔案無誤後，點擊「上傳所選檔案」開始上傳</li>
            </ol>
        </div>
        
        <!-- 檔案上傳限制說明 -->
        <div class="upload-limits">
            <h3>上傳限制</h3>
            <div class="limits-info">
                <div class="limit-item">
                    <span class="limit-label">檔案大小限制：</span>
                    <span class="limit-value"><?php echo isset($settings['max_file_size']) ? $settings['max_file_size'] : '10'; ?> MB</span>
                </div>
                <div class="limit-item">
                    <span class="limit-label">允許的檔案類型：</span>
                    <span class="limit-value"><?php echo isset($settings['allowed_file_types']) ? $settings['allowed_file_types'] : '所有檔案類型'; ?></span>
                </div>
            </div>
        </div>

        <div id="uploadStatus">
            <?php if (isset($message)): ?>
                <div class="success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessages)): ?>
                <div class="error">
                    <ul>
                        <?php foreach($errorMessages as $errMsg): ?>
                            <li><?php echo htmlspecialchars($errMsg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
            <div class="file-input-container">
                <div class="file-input-wrapper">
                    <input type="file" name="files[]" id="fileInput" multiple>
                    <label for="fileInput" class="file-input-label">
                        <span class="button-like">新增檔案</span>
                        <span class="file-names">尚未選擇檔案</span>
                    </label>
                </div>
                <div id="fileList" class="file-list"></div>
            </div>
            <div class="button-group">
                <button type="submit" class="upload-button" disabled>上傳所選檔案</button>
            </div>
        </form>

        <!-- 已上傳檔案列表 -->
        <?php if ($uploadedFiles->num_rows > 0): ?>
        <div class="uploaded-files">
            <h3>已上傳檔案</h3>
            <div class="files-list">
                <?php while ($file = $uploadedFiles->fetch_assoc()): ?>
                    <div class="uploaded-file-item">
                        <span class="file-name"><?php echo htmlspecialchars($file['original_name']); ?></span>
                        <span class="upload-time"><?php echo date('Y-m-d H:i:s', strtotime($file['uploaded_at'])); ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
        <br>
        
        <!-- 聯絡方式區塊 -->
        <div class="contact-info">
            <h3>聯絡資訊</h3>
            <div class="contact-details">
                <?php if (!empty($settings['contact_email'])): ?>
                <div class="contact-item">
                    <i class="contact-icon">📧</i>
                    <span class="contact-label">電子郵件：</span>
                    <a href="mailto:<?php echo htmlspecialchars($settings['contact_email']); ?>"><?php echo htmlspecialchars($settings['contact_email']); ?></a>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($settings['contact_phone'])): ?>
                <div class="contact-item">
                    <i class="contact-icon">📞</i>
                    <span class="contact-label">電話：</span>
                    <span class="contact-value"><?php echo htmlspecialchars($settings['contact_phone']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($settings['contact_line'])): ?>
                <div class="contact-item">
                    <i class="contact-icon">💬</i>
                    <span class="contact-label">LINE ID：</span>
                    <span class="contact-value"><?php echo htmlspecialchars($settings['contact_line']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($settings['contact_url'])): ?>
                <div class="contact-item">
                    <i class="contact-icon">🌐</i>
                    <span class="contact-label">網站：</span>
                    <a href="<?php echo htmlspecialchars($settings['contact_url']); ?>" target="_blank"><?php echo htmlspecialchars($settings['contact_url']); ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> <a href="https://siang.pro" target="_blank">Siang.Pro</a>. All rights reserved.</p>
        </footer>
    </div>

    <script>
    // 用於存儲已選擇的檔案
    let selectedFiles = new Map();
    let fileCounter = 0;

    document.getElementById('fileInput').addEventListener('change', function(e) {
        const fileList = document.getElementById('fileList');
        const uploadButton = document.querySelector('.upload-button');
        
        // 將新選擇的檔案添加到已選擇的檔案列表中
        Array.from(this.files).forEach(file => {
            const fileId = `file-${fileCounter++}`;
            selectedFiles.set(fileId, file);
            
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.dataset.fileId = fileId;
            fileItem.innerHTML = `
                <div class="file-info">
                    <span class="file-name">${file.name}</span>
                    <span class="file-size">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                </div>
                <button type="button" class="delete-file" onclick="removeFile('${fileId}')">
                    <span class="delete-icon">×</span>
                </button>
            `;
            fileList.appendChild(fileItem);
        });
        
        // 清空 input 以便可以重複選擇相同檔案
        this.value = '';
        
        // 更新檔案計數和上傳按鈕狀態
        updateFileStatus();
    });

    function removeFile(fileId) {
        const fileItem = document.querySelector(`[data-file-id="${fileId}"]`);
        if (fileItem) {
            selectedFiles.delete(fileId);
            fileItem.remove();
            updateFileStatus();
        }
    }

    function updateFileStatus() {
        const fileCount = selectedFiles.size;
        const fileNames = document.querySelector('.file-names');
        const uploadButton = document.querySelector('.upload-button');
        
        if (fileCount > 0) {
            fileNames.textContent = `已選擇 ${fileCount} 個檔案`;
            uploadButton.disabled = false;
        } else {
            fileNames.textContent = '尚未選擇檔案';
            uploadButton.disabled = true;
        }
    }

    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (selectedFiles.size === 0) {
            alert('請選擇要上傳的檔案');
            return;
        }
        
        const formData = new FormData();
        selectedFiles.forEach(file => {
            formData.append('files[]', file);
        });
        
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = '上傳中...';
        
        // 使用 fetch 發送請求
        fetch(this.action || window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // 重新載入頁面以顯示結果
            document.documentElement.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            alert('上傳失敗，請稍後再試');
            submitButton.disabled = false;
            submitButton.textContent = '上傳所選檔案';
        });
    });
    </script>
</body>
</html> 