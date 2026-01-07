#!/bin/bash
# Deploy Vietnamese locale to OPNsense VM
# Run this on LOCAL MACHINE (van@MSII)

set -e

OPNSENSE_IP="${1:-192.168.56.10}"  # Default IP, change if needed
OPNSENSE_USER="vagrant"

echo "=== Deploying Vietnamese Locale to OPNsense ==="
echo "Target: $OPNSENSE_USER@$OPNSENSE_IP"
echo

# 1. Compile .mo on local
echo "Step 1: Compiling translation file..."
msgfmt ~/firewall/src/share/locale/vi_VN/LC_MESSAGES/OPNsense.po \
    -o ~/firewall/src/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo

FILE_SIZE=$(ls -lh ~/firewall/src/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo | awk '{print $5}')
echo "✓ OPNsense.mo created ($FILE_SIZE)"

# 2. Copy .mo to OPNsense
echo "Step 2: Copying translation file to OPNsense..."
ssh $OPNSENSE_USER@$OPNSENSE_IP "mkdir -p ~/firewall/src/share/locale/vi_VN/LC_MESSAGES"
scp ~/firewall/src/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo \
    $OPNSENSE_USER@$OPNSENSE_IP:~/firewall/src/share/locale/vi_VN/LC_MESSAGES/
echo "✓ File copied"

# 3. Copy system.inc
echo "Step 3: Copying system.inc..."
scp ~/firewall/src/etc/inc/system.inc \
    $OPNSENSE_USER@$OPNSENSE_IP:~/firewall/src/etc/inc/
echo "✓ system.inc copied"

# 4. Install on OPNsense
echo "Step 4: Installing files on OPNsense..."
ssh $OPNSENSE_USER@$OPNSENSE_IP << 'REMOTE_COMMANDS'
# Backup
sudo cp /usr/local/etc/inc/system.inc /usr/local/etc/inc/system.inc.backup 2>/dev/null || true

# Install system.inc
sudo cp ~/firewall/src/etc/inc/system.inc /usr/local/etc/inc/system.inc

# Install translation
sudo cp ~/firewall/src/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo \
    /usr/local/share/locale/vi_VN/LC_MESSAGES/

sudo chmod 644 /usr/local/share/locale/vi_VN/LC_MESSAGES/OPNsense.mo

echo "✓ Files installed"

# Test
echo ""
echo "=== Testing Translation ==="
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
REMOTE_COMMANDS

echo
echo "=== Deployment Complete! ==="
echo
echo "Next steps:"
echo "1. Open OPNsense Web GUI: http://$OPNSENSE_IP"
echo "2. Go to System → Settings → General"
echo "3. Select 'Vietnamese' in Language dropdown"
echo "4. Click Save"
echo "5. Hard refresh browser (Ctrl + Shift + R)"
echo
