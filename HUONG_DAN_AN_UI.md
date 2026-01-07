# Hướng Dẫn Ẩn Các UI Trong OPNsense

Tài liệu này hướng dẫn cách ẩn các thành phần giao diện người dùng (UI) trong hệ thống OPNsense. Có nhiều cách để ẩn UI tùy thuộc vào loại thành phần và mức độ ẩn mong muốn.

## Tổng Quan

OPNsense sử dụng hệ thống menu XML và ACL (Access Control List) để quản lý hiển thị và quyền truy cập các thành phần UI. Có hai cách chính để ẩn UI:

1. **Ẩn khỏi menu** (`visibility="hidden"`): Menu item không hiển thị nhưng trang vẫn có thể truy cập trực tiếp qua URL
2. **Xóa khỏi menu** (`visibility="delete"`): Menu item hoàn toàn bị loại bỏ khỏi menu
3. **Kiểm soát qua ACL**: Kiểm soát quyền truy cập trang thông qua hệ thống ACL

## 1. Dashboard Widgets và Reporting > Health

### 1.1 Dashboard Widgets

Dashboard widgets được định nghĩa trong các file metadata XML tại:
```
/usr/local/opnsense/www/js/widgets/Metadata/*.xml
```

**Cách ẩn widget:**

1. **Qua ACL**: Widget sẽ tự động ẩn nếu người dùng không có quyền truy cập các endpoint liên quan. Widget được kiểm tra qua `DashboardController::canAccessEndpoints()`.

2. **Xóa widget khỏi metadata**: Xóa hoặc comment out phần định nghĩa widget trong file metadata tương ứng.

**Ví dụ ẩn widget Traffic:**
```xml
<!-- File: src/opnsense/www/js/widgets/Metadata/Core.xml -->
<!-- Comment out hoặc xóa phần này -->
<!--
<traffic>
    <filename>Traffic.js</filename>
    <link>/ui/diagnostics/traffic</link>
    <endpoints>
        <endpoint>/api/diagnostics/traffic/stream/*</endpoint>
        <endpoint>/api/diagnostics/traffic/interface</endpoint>
    </endpoints>
    ...
</traffic>
-->
```

**Các widget có thể ẩn:**
- `systeminformation` - System Information
- `memory` - Memory
- `disk` - Disk
- `interfacestatistics` - Interface Statistics
- `traffic` - Traffic Graph
- `firewall` - Firewall
- `services` - Services
- `cpu` - CPU
- Và nhiều widget khác...

### 1.2 Reporting > Health (Packets/Services/System/Traffic)

Menu Reporting > Health được định nghĩa trong:
```
src/opnsense/mvc/app/models/OPNsense/Diagnostics/Menu/Menu.xml
```

**Cách ẩn:**

Thêm thuộc tính `visibility="delete"` vào menu item Health:

```xml
<menu>
    <Reporting>
        <Health url="/ui/diagnostics/systemhealth" cssClass="fa fa-heartbeat fa-fw" visibility="delete"/>
        ...
    </Reporting>
</menu>
```

**Lưu ý:** Trang Health có thể chứa các tab như Packets, Services, System, Traffic. Để ẩn hoàn toàn, bạn cần:
1. Ẩn menu item Health (như trên)
2. Kiểm soát quyền truy cập qua ACL nếu cần

## 2. Kea DHCP

Menu Kea DHCP được định nghĩa trong:
```
src/opnsense/mvc/app/models/OPNsense/Kea/Menu/Menu.xml
```

### 2.1 Ẩn toàn bộ Kea DHCP

```xml
<menu>
    <Services>
        <KeaDHCP VisibleName="Kea DHCP" cssClass="fa fa-bullseye fa-fw" visibility="delete">
            ...
        </KeaDHCP>
    </Services>
</menu>
```

### 2.2 Ẩn từng thành phần

**Ẩn Settings (Kea DHCPv4):**
```xml
<KeaDHCP VisibleName="Kea DHCP" cssClass="fa fa-bullseye fa-fw">
    <Keav4 order="10" VisibleName="Kea DHCPv4" url="/ui/kea/dhcp/v4" visibility="delete"/>
    ...
</KeaDHCP>
```

**Ẩn Subnets, Reservations, HA Peers:**

