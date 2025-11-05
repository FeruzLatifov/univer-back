<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * E-Document Model
 *
 * Represents electronic documents that require signatures
 *
 * @property int $id
 * @property string $hash Unique document hash for URL access
 * @property string $document_title Document title
 * @property string $document_type Document type class
 * @property int $document_id Reference document ID
 * @property string $status pending|signed|rejected
 * @property string $provider webimzo|eduimzo|local
 * @property array $_webimzo_sign_request WebImzo sign request data
 * @property array $_webimzo_sign_data WebImzo sign response data
 * @property array $_eduimzo_sign_request EduImzo sign request data
 * @property array $_eduimzo_sign_data EduImzo sign response data
 * @property int $_admin Admin who created the document
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class EDocument extends Model
{
    protected $table = 'e_document';
    public $timestamps = false;

    const STATUS_PENDING = 'pending';
    const STATUS_SIGNED = 'signed';
    const STATUS_REJECTED = 'rejected';

    const PROVIDER_WEBIMZO = 'webimzo';
    const PROVIDER_EDUIMZO = 'eduimzo';
    const PROVIDER_LOCAL = 'local';

    protected $fillable = [
        'hash',
        'document_title',
        'document_type',
        'document_id',
        'status',
        'provider',
        '_webimzo_sign_request',
        '_webimzo_sign_data',
        '_eduimzo_sign_request',
        '_eduimzo_sign_data',
        '_admin',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        '_webimzo_sign_request' => 'array',
        '_webimzo_sign_data' => 'array',
        '_eduimzo_sign_request' => 'array',
        '_eduimzo_sign_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            if (!$document->hash) {
                $document->hash = (string) Str::uuid();
            }
            if (!$document->status) {
                $document->status = self::STATUS_PENDING;
            }
        });
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(EAdmin::class, '_admin', 'id');
    }

    public function signers(): HasMany
    {
        return $this->hasMany(EDocumentSigner::class, '_document', 'id')
            ->orderBy('priority');
    }

    public function signedSigners(): HasMany
    {
        return $this->hasMany(EDocumentSigner::class, '_document', 'id')
            ->where('status', EDocumentSigner::STATUS_SIGNED)
            ->orderBy('priority');
    }

    public function pendingSigners(): HasMany
    {
        return $this->hasMany(EDocumentSigner::class, '_document', 'id')
            ->where('status', EDocumentSigner::STATUS_PENDING)
            ->orderBy('priority');
    }

    public function isSignedByAll(): bool
    {
        $total = $this->signers()->count();
        $signed = $this->signedSigners()->count();
        return $total > 0 && $total === $signed;
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Imzolanmagan',
            self::STATUS_SIGNED => 'Imzolangan',
            self::STATUS_REJECTED => 'Rad etilgan',
            default => $this->status,
        };
    }

    public function getProviderLabel(): string
    {
        return match($this->provider) {
            self::PROVIDER_WEBIMZO => 'WebImzo',
            self::PROVIDER_EDUIMZO => 'EduImzo',
            self::PROVIDER_LOCAL => 'Lokal',
            default => $this->provider,
        };
    }
}


