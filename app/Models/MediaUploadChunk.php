<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaUploadChunk extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'upload_session_id',
        'chunk_number',
        'chunk_size',
        'chunk_checksum',
        'temp_path',
        'status',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'chunk_number' => 'integer',
            'chunk_size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MediaUploadSession::class, 'upload_session_id');
    }
}
