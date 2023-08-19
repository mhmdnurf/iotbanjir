<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pengaturanM extends Model
{
    use HasFactory;
    protected $table = 'pengaturan';
    protected $primaryKey='idpengaturan';
    protected $guarded=[];
}
