<?php
namespace App\Http\Controllers\Site;
use App\Payment;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends BaseController
{
    public function success(Request $request){
        $data = $request->all();
        Payment::create([
            'text' => serialize($data),
            'type' => 'success'
        ]);
        return redirect('user-home');
    }

    public function fail(Request $request){
        $data = $request->all();
        Payment::create([
            'text' => serialize($data),
            'type' => 'fail'
        ]);
        return redirect('user-home');
    }
}