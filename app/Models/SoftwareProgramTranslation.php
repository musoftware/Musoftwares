<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoftwareProgramTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'software_program_id',
        'locale',
        'field',
        'value'
    ];
}
