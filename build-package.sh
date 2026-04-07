#!/bin/bash
# Script: build-package.sh
# Đóng gói các file đã thay đổi để upload lên hosting (bao gồm vendor nếu có)
# Sử dụng: ./build-package.sh [commit|tag]

set -e

# Tham số: commit hoặc tag để so sánh, mặc định là HEAD~1
COMPARE_REF=${1:-HEAD~1}
PKG_NAME="deploy-$(date +%Y%m%d-%H%M%S).zip"


# 1. Lấy danh sách file thay đổi (trừ node_modules, frontend)
CHANGED_FILES=$(git diff --name-only $COMPARE_REF HEAD | grep -vE '^(frontend/|node_modules/)' || true)

# 1b. Nếu có file mới trong sql/ thì thêm vào gói
SQL_CHANGED=$(git diff --name-only $COMPARE_REF HEAD | grep '^sql/' || true)
if [ -n "$SQL_CHANGED" ]; then
  CHANGED_FILES="$CHANGED_FILES\n$SQL_CHANGED"
fi

# 2. Kiểm tra vendor có thay đổi không
VENDOR_CHANGED=$(echo "$CHANGED_FILES" | grep '^vendor/' || true)

# 3. Luôn thêm public/assets nếu có thay đổi frontend
if git diff --name-only $COMPARE_REF HEAD | grep -q '^frontend/'; then
  CHANGED_FILES="$CHANGED_FILES\npublic/assets/"
fi

# 4. Đóng gói
ZIP_LIST=""
if [ -n "$VENDOR_CHANGED" ]; then
  ZIP_LIST="$CHANGED_FILES\nvendor/"
else
  ZIP_LIST="$CHANGED_FILES"
fi

# Loại bỏ trùng lặp, loại dòng rỗng
ZIP_LIST=$(echo "$ZIP_LIST" | grep -v '^$' | sort -u)

if [ -z "$ZIP_LIST" ]; then
  echo "Không có file nào thay đổi để đóng gói."
  exit 0
fi

PKG_DIR="package"
rm -rf "$PKG_DIR"
mkdir "$PKG_DIR"

COPY_LIST=""
if [ -n "$VENDOR_CHANGED" ]; then
  COPY_LIST="$CHANGED_FILES\nvendor/"
else
  COPY_LIST="$CHANGED_FILES"
fi

# Loại bỏ trùng lặp, loại dòng rỗng
COPY_LIST=$(echo "$COPY_LIST" | grep -v '^$' | sort -u)

if [ -z "$COPY_LIST" ]; then
  echo "Không có file nào thay đổi để đóng gói."
  exit 0
fi

for f in $COPY_LIST; do
  if [ -e "$f" ]; then
    # Nếu là file
    if [ -f "$f" ]; then
      mkdir -p "$PKG_DIR/$(dirname "$f")"
      cp "$f" "$PKG_DIR/$f"
    fi
    # Nếu là thư mục
    if [ -d "$f" ]; then
      mkdir -p "$PKG_DIR/$f"
      cp -r "$f/." "$PKG_DIR/$f/"
    fi
  fi
done

echo "Đã tạo thư mục package/ chứa các file thay đổi."

echo "Đã tạo gói: $PKG_NAME"
