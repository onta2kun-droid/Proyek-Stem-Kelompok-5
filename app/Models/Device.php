<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = ['code', 'name', 'api_key', 'ip', 'rssi', 'last_seen_at'];
    protected $casts = ['last_seen_at' => 'datetime'];

    public function readings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subSeconds(90));
    }
}
