if [ -d "vendor" ]; then
set -e

PKG_DIR="full-package"
rm -rf "$PKG_DIR"
mkdir "$PKG_DIR"

# 1. Copy toàn bộ source code PHP (trừ các thư mục không cần thiết)
# Copy từng thư mục/file cần thiết

# Không copy resources/ (chỉ dùng để build frontend)
for item in app backend bootstrap config public routes vendor composer.json composer.lock db-structure.sql; do
  if [ -e "$item" ]; then
    cp -r "$item" "$PKG_DIR/"
  fi
done

# 2. Copy public/assets nếu có
if [ -d "public/assets" ]; then
  mkdir -p "$PKG_DIR/public/assets"
  cp -r public/assets/. "$PKG_DIR/public/assets/"
fi

# 3. Copy vendor nếu có (đã nằm trong bước trên, nhưng giữ lại cho chắc)
if [ -d "vendor" ]; then
  mkdir -p "$PKG_DIR/vendor"
  cp -r vendor/. "$PKG_DIR/vendor/"
fi

# 4. Copy các file PHP ở thư mục gốc
for f in *.php; do
  if [ -f "$f" ]; then
    cp "$f" "$PKG_DIR/"
  fi
done

  mkdir -p "$PKG_DIR/vendor"
  cp -r vendor/. "$PKG_DIR/vendor/"
fi

echo "Đã tạo thư mục $PKG_DIR/ chứa toàn bộ source code cần deploy."
