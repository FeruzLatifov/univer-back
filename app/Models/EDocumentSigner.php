<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EDocumentSigner extends Model
{
    protected $table = 'e_document_signer';
    public $timestamps = true;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SIGNED = 'signed';
    public const TYPE_REVIEWER = 'reviewer';
    public const TYPE_APPROVER = 'approver';

    protected $fillable = [
        '_document',
        '_employee_meta',
        '_sign_data',
        'priority',
        'status',
        'type',
        'employee_name',
        'employee_position',
        'signed_at',
    ];

    protected $casts = [
        '_sign_data' => 'array',
        'signed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(EDocument::class, '_document', 'id');
    }

    public function employeeMeta(): BelongsTo
    {
        return $this->belongsTo(EEmployeeMeta::class, '_employee_meta', 'id');
    }
}


