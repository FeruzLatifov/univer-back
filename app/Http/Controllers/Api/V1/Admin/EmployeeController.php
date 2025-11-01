<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EmployeeResource;
use App\Models\EEmployee;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Employee Management Controller (Admin Panel)
 *
 * API Version: 1.0
 * Purpose: Xodimlarni boshqarish (CRUD)
 */
class EmployeeController extends Controller
{
    /**
     * Get employees list with filters
     *
     * @route GET /api/v1/admin/employees
     */
    public function index(Request $request)
    {
        $employees = QueryBuilder::for(EEmployee::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('first_name'),
                AllowedFilter::partial('second_name'),
                AllowedFilter::partial('employee_id_number'),
                AllowedFilter::exact('_gender'),
                AllowedFilter::exact('active'),
            ])
            ->allowedIncludes(['admin', 'meta'])
            ->allowedSorts(['id', 'second_name', 'first_name', 'hire_date', 'created_at'])
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => EmployeeResource::collection($employees->items()),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ],
        ]);
    }

    /**
     * Get single employee
     *
     * @route GET /api/v1/admin/employees/{id}
     */
    public function show($id)
    {
        $employee = QueryBuilder::for(EEmployee::class)
            ->allowedIncludes(['admin', 'meta'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new EmployeeResource($employee),
        ]);
    }

    /**
     * Create new employee
     *
     * @route POST /api/v1/admin/employees
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'second_name' => 'required|string|max:100',
            'third_name' => 'nullable|string|max:100',
            'birth_date' => 'required|date|before:today',
            'employee_id_number' => 'required|string|unique:e_employee,employee_id_number',
            '_gender' => 'required|string',
            '_country' => 'required|string',
            'passport_number' => 'nullable|string|max:50',
            'passport_pin' => 'nullable|string|max:50',
            'hire_date' => 'required|date',
            'image' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        $employee = EEmployee::create($validated);

        return response()->json([
            'success' => true,
            'data' => new EmployeeResource($employee),
            'message' => 'Xodim muvaffaqiyatli yaratildi',
        ], 201);
    }

    /**
     * Update employee
     *
     * @route PUT /api/v1/admin/employees/{id}
     */
    public function update(Request $request, $id)
    {
        $employee = EEmployee::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'second_name' => 'sometimes|string|max:100',
            'third_name' => 'nullable|string|max:100',
            'birth_date' => 'sometimes|date|before:today',
            'employee_id_number' => 'sometimes|string|unique:e_employee,employee_id_number,' . $id,
            '_gender' => 'sometimes|string',
            '_country' => 'sometimes|string',
            'passport_number' => 'nullable|string|max:50',
            'passport_pin' => 'nullable|string|max:50',
            'hire_date' => 'sometimes|date',
            'image' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'data' => new EmployeeResource($employee),
            'message' => 'Xodim muvaffaqiyatli yangilandi',
        ]);
    }

    /**
     * Delete employee (soft delete)
     *
     * @route DELETE /api/v1/admin/employees/{id}
     */
    public function destroy($id)
    {
        $employee = EEmployee::findOrFail($id);
        $employee->update(['active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Xodim o\'chirildi',
        ]);
    }
}
