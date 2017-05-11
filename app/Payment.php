<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model{
    protected $table = 'tbl_payment';
    protected $fillable = [
        'user_id', 'money_amount', 'gold_amount', 'pay_status', 'type'
    ];
}