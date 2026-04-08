if [ -d "vendor" ]; then
set -e

PKG_DIR="full-package"
rm -rf "$PKG_DIR"
mkdir "$PKG_DIR"


# 1. Copy các thư mục cần thiết (bỏ db-structure.sql, giữ robots.txt, favicon, .htaccess)
for item in app backend bootstrap config public routes vendor composer.json composer.lock sql; do
  if [ -e "$item" ]; then
    cp -r "$item" "$PKG_DIR/"
  fi
done


# 2. Copy các file đặc biệt (.htaccess, robots.txt, favicon.ico) từ root vào full-package/
for f in .htaccess robots.txt favicon.png; do
  if [ -f "$f" ]; then
    cp "$f" "$PKG_DIR/"
  fi
done


# 3. Copy public/assets nếu có
if [ -d "public/assets" ]; then
  mkdir -p "$PKG_DIR/public/assets"
  cp -r public/assets/. "$PKG_DIR/public/assets/"
fi


# 4. Copy vendor nếu có (đã nằm trong bước trên, nhưng giữ lại cho chắc)
if [ -d "vendor" ]; then
  mkdir -p "$PKG_DIR/vendor"
  cp -r vendor/. "$PKG_DIR/vendor/"
fi


# 5. Copy các file PHP ở thư mục gốc (nếu cần)
for f in *.php; do
  if [ -f "$f" ]; then
    cp "$f" "$PKG_DIR/"
  fi
done

  mkdir -p "$PKG_DIR/vendor"
  cp -r vendor/. "$PKG_DIR/vendor/"
fi

echo "Đã tạo thư mục $PKG_DIR/ chứa toàn bộ source code cần deploy."
