<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EDocument extends Model
{
    protected $table = 'e_document';
    public $timestamps = false; // uses created_at/updated_at as datetime without Laravel casting

    protected $fillable = [
        'hash',
        'document_title',
        'document_type',
        'document_id',
        'status',
        '_webimzo_sign_request',
        '_webimzo_sign_data',
        '_admin',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        '_webimzo_sign_request' => 'array',
        '_webimzo_sign_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(EAdmin::class, '_admin', 'id');
    }

    public function signers(): HasMany
    {
        return $this->hasMany(EDocumentSigner::class, '_document', 'id');
    }
}


