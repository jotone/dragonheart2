<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model{
    protected $table = 'tbl_wm_tests';
    protected $fillable = [
        'text', 'type'
    ];
}