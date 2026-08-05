<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class GoalContribution extends Model
{
    use HasUuid;

    protected $fillable = ['uuid', 'savings_goal_id', 'transaction_id', 'created_by', 'amount_minor', 'contributed_on', 'notes'];

    protected $casts = ['contributed_on' => 'date'];
}
