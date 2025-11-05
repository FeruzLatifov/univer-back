<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * System Message Translation Model
 *
 * Stores translations for each message in different languages
 *
 * @property int $id
 * @property string $language
 * @property string|null $translation
 *
 * @property-read ESystemMessage $message
 */
class ESystemMessageTranslation extends Model
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
     * Get the message this translation belongs to
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ESystemMessage::class, 'id');
    }

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        if (!is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }

    /**
     * Get the primary key value for a save query.
     *
     * @param  mixed  $keyName
     * @return mixed
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        if (is_null($keyName)) {
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }
}
