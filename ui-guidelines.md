# UI Guidelines - Material Design Commerce

## Tong quan

Giao dien web app theo phong cach Material Design hien dai, tap trung vao:

- Ro rang
- Co phan cap thi giac ro
- De scan thong tin
- Surface, elevation va motion co kiem soat
- Icon dung Lucide

Muc tieu:

- Giong ung dung quan tri thuong mai hien dai
- Mobile-first nhung van dep tren desktop
- Dung surface, tonal color va elevation de tao cau truc
- Tranh UI phang qua muc lam mat thu bac thao tac

## Nguyen tac cot loi

- Mobile-first tuyet doi
- Material surface + elevation vua du
- Tonal palette nhat quan
- Bo goc lon, than thien, hien dai
- Consistency quan trong hon trang tri

## Layout

- max-w-6xl mx-auto
- Nen nhieu lop nhe: gradient + surface tint
- Padding tong: px-4 md:px-6
- Bottom space: pb-24

Cau truc uu tien:

- Sticky top app bar
- Content surface
- Bottom navigation kieu Material
- Modal/sheet bo lon

Co the dung grid nhieu cot o desktop neu noi dung can scan nhanh.

## Mau sac

- Primary: brand-500 xanh duong
- Background: xanh xam nhat nhieu lop
- Surface: trang ban mo / trang dac

Mau trang thai:

- Success: brand / teal
- Info: blue
- Warning: amber
- Error: red

Quy tac:

- 1 mau chinh + mau trang thai
- Surface trang, text slate, accent xanh
- Mau dung cho CTA, trang thai, vung active va chip

## Surface va Elevation

- Mac dinh: border-white/60 hoac border-slate-200/70
- Shadow mem, ngan, khong qua toi
- Backdrop blur chi dung cho bar, filter box, modal

Quy tac:

- Co the ket hop border manh + shadow mem
- Surface chinh la bg-white/85 hoac bg-white/95
- Card long card chi khi that su la phan cap nghiep vu khac nhau

## Bo goc

- Card: rounded-card
- Button/Input/Filter: rounded-lg hoac rounded-[1.1rem]
- Modal sheet: rounded-t-[1.75rem]

## Spacing va Size

- Button: min-h-11
- Input: min-h-12
- Padding chuan: px-4 py-3
- Khoang cach block: space-y-3 den space-y-5

## Typography

- Display: font-display voi Lexend
- Body: font-sans voi Roboto Flex
- Title: text-lg den text-2xl
- Noi dung: text-sm
- Label/meta: text-xs uppercase tracking-[0.18em]

Quy tac:

- Giu hierarchy ro giua title, section label, supporting text
- Dung tracking cho label dieu huong, khong dung cho body text

## Button

Primary:

- bg-brand-600 text-white
- pill shape
- co elevation nhe

Secondary:

- bg-white text-slate-700 border
- hover tonal brand-50

Tonal / Outline:

- border border-slate-300/80 bg-white/90

Tat ca button:

- rounded-lg
- min-h-11
- transition
- active:scale-[0.99]

## Input

- border border-slate-300/80
- rounded-[1.1rem]
- px-4 py-3
- focus:border-brand-500
- focus:ring-4 focus:ring-brand-100

## Card

- bg-white/88
- border border-white/60
- rounded-card
- p-4
- shadow-app

Gradient chi dung cho hero/CTA noi bat, khong dung cho moi card.

## List / Item

Surface list:

- rounded-card border bg-white/88
- item dung spacing hoac divider manh

Quick action cards:

- icon capsule ben trai
- title ro
- trang thai active dung tonal fill

## Table

- Header: bg-slate-50/80
- Row: border-b border-slate-100
- Co the giu sticky header neu bang dai

## Navigation

Bottom nav:

- fixed bottom-0
- bg-white/82 border-t border-white/60 backdrop-blur-xl

Active:

- bg-brand-50 text-brand-700

## Interaction

- transition 150-200ms
- easing mem, khong gat
- hover: bg-brand-50/60 hoac bg-slate-50
- active: scale-[0.99]

Khong dung animation phuc tap hoac bong qua manh.

## Iconography

- Toan bo icon dung Lucide
- Stroke dong nhat
- Khong tron Heroicons hoac icon fill khac he

## Gradient

Chi dung neu that su can:

- CTA dac biet
- Dashboard hero
- Brand accent nho

Khong dung gradient tran lan tren card noi dung thuong.
