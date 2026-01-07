#!/bin/sh
# Setup Vietnamese Locale on OPNsense VM
# Run this script on vagrant@OPNsense machine

set -e

echo "=== Vietnamese Locale Setup for OPNsense ==="
echo

# Find msgfmt command
MSGFMT=$(which msgfmt 2>/dev/null || find /usr/local -name msgfmt 2>/dev/null | head -1)
if [ -z "$MSGFMT" ]; then
    echo "ERROR: msgfmt not found. Installing gettext-tools..."
    sudo pkg install -y gettext-tools
    MSGFMT=$(which msgfmt 2>/dev/null || find /usr/local -name msgfmt 2>/dev/null | head -1)
fi

echo "Using msgfmt: $MSGFMT"
echo

# Step 1: Create locale directory
echo "Step 1: Creating locale directory..."
mkdir -p ~/firewall/src/share/locale/vi_VN/LC_MESSAGES

# Step 2: Create Vietnamese translation file
echo "Step 2: Creating Vietnamese translation file..."
cat > ~/firewall/src/share/locale/vi_VN/LC_MESSAGES/OPNsense.po << 'EOFILE'
# Vietnamese translations for OPNsense
msgid ""
msgstr ""
"Project-Id-Version: OPNsense 25.1\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Language: vi_VN\n"
"Plural-Forms: nplurals=1; plural=0;\n"

msgid "Dashboard"
msgstr "Bảng điều khiển"

msgid "System"
msgstr "Hệ thống"

msgid "Interfaces"
msgstr "Giao diện mạng"

msgid "Firewall"
msgstr "Tường lửa"

msgid "Services"
msgstr "Dịch vụ"

msgid "VPN"
msgstr "VPN"

msgid "Lobby"
msgstr "Sảnh"

msgid "Power"
msgstr "Nguồn"

msgid "Help"
msgstr "Trợ giúp"

msgid "Logout"
msgstr "Đăng xuất"

msgid "Login"
msgstr "Đăng nhập"

msgid "Username"
msgstr "Tên đăng nhập"

msgid "Password"
msgstr "Mật khẩu"

msgid "Language"
msgstr "Ngôn ngữ"

msgid "Save"
msgstr "Lưu"

msgid "Apply"
msgstr "Áp dụng"

msgid "Cancel"
msgstr "Hủy bỏ"

msgid "Delete"
msgstr "Xóa"

msgid "Edit"
msgstr "Sửa"

msgid "Add"
msgstr "Thêm"

msgid "Close"
msgstr "Đóng"

msgid "Settings"
msgstr "Cài đặt"

msgid "General"
msgstr "Chung"

msgid "Configuration"
msgstr "Cấu hình"

msgid "Backup"
msgstr "Sao lưu"

msgid "Restore"
msgstr "Khôi phục"

msgid "Update"
msgstr "Cập nhật"

msgid "Firmware"
msgstr "Phần mềm"

msgid "Reboot"
msgstr "Khởi động lại"

msgid "Halt"
msgstr "Tắt máy"

msgid "Network"
msgstr "Mạng"

msgid "Address"
msgstr "Địa chỉ"

msgid "Gateway"
msgstr "Cổng"

msgid "Rules"
msgstr "Quy tắc"

msgid "Success"
msgstr "Thành công"

msgid "Error"
msgstr "Lỗi"

msgid "Warning"
msgstr "Cảnh báo"
EOFILE

echo "✓ OPNsense.po created"

# Step 3: Compile .po to .mo
echo "Step 3: Compiling translation file..."
$MSGFMT ~/firewall/src/share/locale/vi_VN/LC_MESSAGES/OPNsense.po \
    -o ~/firewall/src/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo

echo "✓ OPNsense.mo compiled"

# Step 4: Install translation file
echo "Step 4: Installing translation file to system..."
sudo cp ~/firewall/src/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo \
    /usr/local/share/locale/vi_VN/LC_MESSAGES/

sudo chmod 644 /usr/local/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo
echo "✓ Translation file installed"

# Step 5: Backup and update system.inc
echo "Step 5: Updating system.inc..."
if [ -f /usr/local/etc/inc/system.inc ]; then
    sudo cp /usr/local/etc/inc/system.inc /usr/local/etc/inc/system.inc.backup
    sudo cp ~/firewall/src/etc/inc/system.inc /usr/local/etc/inc/system.inc
    echo "✓ system.inc updated"
else
    echo "⚠️  /usr/local/etc/inc/system.inc not found - skipping"
fi

# Step 6: Clear cache
echo "Step 6: Clearing cache..."
sudo rm -rf /tmp/cache_* 2>/dev/null || true
sudo configctl system cache_flush 2>/dev/null || echo "  (configctl not available, skipped)"
echo "✓ Cache cleared"

# Step 7: Restart webgui
echo "Step 7: Restarting webgui..."
if [ -f /usr/local/etc/rc.restart_webgui ]; then
    sudo /usr/local/etc/rc.restart_webgui
    echo "✓ Webgui restarted"
else
    echo "⚠️  rc.restart_webgui not found - please restart manually"
fi

# Step 8: Test translation
echo
echo "=== Testing Vietnamese Translation ==="
php -r "
putenv('LANG=vi_VN.UTF-8');
setlocale(LC_ALL, 'vi_VN.UTF-8');
bindtextdomain('OPNsense', '/usr/local/share/locale');
textdomain('OPNsense');
echo 'Dashboard: ' . gettext('Dashboard') . PHP_EOL;
echo 'System: ' . gettext('System') . PHP_EOL;
echo 'Firewall: ' . gettext('Firewall') . PHP_EOL;
echo 'Settings: ' . gettext('Settings') . PHP_EOL;
"

echo
echo "=== Setup Complete! ==="
echo
echo "Next steps:"
echo "1. Open OPNsense Web GUI"
echo "2. Go to System → Settings → General"
echo "3. Select 'Vietnamese' in Language dropdown"
echo "4. Click Save"
echo "5. Hard refresh browser (Ctrl + Shift + R)"
echo