Các tab này được quản lý trong view file. Để ẩn chúng, bạn có thể:

1. **Ẩn qua CSS/JavaScript**: Thêm class `visibility="delete"` vào các tab trong view file:
   - File: `src/opnsense/mvc/app/views/OPNsense/Kea/dhcpv4.volt`
   - File: `src/opnsense/mvc/app/views/OPNsense/Kea/dhcpv6.volt`

2. **Ẩn qua ACL**: Kiểm soát quyền truy cập các API endpoint liên quan trong:
   ```
   src/opnsense/mvc/app/models/OPNsense/Kea/ACL/ACL.xml
   ```

**Ví dụ ẩn tab Subnets:**
Trong file `.volt`, các tab có class `is_managed` có thể được ẩn bằng cách thêm điều kiện hoặc CSS.

**Ẩn Leases:**
```xml
<Leases4 order="50" VisibleName="Leases DHCPv4" url="/ui/kea/dhcp/leases4" visibility="delete"/>
<Leases6 order="60" VisibleName="Leases DHCPv6" url="/ui/kea/dhcp/leases6" visibility="delete"/>
```

**Ẩn Log:**
```xml
<LogFile order="100" VisibleName="Log File" url="/ui/diagnostics/log/core/kea" visibility="delete"/>
```

## 3. Firewall

Menu Firewall được định nghĩa trong:
```
src/opnsense/mvc/app/models/OPNsense/Core/Menu/Menu.xml
src/opnsense/mvc/app/models/OPNsense/Firewall/Menu/Menu.xml
```

### 3.1 NAT Port Forward

```xml
<Firewall>
    <NAT cssClass="fa fa-exchange fa-fw">
        <PortForward order="100" VisibleName="Port Forward" url="/firewall_nat.php" visibility="delete">
            <Edit url="/firewall_nat_edit.php*" visibility="hidden"/>
        </PortForward>
        ...
    </NAT>
</Firewall>
```

**Lưu ý:** Form Add/Edit được ẩn tự động khi menu item cha bị ẩn. Nếu chỉ muốn ẩn form nhưng giữ menu, chỉ cần thêm `visibility="hidden"` vào item Edit.

### 3.2 NAT One-to-One

```xml
<Firewall>
    <NAT>
        <OneToOne order="200" VisibleName="One-to-One" url="/ui/firewall/one_to_one/" visibility="delete">
            <FilterRef url="/ui/firewall/one_to_one#*" visibility="hidden"/>
        </OneToOne>
    </NAT>
</Firewall>
```

### 3.3 Rules (Floating/LAN/WAN)

Rules được tự động tạo động dựa trên interfaces. Chúng được quản lý trong:
```
src/opnsense/mvc/app/models/OPNsense/Base/Menu/MenuSystem.php
```

**Cách ẩn:**

1. **Ẩn menu Rules hoàn toàn:**
```xml
<Firewall>
    <Rules cssClass="fa fa-check fa-fw" visibility="delete"/>
</Firewall>
```

2. **Ẩn từng interface rule cụ thể:**

Trong `MenuSystem.php`, các rule được tạo động. Để ẩn một interface cụ thể, bạn có thể:
- Sửa code trong `MenuSystem.php` để bỏ qua interface đó
- Hoặc ẩn qua ACL bằng cách không cấp quyền truy cập pattern tương ứng

**Ví dụ ẩn Floating Rules:**
```php
// Trong MenuSystem.php, tìm phần tạo FloatingRules và thêm điều kiện ẩn
if ($key == 'FloatingRules') {
    // Bỏ qua hoặc thêm visibility="delete"
}
```

**Ẩn form Add/Edit rule:**

Form Add/Edit được tự động ẩn khi menu item cha bị ẩn. Nếu muốn chỉ ẩn form:
```xml
<Firewall>
    <Rules cssClass="fa fa-check fa-fw">
        <AddFloatingRules url="/firewall_rules_edit.php?if=FloatingRules" visibility="hidden"/>
        <EditFloatingRules url="/firewall_rules_edit.php?if=FloatingRules&*" visibility="hidden"/>
        ...
    </Rules>
</Firewall>
```

### 3.4 Live View Log

