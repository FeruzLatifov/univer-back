<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESystemMessage extends Model
{
    use HasFactory;

    protected $table = 'e_system_message';

    protected $fillable = [
        'code',
        'category',
        'message',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get all translations for this message
     */
    public function translations()
    {
        return $this->hasMany(ESystemMessageTranslation::class, 'id', 'id');
    }

    /**
     * Get translation for specific language
     */
    public function getTranslation(string $language)
    {
        return $this->translations()->where('language', $language)->first();
    }
}
