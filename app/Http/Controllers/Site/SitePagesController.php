<?php
namespace App\Http\Controllers\Site;

use App\Battle;
use App\BattleMembers;
use App\Card;
use App\Fraction;
use App\EtcData;
use App\League;
use App\Page;
use App\Rubric;
use App\User;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Auth;
use Crypt;
use Validator;

class SitePagesController extends BaseController
{
	//Главная страница
	public function homePage(){
		SiteFunctionsController::updateConnention();
		$exchange_options = \DB::table('tbl_etc_data')
			->select('label_data','meta_key','meta_value', 'meta_key_title')
			->where('label_data', '=', 'premium_buing')
			->orderBy('meta_value','asc')
			->get();

		$fractions = Fraction::where('type', '=', 'race')->orderBy('position','asc')->get();
		$output = [];
        $user = Auth::user();

        $fraction_image = Fraction::select('slug','bg_img')->where('slug', '=', $user['user_current_deck'])->get();

        if(count($fraction_image->all()) > 0){
            $bg_img = (!empty($fraction_image[0]->bg_img))
                ? '../img/fractions_images/'.$fraction_image[0]->bg_img
                : '../images/main_bg_1.jpg';
        }else{
            $bg_img = '../images/main_bg_1.jpg';
        }

		foreach($fractions as $key => $fraction) {
			$output[$key]['title'] = $fraction['title'];
			$output[$key]['slug'] = $fraction['slug'];
			$output[$key]['img_url'] = $fraction['img_url'];
			$output[$key]['bg_img'] = $fraction['bg_img'];
			$output[$key]['type'] = $fraction['type'];
			$output[$key]['description'] = $fraction['description'];
			$output[$key]['short_description'] = $fraction['short_description'];
		}

		$page_content = Page::where('slug','=','about_game')->get();

		return view('home', [
		    'fractions' => $output,
            'exchange_options' => $exchange_options,
            'user' => $user,
            'bg_img' => $bg_img,
            'page_content' => $page_content[0]
        ]);
	}

	//Страница игры
	public function playPage($id){
		SiteFunctionsController::updateConnention();

		$battle_data = Battle::find($id);
		$battle_members = BattleMembers::where('battle_id','=',$battle_data->id)->get();
		if(!$battle_data){
			return view('play')->withErrors(['Данный стол не существует.']);
		}


		$sec = intval(getenv('GAME_SEC_TIMEOUT'));
		if($sec<=0) $sec = 60;

		$user = Auth::user();
		$hash = md5(getenv('SECRET_MD5_KEY').$user->id);
		return view('play', [
			'battle_data' => $battle_data,
			'battle_members' => $battle_members,
			'hash'=>$hash,
			'user'=>$user,
			'dom'=>getenv('APP_DOMEN_NAME'),
			'timeOut'=>$sec
		]);
	}

