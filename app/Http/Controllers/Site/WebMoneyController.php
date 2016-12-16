<?php
namespace App\Http\Controllers\Site;

use App\User;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class WebMoneyController extends BaseController
{
    public function pay(Request $request){
        $data = $request->all();
        $type = 'pay';
        $data = serialize($data);
        \DB::table('tbl_wm_test')->insert([
            'type' => $type,
            'text' => $data
        ]);
    }

    public function success(Request $request){
        $data = $request->all();
        $type = 'success';
        $data = serialize($data);
        \DB::table('tbl_wm_test')->insert([
            'type' => $type,
            'text' => $data
        ]);
    }

    public function fail(Request $request){
        $data = $request->all();
        $type = 'fail';
        $data = serialize($data);
        \DB::table('tbl_wm_test')->insert([
            'type' => $type,
            'text' => $data
        ]);
    }
}