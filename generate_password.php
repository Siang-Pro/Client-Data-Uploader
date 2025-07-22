<?php
$password = "password"; // 在這裡輸入您想要的新密碼
$hashed = password_hash($password, PASSWORD_BCRYPT);
echo "加密後的密碼: " . $hashed;
?> 