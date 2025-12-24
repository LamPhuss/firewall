# Hướng Dẫn Về Plugin OPNsense

## 1. Vị Trí Các Plugin Mặc Định

OPNsense có hai loại plugin chính:

### 1.1. Legacy Plugins (Plugins Hệ Thống)

**Vị trí trong mã nguồn:**
```
src/etc/inc/plugins.inc.d/
```

**Vị trí sau khi cài đặt:**
```
/usr/local/etc/inc/plugins.inc.d/
```

**Các plugin mặc định hiện có:**
- `captiveportal.inc` - Captive Portal
- `core.inc` - Core system services
- `dhcpd.inc` - DHCP server
- `dhcrelay.inc` - DHCP relay
- `dnsmasq.inc` - DNSMasq
- `dpinger.inc` - Gateway monitoring
- `ipfw.inc` - IPFW firewall
- `ipsec.inc` - IPsec VPN
- `kea.inc` - Kea DHCP
- `loopback.inc` - Loopback interface
- `monit.inc` - Monit monitoring
- `netflow.inc` - Netflow
- `ntpd.inc` - NTP daemon
- `opendns.inc` - OpenDNS
- `openssh.inc` - OpenSSH
- `openvpn.inc` - OpenVPN (có thư mục con `openvpn/`)
- `pf.inc` - Packet Filter firewall
- `radvd.inc` - Router Advertisement Daemon
- `suricata.inc` - Suricata IDS/IPS
- `unbound.inc` - Unbound DNS resolver
- `vxlan.inc` - VXLAN
- `webgui.inc` - Web GUI
- `wireguard.inc` - WireGuard VPN

**Cách hoạt động:**
- Hàm `plugins_scan()` trong `src/etc/inc/plugins.inc` sẽ quét thư mục này
- Mỗi file `.inc` được coi là một plugin
- Plugin phải định nghĩa các hàm theo pattern: `{tên_plugin}_{chức_năng}()`
- Ví dụ: `dhcpd_configure()`, `dhcpd_services()`, `dhcpd_firewall()`, v.v.

### 1.2. MVC Plugins (Plugins Giao Diện Web)

**Vị trí trong mã nguồn:**
```
src/opnsense/mvc/app/
├── controllers/OPNsense/    # Controllers
├── models/OPNsense/         # Models
├── views/OPNsense/          # Views
└── plugins/                  # (Thư mục này không tồn tại trong core, dành cho plugins bên ngoài)
```

**Cấu trúc MVC:**
- Mỗi module có thể có:
  - **Controllers**: `src/opnsense/mvc/app/controllers/OPNsense/{Module}/`
  - **Models**: `src/opnsense/mvc/app/models/OPNsense/{Module}/`
  - **Views**: `src/opnsense/mvc/app/views/OPNsense/{Module}/`

**Ví dụ các module MVC mặc định:**
- `Core`, `Firewall`, `Interfaces`, `IPsec`, `OpenVPN`, `Wireguard`, `Unbound`, `IDS`, v.v.

## 2. Cách Thay Thế Plugin

### 2.1. Thay Thế Legacy Plugin

#### Bước 1: Sao lưu plugin cũ
```bash
cp /usr/local/etc/inc/plugins.inc.d/{tên_plugin}.inc /usr/local/etc/inc/plugins.inc.d/{tên_plugin}.inc.backup
```

#### Bước 2: Thay thế file plugin
Có hai cách:

**Cách 1: Thay thế trực tiếp file**
```bash
# Copy file plugin mới vào thư mục
cp /path/to/new/{tên_plugin}.inc /usr/local/etc/inc/plugins.inc.d/{tên_plugin}.inc
```

**Cách 2: Sửa đổi trong mã nguồn và rebuild**
```bash
# Sửa file trong src/etc/inc/plugins.inc.d/{tên_plugin}.inc
# Sau đó rebuild và cài đặt lại
make install
```

#### Bước 3: Đảm bảo plugin mới có đúng cấu trúc
Plugin phải định nghĩa các hàm cần thiết, ví dụ:
- `{tên_plugin}_configure()` - Cấu hình
- `{tên_plugin}_services()` - Dịch vụ
- `{tên_plugin}_firewall()` - Firewall rules
- `{tên_plugin}_interfaces()` - Interfaces
- `{tên_plugin}_cron()` - Cron jobs
- `{tên_plugin}_syslog()` - Syslog
- `{tên_plugin}_devices()` - Devices
- `{tên_plugin}_run()` - Runtime hooks
- `{tên_plugin}_xmlrpc_sync()` - XMLRPC sync

#### Bước 4: Kiểm tra và reload
```bash
# Kiểm tra syntax PHP
php -l /usr/local/etc/inc/plugins.inc.d/{tên_plugin}.inc

# Reload cấu hình
/usr/local/etc/rc.reload_all
```

### 2.2. Thay Thế MVC Plugin (Controllers/Models/Views)

#### Bước 1: Xác định module cần thay thế
Ví dụ: Thay thế module `Firewall`

