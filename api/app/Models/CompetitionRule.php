<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionRule extends Model
{
    protected $table = 'competition_rules';

    protected $fillable = [
        'title', 'summary', 'content', 'rule_date', 'status',
    ];

    protected $casts = [
        'status'    => 'integer',
        'rule_date' => 'date:Y-m-d',
    ];
}
