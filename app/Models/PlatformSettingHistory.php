<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSettingHistory extends Model
{
    use HasFactory;

    protected $table = 'platform_setting_histories';

    protected $fillable = [
        'platform_setting_id',
        'key',
        'old_value',
        'new_value',
        'changed_by',
        'reason',
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(PlatformSetting::class, 'platform_setting_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
