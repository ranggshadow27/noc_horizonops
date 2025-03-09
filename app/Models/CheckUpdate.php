<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckUpdate extends Model
{
    use HasFactory;

    protected $table = 'check_updates';

    protected $fillable = [
        'update_name',
        'update_time',
    ];

    public $timestamps = false;
}

