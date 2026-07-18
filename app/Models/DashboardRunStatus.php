<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardRunStatus extends Model
{
    protected $table = 'dashboard_run_status';

    protected $primaryKey = 'run_name';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'run_name',
        'last_run_at',
        'status',
        'details',
    ];
}
