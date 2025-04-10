<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sop extends Model
{
    use HasFactory;

    protected $table = 'sops';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'description',
        'file_path',
    ];

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                // Dapatkan nomor terakhir dari database dan tambahkan 1
                $lastRecord = self::orderBy('id', 'desc')->first();
                if ($lastRecord) {
                    // Ekstrak nomor dari ID terakhir
                    $lastNumber = (int) substr($lastRecord->id, -3);
                    $newNumber = $lastNumber + 1;
                } else {
                    // Jika tidak ada record, mulai dari 1
                    $newNumber = 1;
                }

                // Format ID dengan SOP-MHG-XXX
                $model->{$model->getKeyName()} = 'NSOP-MHG' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }


    public function getNumberAttribute()
    {
        // Ekstrak nomor dari ID SOP (misal: SOP-MHG-001 -> 1)
        return (int) substr($this->id, -3);
    }
}
