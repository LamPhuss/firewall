# Hướng Dẫn Về Theme Trong OPNsense

## 1. Vị Trí Theme Mặc Định

Theme mặc định của OPNsense được lưu trữ trong các thư mục sau:

### Trong Mã Nguồn (Source Code):
- **Thư mục chính**: `src/opnsense/www/themes/`
- **Theme mặc định**: `src/opnsense/www/themes/opnsense/`
- **Theme dark**: `src/opnsense/www/themes/opnsense-dark/`

### Trên Hệ Thống Đã Cài Đặt:
- **Thư mục chính**: `/usr/local/opnsense/www/themes/`
- **Theme mặc định**: `/usr/local/opnsense/www/themes/opnsense/`
- **Theme dark**: `/usr/local/opnsense/www/themes/opnsense-dark/`

### Cấu Trúc Thư Mục Theme:

Mỗi theme có cấu trúc như sau:
```
theme-name/
├── assets/              # Tài nguyên nguồn (source files)
│   ├── fonts/          # Fonts
│   └── stylesheets/    # SCSS files
│       ├── main.scss   # File SCSS chính
│       ├── config/     # Cấu hình màu sắc
│       └── bootstrap/  # Bootstrap SCSS
└── build/              # Files đã build (compiled)
    ├── css/            # CSS files đã compile
    ├── fonts/          # Fonts đã copy
    └── images/         # Hình ảnh (favicon, logo, etc.)
```

## 2. Cách Hệ Thống Load Theme

### Cấu Hình Theme:
- Theme được lưu trong file config: `/conf/config.xml` với tag `<theme>tên-theme</theme>`
- Mặc định là: `<theme>opnsense</theme>`

### Cơ Chế Load Theme:

1. **Trong PHP (Legacy GUI)** - `src/www/guiconfig.inc`:
   ```php
   function get_themed_filename($url, $exists = false)
   {
       $theme = 'opnsense'; // Mặc định
       
       if (isset($config['theme']) && 
           is_dir('/usr/local/opnsense/www/themes/' . $config['theme'])) {
           $theme = $config['theme'];
       }
       
       // Tìm file trong theme/build/ trước, nếu không có thì dùng file mặc định
       foreach (["/themes/{$theme}/build/", '/'] as $pattern) {
           $filename = "/usr/local/opnsense/www{$pattern}{$url}";
           if (file_exists($filename)) {
               return "/ui{$pattern}{$url}";
           }
       }
       return $url; // Không tìm thấy, trả về URL gốc
   }
   ```

2. **Trong MVC Framework** - `src/opnsense/www/index.php`:
   ```php
   function view_fetch_themed_filename($url, $theme)
   {
       $search_pattern = array(
           "/themes/{$theme}/build/",  // Tìm trong theme trước
           "/"                          // Nếu không có thì dùng mặc định
       );
       foreach ($search_pattern as $pattern) {
           $filename = __DIR__ . "{$pattern}{$url}";
           if (file_exists($filename)) {
               return "/ui{$pattern}{$url}";
           }
       }
       return $url;
   }
   ```

3. **Trong Template Volt** - `src/opnsense/mvc/app/views/layouts/default.volt`:
   ```volt
   {% set theme_name = ui_theme|default('opnsense') %}
   <link href="{{ cache_safe(theme_file_or_default(filename, theme_name)) }}" rel="stylesheet">
   ```

### Cách Chọn Theme:
- Trong Web GUI: **System > Settings > General > Theme**
- Code tự động quét tất cả thư mục trong `/usr/local/opnsense/www/themes/*` và hiển thị trong dropdown

## 3. Cách Thay Thế Theme

### Phương Pháp 1: Thay Thế Theme Mặc Định

1. **Backup theme hiện tại**:
   ```bash
   cp -r /usr/local/opnsense/www/themes/opnsense /usr/local/opnsense/www/themes/opnsense.backup
   ```

