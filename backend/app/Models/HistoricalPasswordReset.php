<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Sosadfun\Traits\ColumnTrait;

class PasswordReset extends Model
{
    protected $table='history_password_resets';
    protected $guarded = [];
    protected $primaryKey = 'id';
    const UPDATED_AT = null;
}
