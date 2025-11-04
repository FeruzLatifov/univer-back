<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;

/**
 * API Documentation Filter Service
 *
 * Filters OpenAPI spec based on user permissions and roles
 * Single Source of Truth approach
 */
class ApiDocumentationService
{
    /**
     * Filter OpenAPI spec by user permissions
     *
     * @param array $userPermissions User's permissions
     * @param array $userRoles User's roles
     * @param string $userType User type (employee, student, system)
     * @return array Filtered OpenAPI spec
     */
    public function filterByPermissions(
        array $userPermissions,
        array $userRoles,
        string $userType
    ): array {
        $masterSpec = $this->loadMasterSpec();

        // Filter paths
        $filteredPaths = [];

        foreach ($masterSpec['paths'] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                // Skip non-operation keys (parameters, etc)
                if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    continue;
                }

                // Check if user has access
                if ($this->hasAccess($operation, $userPermissions, $userRoles, $userType)) {
                    if (!isset($filteredPaths[$path])) {
                        $filteredPaths[$path] = [];
                    }
                    $filteredPaths[$path][$method] = $operation;
                }
            }
        }

        $masterSpec['paths'] = $filteredPaths;

        return $masterSpec;
    }

    /**
     * Generate role-specific view
     *
     * @param string $role Role name (teacher, student, admin)
     * @return array Filtered spec
     */
    public function generateRoleView(string $role): array
    {
        $rolePermissions = $this->getRolePermissions($role);
        $userType = $this->getRoleUserType($role);

        return $this->filterByPermissions(
            $rolePermissions['permissions'],
            $rolePermissions['roles'],
            $userType
        );
    }

    /**
     * Check if user has access to endpoint
     */
    private function hasAccess(
        array $operation,
        array $userPermissions,
        array $userRoles,
        string $userType
    ): bool {
        // Public endpoint (no auth required)
        if (isset($operation['x-auth-required']) && $operation['x-auth-required'] === false) {
            return true;
        }

        // Check user type
        if (isset($operation['x-user-types'])) {
            if (!in_array($userType, $operation['x-user-types']) &&
                !in_array('public', $operation['x-user-types'])) {
                return false;
            }
        }

        // Check permissions (if user has ANY of required permissions)
        if (isset($operation['x-permissions']) && !empty($operation['x-permissions'])) {
            $hasPermission = false;

            foreach ($operation['x-permissions'] as $requiredPerm) {
                // Support wildcard permissions (admin.*)
                foreach ($userPermissions as $userPerm) {
                    if ($this->matchesPermission($userPerm, $requiredPerm)) {
                        $hasPermission = true;
                        break 2;
                    }
                }
            }

            if (!$hasPermission) {
                return false;
            }
        }

        // Check roles (if user has ANY of allowed roles)
        if (isset($operation['x-roles']) && !empty($operation['x-roles'])) {
            $hasRole = false;

            foreach ($operation['x-roles'] as $allowedRole) {
                if (in_array($allowedRole, $userRoles)) {
                    $hasRole = true;
                    break;
                }
            }

            if (!$hasRole) {
                return false;
            }
        }

        return true;
    }

    /**
     * Match permission with wildcard support
     */
    private function matchesPermission(string $userPerm, string $requiredPerm): bool
    {
        // Exact match
        if ($userPerm === $requiredPerm) {
            return true;
        }

        // Wildcard match (e.g., admin.* matches admin.teacher.view)
        if (str_ends_with($userPerm, '.*')) {
            $prefix = substr($userPerm, 0, -2);
            if (str_starts_with($requiredPerm, $prefix . '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load master spec from file
     */
    private function loadMasterSpec(): array
    {
        $yamlPath = storage_path('api-docs/master-api.yaml');

        if (!file_exists($yamlPath)) {
            $yamlPath = base_path('docs/api/v1/master.yaml');
        }

        if (!file_exists($yamlPath)) {
            throw new \Exception('Master API spec not found');
        }

        return Yaml::parseFile($yamlPath);
    }

    /**
     * Get permissions for role (for generating static views)
     */
    private function getRolePermissions(string $role): array
    {
        $map = [
            'teacher' => [
                'permissions' => [
                    'teacher.*',
                ],
                'roles' => [
                    'teacher',
                    'senior_teacher',
                    'head_teacher',
                    'assistant_teacher',
                ],
            ],
            'student' => [
                'permissions' => [
                    'student.*',
                ],
                'roles' => [
                    'student',
                    'graduate_student',
                ],
            ],
            'admin' => [
                'permissions' => [
                    'admin.*',
                ],
                'roles' => [
                    'admin',
                    'superadmin',
                    'dean',
                    'rector',
                    'hr_manager',
                ],
            ],
            'integration' => [
                'permissions' => [
                    'integration.*',
                ],
                'roles' => [
                    'system',
                    'integration_admin',
                ],
            ],
        ];

        return $map[$role] ?? [
            'permissions' => [],
            'roles' => [],
        ];
    }

    /**
     * Get user type for role
     */
    private function getRoleUserType(string $role): string
    {
        $map = [
            'teacher' => 'employee',
            'student' => 'student',
            'admin' => 'employee',
            'integration' => 'system',
        ];

        return $map[$role] ?? 'employee';
    }

    /**
     * Export filtered spec to YAML file
     */
    public function exportToYaml(array $spec, string $filename): void
    {
        $yamlPath = storage_path("api-docs/{$filename}");

        file_put_contents($yamlPath, Yaml::dump($spec, 10, 2));
    }
}
