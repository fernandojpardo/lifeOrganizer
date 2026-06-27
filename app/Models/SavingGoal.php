<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingGoal extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'target_amount', 'current_amount', 'monthly_percentage', 'target_date', 'description'];
    protected $casts = ['target_amount' => 'decimal:2', 'current_amount' => 'decimal:2', 'monthly_percentage' => 'decimal:2', 'target_date' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