2. **Copy theme mới vào**:
   ```bash
   cp -r /path/to/new-theme /usr/local/opnsense/www/themes/opnsense
   ```

3. **Hoặc đổi tên theme mới thành tên theme cũ**:
   ```bash
   mv /usr/local/opnsense/www/themes/new-theme /usr/local/opnsense/www/themes/opnsense
   ```

### Phương Pháp 2: Thêm Theme Mới (Khuyến Nghị)

1. **Copy theme vào thư mục themes**:
   ```bash
   cp -r /path/to/new-theme /usr/local/opnsense/www/themes/new-theme-name
   ```

2. **Chọn theme trong Web GUI**:
   - Vào **System > Settings > General**
   - Chọn theme mới trong dropdown **Theme**
   - Click **Save**

3. **Hoặc chỉnh sửa config trực tiếp**:
   ```bash
   # Chỉnh sửa /conf/config.xml
   # Tìm dòng <theme>opnsense</theme>
   # Đổi thành <theme>new-theme-name</theme>
   ```

## 4. Cách Thêm Theme-Advanced Từ GitHub

### Cách 1: Cài Đặt Qua Plugin (Khuyến Nghị)

1. **Cài đặt plugin từ Web GUI**:
   - Vào **System > Firmware > Plugins**
   - Tìm `os-theme-advanced`
   - Click nút **+** để cài đặt

2. **Kích hoạt theme**:
   - Vào **System > Settings > General**
   - Chọn **Advanced** trong dropdown **Theme**
   - Click **Save**

### Cách 2: Cài Đặt Từ Mã Nguồn GitHub

#### Bước 1: Clone Repository
```bash
# Clone repository plugins
git clone https://github.com/opnsense/plugins.git /tmp/opnsense-plugins
```

#### Bước 2: Build và Cài Đặt Plugin
```bash
cd /tmp/opnsense-plugins/misc/theme-advanced

# Kiểm tra cấu trúc plugin
ls -la

# Build plugin (nếu có Makefile)
make install

# Hoặc copy thủ công
# Xem cấu trúc trong thư mục plugin để biết cách copy
```

#### Bước 3: Copy Theme Vào Thư Mục Themes

Sau khi build plugin, theme sẽ được cài vào `/usr/local/opnsense/www/themes/`. Nếu không, bạn có thể:

1. **Tìm thư mục theme trong plugin**:
   ```bash
   find /tmp/opnsense-plugins/misc/theme-advanced -type d -name "*theme*"
   ```

2. **Copy theme vào thư mục themes**:
   ```bash
   # Giả sử theme nằm trong src/www/themes/theme-advanced
   cp -r /tmp/opnsense-plugins/misc/theme-advanced/src/www/themes/theme-advanced \
         /usr/local/opnsense/www/themes/theme-advanced
   ```

3. **Đảm bảo quyền truy cập**:
   ```bash
   chown -R www:www /usr/local/opnsense/www/themes/theme-advanced
   chmod -R 755 /usr/local/opnsense/www/themes/theme-advanced
   ```

#### Bước 4: Build CSS (Nếu Cần)

Nếu theme có SCSS files cần compile:

```bash
cd /usr/local/opnsense/www/themes/theme-advanced

# Nếu có package.json, cài dependencies
npm install

# Build CSS
npm run build
# hoặc
gulp build
# hoặc
make build
```

#### Bước 5: Kích Hoạt Theme

1. **Qua Web GUI**:
   - Vào **System > Settings > General**
   - Chọn theme trong dropdown **Theme**
   - Click **Save**

2. **Hoặc chỉnh sửa config**:
   ```bash
   # Sửa /conf/config.xml
   vi /conf/config.xml
   # Tìm và đổi: <theme>opnsense</theme> thành <theme>theme-advanced</theme>
   ```

## 5. Cấu Trúc Plugin Theme-Advanced

Dựa trên cấu trúc plugin OPNsense thông thường, theme-advanced sẽ có cấu trúc:

