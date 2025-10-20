<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProvider extends Model
{
    use HasFactory;

    protected $fillable = ['sp_id', 'sp_name', 'total_site'];
    public $incrementing = false;
    protected $primaryKey = 'sp_id';
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->sp_id)) {
                $lastSp = static::orderBy('sp_id', 'desc')->first();
                $lastNumber = $lastSp ? (int) str_replace('SPID-', '', $lastSp->sp_id) : 0;
                $newNumber = $lastNumber + 1;
                $model->sp_id = 'SPID-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT); // SPID-001, SPID-002, dst
            }
        });
    }

    public function performances(): HasMany
    {
        return $this->hasMany(SpPerformance::class, 'sp_id');
    }
}
