# 客戶檔案上傳系統

一個安全、高效的客戶檔案上傳管理系統，允許管理員創建專屬上傳連結，客戶通過連結上傳檔案，管理員可統一管理所有資料。

![檔案上傳系統](https://siang.pro/image/blog-logo.png) <!-- 上線後可以替換為實際的截圖 -->

## 功能特點

### 管理員功能
- 🔑 安全的管理員登入系統
- 🔗 建立客戶專屬上傳連結
- 👁️ 查看和管理已上傳的檔案
- 🚫 啟用/關閉上傳連結
- 🗑️ 刪除檔案和連結
- ⚙️ 系統設定 (網站名稱、聯絡資訊等)
- 🛡️ 設定允許的檔案類型和大小限制

### 客戶功能
- 📤 通過專屬連結上傳多個檔案
- 📋 查看已上傳檔案列表
- 🔍 上傳前預覽檔案
- ❌ 上傳前移除已選擇的檔案

## 技術規格

- 📱 響應式設計，適配桌面和移動設備
- 🔒 安全的檔案處理和存儲機制
- 💾 MySQL 資料庫管理上傳記錄
- 📂 獨立的客戶檔案目錄結構
- 🔐 密碼安全加密 (bcrypt)
- 🌐 支援多種檔案類型上傳

## 安裝指南

### 系統需求
- PHP 7.4+
- MySQL 5.7+
- Apache 或 Nginx 網頁伺服器
- mod_rewrite 模組 (Apache)

### 安裝步驟

1. 克隆此倉庫到您的網頁伺服器目錄：
   ```
   git clone https://github.com/Siang-Pro/Client-Data-Uploader.git
   ```

2. 導入資料庫結構：
   ```
   mysql -u username -p database_name < database.sql
   ```

3. 修改 `config.php` 檔案，設定您的資料庫連接資訊：
   ```php
   $db_host = 'localhost';
   $db_user = 'your_username';
   $db_pass = 'your_password';
   $db_name = 'your_database_name';
   ```

4. 確保 `uploads` 目錄具有可寫入權限：
   ```
   chmod 755 uploads
   ```

5. 設定管理員密碼（預設帳號：admin 預設密碼：password）：
   ```
   php generate_password.php 可產生加密後的密碼工具 上限建議移除或新增權限
   ```
   然後使用產生的哈希值更新資料庫中的管理員密碼。

6. 訪問網站，登入管理員後台 (admin_login.php)。

## 使用說明

### 管理員
1. 登入管理員後台 (`admin_login.php`)
2. 在首頁創建客戶專屬上傳連結
3. 分享連結給對應客戶
4. 通過儀表板查看和管理已上傳檔案
5. 使用系統設定頁面調整全局參數

### 客戶
1. 通過收到的專屬連結訪問上傳頁面
2. 點擊「新增檔案」選擇要上傳的檔案
3. 檢查選擇的檔案，可移除不需要的項目
4. 點擊「上傳所選檔案」完成上傳

## 定製與擴展

### 修改網站外觀
- 編輯 `css/style.css` 文件自定義界面樣式
- 修改 `settings.php` 中的系統設定以更改網站名稱和聯絡資訊

### 增加更多功能
- 文件結構已模組化設計，便於擴展
- 遵循現有的代碼風格添加新功能

## 授權信息

© Siang.Pro. All rights reserved.

此項目僅供學習參考和自行使用，未經授權不得用於商業販售目的。

## 聯絡方式

- 網站：[Siang.Pro](https://siang.pro)
- 問題反饋：請通過 GitHub Issues 提交 
