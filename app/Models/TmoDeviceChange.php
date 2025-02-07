<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TmoDeviceChange extends Model
{
    use HasFactory;

    protected $table = 'tmo_device_change';
    protected $primaryKey = 'tmo_device_change_id';
    public $incrementing = false; // Karena primary key-nya bukan auto-increment
    public $timestamps = true;

    protected $fillable = [
        'tmo_device_change_id',
        'device_name',
        'device_sn',
        'device_img',
        'tmo_id',
    ];

    public static function boot()
    {
        parent::boot();

        // Event sebelum record dibuat
        static::creating(function ($model) {
            $model->tmo_device_change_id = self::generateTmoDCId();
        });
    }

    public static function generateTmoDCId()
    {
        // Format tanggal (2 digit tahun, bulan, tanggal)
        $dateCode = now()->format('ymd');

        // Cari nomor urut terakhir berdasarkan tanggal
        $latestTmo = self::where('tmo_device_change_id', 'LIKE', "DVC-MHG-$dateCode-%")
            ->orderBy('tmo_device_change_id', 'desc')
            ->first();

        // Jika tidak ada record sebelumnya, mulai dari nomor 1
        $lastNumber = $latestTmo ? (int) substr($latestTmo->tmo_device_change_id, -3) : 0;

        // Mulai dari nomor urut berikutnya
        $newNumber = $lastNumber + 1;

        // Format nomor urut dengan 3 digit
        $newNumberStr = str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        // Pastikan nomor ID yang baru tidak duplikat
        $newId = "DVC-MHG-$dateCode-$newNumberStr";

        // Jika ID sudah ada, coba lagi dengan menambah nomor urut
        if (self::where('tmo_device_change_id', $newId)->exists()) {
            return self::generateTmoDCId(); // Rekursi untuk mencoba ID yang lain
        }

        return $newId;
    }


    public function tmoData()
    {
        return $this->belongsTo(TmoData::class, 'tmo_id', 'tmo_id');
    }
}
