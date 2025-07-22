CREATE TABLE upload_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    link_id VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (link_id)
);

CREATE TABLE uploaded_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_id VARCHAR(32) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (link_id) REFERENCES upload_links(link_id)
);

CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(50) NOT NULL,
    setting_value TEXT,
    UNIQUE KEY (setting_name)
);

-- 新增管理員帳號（密碼會被 bcrypt 加密）
INSERT INTO admin_users (username, password) VALUES 
('admin', '$2y$10$LHkpCBcX7NbRkfhM5.ZNCelr.8oAqe10zJ9vxs1lelKWMggCURcfK'); 

-- 初始化系統設定
INSERT INTO system_settings (setting_name, setting_value) VALUES
('site_name', 'Siang.Pro資料上傳系統'),
('contact_email', 'contact@example.com'),
('contact_phone', ''),
('contact_line', ''),
('contact_url', 'https://www.siang.pro'),
('max_file_size', '10'), -- 以 MB 為單位
('allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt'); 
