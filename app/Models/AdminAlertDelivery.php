<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAlertDelivery extends Model
{
    use HasFactory;

    public const CHANNEL_IN_APP = 'in_app';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_WEBHOOK = 'webhook';

    public const CHANNEL_SLACK = 'slack';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'admin_alert_id',
        'channel',
        'recipient',
        'status',
        'attempts',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(AdminAlert::class, 'admin_alert_id');
    }
}
