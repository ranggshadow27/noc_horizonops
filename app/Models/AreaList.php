<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AreaList extends Model
{
    use HasFactory;

    protected $table = 'area_list'; // Nama tabel
    protected $primaryKey = 'province'; // Primary key pakai string (provinsi)
    public $incrementing = false; // Karena primary key bukan angka
    protected $keyType = 'string'; // Supaya province dianggap string

    protected $fillable = [
        'province',
        'area',
        'po',
        'head_po',
    ]; // Kolom yang bisa diisi

    protected $casts = [
        'po' => 'array', // Cast JSON ke array untuk kemudahan akses di PHP
    ];

    /**
     * Relasi ke TmoData (One to Many)
     */
    public function tmoData(): HasMany
    {
        return $this->hasMany(TmoData::class, 'site_province', 'province');
    }

    public function siteDetails()
    {
        return $this->hasMany(SiteDetail::class, 'province', 'province');
    }

    public function cbossTmo()
    {
        return $this->hasMany(CbossTmo::class, 'province', 'province');
    }
}
