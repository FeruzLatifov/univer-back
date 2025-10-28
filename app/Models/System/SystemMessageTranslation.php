<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tizim xabarlari tarjimalari modeli
 *
 * @property int $id
 * @property string $language
 * @property string|null $translation
 * @property SystemMessage $message
 */
class SystemMessageTranslation extends Model
{
    protected $table = 'e_system_message_translation';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = ['id', 'language'];

    protected $fillable = [
        'id',
        'language',
        'translation',
    ];

    /**
     * Asl xabar bilan bog'lanish
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(SystemMessage::class, 'id', 'id');
    }
}
