# Ke hoach nang cap UX/UI theo huong mobile app (Tailwind-first)

## 1) Muc tieu

- Bien trai nghiem hien tai tu mobile web thanh mobile app-like: thao tac nhanh, nhin hien dai, cac khu vuc quan trong de bam ngon tay.
- Giu nguyen stack hien tai (PHP + Tailwind CSS + jQuery), khong doi architecture backend.
- Uu tien dung class Tailwind truc tiep trong view/component; han che them CSS thu cong trong style.css.

## 2) Nguyen tac thuc hien

- Tailwind-first:
  - Viet giao dien bang utility class trong cac file view.
  - Chi giu style.css cho 2 nhom: reset nho va override bat buoc cho thu vien ben thu 3 (select2, flatpickr).
- Component-first:
  - Dong bo class qua component helper tai app/Views/partials/components.php.
  - Tranh copy class dai lap lai o nhieu man hinh.
- Mobile-first:
  - Thiet ke theo man 360-430px truoc, sau do mo rong cho tablet/desktop.
  - Tap target toi thieu 44px, khoang cach nhat quan.
- Design token:
  - Dua mau/bo goc/bong/chuyen dong vao tailwind.config.js (theme.extend).
  - Dung token thong nhat cho nut, card, badge, input, status.
- Flat design-first:
  - Uu tien giao dien phang, tach lop bang mau nen, border, khoang trang thay vi do bong dam.
  - Han che box shadow: chi dung shadow nhe cho thanh phan can nhan manh (modal, bottom sheet, CTA chinh), khong dung dai tra tren moi card.

## 3) Van de hien tai (uu tien xu ly)

- Visual hierarchy chua ro o cac man danh sach (don hang, san pham), thong tin bi dan trai.
- Action quan trong (tao don, nhap don, loc/tim) chua du noi bat va chua on dinh vi tri.
- Bottom navigation co san nhung chua co trang thai active ro rang, chua tao cam giac app shell.
- style.css con ganh mot so style form/select2 thay vi gom ve token + utility.
- Nhieu block lap lai class, de gay lech UI khi update.

## 4) Pham vi Phase 1 (2-4 ngay)

- Man hinh uu tien:
  - Dashboard: app bar, tong quan KPI, quick actions, canh bao ton thap, recent orders.
  - POS (Tao don): khu san pham, tam tinh, thong tin khach, thanh toan, CTA Nhap don.
  - Product list: search/filter chips, card san pham, status ton.
  - Order list: search/filter tabs, card don, status/thong tin tong.
- Khong doi logic nghiep vu; chi doi presentation + vi tri thao tac.

## 5) Backlog task chi tiet

## 5.1 Nen tang UI (bat buoc lam truoc)

- [x] Mo rong token trong tailwind.config.js:
  - Mau thuong hieu (primary, success, warning, danger, surface, ink).
  - Border radius (card, chip, button), shadow 2 cap, spacing cho mobile.
  - Motion nhe (fade/slide 150-250ms) cho drawer/toast/sheet.
- [x] Them utility class tai resources/css/tailwind.css bang @layer components cho nhom chung:
  - .app-shell, .app-card, .app-section-title, .app-chip, .app-input, .app-btn-primary, .app-btn-secondary, .app-kpi.
  - Luu y: day van la Tailwind layer, khong day vao style.css.
- [x] Rut gon style.css:
  - Giu lai override can thiet cho select2/flatpickr.
  - Loai bo class khong con su dung sau khi migrate.

## 5.2 Khung dieu huong giong mobile app

- [x] Refactor app/Views/layout/main.php:
  - App bar sticky (safe area top), title + action icon ro rang.
  - Bottom nav sticky + active state theo route.
  - Tang khoang dung cho noi dung de tranh de len bottom nav.
- [x] Chuan hoa sheet/menu:
  - Mo menu theo kieu bottom sheet full width cho mobile.
  - Co backdrop + swipe-close co the lam sau; hien tai uu tien nut dong + animation.

## 5.3 Dashboard

