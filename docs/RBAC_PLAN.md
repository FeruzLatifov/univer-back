# RBAC (Roles/Permissions) Mapping Rejasi

## Model
- Roles: `e_admin_role` (code, name, status, position, translations)
- Resources: `e_admin_resource` (path, group, name, skip, login, active)
- Pivot: `e_admin_role_resource` (_role, _resource)

## Parse/Sync Strategiya
- Controller/Action dan resources generatsiya (route list/reflection)
- admin-roles.json dan rollarni va resources'ni DBga sinxronlash
- Super Admin – doim enable, delete taqiqlangan

## Frontend Guard
- JWT claims: roles[code] (+ ixtiyoriy: resources[path])
- Route meta: requiredRole/requiredResource
- UI-level guard: hasRole/hasResource helper

## Audit
- Role × Resource matrisa (CSV/JSON) export
- Snapshot test: turli rolda 401/403 va UI ko'rinish

## Xavfsizlik
- Public (skip) resource'lar minimal bo'lsin
- Rate limit login va heavy endpointlarda
- RBAC o'zgarishlari auditi (log)

## Rollar Ro'yxati (univer-yii2 dan)
- `super_admin` - Sistema administratori
- `dean` - Fakultet dekani  
- `teacher` - O'qituvchi
- `department` - Kafedra mudiri
- `staff` - Kadrlar bo'limi
- `direction` - Rahbariyat
- `academic_board` - O'quv bo'limi
- `marketing` - Marketing bo'limi
- `accounting` - Buxgalteriya
- `inspector` - Nazoratchi
- `academic_vice_rector` - O'quv ishlari bo'yicha prorektor
- `registrator_office` - Registratura
- `auditor` - Auditor
- `lawyer` - Yurist
- `finance_control` - Moliya nazorati
- `librarian` - Kutubxonachi
- `dormitory` - Yotoqxona
- `user` - Oddiy foydalanuvchi

## Resource Path Format
```
controller-id/action-id
```

Misol:
```
students/index
students/view
students/create
students/update
students/delete
employees/index
decrees/create
```