```
misc/theme-advanced/
├── Makefile              # Build script
├── pkg-descr            # Mô tả plugin
├── pkg-plist            # Danh sách files
└── src/
    └── www/
        └── themes/
            └── theme-advanced/  # Hoặc tên khác
                ├── assets/
                └── build/
```

## 6. Lưu Ý Quan Trọng

1. **Backup trước khi thay đổi**: Luôn backup theme hiện tại và config trước khi thay đổi
2. **Quyền truy cập**: Đảm bảo web server (www user) có quyền đọc theme
3. **Build files**: Một số theme cần build CSS từ SCSS, đảm bảo đã build đầy đủ
4. **Cache**: Sau khi thay đổi theme, có thể cần clear cache hoặc hard refresh browser (Ctrl+F5)
5. **Tương thích**: Đảm bảo theme tương thích với phiên bản OPNsense đang dùng

## 7. Kiểm Tra Theme Đã Cài Đặt

```bash
# Liệt kê tất cả themes
ls -la /usr/local/opnsense/www/themes/

# Kiểm tra theme hiện tại trong config
grep "<theme>" /conf/config.xml

# Kiểm tra theme có tồn tại không
test -d /usr/local/opnsense/www/themes/theme-advanced && echo "Theme exists" || echo "Theme not found"
```

## 8. Cách Xóa Cache Giao Diện OPNsense

OPNsense sử dụng nhiều loại cache khác nhau. Sau khi thay đổi theme hoặc chỉnh sửa giao diện, bạn cần xóa cache để thấy thay đổi.

### 8.1. Các Loại Cache Trong OPNsense

1. **Volt Template Cache**: Cache các template Volt đã compile thành PHP
   - Vị trí: `/var/lib/php/cache/`
   - Chứa các file `.php` đã compile từ template `.volt`

2. **Model Cache**: Cache dữ liệu model
   - Vị trí: `/var/lib/php/tmp/mdl_cache_*.json`
   - Cache các model data để tăng tốc độ truy vấn

3. **ACL Cache**: Cache Access Control List
   - Được quản lý bởi `OPNsense\Core\ACL()->invalidateCache()`

4. **Menu Cache**: Cache menu system
   - Được quản lý bởi `MenuSystem()->invalidateCache()`

5. **Configd Cache**: Cache của configd service
   - Cache các script output có `cache_ttl` được định nghĩa

6. **Browser Cache**: Cache ở phía client (trình duyệt)

### 8.2. Cách Xóa Cache

#### Phương Pháp 1: Xóa Tất Cả Cache (Khuyến Nghị)

**Qua Configd (Cách đơn giản nhất)**:
```bash
# Xóa tất cả cache hệ thống
configctl system cache_flush

# Hoặc qua pluginctl
pluginctl -cq cache_flush
```

**Xóa thủ công từng loại cache**:
```bash
# 1. Xóa Volt template cache
find /var/lib/php/cache -name '*.php' -delete

# 2. Xóa model cache
rm -f /var/lib/php/tmp/mdl_cache_*.json

# 3. Xóa tất cả cache PHP (bao gồm cả Volt và model)
rm -rf /var/lib/php/cache/*
rm -rf /var/lib/php/tmp/mdl_cache_*.json
```

#### Phương Pháp 2: Xóa Cache Qua PHP Function

Nếu bạn đang phát triển và có quyền truy cập PHP:
```php
// Trong PHP code
system_cache_flush(true); // true = verbose output
```

Hoặc gọi từng phần:
```php
// Xóa ACL cache
(new OPNsense\Core\ACL())->invalidateCache();

// Xóa Menu cache
(new OPNsense\Base\Menu\MenuSystem())->invalidateCache();

// Xóa model cache
foreach (glob('/var/lib/php/tmp/mdl_cache_*.json') as $filename) {
    @unlink($filename);
}
```

#### Phương Pháp 3: Xóa Browser Cache