- [x] Bo cuc card tong quan theo nhom thong tin (Doanh thu, Loi nhuan, Da thu, Con no) voi hierarchy ro rang.
- [x] Quick actions doi sang card icon + text de bam 1 tay.
- [x] Khoi Hang sap het: status mau ro, CTA "Xem ton kho" noi bat.
- [x] Danh sach don gan day: card compact, can le thong tin theo cap (ma don -> metadata -> tong ket).

## 5.4 POS (Tao don)

- [x] Tach section ro rang: San pham, Tam tinh, Khach hang, Thanh toan, Ghi chu.
- [x] CTA "Nhap don" dang floating de thao tac nhanh, dong bo voi JS hien co.
- [x] Group segmented control cho Khach hang/Thanh toan/Hinh thuc thanh toan nhat quan style.
- [x] Input so tien: font de doc, auto format, trang thai loi ro rang.

## 5.5 Product list

- [x] Tim kiem sticky + filter chips ngang co trang thai active.
- [x] Card san pham toi uu cho quet nhanh:
  - Dong 1: ten + status ton (con hang/ton thap/het hang).
  - Dong 2: SKU + nhom + ton kho.
  - Dong 3: gia ban/gia von.
- [x] Nut them (+) dung vi tri va kich thuoc de bam (floating action button).

## 5.6 Order list

- [x] Search + filter tabs sticky duoi app bar.
- [x] Card don hang: uu tien ma don + trang thai + tong tien/thu/no/LN.
- [x] Action xem chi tiet dat ben phai, icon ro rang va gon hon (32px) theo yeu cau UX hien tai.

## 5.7 Component hoa de tranh lap class

- [x] Nang cap helper app/Views/partials/components.php:
  - Them bien the button size/state.
  - Them helper chip, badge status, section card.
  - Chuan hoa input/select/textarea cho mobile.
- [x] Reuse helper cho 4 man hinh uu tien truoc.

## 5.8 Kiem thu UX

- [x] Test tren breakpoint 360, 390, 430, 768.
- [x] Kiem tra 5 luong nhanh:
  - Tao don moi -> them san pham -> thanh toan -> nhap don.
  - Xem dashboard -> vao don gan day.
  - Tim san pham -> loc ton thap.
  - Tim don -> xem chi tiet.
  - Mo menu nhanh tu bottom nav.
- [x] Dam bao khong vo chuc nang hien co.

## 6) Tieu chi nghiem thu (Definition of Done)

- >= 80% style o 4 man hinh uu tien su dung Tailwind utility hoac @layer components; khong them CSS roi rac vao style.css.
- style.css giam vai tro, chu yeu con override cho plugin ben thu 3.
- Thao tac 1 tay tren mobile de hon: CTA chinh luon de thay/de bam.
- Visual thong nhat: mau, bo goc, shadow, typography, khoang cach nhat quan.
- Visual theo huong flat design: shadow toi gian, khong tao cam giac giao dien "noi khoi" qua nhieu.
- Khong phat sinh bug nghiep vu khi tao don, xem don, tim/loc.

## 7) Thu tu trien khai de xuat

1. Nen tang token + app shell + bottom nav active state.
2. Dashboard.
3. POS.
4. Product list.
5. Order list.
6. Refactor helper component + don dep style.css.
7. Regression test mobile.

## 8) Rui ro va cach giam

- Rui ro: class Tailwind dai, kho bao tri.
  - Giam: tach helper component + @layer components cho pattern lap lai.
- Rui ro: plugin select2 khong dong bo style.
  - Giam: giu override toi thieu trong style.css, dung token mau/bo goc tuong dong.
- Rui ro: thay doi nhieu man hinh de gay lech nho.
  - Giam: trien khai theo phase, moi phase test luong chinh.

## 9) De xuat tiep theo (Phase 2)

- Them micro-interaction co chu dich (skeleton loading, pull-to-refresh gia lap, hieu ung transition route nhe).
- Cai thien a11y (contrast, focus ring ro, keyboard support cho form quan trong).
- Toi uu hieu nang CSS build (purge content day du, giam class khong dung).
