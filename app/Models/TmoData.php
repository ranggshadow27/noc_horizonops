<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TmoData extends Model
{
    use HasFactory;

    protected $table = 'tmo_data';
    protected $primaryKey = 'tmo_id';
    public $incrementing = false; // Karena primary key-nya bukan auto-increment

    protected $fillable = [
        'tmo_id',
        'site_id',
        'site_name',
        'site_province',
        'site_address',
        'site_latitude',
        'site_longitude',
        'engineer_name',
        'engineer_number',
        'spmk_number',
        'pic_name',
        'pic_number',
        'sqf',
        'esno',
        'power_source',
        'power_source_backup',
        'fan_rack1',
        'fan_rack2',
        'grounding',
        'ifl_length',
        'signal',
        'weather',
        'problem_json',
        'action_json',
        'engineer_note',
        'tmo_type',
        'tmo_start_date',
        'tmo_end_date',
        'cboss_tmo_code',
        'approval',
        'approval_details',
        'is_device_change',
        'approval_by',
        'created_by',
    ];

    protected $casts = [
        'problem_json' => 'array',
        'action_json' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        // Event sebelum record dibuat
        static::creating(function ($model) {
            $model->tmo_id = self::generateTmoId();
        });
    }

    public static function generateTmoId()
    {
        // Format tanggal (2 digit tahun, bulan, tanggal)
        $dateCode = now()->format('ymd');

        // Cari nomor urut terakhir berdasarkan tanggal
        $latestTmo = self::where('tmo_id', 'LIKE', "TMO-MHG-$dateCode-%")
            ->orderBy('tmo_id', 'desc')
            ->first();

        if ($latestTmo) {
            // Ambil nomor urut terakhir
            $lastNumber = (int) substr($latestTmo->tmo_id, -3);
        } else {
            $lastNumber = 0;
        }

        // Tambahkan 1 ke nomor urut terakhir
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        // Gabungkan ke ID baru
        return "TMO-MHG-$dateCode-$newNumber";
    }

    public function area()
    {
        return $this->belongsTo(AreaList::class, 'site_province', 'province');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approval_by', 'id');
    }

    public function site()
    {
        return $this->belongsTo(SiteDetail::class, 'site_id', 'site_id');
    }

    public function tmoDetail()
    {
        return $this->hasOne(TmoDetail::class, 'tmo_id', 'tmo_id');
    }

    public function tmoImages()
    {
        return $this->hasOne(TmoImage::class, 'tmo_id', 'tmo_id');
    }

    public function deviceChanges()
    {
        return $this->hasMany(TmoDeviceChange::class, 'tmo_id', 'tmo_id');
    }
}
