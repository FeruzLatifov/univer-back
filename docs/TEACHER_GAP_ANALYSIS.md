# Teacher Role — Yii2 → Laravel Gap Analysis

**Sana**: 2026-05-19  
**Maqsad**: Yii2 (`../univer/`) loyihasidagi **tutor/teacher** funksionali bilan yangi `univer-back` Laravel'dagi joriy implementatsiyani solishtirish va yetishmagan qismlarni aniqlash.

> Yii2'da ikki tushuncha bor:
> - **Teacher** (o'qituvchi) — fan beradigan xodim, baho qo'yadi, dars o'tadi.
> - **Tutor** (kurator) — guruh boshlig'i, talabalarning ijtimoiy faolligi, davomati, kontrakt qarzlari bilan ishlaydi.
>
> `univer-back` hozircha ikkisini ham **"teacher"** deb bitta nomda qarayapti. Yii2'dagi `tutor` (`/api/modules/ver1/tutor/`) modul aslida **ko'p funksiyali kurator paneli** edi — uning ko'p qismi univer-back'da hali yo'q.

---

## 1. ✅ univer-back'da TAYYOR bo'lganlar

Quyidagilar to'liq ishlangan, service layer'i bilan, frontend bilan ulangan:

| Sohada | Endpoints | Service | Frontend Page |
|---|---|---|---|
| **Dashboard** | 3 (`/dashboard`, `/activities`, `/stats`) | `DashboardService.php` (372 q) | — (faqat ma'lumot ko'rsatish kerakli sahifa yo'q) |
| **Schedule / Workload** | 5 (`/schedule`, `/today`, `/day/{d}`, `/workload`, `/groups`) | `ScheduleService.php` (265 q) | `SchedulePage.tsx`, `WorkloadPage.tsx` |
| **Subjects** | 3 (list, detail, students) | `SubjectService.php` (179 q) | `SubjectsPage.tsx`, `SubjectDetailPage.tsx` |
| **Attendance** | 4 (list, mark-bulk, update, report) | `AttendanceService.php` (254 q) | `AttendancePage.tsx` |
| **Grades** | 4 (list, create, update, report) | `GradeService.php` (314 q) | `GradesPage.tsx` |
| **Assignments** | 14 (CRUD + submissions + grading + publish/unpublish + stats + activities + my-subjects/my-groups) | `AssignmentService.php` (577 q) | `assignments/*` (3 sahifa) |
| **Tests/Quizzes** | 18 (CRUD + questions CRUD + reorder + results + stats + import/export + template) | `TestService.php` (577 q) | `tests/*` (6 sahifa) |
| **Exams** | 5 (list, detail, create, enter results, stats) | `ExamService.php` (287 q) | `ExamsPage.tsx` |
| **Topics** | 6 (CRUD + reorder + syllabus) | `TopicService.php` (181 q) | `TopicsPage.tsx`, `CreateTopicPage.tsx` |
| **Resources** | 6 (CRUD + types + download) | `ResourceService.php` (175 q) | `ResourcesPage.tsx` |
| **Document Signing** | 4 (sign/view/status/list) | shared via `DocumentService` | (Employee bo'limida) |
| **Messaging** | shared | shared `MessagingController` | `MessagesPage.tsx`, `MessageDetailPage.tsx`, `ComposeMessagePage.tsx` |
| **Notifications** | shared | shared `NotificationController` | `NotificationsPage.tsx`, `NotificationSettingsPage.tsx` |
| **Forum** | shared | shared `ForumController` | `ForumCategoriesPage.tsx`, `ForumTopicsPage.tsx`, `ForumTopicDetailPage.tsx` |
| **Workload export** | 1 (`/export/reports/teacher-workload/{id}`) | `ExportService` | — |

**Jami**: ~70 endpoint, 10 service, 20 frontend sahifa.

---

## 2. ❌ univer-back'da YETISHMAYAPTI

Quyidagilar Yii2'da bor, lekin univer-back'da yo'q yoki yarim ishlangan.

### 2A. KRITIK — kurator (tutor) funksiyalari

Yii2'ning `api/modules/ver1/tutor/` moduli kuratorlik panelining yuragi. **Hech biri univer-back'da yo'q.**

#### 🔴 G1. Tutor visits (kurator tashriflari) — `ETutorVisit` modeli
- Yii2: `StudentController::actionVisitList()`, `actionVisitCreate($id)`
- URLs: `GET /v1/tutor/student/visit-list`, `POST /v1/tutor/student/visit-create?id={id}`
- Maqsad: Kurator talaba uyiga/yotoqxonasiga tashrif buyurib, hisobot yozadi. **Pedagogik nazorat uchun majburiy.**
- univer-back: yo'q (model ham, controller ham, service ham, sahifa ham yo'q)

#### 🔴 G2. Social Activity / Ijtimoiy faollik (KATTA modul!)
- Yii2: `SocialActivityController` — **13 ta action**
- URLs: `/v1/tutor/social-activity/{directions,applications,application,criteria-approve,criteria-reject,application-approve,application-reject,category-approve,direction-reject,file-upload,criteria-save,calculate-system-scores,set-manual-system-criteria,rating}`
- Maqsad: Talabalarning ijtimoiy/akademik faollik ballarini hisoblash → grant/stipendiya berishga ta'sir qiladi. **Davlat hujjatlari bilan bog'liq.**
- univer-back: butunlay yo'q (`App\Services\SocialActivityService.php` ham yo'q)
- Frontend: sahifa ham yo'q

#### 🔴 G3. Contracts / Shartnomalar (moliyaviy)
- Yii2: `ContractController::actionList()`, `actionView($id)`, `actionDebtors()`
- URLs: `GET /v1/tutor/contract/{list,view,debtors}`
- Maqsad: Kurator o'z guruh talabalari to'lov shartnomalari va qarzdorlikni ko'radi. **Buxgalteriya integratsiyasi.**
- univer-back: yo'q (admin uchun ham yo'q ko'rinadi)

#### 🟡 G4. Student profile management by tutor
- Yii2: `StudentController::actionView/Update/History/List/Passport`
- URLs: `GET /v1/tutor/student/{view,history,history-list,list,passport}?id={id}`, `POST /v1/tutor/student/update?id={id}`
- Maqsad: Kurator o'z talabalarining anketa ma'lumotlarini (telefon, manzil, yashash holati, ish joyi, oila) yangilaydi.
- univer-back: yo'q. Hozir faqat admin Student CRUD bor (`Api/V1/Admin/StudentController`), lekin tutor uchun **scope cheklangan** versiya yo'q.

#### 🟡 G5. Dashboard statistics (tutor uchun)
- Yii2: `StatisticsController` — `actionDashboard()` 14 ta `expand` parametri bilan (gender, education_form, course, social, terrain, living_status, accommodation, district, geo_location, attendance, absenteeism, performance, contracts, groups, students)
- URL: `GET /v1/tutor/statistics/dashboard?expand=...`
- univer-back: oddiy `dashboard/stats` bor, lekin **expansion logikasi** va **demografik break-down** yo'q.

#### 🟡 G6. Tutor rating / Leaderboard
- Yii2: `StatisticsController::actionRating/Leaderboard/LeaderboardExport/Monitoring`
- URLs: `GET /v1/tutor/statistics/{rating,leaderboard,leaderboard-export,monitoring}`
- Maqsad: Kuratorlar reytingi (qaysi kurator faolroq) — universitetning ichki KPI tizimi.
- univer-back: yo'q. `TutorRatingService` va `TutorMonitoringService` modellar darajasida ham yo'q.

#### 🟡 G7. Groups management endpoints
- Yii2: `GroupController::actionList/View/Students/Semesters`
- URLs: `GET /v1/tutor/group/{list,view/{id},students/{id},semesters}`
- univer-back: `Teacher/ScheduleController::groups()` bor, lekin **alohida group view** va **semesters** endpoint'lari yo'q. Group nomi-`scope` va tutor-specific filtering juda farq qiladi.

### 2B. KRITIK — fan o'qituvchisi (teacher) funksiyalari

Bular Yii2'da `backend/controllers/TeacherController.php` orqali admin paneli ichida ishlatilardi.

#### 🔴 T1. Rating Journal / Reyting jurnali
- Yii2: `TeacherController::actionRatingJournal`, `actionCheckGrade`, `actionCheckRating`, `actionCheckOverallRating`, `actionPrintRating`, `actionDownloadRating`
- Maqsad: O'qituvchi dars-dars baho qo'yadi (har bir lesson uchun, mavzu bo'yicha). univer-back'dagi "grades" oddiy summativ baho — bu lesson-level granularity yo'q.
- univer-back: **qisman bor** (Grades), lekin **dars-bo'lim-mavzu** uch o'lchovli tartib yo'q.

#### 🔴 T2. Calendar Plan / Taqvimiy reja
- Yii2: `TeacherController::actionCalendarPlan`
- Maqsad: O'qituvchi semestr boshida mavzular jadvalini sanalar bo'yicha tuzadi (ish dasturi).
- univer-back: yo'q. `Topics` mavzularni list qiladi, lekin **sanaviy plan** yo'q.

#### 🔴 T3. Midterm / Final / Other exam tables
- Yii2: `TeacherController::actionMidtermExamTable`, `actionFinalExamTable`, `actionOtherExamTable`
- Maqsad: Imtihon natijalarini **jadval-stilida** (Excel-ga o'xshash) bir vaqtda barcha talabaga kiritish.
- univer-back: `Exam::enterResults()` (bulk POST) bor, lekin **inline editable jadval UI** uchun moslashtirilgan endpoint yo'q (per-student grade with grade-letter A/B/C/F).

#### 🔴 T4. QR Attendance
- Yii2: `TeacherController::actionQrAttendance`, `actionQrAttendancePoll`, `actionQrAttendanceSave`
- Maqsad: O'qituvchi QR-kod ko'rsatadi, talabalar telefondan skanerlab davomat belgilaydi.
- univer-back: yo'q. **Mobil ilova uchun majburiy** feature.

#### 🟡 T5. Subject Tasks (o'qituvchi qabul qiladigan vazifalar)
- Yii2: `TeacherController::actionSubjectTasks`, `actionSubjectTaskList`, `actionSubjectTaskStatus`, `actionSubjectTaskOther`
- Maqsad: Universitet adminstratsiyasi o'qituvchiga vazifa beradi ("3-kursga test tayyorla", "yangi metodika kiriting"). Bu **assignments emas** — bu **work-tickets** (Jira'ga o'xshash).
- univer-back: yo'q.

#### 🟡 T6. Test Export/Import
- Yii2: `TeacherController::actionTestExport`, `actionTestImport`
- univer-back: TestController'da `import` va `export` bor (CSV). ✅ **TAYYOR**

#### 🟡 T7. Subject Resource & Topic Resource Edit (backend admin)
- Yii2: `TeacherController::actionSubjectResources`, `actionSubjectTopicResource`, `actionSubjectTopicResourceEdit`
- univer-back: `Resources` controller'i bor, ammo **topic'ga bog'lash** endpoint'lari to'liq emas (`topic_id` parametri faqat upload'da).

#### 🟡 T8. Surveys / Anketalar
- Yii2: `TeacherController::actionSurveyCheck`, `actionSurvey`, `actionSurveyStart`, `actionSurveyAnswer`, `actionSurveyFinish`
- Maqsad: O'qituvchi davriy anketani to'ldiradi (siz-talabalar fikr-mulohazasi, ish sharoiti, etc.)
- univer-back: yo'q.

#### 🟡 T9. Certificate committee results
- Yii2: `TeacherController::actionCertificateCommitteeResult/Edit`
- Maqsad: Komitet a'zosi sifatida sertifikat natijalarini ko'radi/tasdiqlaydi.
- univer-back: yo'q. (Document signing bor, lekin bu boshqa workflow.)

### 2C. KRITIK — Reference Data (lookup)

Yii2: `ReferenceController` — 12 endpoint:
- `/countries`, `/provinces`, `/districts`, `/terrains`, `/student-living-statuses`, `/accommodations`, `/student-roommate-types`, `/specialties`, `/student-statuses`, `/education-years`, `/subjects`, `/semesters`

#### 🟡 R1. univer-back'da bular yo'q
- Hozir frontend bu ma'lumotlarni admin endpoint'lari yoki hardcoded ro'yxat orqali oladi.
- univer-back'da `HCountry`, `HLanguage` kabi model'lar bor, lekin **public reference endpoint** yo'q (`/api/v1/teacher/reference/*` yo'q).

### 2D. Schedule data shape farqi

Yii2'ning schedule javobi:
```json
{
  "id": 123,
  "lesson_date": "2026-05-19",
  "auditorium": "201",
  "lesson_pair": "1",
  "training_type": "ma'ruza",
  "subject": {...},
  "group": {...},
  "employee": {...}
}
```

univer-back'da `ScheduleService` javobida:
- `auditorium` bormi? (model'da bor: `ELessonPair`)
- `training_type` bormi? — kerakli
- `lesson_pair` raqami?

**Tekshirish kerak** — agar yo'q bo'lsa frontend `SchedulePage.tsx` to'liq jadvalni ko'rsata olmaydi.

### 2E. Schedule filter options endpoint

- Yii2: `ScheduleController::actionFilterOptions()` — faculties, curriculums, education_years, semesters, groups
- univer-back: yo'q (frontend filter qilolmaydi yoki har birini alohida sotib oladi)

### 2F. OneID OAuth — Hukumat SSO

- Yii2: `Tutor/AuthController::onOauthSuccess` + `SsoController` (`/v1/sso/*`)
- Maqsad: Davlat OneID portali orqali login (PIN bo'yicha xodimni topish)
- univer-back: yo'q. `OAuthController` bor, lekin **OneID provider** emas — bu **OAuth2 server** (boshqa tomondan).

### 2G. GPA & Debtors

- Yii2: `GradeController::actionGpa()`, `actionDebtors()`
- univer-back: Faqat student-side compatibility'da `Student\GradeController::index()` bor; **teacher tomonidan o'z guruhi GPA'sini ko'rish** yo'q.

### 2H. Profile features

| Feature | Yii2 | univer-back |
|---|---|---|
| Tutor login | ✅ Alohida `Tutor/AuthController` | 🟡 Employee'da umumiy |
| Update profile (email, phone) | ✅ `Profile/actionUpdate` | ❌ yo'q |
| Photo upload | (talabalar uchun bor) | ❌ Teacher uchun yo'q |

### 2I. Frontend `ReportsPage.tsx` — **MOCK DATA** 🚨

`src/modules/teacher/pages/ReportsPage.tsx:37` da `const mockData = {...}` — sahifa **soxta raqamlar** ko'rsatyapti. Backend'da `reports` controller'i yo'q.

---

## 3. Bog'liqliklar / Ma'lumotlar bazasi modellari

Yii2'da bor lekin univer-back'da yo'q model'lar:

| Yii2 Model | Maqsad | univer-back'da |
|---|---|---|
| `ETutorVisit` | Kurator tashriflari hisoboti | ❌ yo'q |
| `ETutorTask` | Kuratorga tayinlangan vazifalar | ❌ yo'q |
| `ETutorMonitoringStat` | Kurator monitoring stats | ❌ yo'q |
| `ETeacherLoad`, `ETeacherLoadMeta`, `ETeacherLoadMethodical`, `ETeacherLoadScientific`, `ETeacherLoadMetaGroup` | Teacher workload detallari | 🟡 `ETeacherLoad` bor, qolganlari? Tekshirish kerak |
| `EDepartmentLoad`, `EDepartmentLoadMeta` | Kafedra yuklamasi | ❌ yo'q |
| `EEmployeeProfessionalDevelopment`, `EEmployeeTraining`, `EEmployeeForeignCertificate`, `EEmployeeAcademicDegree`, `EEmployeeCompetition` | Xodim trening/sertifikat/daraja | ❌ yo'q |
| Social activity tables (`e_social_*`) | Ijtimoiy faollik | ❌ yo'q |
| Contract tables (`e_contract*`) | Shartnomalar | ❌ yo'q |

---

## 4. Prioritetlar (mening tavsiyam)

**P0 — Frontend hozir buzilgan**:
- ✅ T_FIX. `ReportsPage.tsx` mock'larini olib tashlash yoki real Reports API yaratish

**P1 — Tutor sifatida ishlash uchun majburiy** (kurator paneli):
- G1. Tutor visits
- G3. Contracts (talabalar to'lov ko'rinishi)
- G7. Groups management endpoints
- G4. Student profile by tutor (scope-cheklangan)
- G2. Social activity (KATTA — bosqichma-bosqich)

**P2 — Teacher sifatida to'liq bo'lishi uchun**:
- T1. Rating Journal (lesson-level grades)
- T2. Calendar Plan
- T3. Inline exam tables
- T4. QR Attendance (mobil uchun)

**P3 — Sistema yaxshilashlari**:
- 2D. Schedule data shape (auditorium, training_type)
- 2E. Schedule filter options
- 2C. Reference data endpoints
- G5/G6. Statistics expansion, leaderboard
- T5. Subject tasks (work-tickets)

**P4 — Keyinroq**:
- 2F. OneID OAuth (hukumat integratsiyasi tayyor bo'lganda)
- T8. Surveys
- T9. Certificate committee

---

## 5. Effort estimate (qo'pol)

| Bo'lim | Endpoint'lar | Service | Migration | Sahifa | Effort |
|---|---|---|---|---|---|
| ReportsPage fix | 1-2 | 1 | 0 | 1 | 0.5 kun |
| Tutor visits | 2 | 1 | 1 | 1 | 1 kun |
| Contracts | 3 | 1 | maybe (read-only) | 1 | 1 kun |
| Groups management | 4 | extend | 0 | extend SubjectsPage | 1 kun |
| Student profile by tutor | 5 | 1 | 0 | 1 | 2 kun |
| Social activity | 13 | 1 | 3-4 | 2-3 | **5-7 kun** |
| Rating Journal | 4 | extend Grade | 1 (jurnal jadvali) | 1 | 2 kun |
| Calendar Plan | 4 | 1 | 1 | 1 | 1.5 kun |
| Inline exam tables | 1-2 (extend) | extend Exam | 0 | 1 | 1 kun |
| QR Attendance | 3 | 1 | 1 | 1 | 1.5 kun |
| Schedule shape fix | 0 | extend | 0 | 0 | 0.5 kun |
| Schedule filter options | 1 | 1 | 0 | extend page | 0.5 kun |
| Reference data | 8-12 | 1 | 0 | 0 | 0.5 kun |
| Statistics expansion | 4 | extend | 0 | 1 | 2 kun |
| **Jami** | **~55** | **~10 yangi** | **~7** | **~13** | **~20-25 kun** |

---

## 6. Yon-ta'sirlar va Yii2 bilan moslik

- `e_*` jadvallari (`ETutorVisit`, `e_social_*`, `e_contract`) Yii2 tomonida hali ham ishlatiladi. Migration qilishdan oldin Yii2 model'larini ko'rib chiqish kerak (skill: `add-shared-table-migration`).
- Social activity hisob-kitobi (`calculate-system-scores`) algoritmi murakkab — Yii2'dagi `SocialActivityService` ni o'qib, **bir xil natija beradigan** logikani Laravel'da takrorlash kerak.
- Contracts'lar bilan ishlash uchun moliyaviy modul (`e_contract*`, `e_payment*`) sxemasini to'liq tushunish kerak.

---

**Xulosa**: Teacher roli hozir ~**70%** tugallangan deyish mumkin (asosiy dars/baho/test workflow). Lekin **kurator (tutor) funksiyalari ~10%** tayyor, va admin paneli ichidagi teacher tools ~30% tayyor. To'liq feature parity uchun yana ~20-25 kunlik ish.
