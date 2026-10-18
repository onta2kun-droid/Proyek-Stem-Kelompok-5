<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceCommand extends Model
{
    protected $table = 'commands';
    protected $fillable = ['device_id', 'mode', 'issued_at'];
    protected $casts = ['issued_at' => 'datetime'];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // Mode terbaru untuk sebuah device (buatkan baris 'auto' jika belum ada)
    public static function currentFor(Device $device): self
    {
        return static::firstOrCreate(
            ['device_id' => $device->id],
            ['mode' => 'auto', 'issued_at' => null]
        );
    }
}
