<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareProgramTranslation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'software_program_id',
        'locale',
        'field',
        'value',
    ];
}
