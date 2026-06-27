<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'total_amount', 'remaining_amount', 'monthly_percentage', 'creditor', 'due_date', 'description'];
    protected $casts = ['total_amount' => 'decimal:2', 'remaining_amount' => 'decimal:2', 'monthly_percentage' => 'decimal:2', 'due_date' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
