<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;

     protected $fillable = [
        'customer_id',
        'created_by',
        'description',
        'due_date_time',
        'is_completed',
        'reminder_sent_at',
    ];

    protected $casts = [
        'due_date_time' => 'datetime',
        'is_completed' => 'boolean',
    ];
    
       public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // public function employee(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'created_by');
    // }
    
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
