<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_vendor_id',
        'client_id',
        'status',
        'failure_reason',
        'proof_file',
        'proof_verified_at',
        'verified_by',
        'assigned_backup_id',
        'backup_assigned_at',
        'resolution_time_minutes',
        'resolution_notes',
    ];

    protected $casts = [
        'proof_verified_at' => 'datetime',
        'backup_assigned_at' => 'datetime',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventVendor(): BelongsTo
    {
        return $this->belongsTo(EventVendor::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function assignedBackup(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'assigned_backup_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSearching($query)
    {
        return $query->where('status', 'searching');
    }

    // Helpers
    public function verifyProof(int $adminId): void
    {
        $this->update([
            'status' => 'searching',
            'proof_verified_at' => now(),
            'verified_by' => $adminId,
        ]);
    }

    public function assignBackup(int $backupVendorId): void
    {
        $resolutionTime = $this->created_at ? now()->diffInMinutes($this->created_at) : null;
        
        $this->update([
            'status' => 'backup_found',
            'assigned_backup_id' => $backupVendorId,
            'backup_assigned_at' => now(),
            'resolution_time_minutes' => $resolutionTime,
        ]);
    }

    public function resolve(string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution_notes' => $notes,
        ]);
    }
}
