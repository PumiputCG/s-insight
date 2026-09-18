<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;

/** แจ้งเตือน in-app ต่อพนักงาน — เห็นเฉพาะของตัวเอง */
class A5sNotification extends Model
{
    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_notifications';

    protected $fillable = ['employee_code', 'type', 'title', 'body', 'link', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