#### Bước 2: Sao lưu các file cũ
```bash
# Sao lưu controllers
cp -r /usr/local/opnsense/mvc/app/controllers/OPNsense/Firewall \
      /usr/local/opnsense/mvc/app/controllers/OPNsense/Firewall.backup

# Sao lưu models
cp -r /usr/local/opnsense/mvc/app/models/OPNsense/Firewall \
      /usr/local/opnsense/mvc/app/models/OPNsense/Firewall.backup

# Sao lưu views
cp -r /usr/local/opnsense/mvc/app/views/OPNsense/Firewall \
      /usr/local/opnsense/mvc/app/views/OPNsense/Firewall.backup
```

#### Bước 3: Thay thế các file
```bash
# Thay thế controllers
cp -r /path/to/new/Firewall/* \
      /usr/local/opnsense/mvc/app/controllers/OPNsense/Firewall/

# Thay thế models
cp -r /path/to/new/Firewall/* \
      /usr/local/opnsense/mvc/app/models/OPNsense/Firewall/

# Thay thế views
cp -r /path/to/new/Firewall/* \
      /usr/local/opnsense/mvc/app/views/OPNsense/Firewall/
```

#### Bước 4: Xóa cache và reload
```bash
# Xóa cache PHP
rm -rf /var/lib/php/cache/*

# Restart web server
/usr/local/etc/rc.d/lighttpd restart
```

### 2.3. Thay Thế Plugin Từ Mã Nguồn (Development)

Nếu bạn đang phát triển từ mã nguồn:

#### Bước 1: Sửa đổi trong thư mục source
```bash
# Sửa legacy plugin
vim src/etc/inc/plugins.inc.d/{tên_plugin}.inc

# Hoặc sửa MVC components
vim src/opnsense/mvc/app/controllers/OPNsense/{Module}/
vim src/opnsense/mvc/app/models/OPNsense/{Module}/
vim src/opnsense/mvc/app/views/OPNsense/{Module}/
```

#### Bước 2: Build và cài đặt
```bash
# Build
make

# Cài đặt
make install
```

#### Bước 3: Kiểm tra
```bash
# Kiểm tra syntax
php -l /usr/local/etc/inc/plugins.inc.d/{tên_plugin}.inc

# Reload services
/usr/local/etc/rc.reload_all
```

## 3. Lưu Ý Quan Trọng

### 3.1. Tên Plugin Phải Unique
- Tên plugin không được trùng với các file trong `/usr/local/etc/inc/`
- Hàm `plugins_scan()` sẽ bỏ qua plugin nếu tên bị trùng

### 3.2. Cấu Trúc Hàm Plugin
Mỗi plugin phải tuân theo naming convention:
- Tên hàm: `{tên_plugin}_{chức_năng}()`
- Ví dụ: `dhcpd_configure()`, `openvpn_services()`

### 3.3. Kiểm Tra Trước Khi Thay Thế
```bash
# Kiểm tra plugin hiện tại đang được sử dụng
grep -r "{tên_plugin}" /usr/local/etc/

# Kiểm tra dependencies
grep -r "{tên_plugin}" /usr/local/etc/inc/plugins.inc.d/
```

### 3.4. Backup Trước Khi Thay Thế
Luôn sao lưu trước khi thay thế:
```bash
# Backup toàn bộ plugins
tar -czf plugins_backup_$(date +%Y%m%d).tar.gz \
    /usr/local/etc/inc/plugins.inc.d/ \
    /usr/local/opnsense/mvc/app/controllers/OPNsense/ \
    /usr/local/opnsense/mvc/app/models/OPNsense/ \
    /usr/local/opnsense/mvc/app/views/OPNsense/
```

## 4. Ví Dụ Cụ Thể

### Ví dụ: Thay thế plugin DHCP

```bash
# 1. Backup
cp /usr/local/etc/inc/plugins.inc.d/dhcpd.inc \
   /usr/local/etc/inc/plugins.inc.d/dhcpd.inc.backup

# 2. Thay thế
cp /path/to/new/dhcpd.inc /usr/local/etc/inc/plugins.inc.d/dhcpd.inc

# 3. Kiểm tra syntax
php -l /usr/local/etc/inc/plugins.inc.d/dhcpd.inc

# 4. Reload
/usr/local/etc/rc.reload_all
```

### Ví dụ: Thay thế MVC module Firewall

```bash
# 1. Backup
cp -r /usr/local/opnsense/mvc/app/controllers/OPNsense/Firewall \
      /usr/local/opnsense/mvc/app/controllers/OPNsense/Firewall.backup

# 2. Thay thế
cp -r /path/to/new/Firewall/* \
      /usr/local/opnsense/mvc/app/controllers/OPNsense/Firewall/

# 3. Xóa cache
rm -rf /var/lib/php/cache/*

# 4. Restart web server
/usr/local/etc/rc.d/lighttpd restart
```

## 5. Tài Liệu Tham Khảo

- File chính quét plugins: `src/etc/inc/plugins.inc`
- Thư mục plugins: `src/etc/inc/plugins.inc.d/`
- Cấu hình MVC: `src/opnsense/mvc/app/config/config.php`
- Plugin mẫu: Xem `src/etc/inc/plugins.inc.d/core.inc` hoặc `dhcpd.inc`

