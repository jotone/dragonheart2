<?php
namespace App\Http\Controllers\Site;

use App\User;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class WebMoneyController extends BaseController
{
    public function pay(Request $request){
        echo 'YES';
        $data = $request->all();
        $type = 'pay';
        $data = serialize($data);
        //$chkstring =  ${'WM_SHOP_PURSE_'.$pay['unit']}.$pay['price'].$pay['id'].$_POST['LMI_MODE'].$_POST['LMI_SYS_INVS_NO'].$_POST['LMI_SYS_TRANS_NO'].$_POST['LMI_SYS_TRANS_DATE'].$LMI_SECRET_KEY.$_POST['LMI_PAYER_PURSE'].$_POST['LMI_PAYER_WM'];
        \DB::table('tbl_wm_tests')->insert([
            'type' => $type,
            'text' => $data
        ]);

    }

    public function success(Request $request){
        $data = $request->all();
        $type = 'success';
        $data = serialize($data);
        \DB::table('tbl_wm_tests')->insert([
            'type' => $type,
            'text' => $data
        ]);
        return view('wm', [
            'title' => 'Success',
            'text'  => 'Платеж был выполнен.'
        ]);
    }

    public function fail(Request $request){
        $data = $request->all();
        $type = 'fail';
        $data = serialize($data);
        \DB::table('tbl_wm_tests')->insert([
            'type' => $type,
            'text' => $data
        ]);
        return view('wm', [
            'title' => 'Fail',
            'text'  => 'Платеж не был выполнен.'
        ]);
    }
}