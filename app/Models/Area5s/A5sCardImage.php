<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;

class A5sCardImage extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_card_images';

    protected $fillable = ['card_id', 'path', 'sort'];
}
