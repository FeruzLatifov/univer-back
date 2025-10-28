<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * MessageAttachment Model
 *
 * Handles file attachments for messages
 * Supports: documents, images, PDFs, etc.
 */
class MessageAttachment extends Model
{
    use HasFactory;

    protected $table = 'message_attachments';

    /**
     * Disable updated_at timestamp (we only track created_at)
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'message_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Allowed file types
     */
    const ALLOWED_TYPES = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'gif', 'svg',
        'zip', 'rar', '7z',
        'txt', 'csv',
    ];

    /**
     * Maximum file size (in bytes) - 10MB
     */
    const MAX_FILE_SIZE = 10 * 1024 * 1024;

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the message
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope for specific file type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Scope for images only
     */
    public function scopeImages($query)
    {
        return $query->whereIn('file_type', ['jpg', 'jpeg', 'png', 'gif', 'svg']);
    }

    /**
     * Scope for documents only
     */
    public function scopeDocuments($query)
    {
        return $query->whereIn('file_type', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
    }

    // ==================== METHODS ====================

    /**
     * Get full URL to the file
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get download URL
     */
    public function getDownloadUrlAttribute()
    {
        return route('api.messages.attachments.download', $this->id);
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedSizeAttribute()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;

        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } else {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
    }

    /**
     * Get file icon based on file type
     */
    public function getIconAttribute()
    {
        return match($this->file_type) {
            'pdf' => 'file-pdf',
            'doc', 'docx' => 'file-word',
            'xls', 'xlsx' => 'file-excel',
            'ppt', 'pptx' => 'file-powerpoint',
            'jpg', 'jpeg', 'png', 'gif', 'svg' => 'file-image',
            'zip', 'rar', '7z' => 'file-archive',
            'txt' => 'file-text',
            default => 'file',
        };
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return in_array($this->file_type, ['jpg', 'jpeg', 'png', 'gif', 'svg']);
    }

    /**
     * Check if file is a document
     */
    public function isDocument(): bool
    {
        return in_array($this->file_type, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
    }

    /**
     * Check if file type is allowed
     */
    public static function isAllowedType($extension): bool
    {
        return in_array(strtolower($extension), self::ALLOWED_TYPES);
    }

    /**
     * Check if file size is allowed
     */
    public static function isAllowedSize($size): bool
    {
        return $size <= self::MAX_FILE_SIZE;
    }

    /**
     * Delete the file from storage
     */
    public function deleteFile()
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Delete file when attachment is deleted
        static::deleting(function ($attachment) {
            $attachment->deleteFile();
        });
    }
}
