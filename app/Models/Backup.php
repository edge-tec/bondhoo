<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Backup — ব্যাকআপ রেকর্ড মডেল
 *
 * এই মডেলটি ডাটাবেজ, মিডিয়া এবং রেডিসের সমস্ত ব্যাকআপ আর্কাইভিং ও স্ট্যাটাস সংরক্ষণ করে।
 */
class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'disk',
        'type',
        'size_bytes',
        'status',
        'checksum',
        'encrypted',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'encrypted' => 'boolean',
            'completed_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }
}
