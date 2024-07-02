<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class PaymentHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'group_id', 'amount', 'payment_date', 'renewal_date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function setRenewalDate()
    {
        $this->renewal_date = Carbon::parse($this->payment_date)->addMonth();
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            $payment->renewal_date = Carbon::parse($payment->payment_date)->addMonth();
        });

        static::updating(function ($payment) {
            $payment->renewal_date = Carbon::parse($payment->payment_date)->addMonth();
        });
    }
}
