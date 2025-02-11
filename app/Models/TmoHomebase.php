<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TmoHomebase extends Model
{
    use HasFactory;

    protected $table = 'tmo_homebase';
    protected $primaryKey = 'homebase_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'homebase_id',
        'location',
        'pic_name',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($homebase) {
            // Set auto-generated homebase_id
            $homebase->homebase_id = self::generateHomebaseId();
        });
    }

    public static function generateHomebaseId()
    {
        // Ambil homebase_id terakhir
        $lastHomebase = self::orderByDesc('homebase_id')->first();

        if ($lastHomebase) {
            // Ambil angka terakhir dari ID, misal 'MHG-HB-092' -> 92
            $lastNumber = (int) substr($lastHomebase->homebase_id, -3);
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            // Jika tidak ada data, mulai dari 001
            $nextNumber = '001';
        }

        return "MHG-HB-$nextNumber";
    }

    public function deviceChanges()
    {
        return $this->hasMany(TmoDeviceChange::class, 'homebase_id', 'homebase_id');
    }
}
