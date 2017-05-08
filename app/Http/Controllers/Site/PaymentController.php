<?php
namespace App\Http\Controllers\Site;

use App\Payment;
use App\User;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends BaseController
{
	public function success(Request $request){
		$data = $request->all();
		if(!empty($data['label'])){
			$operation = explode('_',$data['label']);
			if(count($operation) == 2){
				$payment = Payment::find($operation[1]);
				if(($payment->user_id == $operation[0]) && ($payment->pay_status == 0)){
					$payment->pay_status = 1;
					$payment->save();

					$user = User::find($operation[0]);
					$user->user_gold = $user->user_gold + $payment->gold_amount;
					$user->save();
				}
			}
		}
		return 'success';
	}

	public function createPayStory(Request $request){
		$data = $request->all();
		$user = Auth::user();
		$result = Payment::create([
			'user_id'		=> $user['id'],
			'money_amount'	=> $data['money'],
			'gold_amount'	=> floor($data['gold']),
			'pay_status'	=> 0
		]);
		if($result !== false){
			return json_encode([
				'message'		=> 'success',
				'transaction'	=> $result->id
			]);
		}else{
			return 'fail';
		}
	}
}