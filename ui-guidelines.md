# 📱 App-like UX Guidelines

---

## 🎯 Mục tiêu

Tạo trải nghiệm **giống mobile app native**, không phải web truyền thống:

* Không reload
* Mượt
* Phản hồi nhanh
* Cảm giác “instant”
* Chỉ 1 thiết kế dạng app-like cho mọi màn hình, không cần responsive riêng cho từng màn
* Sử dụng card, dạng click cả card thay vì sử dụng table
---
## KHÔNG
* Sử dụng table.
---
# 📱 Layout
## Structure
* [ Header ]
* [ Content (scroll) ]
* [ Bottom Nav ]

---

# 🔻 1. Bottom Navigation (Persistent)

## Yêu cầu

* Luôn hiển thị cố ở dưới cùng màn hình
* Không re-render khi chuyển route
* Có từ 4–5 item:

  * Home
  * POS
  * Products
  * Reports
  * Menu

## UI Rules

* Icon + label nhỏ
* Active state rõ ràng:

  * text đậm hơn hoặc đổi màu
* Chiều cao tối thiểu: `56px`

## Icon sử dụng lucide icon

## Layout

```html
<div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200">
```

---

# 🔺 2. Slide-up Sheet (Thay modal)

## Mục tiêu

Thay thế:

* dropdown
* modal nhỏ

## Behavior

* Trượt từ dưới lên
* Có overlay mờ phía sau
* Click ngoài → đóng
* Có thể swipe xuống để đóng (mobile)

## Animation

* Duration: `200–300ms`
* Ease: `ease-out`

## Structure

```html
<div class="fixed inset-0 bg-black/30"></div>

<div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-xl p-4">
```

---

# ⏳ 3. Skeleton Loader

## Bắt buộc

❌ Không dùng:

```
Loading...
```

✔ Phải dùng skeleton

## Rules

* Hiển thị ngay lập tức khi call API
* Giữ layout giống thật

## Example

```html
<div class="animate-pulse space-y-2">
  <div class="h-4 bg-gray-200 rounded w-1/2"></div>
  <div class="h-4 bg-gray-200 rounded w-1/3"></div>
</div>
```

---

# ⚡ 4. Optimistic UI

## Mục tiêu

* UI phản hồi ngay
* Không chờ API

## Áp dụng

* Thêm sản phẩm
* Xóa item
* Đổi trạng thái

## Rule

1. Update UI ngay
2. Gọi API
3. Nếu lỗi → rollback

---

# 📡 5. Offline Toast

## Khi nào hiển thị

* Mất mạng
* API fail (network error)

## UI

* Toast nhỏ ở top hoặc bottom
* Nội dung:

  * “Mất kết nối”
  * “Thử lại sau”

## Behavior

* Tự ẩn sau 2–3s
* Không block UI

---

# 🎬 6. Page Transition (QUAN TRỌNG NHẤT)

## Mục tiêu

* Cảm giác giống app mobile (iOS style)
* Hiệu ứng dạng fadeInUp khi chuyển page.

## Vue Setup

```vue
<transition :name="transitionName" mode="out-in">
  <router-view />
</transition>
```

---

## CSS Animation

```css
/* forward */
.slide-left-enter-active,
.slide-left-leave-active {
  transition: all 0.25s ease;
  position: absolute;
  width: 100%;
}

.slide-left-enter-from {
  transform: translateX(100%);
}

.slide-left-leave-to {
  transform: translateX(-30%);
  opacity: 0.3;
}

/* back */
.slide-right-enter-active,
.slide-right-leave-active {
  transition: all 0.25s ease;
  position: absolute;
  width: 100%;
}

.slide-right-enter-from {
  transform: translateX(-30%);
  opacity: 0.3;
}

.slide-right-leave-to {
  transform: translateX(100%);
}
```

---

## Layout Requirement

```html
<div class="relative h-full overflow-hidden">
```

---

# 🔄 7. Pull-to-Refresh (Mobile)

## Áp dụng

* List page
* Dashboard

## Behavior

* Kéo xuống → refresh data
* Có indicator (spinner)

## Rule

* Không reload page
* Chỉ gọi lại API

---

# 📦 8. PWA (Progressive Web App)

## Yêu cầu

* Có `manifest.webmanifest`
* Có service worker

## Cache Strategy

* Cache:

  * JS
  * CSS
  * Icon
* Không cache API động (trừ khi cần)

## Offline Behavior

* Hiển thị UI cơ bản
* Báo offline

---

# ⚡ Global UX Rules

* Không reload trang
* Không flicker UI
* Disable button khi submit
* Transition < 300ms
* UI phản hồi ngay lập tức

---

# ❌ Forbidden

* Reload page
* Loading text
* Modal truyền thống (trừ trường hợp đặc biệt)
* Delay UI chờ API

---

# ✅ Copilot Must Follow

Khi generate UI:

1. Luôn dùng pattern trên
2. Luôn ưu tiên cảm giác “instant”
3. Không dùng giải pháp web cũ (reload, modal, loading text)
4. Mọi interaction phải giống mobile app

---

# 📌 Final Note

> Đây là app admin nhưng phải có cảm giác như mobile app native

* Mượt
* Nhanh
* Không giật lag
* Không chờ đợi