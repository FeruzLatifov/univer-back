<?php

namespace Database\Factories;

use App\Models\EStudentMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

class EStudentMetaFactory extends Factory
{
    protected $model = EStudentMeta::class;

    public function definition(): array
    {
        return [
            '_student' => null,
            '_group' => null,
            '_curriculum' => null,
            '_semester' => 1,
            '_education_year' => '2023',
            '_student_status' => '11',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