```xml
<Firewall>
    <LogFiles order="400" VisibleName="Log Files" cssClass="fa fa-eye fa-fw">
        <Live VisibleName="Live View" url="/ui/diagnostics/firewall/log" visibility="delete"/>
        ...
    </LogFiles>
</Firewall>
```

## 4. Interfaces > Overview

Menu Interfaces được định nghĩa trong:
```
src/opnsense/mvc/app/models/OPNsense/Interfaces/Menu/Menu.xml
```

**Ẩn Overview:**
```xml
<Interfaces>
    <Overview order="230" url="/ui/interfaces/overview" cssClass="fa fa-tasks fa-fw" visibility="delete"/>
    ...
</Interfaces>
```

## 5. System > Routes

Menu Routes được định nghĩa trong:
```
src/opnsense/mvc/app/models/OPNsense/Core/Menu/Menu.xml
```

### 5.1 Ẩn toàn bộ Routes

```xml
<System>
    <Routes cssClass="fa fa-map-signs fa-fw" visibility="delete">
        <Configuration order="10" url="/ui/routes" />
        <Status order="20" url="/ui/diagnostics/interface/routes"/>
        <LogFile order="100" VisibleName="Log File" url="/ui/diagnostics/log/core/routing"/>
    </Routes>
</System>
```

### 5.2 Ẩn từng thành phần

**Ẩn Configuration:**
```xml
<Routes cssClass="fa fa-map-signs fa-fw">
    <Configuration order="10" url="/ui/routes" visibility="delete"/>
    ...
</Routes>
```

**Ẩn Status:**
```xml
<Routes cssClass="fa fa-map-signs fa-fw">
    <Status order="20" url="/ui/diagnostics/interface/routes" visibility="delete"/>
    ...
</Routes>
```

**Ẩn Log:**
```xml
<Routes cssClass="fa fa-map-signs fa-fw">
    <LogFile order="100" VisibleName="Log File" url="/ui/diagnostics/log/core/routing" visibility="delete"/>
</Routes>
```

## 6. Quản Lý Truy Cập Người Dùng (User/Groups/Privileges)

Menu Access được định nghĩa trong:
```
src/opnsense/mvc/app/models/OPNsense/Core/Menu/Menu.xml
```

### 6.1 Ẩn toàn bộ Access

```xml
<System>
    <Access cssClass="fa fa-users fa-fw" visibility="delete">
        <Users order="10" url="/ui/auth/user"/>
        <Groups order="20" url="/ui/auth/group"/>
        <Privileges order="25" url="/ui/auth/priv"/>
        ...
    </Access>
</System>
```

### 6.2 Ẩn từng thành phần

**Ẩn Users:**
```xml
<Access cssClass="fa fa-users fa-fw">
    <Users order="10" url="/ui/auth/user" visibility="delete"/>
    ...
</Access>
```

**Ẩn Groups:**
```xml
<Access cssClass="fa fa-users fa-fw">
    <Groups order="20" url="/ui/auth/group" visibility="delete"/>
    ...
</Access>
```

**Ẩn Privileges:**
```xml
<Access cssClass="fa fa-users fa-fw">
    <Privileges order="25" url="/ui/auth/priv" visibility="delete"/>
    ...
</Access>
```

## Các Phương Pháp Bổ Sung

### Kiểm Soát Qua ACL

Ngoài việc ẩn menu, bạn có thể kiểm soát quyền truy cập qua hệ thống ACL:

1. **File ACL**: Các file ACL được định nghĩa trong:
   ```
   src/opnsense/mvc/app/models/OPNsense/*/ACL/ACL.xml
   ```

2. **Cách hoạt động**: ACL kiểm tra pattern URL và quyết định người dùng có quyền truy cập hay không.

3. **Ví dụ**: Để ngăn truy cập trang Users:
   ```xml
   <!-- File: src/opnsense/mvc/app/models/OPNsense/Core/ACL/ACL.xml -->
   <page-system-usermanager>
       <name>System: Access: Users</name>
       <patterns>
           <pattern>ui/auth/user</pattern>
           <pattern>api/auth/user/*</pattern>
       </patterns>
   </page-system-usermanager>
   ```
   
   Sau đó, chỉ cấp quyền này cho các nhóm/người dùng cần thiết.

### Ẩn Qua CSS/JavaScript

