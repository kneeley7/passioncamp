<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransactionSplit extends Model
{
    protected $table = 'transaction_split';

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getNameAttribute()
    {
        $type = '';
        $method = $this->transaction->type;

        if ($this->amount < 0) {
            $type = 'Refunded';
        }

        if ($method == 'Sale') {
            $method = $this->transaction->card_type . ' ' . $this->transaction->card_num;
        }

        if (in_array($method, array('Check', 'Credit'))) {
            $method = $method . ' ' . $this->transaction->processor_transactionId;
        }

        return trim(sprintf('%s %s', $type, $method));
    }
}

