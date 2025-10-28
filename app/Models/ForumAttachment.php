<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ForumAttachment extends Model
{
    protected $table = 'forum_attachments';
    const UPDATED_AT = null;

    protected $fillable = [
        'attachable_id', 'attachable_type', 'uploaded_by', 'uploader_type',
        'file_name', 'file_path', 'file_type', 'file_size', 'mime_type', 'downloads_count',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'downloads_count' => 'integer',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function attachable() { return $this->morphTo(); }
    public function uploader() { return $this->morphTo('uploader', 'uploader_type', 'uploaded_by'); }

    // Methods
    public function getUrlAttribute() { return Storage::url($this->file_path); }
    public function incrementDownloads() { $this->increment('downloads_count'); }
    public function deleteFile() { if (Storage::exists($this->file_path)) Storage::delete($this->file_path); }

    protected static function boot()
    {
        parent::boot();
        static::deleting(fn($attachment) => $attachment->deleteFile());
    }
}
