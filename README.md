# S-Insight

## S-Insight คืออะไร / About

ระบบกลางของงาน HR ที่ดึงข้อมูลพนักงานจาก ERP ของบริษัทมาเก็บไว้ที่เดียว แล้วส่งต่อให้ทุกระบบย่อยใช้ข้อมูลชุดเดียวกัน พนักงานล็อกอินครั้งเดียวก็เข้าได้ทุกระบบที่เกี่ยวข้องกับตัวเอง

A central HR platform that pulls employee data from the company ERP into one place and shares it with every module inside. Staff sign in once and reach every system that applies to them.

## ทำอะไรได้บ้าง / Features

- ซิงก์ข้อมูลพนักงานจาก ERP อัตโนมัติ ไม่ต้องกรอกซ้ำในแต่ละระบบ
- ประเมินผลประจำปี โดยใช้ข้อมูลจาก ERP ในการคำนวณคะแนน
- ตรวจพื้นที่ 5ส ให้พนักงานถ่ายรูปพื้นที่ของตัวเองแล้วอัปโหลดส่ง
- ขอทำงานล่วงเวลา (OT) และลากิจแบบรับค่าจ้าง 75% พร้อมสายอนุมัติ
- แต่ละคนเห็นเฉพาะเมนูที่ตัวเองมีสิทธิ์ใช้
- ใช้งานได้ 3 ภาษา ไทย อังกฤษ และพม่า

* Syncs employee records from the ERP automatically, so nothing is entered twice
* Annual performance review scored with data from the ERP
* 5S area audits where staff photograph and upload their own work area
* Overtime and 75%-paid personal leave requests with an approval chain
* Everyone sees only the menus they are allowed to use
* Available in Thai, English and Burmese

## Tech Stack

**Backend:** PHP 8, Laravel 12, PhpSpreadsheet

**Frontend:** Blade, Tailwind CSS, Vite, Axios

**Database:** MySQL, Microsoft SQL Server

## ติดตั้ง / Installation

ต้องมี PHP 8.2 ขึ้นไป, Composer, Node.js และ MySQL ระบบนี้แยกฐานข้อมูลตามโมดูลรวม 5 ก้อน ต้องสร้างให้ครบก่อนรัน migrate

Requires PHP 8.2+, Composer, Node.js and MySQL. Each module has its own database, five in total, and all of them must exist before migrating.

```bash
git clone https://github.com/PumiputCG/s-insight.git
cd s-insight
composer install
npm install
cp .env.example .env
php artisan key:generate
```

```sql
CREATE DATABASE insight;
CREATE DATABASE insight_assessment;
CREATE DATABASE insight_area5s;
CREATE DATABASE insight_ot_approval;
CREATE DATABASE insight_recruit;
```

```bash
php artisan migrate
npm run build
php artisan serve
```

การดึงข้อมูลจาก ERP ต้องเปิด extension `pdo_sqlsrv` และตั้งค่า `BPLUS_*` ใน `.env` ก่อน แล้วรัน `php artisan bplus:sync`

Pulling data from the ERP needs the `pdo_sqlsrv` extension and the `BPLUS_*` values in `.env`. Then run `php artisan bplus:sync`.
