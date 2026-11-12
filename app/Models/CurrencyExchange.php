<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyExchange extends Model
{
    //
    protected $guarded = ['id'];

    protected $casts = ['exc_prc' => 'decimal:8'];


    public function baseCurrency()
    {
        return $this->belongsTo(Currency::class, 'base_currency_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