Một số UI có thể được ẩn tạm thời qua CSS hoặc JavaScript trong các file view (`.volt`):

```css
/* Ẩn một tab cụ thể */
#tab_subnets {
    display: none;
}
```

```javascript
// Ẩn một phần tử
$('#tab_subnets').hide();
```

**Lưu ý:** Phương pháp này chỉ ẩn hiển thị, không ngăn truy cập thực sự.

## Lưu Ý Quan Trọng

1. **Backup trước khi sửa**: Luôn backup các file trước khi chỉnh sửa.

2. **Visibility attributes:**
   - `visibility="hidden"`: Ẩn khỏi menu nhưng trang vẫn truy cập được qua URL
   - `visibility="delete"`: Hoàn toàn xóa khỏi menu

3. **Rebuild sau khi sửa**: Sau khi sửa các file XML, bạn **BẮT BUỘC** phải:
   - **Xóa cache** (xem phần dưới)
   - Restart web server (nếu cần)
   - Hoặc rebuild package

4. **Kiểm tra ACL**: Đảm bảo ACL được cấu hình đúng để ngăn truy cập trực tiếp qua URL.

5. **Menu hierarchy**: Khi ẩn menu item cha, các item con cũng sẽ bị ẩn tự động.

6. **Dynamic menus**: Một số menu được tạo động (như Firewall Rules), cần sửa code PHP để ẩn hoàn toàn.

## Ví Dụ Tổng Hợp

Để ẩn tất cả các UI được yêu cầu, bạn có thể tạo một file patch hoặc sửa các file Menu.xml tương ứng:

**File: src/opnsense/mvc/app/models/OPNsense/Core/Menu/Menu.xml**
```xml
<!-- Ẩn Reporting > Health -->
<Reporting order="15" cssClass="fa fa-area-chart" visibility="delete">
    <Health url="/ui/diagnostics/systemhealth" cssClass="fa fa-heartbeat fa-fw" visibility="delete"/>
    ...
</Reporting>

<!-- Ẩn Interfaces > Overview -->
<Interfaces order="30" cssClass="fa fa-sitemap">
    <Overview order="230" url="/ui/interfaces/overview" cssClass="fa fa-tasks fa-fw" visibility="delete"/>
    ...
</Interfaces>

<!-- Ẩn System > Routes -->
<System>
    <Routes cssClass="fa fa-map-signs fa-fw" visibility="delete">
        ...
    </Routes>
    
    <!-- Ẩn User Management -->
    <Access cssClass="fa fa-users fa-fw" visibility="delete">
        ...
    </Access>
</System>

<!-- Ẩn Firewall NAT và Rules -->
<Firewall order="40" cssClass="fa fa-fire">
    <NAT cssClass="fa fa-exchange fa-fw">
        <PortForward visibility="delete"/>
        <OneToOne visibility="delete"/>
    </NAT>
    <Rules cssClass="fa fa-check fa-fw" visibility="delete"/>
    <LogFiles>
        <Live visibility="delete"/>
    </LogFiles>
</Firewall>
```

**File: src/opnsense/mvc/app/models/OPNsense/Kea/Menu/Menu.xml**
```xml
<menu>
    <Services>
        <KeaDHCP VisibleName="Kea DHCP" cssClass="fa fa-bullseye fa-fw" visibility="delete">
            ...
        </KeaDHCP>
    </Services>
</menu>
```

## 7. Xóa Cache Sau Khi Ẩn UI

**QUAN TRỌNG**: Sau khi thực hiện các thay đổi để ẩn UI, bạn **BẮT BUỘC** phải xóa cache để các thay đổi có hiệu lực. Menu và ACL cache được lưu trong bộ nhớ cache, nếu không xóa cache, các thay đổi sẽ không hiển thị.

### 7.1. Cách Xóa Cache Nhanh (Khuyến Nghị)

```bash
# Xóa tất cả cache hệ thống (bao gồm menu cache, ACL cache, model cache)
configctl system cache_flush

# Sau đó hard refresh browser: Ctrl + F5
```

### 7.2. Xóa Cache Chi Tiết

Nếu cách trên không hoạt động, thử xóa cache chi tiết hơn:

