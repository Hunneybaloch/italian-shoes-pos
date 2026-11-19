<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class SystemSetting extends Model
{
    protected $fillable=['key','value','group','updated_by'];
    public function casts(): array { return []; }
}
