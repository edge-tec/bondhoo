<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'certificate_code',
        'verification_token',
        'student_name',
        'course_title',
        'instructor_name',
        'pdf_url',
        'qr_code_url',
        'issued_at',
        'is_revoked',
        'revoked_reason',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'is_revoked' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
