# UI Guidelines – Minimal Flat Mobile SaaS (Border-based)

## Tổng quan

Giao diện web app theo phong cách **minimal, flat**, tập trung vào:

- Rõ ràng
- Gọn gàng
- Dễ scan thông tin
- Không dùng shadow
- Dùng **border làm cấu trúc chính**

Mục tiêu:

- Giống app mobile
- Nhẹ, sạch, không rối mắt
- Phân tách bằng spacing + border (không phải shadow)
- Tránh “thiết kế giả chiều sâu”

---

## Nguyên tắc cốt lõi

- Mobile-first tuyệt đối
- Flat design (không shadow, không elevation)
- Border + spacing thay cho shadow
- Không lồng nhiều lớp UI (tránh card trong card)
- Consistency quan trọng hơn đẹp

---

## Layout (Mobile-first)

- `max-w-md mx-auto`
- Nền: `bg-gray-50` hoặc `bg-white`
- Padding tổng: `p-4`
- Bottom space: `pb-20`

Cấu trúc:

- Header
- Content (scroll)
- Bottom navigation

Không dùng:

- Sidebar
- Grid nhiều cột
- Layout kiểu desktop

---

## Màu sắc

- Primary: `emerald-500`
- Background: `gray-50` / `white`
- Surface: `white`

Màu trạng thái:

- Success: emerald
- Info: blue
- Warning: amber
- Error: red

Quy tắc:

- 1 màu chính + màu trạng thái
- UI chủ yếu là trắng + xám
- Màu chỉ dùng cho:
  - CTA
  - trạng thái
  - highlight nhỏ

---

## Border (Quan trọng nhất)

Đây là core của design.

- Mặc định: `border border-gray-200`
- Divider: `border-t border-gray-100`
- Không dùng border dày

### Quy tắc cực quan trọng:

- ❌ Không lồng border trong border  
  (Card có border → bên trong không thêm box có border nữa)
- ❌ Không vừa border vừa shadow
- ❌ Không tạo nhiều layer không cần thiết

### Thay thế:

| Trường hợp        | Cách làm                      |
|------------------|-------------------------------|
| Card chứa list   | Dùng divider giữa item        |
| Section trong card | Dùng spacing (`space-y`)     |
| Nhóm input       | Dùng 1 border bao ngoài       |

---

## Bo góc (Border Radius)

- Card: `rounded-xl`
- Button/Input: `rounded-lg`
- Không dùng bo quá lớn

---

## Spacing & Size

- Button: `min-h-[44px]`
- Padding chuẩn: `px-4 py-3`
- Khoảng cách block:
  - `space-y-4` (chính)
  - `space-y-3` (dày hơn)

Nguyên tắc:

- Ưu tiên spacing thay vì thêm UI
- Thoáng nhưng không loãng

---

## Typography

- Title: `text-base font-semibold`
- Nội dung: `text-sm`
- Label: `text-xs text-gray-500`

Quy tắc:

- Không quá 3 size font
- Không dùng font weight lung tung
- Text phải dễ đọc

---

## Component Rules

### Button

**Primary**

- `bg-emerald-500 text-white`

**Secondary**

- `bg-gray-100 text-gray-700`

**Outline**

- `border border-gray-200`

**Tất cả button**

- `rounded-lg`
- `min-h-[44px]`
- `transition`
- `active:scale-95`

---

### Input

- `border border-gray-200`
- `rounded-lg`
- `px-3 py-3`
- `focus:border-emerald-500`

Label:

- Nằm trên (tĩnh)
- `text-xs text-gray-500 mb-1`

---

### Card

- `bg-white`
- `border border-gray-200`
- `rounded-xl`
- `p-4`

❌ Không:

- shadow
- gradient
- nhiều lớp card

---

### List / Item

#### 1. Flat list (khuyên dùng)

- Không card
- Dùng:
  - `divide-y divide-gray-100`

#### 2. Card list

- 1 card bao ngoài
- Item bên trong KHÔNG có border
- Dùng padding + spacing

---

### Table

- Header: `bg-gray-50`
- Row: `border-b border-gray-100`
- Không border full ô

---

### Navigation

Bottom nav:

- `fixed bottom-0`
- `bg-white border-t border-gray-200`

Active:

- `text-emerald-600`

---

## Interaction

- `transition 150–200ms`

Hover (desktop):

- `bg-gray-50`

Active:

- `scale-95`

❌ Không:

- shadow hover
- animation phức tạp

---

## Gradient (Hạn chế tối đa)

Chỉ dùng nếu thật sự cần:

- CTA đặc biệt (hiếm)
- Banner

Mặc định:

- ❌ Không dùng gradient

---

## Tránh

- ❌ Shadow mọi cấp độ
- ❌ Card lồng card
- ❌ Border lồng border
- ❌ Gradient tràn lan
- ❌ UI nhiều layer
- ❌ Mỗi màn 1 style khác nhau

---

## Yêu cầu code

- Tailwind rõ ràng, clean
- Không inline style
- 1 component = 1 style duy nhất
- Tái sử dụng class/pattern

---

## Quy tắc với AI / Copilot

- Luôn ưu tiên:
  - flat
  - border
  - spacing
- Không tự thêm shadow/gradient
- Không tạo thêm layer UI không cần thiết
- Nếu phân vân → chọn cách đơn giản hơn