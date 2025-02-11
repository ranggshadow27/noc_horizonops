<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TmoProblem extends Model
{
    use HasFactory;

    protected $table = 'tmo_problems';
    protected $primaryKey = 'problem_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'problem_id',
        'problem_classification',
        'problem_type',
        'problem_category',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Set auto-generated problem_id
            $model->problem_id = self::generateTMOProblemId();
        });
    }

    public static function generateTMOProblemId()
    {
        // Ambil problem_id terakhir
        $lastProblemID = self::orderByDesc('problem_id')->first();

        if ($lastProblemID) {
            // Ambil angka terakhir dari ID, misal 'MHG-HB-092' -> 92
            $lastNumber = (int) substr($lastProblemID->problem_id, -3);
            $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            // Jika tidak ada data, mulai dari 001
            $nextNumber = '001';
        }

        return "MHG-PRB-$nextNumber";
    }


}
