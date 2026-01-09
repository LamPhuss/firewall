#!/bin/bash
# Script cài đặt Vietnamese locale cho OPNsense

echo "=== Cài đặt Vietnamese Locale cho OPNsense ==="
echo ""

# 1. Tạo thư mục
echo "1. Tạo thư mục locale..."
sudo mkdir -p /usr/local/share/locale/vi_VN/LC_MESSAGES

# 2. Copy file .mo
echo "2. Copy file translation..."
sudo cp ~/firewall/src/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo \
    /usr/local/share/locale/vi_VN/LC_MESSAGES/

# 3. Set permissions
echo "3. Set permissions..."
sudo chmod 644 /usr/local/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo
sudo chown root:wheel /usr/local/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo

# 4. Copy system.inc đã được sửa
echo "4. Backup và update system.inc..."
sudo cp /usr/local/etc/inc/system.inc /usr/local/etc/inc/system.inc.backup
sudo cp ~/firewall/src/etc/inc/system.inc /usr/local/etc/inc/system.inc

# 5. Clear cache
echo "5. Clear cache..."
sudo rm -rf /var/lib/php/cache/*
sudo rm -f /var/lib/php/tmp/mdl_cache_*.json
sudo find /var/lib/php/cache -name '*.php' -delete 2>/dev/null

# 6. Flush system cache
echo "6. Flush system cache..."
sudo configctl system cache_flush

# 7. Restart web GUI
echo "7. Restart web GUI..."
sudo /usr/local/etc/rc.restart_webgui

echo ""
echo "=== Hoàn tất! ==="
echo ""
echo "Vui lòng:"
echo "1. Đợi 10-15 giây cho web GUI khởi động lại"
echo "2. Refresh browser (Ctrl + Shift + R)"
echo "3. Vào System → Settings → General"
echo "4. Chọn Language: Vietnamese"
echo "5. Click Save"
echo ""
