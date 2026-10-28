<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_PENDING_USER = 'pending_user';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    protected $table = 'support_tickets';

    protected $fillable = [
        'ticket_number',
        'user_id',
        'guest_name',
        'guest_email',
        'subject',
        'category',
        'priority',
        'status',
        'message',
        'assigned_to',
        'internal_notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Generate unique ticket identifier.
     */
    public static function generateTicketNumber(): string
    {
        return 'TKT-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
    }
}