**Trong trình duyệt**:
- **Chrome/Edge**: `Ctrl + Shift + Delete` hoặc `Ctrl + F5` (hard refresh)
- **Firefox**: `Ctrl + Shift + Delete` hoặc `Ctrl + F5`
- **Safari**: `Cmd + Option + E` hoặc `Cmd + R` (hard refresh)

**Hoặc xóa cache cho domain cụ thể**:
- Mở Developer Tools (F12)
- Right-click vào nút Refresh
- Chọn "Empty Cache and Hard Reload"

### 8.3. Khi Nào Cần Xóa Cache

Xóa cache khi:
- ✅ Thay đổi theme
- ✅ Chỉnh sửa CSS/SCSS files
- ✅ Sửa đổi Volt templates (.volt files)
- ✅ Thay đổi menu structure
- ✅ Cập nhật ACL permissions
- ✅ Thay đổi model structure
- ✅ Giao diện không hiển thị đúng sau khi chỉnh sửa

### 8.4. Script Tự Động Xóa Cache

Tạo script để xóa cache nhanh:
```bash
#!/bin/sh
# File: /usr/local/bin/opnsense-clear-cache.sh

echo "Clearing OPNsense cache..."

# Xóa Volt template cache
echo "  - Clearing Volt template cache..."
find /var/lib/php/cache -name '*.php' -delete 2>/dev/null

# Xóa model cache
echo "  - Clearing model cache..."
rm -f /var/lib/php/tmp/mdl_cache_*.json 2>/dev/null

# Xóa cache qua configd
echo "  - Flushing system cache..."
configctl system cache_flush >/dev/null 2>&1

echo "Cache cleared successfully!"
```

Cho phép thực thi:
```bash
chmod +x /usr/local/bin/opnsense-clear-cache.sh
```

Sử dụng:
```bash
opnsense-clear-cache.sh
```

### 8.5. Xóa Cache Trong Quá Trình Phát Triển

Nếu bạn đang phát triển theme hoặc plugin:

```bash
# Xóa cache mỗi khi thay đổi file
watch -n 2 'find /var/lib/php/cache -name "*.php" -delete && configctl system cache_flush'

# Hoặc tạo alias trong ~/.bashrc hoặc ~/.cshrc
alias opn-cache='find /var/lib/php/cache -name "*.php" -delete && configctl system cache_flush && echo "Cache cleared!"'
```

### 8.6. Kiểm Tra Cache Đã Xóa

```bash
# Kiểm tra Volt cache
ls -la /var/lib/php/cache/ | wc -l

# Kiểm tra model cache
ls -la /var/lib/php/tmp/mdl_cache_*.json 2>/dev/null | wc -l

# Nếu kết quả là 0 hoặc không có file, cache đã được xóa
```

## 9. Troubleshooting

### Theme không hiển thị trong dropdown:
- Kiểm tra thư mục theme có tồn tại: `ls -la /usr/local/opnsense/www/themes/`
- Kiểm tra quyền truy cập: `chmod 755 /usr/local/opnsense/www/themes/theme-name`
- **Xóa cache**: `configctl system cache_flush`

### Theme không áp dụng:
- Kiểm tra config: `grep "<theme>" /conf/config.xml`
- **Xóa cache**: `configctl system cache_flush`
- Clear browser cache (Ctrl+F5)
- Kiểm tra file CSS có tồn tại: `ls -la /usr/local/opnsense/www/themes/theme-name/build/css/`

### Lỗi 404 khi load CSS:
- Kiểm tra đường dẫn file CSS trong theme
- Đảm bảo file đã được build (nếu dùng SCSS)
- Kiểm tra quyền truy cập file
- **Xóa cache**: `find /var/lib/php/cache -name '*.php' -delete`

### Giao diện không cập nhật sau khi chỉnh sửa:
1. Xóa cache: `configctl system cache_flush`
2. Hard refresh browser: `Ctrl + F5`
3. Kiểm tra file đã được lưu chưa
4. Kiểm tra quyền truy cập file

