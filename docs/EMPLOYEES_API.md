# Employees API Contract

## Endpoints

### 1. GET /api/v1/employees
List employees with pagination and filters

**Query Parameters:**
- `q` (string): Search by name, employee_id_number
- `page` (int): Page number (default: 1)
- `limit` (int): Items per page (default: 20)
- `department_id` (string): Filter by department
- `faculty_id` (string): Filter by faculty
- `is_teacher` (boolean): Filter teachers only
- `status` (string): active|inactive|on-leave

**Response 200:**
```json
{
  "data": [
    {
      "id": "uuid",
      "employee_id_number": "EMP001",
      "full_name": "Alimov Jasur Rustamovich",
      "position": "Professor",
      "department_name": "Software Engineering",
      "faculty_name": "Information Technologies",
      "phone": "+998901234567",
      "email": "j.alimov@university.uz",
      "is_teacher": true,
      "academic_degree": "PhD",
      "academic_rank": "Professor",
      "status": "active"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  }
}
```

### 2. GET /api/v1/employees/{id}
Get employee details

**Response 200:**
```json
{
  "id": "uuid",
  "employee_id_number": "EMP001",
  "full_name": "Alimov Jasur Rustamovich",
  "first_name": "Jasur",
  "last_name": "Alimov",
  "middle_name": "Rustamovich",
  "birth_date": "1980-05-15",
  "gender": "male",
  "phone": "+998901234567",
  "email": "j.alimov@university.uz",
  "position": "Professor",
  "department_id": "dept-id",
  "department_name": "Software Engineering",
  "faculty_id": "fac-id",
  "faculty_name": "Information Technologies",
  "is_teacher": true,
  "academic_degree": "PhD",
  "academic_rank": "Professor",
  "hire_date": "2010-09-01",
  "status": "active",
  "photo": "url",
  "created_at": "2010-09-01T00:00:00Z",
  "updated_at": "2025-01-20T00:00:00Z"
}
```

### 3. GET /api/v1/employees/stats
Get employee statistics

**Response 200:**
```json
{
  "total_employees": 150,
  "total_teachers": 120,
  "total_staff": 30,
  "by_status": {
    "active": 140,
    "inactive": 5,
    "on_leave": 5
  },
  "by_degree": {
    "phd": 45,
    "master": 60,
    "bachelor": 15,
    "none": 30
  },
  "by_faculty": [
    {
      "faculty_id": "fac-1",
      "faculty_name": "IT",
      "count": 50
    }
  ]
}
```

### 4. POST /api/v1/employees (Phase 2)
Create employee

**Body:**
```json
{
  "employee_id_number": "EMP002",
  "first_name": "Ali",
  "last_name": "Karimov",
  "middle_name": "Bahromovich",
  "birth_date": "1985-03-20",
  "gender": "male",
  "phone": "+998901234568",
  "email": "a.karimov@university.uz",
  "position": "Senior Lecturer",
  "department_id": "dept-id",
  "is_teacher": true,
  "hire_date": "2015-09-01"
}
```

**Response 201:**
```json
{
  "id": "uuid",
  "employee_id_number": "EMP002",
  ...
}
```

### 5. PUT /api/v1/employees/{id} (Phase 2)
Update employee

### 6. DELETE /api/v1/employees/{id} (Phase 2)
Delete employee (soft delete)

## Database Mapping (Zero Schema Change)

**Table:** `e_employee`

**Columns:**
- `id` → id
- `employee_id_number` → employee_id_number
- `first_name` → first_name
- `second_name` → last_name
- `third_name` → middle_name
- `birth_date` → birth_date
- `_gender` → gender (FK to h_gender)
- `telephone` → phone
- `email` → email
- `_position` → position (FK to h_position)
- `_employee_status` → status
- `image` → photo
- `created_at`, `updated_at`

**Relations:**
- `_department` → `e_department` (id)
- `_faculty` → (through department)

## Validation
- employee_id_number: required, unique
- email: email format, nullable
- phone: nullable
- birth_date: date, before today

## Authorization
- List/View: any authenticated user
- Create/Update/Delete: `staff` or `super_admin` role

