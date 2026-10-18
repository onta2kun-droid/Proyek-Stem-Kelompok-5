<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    public const DRY_DEFAULT = 35;
    public const WET_DEFAULT = 70;

    protected $fillable = ['device_id', 'kelembapan', 'suhu', 'rssi', 'ip',
                           'jarak', 'gerakan', 'pompa', 'alarm'];
    protected $casts = [
        'kelembapan' => 'float', 'suhu' => 'float', 'rssi' => 'integer',
        'jarak' => 'float', 'gerakan' => 'boolean', 'pompa' => 'boolean', 'alarm' => 'boolean',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public static function dryThreshold(): int
    {
        return (int) Setting::get('threshold_dry', self::DRY_DEFAULT);
    }

    public static function wetThreshold(): int
    {
        return (int) Setting::get('threshold_wet', self::WET_DEFAULT);
    }

    public function status(): string
    {
        return match (true) {
            $this->kelembapan < self::dryThreshold()  => 'kering',
            $this->kelembapan <= self::wetThreshold() => 'optimal',
            default => 'basah',
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'kering' => 'KERING',
            'basah'  => 'TERLALU BASAH',
            default  => 'OPTIMAL',
        };
    }
}
