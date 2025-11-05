<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="Univer API - Universitet Boshqaruv Tizimi",
 *         description="
# 🎓 Univer API Documentation

Universitet boshqaruv tizimi uchun RESTful API.

## 📋 API Turlari

### 1. Student API (Talaba Kabineti)
Talabalar uchun shaxsiy kabinet API lari.
- Dashboard (Bosh sahifa)
- Profile (Profil)
- Documents (Hujjatlar: buyruq, kontrakt, ma'lumotnoma)
- Grades (Baholar)
- Schedule (Dars jadvali)
- Subjects (Fanlar)
- Tests (Testlar)
- Assignments (Topshiriqlar)

### 2. Teacher API (O'qituvchi Portali)
O'qituvchilar uchun dars yuritish va baholash API lari.
- Subjects (Fanlar)
- Schedule (Dars jadvali)
- Attendance (Davomat)
- Grading (Baholash)
- Resources (Dars materiallari)
- Assignments (Topshiriqlar)
- Tests (Testlar va savollar)
- Exams (Imtihonlar)

### 3. Staff API (Xodim Portali)
Xodimlar uchun shaxsiy kabinet.
- Profile (Profil)
- Authentication (Kirish)

### 4. Admin API (Boshqaruv Paneli)
Tizim administratorlari uchun CRUD API lari.
- Students (Talabalar)
- Employees (Xodimlar)
- Groups (Guruhlar)
- Specialties (Mutaxassisliklar)
- Departments (Kafedralar)
- HEMIS Integration (HEMIS bilan integratsiya)

## 🔐 Autentifikatsiya

Barcha himoyalangan API lar JWT Bearer token talab qiladi.

**Token olish:**
```bash
POST /api/v1/student/auth/login
POST /api/v1/employee/auth/login
```

**Token ishlatish:**
```bash
Authorization: Bearer YOUR_JWT_TOKEN
```

**Token yangilash:**
```bash
POST /api/v1/student/auth/refresh
POST /api/v1/employee/auth/refresh
```

## 📊 Response Format

Barcha API lar standart format qaytaradi:

**Success:**
```json
{
  ""success"": true,
  ""data"": {
    // Ma'lumotlar shu yerda
  },
  ""message"": ""Operatsiya muvaffaqiyatli bajarildi""
}
```

**Error:**
```json
{
  ""success"": false,
  ""message"": ""Xatolik yuz berdi"",
  ""errors"": {
    ""field"": [""Validatsiya xatosi""]
  }
}
```

## 🌍 Tillar

API barcha ma'lumotlarni o'zbek tilida qaytaradi.

## 📧 Qo'llab-quvvatlash

Texnik yordam: support@univer.uz
Telegram: @univer_support
         ",
 *         @OA\Contact(
 *             email="support@univer.uz",
 *             name="Univer Support Team"
 *         ),
 *         @OA\License(
 *             name="MIT",
 *             url="https://opensource.org/licenses/MIT"
 *         )
 *     ),
 *     @OA\Server(
 *         url="http://localhost:8000",
 *         description="API Server (Development)"
 *     ),
 *     @OA\Server(
 *         url="http://api.univer.uz",
 *         description="API Server (Production)"
 *     ),
 *     @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="bearerAuth",
 *             type="http",
 *             scheme="bearer",
 *             bearerFormat="JWT",
 *             description="JWT token ni kiriting (login API dan olinadi)"
 *         )
 *     ),
 *     @OA\Tag(
 *         name="Student - Auth",
 *         description="Talaba autentifikatsiya API lari"
 *     ),
 *     @OA\Tag(
 *         name="Student - Profile",
 *         description="Talaba profil API lari"
 *     ),
 *     @OA\Tag(
 *         name="Student - Documents",
 *         description="Talaba hujjatlari: buyruq, kontrakt, ma'lumotnoma"
 *     ),
 *     @OA\Tag(
 *         name="Teacher - Subjects",
 *         description="O'qituvchi fanlar API lari"
 *     ),
 *     @OA\Tag(
 *         name="Teacher - Attendance",
 *         description="Davomat boshqaruvi"
 *     ),
 *     @OA\Tag(
 *         name="Teacher - Grades",
 *         description="Baholash tizimi"
 *     ),
 *     @OA\Tag(
 *         name="Employee - Auth",
 *         description="Xodim autentifikatsiya"
 *     ),
 *     @OA\Tag(
 *         name="Employee - Profile",
 *         description="Xodim profil"
 *     ),
 *     @OA\Tag(
 *         name="Teacher - Assignments",
 *         description="O'qituvchi topshiriqlar boshqaruvi (vazifalar, baholash, statistika)"
 *     ),
 *     @OA\Tag(
 *         name="Teacher - Tests",
 *         description="O'qituvchi testlar boshqaruvi (testlar, savollar, natijalar, import/export)"
 *     ),
 *     @OA\Tag(
 *         name="Teacher - Exams",
 *         description="O'qituvchi imtihonlar boshqaruvi (imtihonlar, natijalar kiritish, statistika)"
 *     ),
 *     @OA\Tag(
 *         name="Teacher - Topics",
 *         description="O'qituvchi mavzular boshqaruvi (mavzular, o'quv dasturi, tartibga solish)"
 *     ),
 *     @OA\Tag(
 *         name="Teacher - Resources",
 *         description="O'qituvchi resurslar boshqaruvi (fayllar, havolalar, materiallar)"
 *     ),
 *     @OA\Tag(
 *         name="Teacher - Subjects",
 *         description="O'qituvchi fanlar (fanlar ro'yxati, talabalar, fan tafsilotlari)"
 *     )
 * )
 */
class OpenApiController extends Controller
{
    // This class only contains OpenAPI annotations
}
