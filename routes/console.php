<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\EAdminResource;
use App\Models\EAdmin;
use App\Services\Menu\MenuService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Diagnostics: Check resources and role linkage (Yii2-compatible)
Artisan::command('menu:check-resource {paths*} {--user=admin}', function () {
    $paths = (array) $this->argument('paths');
    $login = (string) $this->option('user');

    $user = EAdmin::where('login', $login)->first();
    if (!$user) {
        $this->error("User with login '{$login}' not found");
        return 1;
    }

    $this->info("User: {$user->login}, role_id={$user->_role}");

    foreach ($paths as $p) {
        $path = trim($p, '/');
        $res = EAdminResource::where('path', $path)->first();
        if (!$res) {
            $this->line("{$p} => NOT_FOUND");
            continue;
        }

        $linked = $user->role
            ? $user->role->resources()->where('e_admin_resource.id', $res->id)->exists()
            : false;

        $dot = $res->toPermissionName();
        $perms = $user->getAllPermissions();
        $hasDot = in_array($dot, $perms, true);
        $hasRoute = in_array($path, $perms, true);

        $this->line(sprintf(
            "%s => FOUND id=%d active=%d skip=%d linked_to_role=%d dot=%s in_perms(dot)=%d in_perms(route)=%d",
            $p,
            $res->id,
            $res->active ? 1 : 0,
            $res->skip ? 1 : 0,
            $linked ? 1 : 0,
            $dot,
            $hasDot ? 1 : 0,
            $hasRoute ? 1 : 0,
        ));
    }
})->purpose('Diagnose e_admin_resource linkage and permissions');

// Preview: Build filtered menu for user
Artisan::command('menu:preview {--user=admin} {--locale=uz} {--json}', function () {
    $login = (string) $this->option('user');
    $locale = (string) $this->option('locale');
    $asJson = (bool) $this->option('json');

    $user = EAdmin::where('login', $login)->first();
    if (!$user) {
        $this->error("User with login '{$login}' not found");
        return 1;
    }

    /** @var MenuService $svc */
    $svc = app(MenuService::class);
    $resp = $svc->getMenuForUser($user, $locale);

    if ($asJson) {
        $this->line(json_encode($resp->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return 0;
    }

    $menu = $resp->toArray();
    $this->info('Menu generated:');
    $this->line('items='.count($menu['data']['menu']).' cached='.(int)$menu['meta']['cached'].' locale='.$menu['data']['locale']);
})->purpose('Preview filtered menu for a user and locale');
