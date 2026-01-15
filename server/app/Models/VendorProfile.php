<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'profile_image',
        'cover_image',
        'phone',
        'whatsapp',
        'email',
        'website',
        'instagram',
        'facebook',
        'services_offered',
        'equipment_list',
        'terms_conditions',
        'cancellation_policy',
    ];

    protected $casts = [
        'services_offered' => 'array',
        'equipment_list' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
