# Auth API Paritet Spetsifikatsiyasi

## Maqsad
Yii2 dagi Auth oqimini 1:1 paritet bilan Laravelga ko‘chirish (DB sxemasiz).

## Endpointlar

### 1) Admin/Employee Login
- POST /v1/auth/admin-login
- Body: { login: string, password: string, rememberMe?: boolean }
- RateLimit: login/IP bo‘yicha (xatolar soni cheklangan)
- Response 200: { id, uuid, employee_id_number, type: 'employee', roles: Role[], name, login, email, phone, picture, university_id }
- Response 401: { message }

### 2) Student Login
- POST /v1/auth/login
- Body: { login: string, password: string }
- Response 200: { token: string }
- Set-Cookie: refresh-token=...; Path=/v1/auth/refresh-token; HttpOnly; Secure; SameSite=None
- Response 401/403: { message }

### 3) Refresh Token
- POST /v1/auth/refresh-token
- Cookie: refresh-token
- Response 200: { token: string }
- Response 401: { message }

## JWT Talablari
- Alg: HS256
- Claims: { uid, type: 'student'|'employee', iat, exp }
- Expiry: ~3600s (configga bog‘liq)

## Headerlar
- Authorization: Bearer <token>
- X-RateLimit-* (agar mavjud bo‘lsa)

## Xatoliklar
- 400/401/403 formatlari Yii2 dagidek (message)

## Eslatma
- Refresh token DB’da saqlanadi (StudentRefreshToken ekvivalent)
- Cookie: HttpOnly + Secure + SameSite=None


