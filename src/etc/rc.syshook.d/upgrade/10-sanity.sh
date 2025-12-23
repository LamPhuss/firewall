#!/bin/sh

CORE=$(opnsense-version -n)
PKG="/usr/local/sbin/pkg-static"

if [ -z "${CORE}" ]; then
	echo "Could not determine core package name."
	echo "Không xác định được tên gói core."
	exit 1
fi

if [ ! -f "${PKG}" ]; then
	echo "No package manager is installed to perform upgrades."
	echo "Chưa có trình quản lý gói để thực hiện nâng cấp."
	exit 1
fi

if [ -z "$(${PKG} query %n ${CORE})" ]; then
	echo "Core package \"${CORE}\" not known to package database."
	echo "Gói core \"${CORE}\" không có trong cơ sở dữ liệu gói."
	exit 1
fi

if [ "$(${PKG} query %R pkg)" = "FreeBSD" ]; then
	echo "The Package manager \"pkg\" is incompatible and needs a reinstall."
	echo "Trình quản lý gói \"pkg\" không tương thích và cần cài đặt lại."
	exit 1
fi

echo "Passed all upgrade tests."
echo "Đã vượt qua tất cả bài kiểm tra nâng cấp."

exit 0
