<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SecurityEvent — সিকিউরিটি অপারেশন্স সেন্টার (SOC) ইভেন্ট মডেল
 *
 * এই মডেলটি ব্রুট-ফোর্স, ইম্পসিবল ট্রাভেল, সেশন হাইজ্যাকিং এবং ম্যালওয়্যার
 * ডিটেকশনের সমস্ত নিরাপত্তা ইভেন্ট ধারণ করে।
 */
class SecurityEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'severity',
        'ip_address',
        'country_code',
        'user_agent',
        'details',
        'resolved',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'resolved' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
