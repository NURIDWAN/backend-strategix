<?php
// app/Models/TeamStructure.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_background_id',
        'operational_plan_id',
        'team_category',
        'member_name',
        'position',
        'salary',
        'jobdesk',
        'experience',
        'photo',
        'sort_order',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businessBackground()
    {
        return $this->belongsTo(BusinessBackground::class);
    }

    // Relasi ke operational plan
    public function operationalPlan()
    {
        return $this->belongsTo(OperationalPlan::class);
    }
}
