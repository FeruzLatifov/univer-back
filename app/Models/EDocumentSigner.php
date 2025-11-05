<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * E-Document Signer Model
 *
 * Represents a person who needs to sign a document
 *
 * @property int $id
 * @property int $_document Document ID
 * @property int $_employee_meta Employee meta ID
 * @property array $_sign_data Digital signature data
 * @property int $priority Signing order (1, 2, 3...)
 * @property string $status pending|signed
 * @property string $type reviewer|approver
 * @property string $employee_name Employee full name (cached)
 * @property string $employee_position Position title (cached)
 * @property \DateTime $signed_at When was signed
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class EDocumentSigner extends Model
{
    protected $table = 'e_document_signer';
    public $timestamps = true;

    const STATUS_PENDING = 'pending';
    const STATUS_SIGNED = 'signed';
    const TYPE_REVIEWER = 'reviewer';  // Kelishuvchi
    const TYPE_APPROVER = 'approver';  // Tasdiqlovchi

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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['document_hash'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(EDocument::class, '_document', 'id');
    }

    public function employeeMeta(): BelongsTo
    {
        return $this->belongsTo(EEmployeeMeta::class, '_employee_meta', 'id');
    }

    public function employee(): HasOneThrough
    {
        return $this->hasOneThrough(
            EEmployee::class,
            EEmployeeMeta::class,
            '_employee',      // Foreign key on employee_meta table
            'id',             // Foreign key on employee table
            '_employee_meta', // Local key on document_signer table
            'id'              // Local key on employee_meta table
        );
    }

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isReviewer(): bool
    {
        return $this->type === self::TYPE_REVIEWER;
    }

    public function isApprover(): bool
    {
        return $this->type === self::TYPE_APPROVER;
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_REVIEWER => 'Kelishuvchi',
            self::TYPE_APPROVER => 'Tasdiqlovchi',
            default => $this->type,
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Imzolanmagan',
            self::STATUS_SIGNED => 'Imzolangan',
            default => $this->status,
        };
    }

    /**
     * Get document hash accessor
     */
    public function getDocumentHashAttribute(): ?string
    {
        return $this->document?->hash;
    }
}


