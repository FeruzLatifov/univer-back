<?php
namespace App\Services\Employee;

use App\Models\EAdmin;
use App\Models\EEmployee;
use App\Services\CaptchaService;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    private CaptchaService $captchaService;
    
    public function __construct(CaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
    }
    
    public function attemptLogin(string $login, string $password, ?string $captcha, string $ipAddress): array
    {
        // CAPTCHA verification
        if ($this->captchaService->isEnabled()) {
            $result = $this->captchaService->verify($captcha, $ipAddress, 'employee_login');
            if (!$result['success']) {
                throw new \Exception('CAPTCHA verification failed');
            }
        }
        
        // Find admin by login or employee ID
        $admin = $this->findAdmin($login);
        
        if (!$admin || !Hash::check($password, $admin->password)) {
            throw new \Exception('Login yoki parol noto\'g\'ri');
        }
        
        // Generate token
        $token = auth('api')->login($admin);
        
        return [
            'user' => $admin,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }
    
    private function findAdmin(string $login)
    {
        // Try employee_id_number first
        if (ctype_digit($login) && strlen($login) > 9) {
            $employee = EEmployee::where('employee_id_number', $login)->first();
            if ($employee && $employee->admin) {
                return $employee->admin;
            }
        }
        
        // Fallback to login
        return EAdmin::where('login', $login)->where('status', 'enable')->first();
    }
    
    public function refreshToken(): array
    {
        return [
            'access_token' => auth('api')->refresh(),
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }
    
    public function logout(): void
    {
        auth('api')->logout();
    }
}
