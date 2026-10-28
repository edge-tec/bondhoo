<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDailyAggregate extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'dau',
        'wau',
        'mau',
        'registrations_count',
        'posts_count',
        'comments_count',
        'reactions_count',
        'shares_count',
        'messages_count',
        'reports_count',
        'security_events_count',
        'metrics',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'dau' => 'integer',
            'wau' => 'integer',
            'mau' => 'integer',
            'registrations_count' => 'integer',
            'posts_count' => 'integer',
            'comments_count' => 'integer',
            'reactions_count' => 'integer',
            'shares_count' => 'integer',
            'messages_count' => 'integer',
            'reports_count' => 'integer',
            'security_events_count' => 'integer',
            'metrics' => 'array',
        ];
    }
}
