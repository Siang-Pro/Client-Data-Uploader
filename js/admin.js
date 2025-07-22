function toggleLink(linkId) {
    if (confirm('確定要切換此連結的狀態？')) {
        fetch('toggle_link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ linkId: linkId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 找到對應的按鈕和狀態單元格
                const button = document.querySelector(`button[onclick="toggleLink('${linkId}')"]`);
                const statusCell = document.querySelector(`.status-cell[data-link-id="${linkId}"]`);
                const linkCell = button.parentElement.previousElementSibling.previousElementSibling;
                
                // 切換按鈕的類別和文字
                const isCurrentlyActive = button.classList.contains('active');
                button.classList.toggle('active');
                button.classList.toggle('inactive');
                button.textContent = isCurrentlyActive ? '開啟' : '關閉';
                
                // 更新狀態文字
                statusCell.textContent = isCurrentlyActive ? '已關閉' : '啟用中';
                
                // 更新連結顯示
                if (isCurrentlyActive) {
                    linkCell.innerHTML = '<span class="inactive-link">連結已關閉</span>';
                } else {
                    linkCell.innerHTML = `<a href="upload.php?id=${linkId}" target="_blank">${linkId}</a>`;
                }
            } else {
                alert('操作失敗，請稍後再試');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('操作失敗，請稍後再試');
        });
    }
} 