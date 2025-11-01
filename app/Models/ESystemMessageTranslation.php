<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESystemMessageTranslation extends Model
{
    use HasFactory;

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
     * Get the message that owns this translation
     */
    public function message()
    {
        return $this->belongsTo(ESystemMessage::class, 'id', 'id');
    }
}
