<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EStudentMeta extends Model
{
    protected $table = 'e_student_meta';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        '_student',
        '_specialty',
        '_group',
        '_department',
        '_education_type',
        '_education_form',
        '_education_year',
        '_level',
        '_payment_form',
        'student_status',
        'academic_leave',
        'year_of_entered',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'academic_leave' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(EStudent::class, '_student', 'id');
    }

    public function group()
    {
        return $this->belongsTo(EGroup::class, '_group', 'id');
    }

    public function specialty()
    {
        return $this->belongsTo(ESpecialty::class, '_specialty', 'id');
    }

    public function department()
    {
        return $this->belongsTo(EDepartment::class, '_department', 'id');
    }

    public function educationType()
    {
        return $this->belongsTo(HEducationType::class, '_education_type', 'code');
    }

    public function educationForm()
    {
        return $this->belongsTo(HEducationForm::class, '_education_form', 'code');
    }

    public function paymentForm()
    {
        return $this->belongsTo(HPaymentForm::class, '_payment_form', 'code');
    }
}