	//Играть
	public function gamesPage(Request $request){
		SiteFunctionsController::updateConnention();
		$user = Auth::user();

		//Данные Лиг
		$leagues = \DB::table('tbl_league')->select('title','min_lvl')->orderBy('min_lvl','asc')->get();

		//Текущие колоды пользователя
		$current_deck = unserialize($user->user_cards_in_deck);

		if(!empty($current_deck[$request->input('currentRace')])) {
			//Вес колоды
			$deck_weight = 0;

			//Подсчет веса колоды
			foreach($current_deck[$request->input('currentRace')] as $key => $value){
				$card = Card::where('id', '=', $key)->get();
				if(isset($card[0])){
					$deck_weight += $card[0]->card_value * $value;
				}
			}
		}

		//Текущая лига
		$current_user_league = '';
		foreach ($leagues as $league) {
			//если Вес колоды больше минимального уровня вхождения в лигу
			if($deck_weight >= $league->min_lvl){
				$current_user_league = $league->title;
			}
		}
		//Расы
		$fractions = Fraction::where('type','=','race')->orderBy('position','asc')->get();
		//Активные для данной лиги столы
		$battles = Battle::where('league','=',$current_user_league)->where('fight_status', '<', 3)->get();

		$tmp_battles = ['allow' => [], 'back' => []];
		foreach($battles as $battle_iter => $battle_data){

			$user_creator = User::find($battle_data['creator_id']);
			$current_battle_members = BattleMembers::where('battle_id', '=', $battle_data['id'])->count();

			if( ($user['id'] == $battle_data['creator_id']) || ($user['id'] == $battle_data['opponent_id']) ){
				$tmp_battles['back'][$battle_data['id']] = [
					'data'      => $battle_data,
					'creator'   => $user_creator['login'],
					'users_count'=>$current_battle_members
				];
			}else if( ($current_battle_members != 2) && ($current_battle_members != 0) ){
				$tmp_battles['allow'][$battle_data['id']] = [
					'data'      => $battle_data,
					'creator'   => $user_creator['login'],
					'users_count'=>$current_battle_members
				];
			}
		}

		\DB::table('users')->where('login','=',$user->login)->update(['user_current_deck' => $request->input('currentRace')]);

		$exchange_options = \DB::table('tbl_etc_data')
			->select('label_data','meta_key','meta_value', 'meta_key_title')
			->where('label_data', '=', 'premium_buing')
			->orderBy('meta_value','asc')
			->get();

		return view('game', [
			'exchange_options' => $exchange_options,
			'fractions'     => $fractions,
			'deck_weight'   => Crypt::encrypt($deck_weight),
			'battles'       => $tmp_battles,
			'league'        => Crypt::encrypt($current_user_league)
		]);
	}

	//Рейтинг
	public function ratingPage(){
		SiteFunctionsController::updateConnention();
		$user = Auth::user();
		$leagues = League::orderBy('min_lvl', 'desc')->get();
		$fractions = Fraction::where('type', '=', 'race')->orderBy('position','asc')->get();
		$exchange_options = \DB::table('tbl_etc_data')
			->select('label_data','meta_key','meta_value', 'meta_key_title')
			->where('label_data', '=', 'premium_buing')
			->orderBy('meta_value','asc')
			->get();

		$users = User::get();
		foreach($users as $user_iter => $user_to_rate_data){
			$users_rates[] = SiteFunctionsController::calcUserRating('all', $user_to_rate_data);
		}

		usort($users_rates, function($a, $b){return ($b['rating'] - $a['rating']);});

		$indexes = [];

		$user_rates_count = count($users_rates);
		for($i = 0; $i < $user_rates_count; $i++){
			if($i<3) $indexes[] = $i;//Первые 20 пользователей
			if($user->login == $users_rates[$i]['login']) $user_current_index = $i;
			$users_rates[$i]['position'] = $i+1;
		}

		if($user_current_index - 7 >= 0){
			$top = $user_current_index - 7;
			$bottom_adds = 0;
		}else{
			$top = 0;
			$bottom_adds = abs($user_current_index - 7);
		}

		if($user_current_index + 7 <= $user_rates_count){
			$bottom = $user_current_index + 7 + $bottom_adds;
		}else{
			$bottom = $user_rates_count-1;
			$top -= abs($user_current_index + 8 - $user_rates_count);
		}

		for($i=$top; $i<=$bottom; $i++) $indexes[] = $i;
		$indexes = array_values(array_unique($indexes));

		$users_out = [];
		foreach($indexes as $i => $index){
			if(isset($users_rates[$index])) $users_out[] = $users_rates[$index];
		}
		return view('rating', [
			'exchange_options' => $exchange_options,
			'fractions' => $fractions,
			'users_data' => $users_out,
			'leagues' => $leagues
		]);
	}

	//Страница регистрации
	public function registration(){
		$exchange_options = \DB::table('tbl_etc_data')
			->select('label_data','meta_key','meta_value', 'meta_key_title')
			->where('label_data', '=', 'premium_buing')
			->orderBy('meta_value','asc')
			->get();

        $page_content = Page::where('slug','=','license')->get();

		$fractions = Fraction::where('type', '=', 'race')->orderBy('position','asc')->get();
		return view('registration', [
		    'fractions' => $fractions,
            'exchange_options' => $exchange_options,
            'page_content' => $page_content[0]
        ]);
	}

