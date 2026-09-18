<?php

namespace App\Models\OtApproval;

use Illuminate\Database\Eloquent\Model;

/** ประวัติการกดดาวน์โหลดเอกสารส่ง HR (ทั้ง OT และการลา) */
class OtExportDownload extends Model
{
    public const MODULE_OT = 'ot';

    public const MODULE_LEAVE = 'leave';

    protected $connection = 'mysql_ot_approval';

    protected $table = 'ot_export_downloads';

    protected $guarded = ['id'];

    protected $casts = [
        'target_date' => 'immutable_date',
        'submitted_on' => 'immutable_date',
        'row_count' => 'integer',
        'downloaded_at' => 'immutable_datetime',
    ];
}
