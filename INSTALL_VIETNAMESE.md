# Hướng Dẫn Cài Đặt Vietnamese Locale cho OPNsense

## 📁 Files Đã Tạo

1. **Translation Files:**
   - `src/share/locale/vi_VN/LC_MESSAGES/OPNsense.po` - Source translation
   - `src/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo` - Compiled translation

2. **Code Changes:**
   - `src/etc/inc/system.inc` - Enabled Vietnamese locale (commented out unset)
   - `src/Makefile` - Added `share` to TREES for installation

## 🚀 Cài Đặt Trên OPNsense System

### Bước 1: Install Translation Files

```bash
# Trên OPNsense system
sudo mkdir -p /usr/local/share/locale/vi_VN/LC_MESSAGES

# Copy files từ build
sudo cp src/share/locale/vi_VN/LC_MESSAGES/OPNsense.* \
    /usr/local/share/locale/vi_VN/LC_MESSAGES/

# Set permissions
sudo chmod 644 /usr/local/share/locale/vi_VN/LC_MESSAGES/OPNsense.*
sudo chown root:wheel /usr/local/share/locale/vi_VN/LC_MESSAGES/OPNsense.*
```

### Bước 2: Install Updated system.inc

```bash
# Backup original
sudo cp /usr/local/etc/inc/system.inc /usr/local/etc/inc/system.inc.backup

# Install new version
sudo cp src/etc/inc/system.inc /usr/local/etc/inc/system.inc
```

### Bước 3: Clear Cache

```bash
# Xóa PHP cache
sudo rm -rf /var/lib/php/cache/*
sudo rm -f /var/lib/php/tmp/mdl_cache_*.json

# Xóa Volt template cache
sudo find /var/lib/php/cache -name '*.php' -delete

# Flush system cache
sudo configctl system cache_flush
```

### Bước 4: Restart Web GUI

```bash
sudo /usr/local/etc/rc.restart_webgui
```

## 🌐 Sử Dụng Vietnamese

### Web GUI:
1. Login vào OPNsense
2. Vào **System → Settings → General**
3. Trong dropdown **Language**, chọn **Vietnamese**
4. Click **Save**
5. Hard refresh browser: `Ctrl + Shift + R`

### Console Menu:
Console menu đã hỗ trợ Vietnamese toggle:
```bash
# Toggle qua Vietnamese
php /usr/local/opnsense/scripts/shell/langmode.php toggle

# Hoặc set trực tiếp
php /usr/local/opnsense/scripts/shell/langmode.php set vi
```

## ✅ Kiểm Tra

### Test Translation từ Command Line:

```bash
php -r "
putenv('LANG=vi_VN.UTF-8');
setlocale(LC_ALL, 'vi_VN.UTF-8');
bindtextdomain('OPNsense', '/usr/local/share/locale');
textdomain('OPNsense');
echo gettext('Dashboard') . PHP_EOL;
echo gettext('System') . PHP_EOL;
echo gettext('Firewall') . PHP_EOL;
"
```

**Expected Output:**
```
Bảng điều khiển
Hệ thống
Tường lửa
```

### Check Locale List:

```bash
php -r "
require_once '/usr/local/etc/inc/config.inc';
require_once '/usr/local/etc/inc/system.inc';
\$locales = get_locale_list();
print_r(array_filter(\$locales, function(\$k) {
    return strpos(\$k, 'vi') !== false;
}, ARRAY_FILTER_USE_KEY));
"
```

## 📝 Build từ Source

### Full Build & Install:

```bash
cd ~/firewall
make upgrade
```

### Hoặc chỉ install src:

```bash
cd ~/firewall/src
sudo make install
```

## 🔧 Thêm Translations

Để thêm/sửa translations:

1. **Edit file `.po`:**
   ```bash
   vi src/share/locale/vi_VN/LC_MESSAGES/OPNsense.po
   ```

2. **Thêm cặp msgid/msgstr:**
   ```po
   msgid "New English Text"
   msgstr "Văn Bản Tiếng Việt Mới"
   ```

3. **Recompile:**
   ```bash
   msgfmt src/share/locale/vi_VN/LC_MESSAGES/OPNsense.po \
       -o src/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo
   ```

4. **Reinstall:**
   ```bash
   sudo cp src/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo \
       /usr/local/share/locale/vi_VN/LC_MESSAGES/
   
   sudo configctl system cache_flush
   ```

## 📊 Translation Statistics

Kiểm tra translation coverage:

```bash
msgfmt --statistics src/share/locale/vi_VN/LC_MESSAGES/OPNsense.po
```

## 🌍 Download Official Translations

Để lấy translations từ OPNsense official:

```bash
# Download latest Vietnamese translation
wget -O /tmp/vi_VN.po \
    "https://translate.opnsense.org/download/opnsense/core/vi/?format=po"

# Compile
msgfmt /tmp/vi_VN.po -o /tmp/OPNsense.mo

# Install
sudo cp /tmp/OPNsense.* /usr/local/share/locale/vi_VN/LC_MESSAGES/
```

## 🐛 Troubleshooting

### Vietnamese không hiển thị trong dropdown:

```bash
# 1. Kiểm tra file system.inc
grep -A5 "vi_VN" /usr/local/etc/inc/system.inc

# 2. Xóa cache
sudo configctl system cache_flush
sudo rm -rf /var/lib/php/cache/*

# 3. Restart webgui
sudo /usr/local/etc/rc.restart_webgui
```

### Translations không hoạt động:

```bash
# 1. Kiểm tra file .mo tồn tại
ls -lh /usr/local/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo

# 2. Kiểm tra locale
locale -a | grep vi_VN

# 3. Nếu không có, generate locale
sudo localedef -i vi_VN -f UTF-8 vi_VN.UTF-8

# 4. Test trực tiếp
LANG=vi_VN.UTF-8 gettext -d OPNsense "Dashboard"
```

### Web GUI bị lỗi sau khi thay đổi:

```bash
# Restore backup
sudo cp /usr/local/etc/inc/system.inc.backup \
    /usr/local/etc/inc/system.inc

# Clear cache
sudo configctl system cache_flush

# Restart
sudo /usr/local/etc/rc.restart_webgui
```

## 📚 Tham Khảo

- **OPNsense Translation Server:** https://translate.opnsense.org/
- **GNU Gettext Manual:** https://www.gnu.org/software/gettext/manual/
- **Vietnamese Locale:** vi_VN.UTF-8

## ✨ Sample Translations

Current translations include:

| English | Vietnamese |
|---------|-----------|
| Dashboard | Bảng điều khiển |
| System | Hệ thống |
| Interfaces | Giao diện mạng |
| Firewall | Tường lửa |
| Services | Dịch vụ |
| Settings | Cài đặt |
| Save | Lưu |
| Apply | Áp dụng |
| Cancel | Hủy bỏ |
| Delete | Xóa |
| Edit | Sửa |
| Add | Thêm |
| Reboot | Khởi động lại |
| Configuration | Cấu hình |
| Backup | Sao lưu |
| Restore | Khôi phục |

**Total:** 80+ translations

---

**Status:** ✅ Ready for deployment
**Last Updated:** December 25, 2025