	//Мои карты
	public function deckPage(){
		SiteFunctionsController::updateConnention();
		$exchange_options = \DB::table('tbl_etc_data')
			->select('label_data','meta_key','meta_value', 'meta_key_title')
			->where('label_data', '=', 'premium_buing')
			->orderBy('meta_value','asc')
			->get();

		$fractions = Fraction::where('type', '=', 'race')->orderBy('position','asc')->get();

		$deck_options = EtcData::where('label_data', '=', 'deck_options')->get();
		$deck = [];
		foreach ($deck_options as $key => $value){
			$deck[$value['meta_key']] = $value['meta_value'];
		}
		return view('deck', [
			'fractions' => $fractions,
			'exchange_options' => $exchange_options,
			'deck' => $deck
		]);
	}

	//Магазин
	public function marketPage(){
		SiteFunctionsController::updateConnention();
		$user = Auth::user();
		$exchange_options = \DB::table('tbl_etc_data')
			->select('label_data','meta_key','meta_value','meta_key_title')
			->where('label_data', '=', 'premium_buing')
			->orderBy('meta_value','asc')
			->get();

		$fractions = Fraction::where('type', '=', 'race')->orderBy('position','asc')->get();
		$fractions_to_view = Fraction::orderBy('position','asc')->get();
		$current_fraction = \DB::table('tbl_fraction')->select('slug','img_url')->where('slug', '=', $user->last_user_deck)->get();
		return view('market', [
			'fractions' => $fractions,
			'fractions_to_view' => $fractions_to_view,
			'exchange_options' => $exchange_options,
			'user_fraction' => $current_fraction[0]
		]);
	}

	//Волшебство
	public function magicPage(){
		SiteFunctionsController::updateConnention();
		$user = Auth::user();
		$exchange_options = \DB::table('tbl_etc_data')
			->select('label_data','meta_key','meta_value', 'meta_key_title')
			->where('label_data', '=', 'premium_buing')
			->orderBy('meta_value','asc')
			->get();

		$fractions = Fraction::where('type', '=', 'race')->orderBy('position','asc')->get();
		$current_fraction = \DB::table('tbl_fraction')->select('slug','img_url')->where('slug', '=', $user->last_user_deck)->get();
		return view('magic', [
			'fractions' => $fractions,
			'exchange_options' => $exchange_options,
			'user_fraction' => $current_fraction[0]
		]);
	}

	//Настройки
	public function settingsPage(){
		SiteFunctionsController::updateConnention();
		$exchange_options = \DB::table('tbl_etc_data')
			->select('label_data','meta_key','meta_value', 'meta_key_title')
			->where('label_data', '=', 'premium_buing')
			->orderBy('meta_value','asc')
			->get();

		$fractions = Fraction::where('type', '=', 'race')->orderBy('position','asc')->get();
		return view('settings', ['fractions' => $fractions, 'exchange_options' => $exchange_options]);
	}

	//Тех поддержка
    public function supportPage(){
        $exchange_options = \DB::table('tbl_etc_data')
            ->select('label_data','meta_key','meta_value', 'meta_key_title')
            ->where('label_data', '=', 'premium_buing')
            ->orderBy('meta_value','asc')
            ->get();

        $fractions = Fraction::where('type', '=', 'race')->orderBy('position','asc')->get();

        $rubrics = Rubric::orderBy('position','asc')->get();
        return view('support', [
            'fractions' => $fractions,
            'exchange_options' => $exchange_options,
            'rubrics' => $rubrics
        ]);
    }

	//Обучение
	public function trainingPage(){
		SiteFunctionsController::updateConnention();
		$exchange_options = \DB::table('tbl_etc_data')
			->select('label_data','meta_key','meta_value', 'meta_key_title')
			->where('label_data', '=', 'premium_buing')
			->orderBy('meta_value','asc')
			->get();

		$fractions = Fraction::where('type', '=', 'race')->orderBy('position','asc')->get();
        $page_content = Page::where('slug','=','training')->get();

		return view('training', [
		    'fractions' => $fractions,
            'exchange_options' => $exchange_options,
            'page_content' => $page_content[0]
        ]);
	}
}