```bash
# 1. Xóa Volt template cache (cache các template đã compile)
find /var/lib/php/cache -name '*.php' -delete

# 2. Xóa model cache (cache dữ liệu model)
rm -f /var/lib/php/tmp/mdl_cache_*.json

# 3. Xóa cache hệ thống qua configd
configctl system cache_flush

# 4. Xóa browser cache: Nhấn Ctrl + F5 trong trình duyệt
```

### 7.3. Xóa Cache Qua PHP Function

Nếu bạn đang phát triển và có quyền truy cập PHP:

```php
// Xóa tất cả cache (bao gồm ACL cache, Menu cache, Model cache)
system_cache_flush(true); // true = verbose output
```

### 7.4. Restart Web Server (Nếu Cần)

Sau khi xóa cache, nếu thay đổi vẫn chưa hiển thị, thử restart web server:

```bash
# Restart webgui service
/usr/local/etc/rc.restart_webgui

# Hoặc restart configd
service configd restart
```

### 7.5. Quy Trình Xóa Cache Đúng Cách

**Bước 1**: Xóa server-side cache
```bash
configctl system cache_flush
```

**Bước 2**: Xóa browser cache
- Nhấn `Ctrl + F5` (hard refresh)
- Hoặc mở Developer Tools (F12) > Right-click Refresh > "Empty Cache and Hard Reload"

**Bước 3**: Kiểm tra
- Reload trang web
- Kiểm tra menu đã ẩn chưa
- Nếu chưa, thử lại từ Bước 1

### 7.6. Script Tự Động Xóa Cache

Tạo script để xóa cache nhanh sau khi sửa menu:

```bash
#!/bin/sh
# File: /usr/local/bin/opnsense-clear-cache.sh

echo "Clearing OPNsense cache after UI changes..."

# Xóa Volt template cache
find /var/lib/php/cache -name '*.php' -delete 2>/dev/null

# Xóa model cache
rm -f /var/lib/php/tmp/mdl_cache_*.json 2>/dev/null

# Xóa cache hệ thống
configctl system cache_flush >/dev/null 2>&1

echo "✓ Cache cleared! Please hard refresh your browser (Ctrl+F5)"
```

Cho phép thực thi:
```bash
chmod +x /usr/local/bin/opnsense-clear-cache.sh
```

Sử dụng:
```bash
opnsense-clear-cache.sh
```

### 7.7. Khi Nào Cần Xóa Cache

Xóa cache **NGAY LẬP TỨC** sau khi:
- ✅ Sửa đổi file Menu.xml
- ✅ Thay đổi ACL.xml
- ✅ Ẩn/hiện menu items
- ✅ Thay đổi visibility attributes
- ✅ Sửa đổi menu structure

### 7.8. Troubleshooting Cache

**Vấn đề**: Menu vẫn hiển thị sau khi xóa cache

**Giải pháp**:
1. Đảm bảo đã xóa cả browser cache (`Ctrl + F5`)
2. Thử xóa cache nhiều lần:
   ```bash
   configctl system cache_flush
   find /var/lib/php/cache -name '*.php' -delete
   configctl system cache_flush
   ```
3. Restart webgui service:
   ```bash
   /usr/local/etc/rc.restart_webgui
   ```
4. Kiểm tra file đã được lưu chưa
5. Thử mở trang trong chế độ Incognito/Private để loại trừ browser cache

**Vấn đề**: Không có quyền xóa cache

**Giải pháp**:
```bash
# Chạy với quyền root
sudo configctl system cache_flush

# Hoặc sửa quyền (nếu cần)
chown -R www:www /var/lib/php/cache
chown -R www:www /var/lib/php/tmp
```

### 7.9. Tham Khảo Thêm

Để biết chi tiết hơn về cache trong OPNsense, xem tài liệu:
- `HUONG_DAN_XOA_CACHE.md` - Hướng dẫn chi tiết về các loại cache và cách xóa

## Kết Luận

Tài liệu này cung cấp hướng dẫn tổng quan về cách ẩn các UI trong OPNsense. Tùy thuộc vào yêu cầu cụ thể, bạn có thể kết hợp các phương pháp trên để đạt được mức độ ẩn mong muốn. Luôn nhớ kiểm tra và test kỹ sau khi thực hiện các thay đổi.

