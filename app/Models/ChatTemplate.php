<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatTemplate extends Model
{
    protected $fillable = ['name', 'type', 'template'];

    public $incrementing = false; // Nonaktifkan auto-increment
    protected $keyType = 'string'; // Set tipe kunci ke string

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                // Ambil record terakhir, ekstrak nomor, tambah 1
                $latest = static::orderBy('id', 'desc')->first();
                $number = $latest ? (int) substr($latest->id, -3) + 1 : 1;
                $model->id = 'CHT-TMP-' . str_pad($number, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
