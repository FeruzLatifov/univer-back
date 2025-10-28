<?php

namespace App\Providers;

use App\Models\EStudent;
use App\Models\EGroup;
use App\Models\EEmployee;
use App\Policies\StudentPolicy;
use App\Policies\GroupPolicy;
use App\Policies\EmployeePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Authorization Service Provider
 *
 * Register policies for authorization
 * Best Practice: Central place for policy registration
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        EStudent::class => StudentPolicy::class,
        EGroup::class => GroupPolicy::class,
        EEmployee::class => EmployeePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
