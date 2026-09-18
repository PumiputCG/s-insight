# S-Insight — HR Hub

**TH:** ศูนย์กลางงาน HR ขององค์กร — ล็อกอินบัญชีเดียว เข้าได้ทุกระบบย่อยที่เกี่ยวกับตัวเอง
**EN:** An HR hub for the organization — one login, and every internal HR system you actually need is behind it.

`PHP 8.2` · `Laravel 12` · `MySQL` · `Tailwind CSS 4` · `Vite 7` · `Blade`

---

## 🇹🇭 ภาษาไทย

### เรื่องมันเป็นอย่างนี้

เดิมทีงาน HR ของบริษัทกระจายอยู่หลายที่ — ประเมินพนักงานอยู่ไฟล์หนึ่ง ตรวจ 5ส อยู่กระดาษ ขอ OT กับลาหยุดเดินเอกสารมือ ส่วนข้อมูลพนักงานตัวจริงอยู่ในระบบ Business Plus ที่ HR เข้าถึงได้คนเดียว พนักงานคนหนึ่งจะทำเรื่องอะไรสักอย่างต้องรู้ว่าต้องไปที่ไหน ถามใคร

Insight เกิดมาเพื่อรวมทั้งหมดไว้ที่เดียว เข้าครั้งเดียว เห็นเฉพาะเมนูที่ตัวเองเกี่ยวข้อง จบ

### ระบบย่อยข้างใน

| โมดูล | ทำอะไร |
|---|---|
| **Assessment** | ประเมินผลพนักงาน — ตั้งรอบประเมิน ตั้งคำถาม ให้คะแนน ประเมินตัวเอง สรุปผลรายระดับ ส่งออกได้ |
| **Area 5S** | ตรวจพื้นที่ 5ส — วางผังพื้นที่ กำหนดผู้รับผิดชอบ ตั้งรอบตรวจ บันทึกผลตรวจ แนบรูป ออกรายงาน |
| **OT & Leave Approval** | ขอ OT และลาหยุด เดินสายอนุมัติตามลำดับบังคับบัญชา ออกรายงานลา 75 |
| **Recruit** | รับสมัครงาน จัดการใบสมัครและผู้สมัคร |

### จุดที่คิดเยอะเป็นพิเศษ

- **ขอบเขตข้อมูลชัด** — ระบบดึงเฉพาะ Employee Master ที่ได้รับอนุมัติจาก Business Plus (SQL Server) เท่านั้น **ไม่แตะข้อมูลเงินเดือนหรือ payroll เลย** เพราะเป็นข้อมูลที่ไม่ควรอยู่ในระบบที่คนทั่วไปเข้าถึงได้
- **สิทธิ์การมองเห็น** — แต่ละคนเห็นเฉพาะสิ่งที่เกี่ยวกับตัวเอง หัวหน้าเห็นของลูกน้อง HR เห็นภาพรวม ควบคุมด้วย `HandlesAssessmentAccess` / `HandlesArea5sAccess` ที่แยกเป็น trait ใช้ซ้ำ
- **หลายภาษา** — รองรับไทย / อังกฤษ / พม่า เพราะพนักงานในโรงงานมีแรงงานพม่าจำนวนมาก
- **Sync ข้อมูล** — ซิงก์จาก Business Plus ผ่าน PowerShell script (เพราะเซิร์ฟเวอร์ยังไม่ได้ลง `pdo_sqlsrv`) แล้วเก็บเป็น mirror แบบอ่านอย่างเดียว

### ติดตั้งลองรัน

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# แก้ค่า DB_* ใน .env ให้ตรงกับเครื่องตัวเอง
php artisan migrate
npm run build
php artisan serve
```

### หมายเหตุ

Repo นี้มีแต่โค้ด — **ไม่มีฐานข้อมูล ไม่มีไฟล์อัปโหลด ไม่มีข้อมูลพนักงานจริง** ตัดออกหมดแล้วเพื่อความปลอดภัย ถ้าอยากเห็นหน้าตาระบบจริงทักมาได้

---

## 🇬🇧 English

### The problem

HR work at the company lived in too many places. Performance reviews sat in one file, 5S audits were on paper, overtime and leave requests moved by hand, and the real employee records lived inside Business Plus where only HR could reach them. If you needed something done, you first had to figure out where to go and who to ask.

Insight pulls all of it into one place. You log in once and see only the systems that concern you.

### What's inside

| Module | What it does |
|---|---|
| **Assessment** | Performance reviews — set cycles, build question sets, score people, self-assessment, per-level summaries, export |
| **Area 5S** | 5S workplace audits — map areas, assign owners, schedule rounds, record findings with photos, generate reports |
| **OT & Leave Approval** | Overtime and leave requests routed through the real approval chain, plus the Leave-75 report |
| **Recruit** | Job postings, applications, and candidate tracking |

### Decisions worth calling out

- **A hard data boundary.** The system mirrors only the approved Employee Master from Business Plus (SQL Server). **Salary and payroll data are deliberately out of scope** — that data has no business being in a system this many people can open.
- **Visibility rules.** You see your own records; managers see their reports; HR sees the whole picture. Enforced through reusable traits (`HandlesAssessmentAccess`, `HandlesArea5sAccess`) rather than scattered checks.
- **Three languages.** Thai, English, and Burmese — a large share of the factory workforce reads Burmese.
- **Sync strategy.** Data comes across from Business Plus via a PowerShell job (the server doesn't have `pdo_sqlsrv` installed) and is kept as a read-only mirror.

### Running it

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# point DB_* at your own database
php artisan migrate && npm run build && php artisan serve
```

### Note

This repository is **code only** — no database, no uploads, no real employee data. All of it was stripped before publishing.
