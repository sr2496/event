<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action_type',
        'target_type',
        'target_id',
        'data_before',
        'data_after',
        'reason',
        'ip_address',
    ];

    protected $casts = [
        'data_before' => 'array',
        'data_after' => 'array',
    ];

    // Relationships
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // Helpers
    public static function log(
        int $adminId,
        string $actionType,
        string $targetType,
        int $targetId,
        array $dataBefore = null,
        array $dataAfter = null,
        string $reason = null
    ): self {
        return self::create([
            'admin_id' => $adminId,
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'data_before' => $dataBefore,
            'data_after' => $dataAfter,
            'reason' => $reason,
            'ip_address' => request()->ip(),
        ]);
    }
}
