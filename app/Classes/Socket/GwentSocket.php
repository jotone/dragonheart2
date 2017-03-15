<?php
namespace App\Classes\Socket;

use App\Battle;
use App\BattleMembers;
use App\League;
use App\User;
use App\Classes\Socket\Base\BaseSocket;
use App\Http\Controllers\Site\SiteGameController;
use Crypt;
use Ratchet\ConnectionInterface;

class GwentSocket extends BaseSocket
{
	protected $clients;  //Соединения клиентов
	protected $battles;
	protected $battle_id;
	protected $users_data;
	protected $magic_usage;
	public $step_status;

	public function __construct(){
		$this->clients = new \SplObjectStorage;
	}

    //Socket actions
	public function onError(ConnectionInterface $conn, \Exception $e){
		echo 'An error has occured: '.$e->getMessage()."\n";
		$conn -> close();
	}

	public function onOpen(ConnectionInterface $conn){
		//Пользователь присоединяется к сессии
		$this->clients->attach($conn); //Добавление клиента
		echo 'New connection ('.$conn->resourceId.')'."\n\r";
	}

    public function onClose(ConnectionInterface $conn){
        $battle = Battle::find($this->battle_id);

        if($battle->fight_status < 3){
            if($battle->disconected_count <= 2){
                $battle->disconected_count++;
                $battle->save();
            }

            if($battle->disconected_count == 2){
                self::waitForRoundEnds($this, $conn);
            }
        }else{
            $this->clients->detach($conn);
            echo 'Connection '.$conn->resourceId.' has disconnected'."\n";
        }
    }

    protected static function sendMessageToOthers($from, $result, $battles){
        foreach ($battles as $client) {
            if ($client->resourceId != $from->resourceId) {
                $client->send(json_encode($result));
            }
        }
    }

    protected static function sendMessageToSelf($from, $message){
        $from->send(json_encode($message));
    }
    //Socket actions end

    //Service functons
    protected static function strRowToInt($field){
        switch($field){ //Порядковый номер поля
            case 'meele':		$field_row = 0; break;
            case 'range':		$field_row = 1; break;
            case 'superRange':	$field_row = 2; break;
            case 'sortable-cards-field-more':$field_row = 3; break;
        }
        return $field_row;
    }

    public static function transformObjToArr($card){
        if(!is_array($card)){
            $card = get_object_vars($card);
        }
        return $card;
    }

    protected static function sortingDeck(&$deck){
        usort($deck, function($a, $b){
            $r = ($b['strength']  - $a['strength']);
            if($r !== 0) return $r;
            return strnatcasecmp($a['title'], $b['title']);
        });
    }

    protected static function sortDecksByStrength($users_data){
        self::sortingDeck($users_data['user']['deck']);
        self::sortingDeck($users_data['user']['discard']);
        self::sortingDeck($users_data['user']['hand']);
        self::sortingDeck($users_data['opponent']['deck']);
        self::sortingDeck($users_data['opponent']['discard']);
        self::sortingDeck($users_data['opponent']['hand']);
        return $users_data;
    }
    // /Service functons

	//Обработчик каждого сообщения
	public function onMessage(ConnectionInterface $from, $msg){
        
		$msg = json_decode($msg); // сообщение от пользователя arr[action, ident[battleId, UserId, Hash]]

		if(!isset($this->battles[$msg->ident->battleId])){
			$this->battles[$msg->ident->battleId] = new \SplObjectStorage; 
		}

		if(!$this->battles[$msg->ident->battleId]->contains($from)){
			$this->battles[$msg->ident->battleId]->attach($from);
		}
		$SplBattleObj = $this->battles;

		$timing_settings = SiteGameController::getTimingSettings();

		$battle = Battle::find($msg->ident->battleId); //Даные битвы
		$this->battle_id = $msg->ident->battleId;

		$battle_members = BattleMembers::where('battle_id', '=', $msg->ident->battleId)->get(); //Данные о участвующих в битве
		$users_data = [];

		\DB::table('users')->where('id', '=', $msg->ident->userId)->update([
			'updated_at'	=> date('Y-m-d H:i:s'),
			'user_online'	=> '1'
		]);

		//Создание массивов пользовательских данных
		foreach($battle_members as $key => $value){
			$user = User::find($value->user_id);
			$user_identificator = ($value->user_id == $battle->creator_id)? 'p1' : 'p2';
			if($value->user_id == $msg->ident->userId){
				$users_data['user'] = [
					'id'			=> $value->user_id,
					'login'			=> $user->login,
					'player'		=> $user_identificator,					//Идентификатор поля пользователя
					'user_magic'	=> unserialize($user->user_magic),
					'magic_effects'	=> unserialize($value->magic_effects),	//Список активных маг. эффектов
					'energy'		=> $user->user_energy,					//Колличество энергии пользователя
					'deck'			=> unserialize($value->user_deck),		//Колода пользователя
					'hand'			=> unserialize($value->user_hand),		//Рука пользователя
					'discard'		=> unserialize($value->user_discard),	//Отбой пользователя
					'current_deck'	=> $user->user_current_deck,			//Название фракции текущей колоды пользоватля
					'card_source'	=> $value->card_source,					//Источник карт (рука/колода/отбой) текущего хода
					'player_source'	=> $value->player_source,				//Источник карт игрока (свои/противника) текущего хода
					'cards_to_play'	=> unserialize($value->card_to_play),	//Массив определенных условиями действия карт при отыгрыше из колоды или отбое
					'round_passed'	=> $value->round_passed,				//Маркер паса
					'addition_data'	=> unserialize($value->addition_data),
					'battle_member_id'=> $value->id,						//ID текущей битвы
					'turn_expire'	=> $value->turn_expire,
                    'time_shift'	=> $value->time_shift,
				];
				$users_data[$user_identificator] = &$users_data['user'];
				$users_data[$value->user_id] = &$users_data['user'];
			}else{
				$users_data['opponent'] = [
					'id'			=> $value->user_id,
					'login'			=> $user->login,
					'player'		=> $user_identificator,
					'user_magic'	=> unserialize($user->user_magic),
					'magic_effects'	=> unserialize($value->magic_effects),
					'energy'		=> $user->user_energy,
					'deck'			=> unserialize($value->user_deck),
					'hand'			=> unserialize($value->user_hand),
					'discard'		=> unserialize($value->user_discard),
					'current_deck'	=> $user->user_current_deck,
					'card_source'	=> $value->card_source,
					'player_source'	=> $value->player_source,
					'cards_to_play'	=> unserialize($value->card_to_play),
					'round_passed'	=> $value->round_passed,
					'addition_data'	=> unserialize($value->addition_data),
					'battle_member_id'=> $value->id,
					'turn_expire'	=> $value->turn_expire,
                    'time_shift'	=> $value->time_shift
				];
				$users_data[$user_identificator] = &$users_data['opponent'];
                $users_data[$value->user_id] = &$users_data['opponent'];
			}
		}

		$this->step_status = [
            'added_cards'   => [],
            'played_card'   => [
                'card' => '',
                'move_to' => [
                        'player'=> '',
                        'row'   => '',
                        'user'  => ''
                    ],
                'strength' => ''
            ],
            'dropped_cards' => [],
            'played_magic'  => '',
            'cards_strength'=> [],
            'actions'       => []
        ];
		if(isset($msg->timing)) $users_data['user']['turn_expire'] = $msg->timing - $users_data['user']['time_shift'];
        var_dump($msg);
        switch($msg->action){
            //Пользователь присоединился
            case 'userJoinedToRoom':
                if($battle->user_id_turn != 0){
                    $user_turn = $users_data[$battle->user_id_turn]['login'];
                }else{
                    $user_turn = '';
                }
                if ($battle->fight_status <= 1) {
                    if (count($battle_members) == $battle->players_quantity) {
                        if ($battle->fight_status == 0) {
                            $battle->turn_expire = $timing_settings['card_change'] - $users_data['user']['time_shift'] + time();
                            $battle->fight_status = 1; // Подключилось нужное количество пользователей
                            $battle->save();
                        }

                        $result = [
                            'message'	=> 'usersAreJoined',
                            'JoinedUser'=> $users_data['user']['login'],
                            'login'		=> $user_turn,
                            'battleInfo'=> $msg->ident->battleId,
                            'turn_expire'=>$battle->turn_expire
                        ];

                        self::sendMessageToSelf($from, $result); //Отправляем результат отправителю
                        self::sendMessageToOthers($from, $result, $this->battles[$msg->ident->battleId]);
                    }
                }

                if ($battle->fight_status == 2) {
                    $result = [
                        'message'		=> 'allUsersAreReady',
                        'timing'		=> $battle->turn_expire,
                        'addition_data'	=> $users_data['user']['addition_data'],
                        'battleInfo'	=> $msg->ident->battleId,
                        'login'			=> $user_turn,
                        'round'         => $battle->round_count,
                        'users'			=> [
                            $users_data['user']['login']	=> $users_data['user']['energy'],
                            $users_data['opponent']['login']=> $users_data['opponent']['energy']
                        ],
                        'turnDescript'	=> [
                            'cardSource'	=> $users_data['user']['card_source'],
                            'playerSource'	=> $users_data['user']['player_source'],
                            'cardToPlay'	=> $users_data['user']['cards_to_play'],
                        ]
                    ];
                    self::sendMessageToSelf($from, $result);
                }
            break;

            case 'changeCardInHand':
                $users_battle_data = BattleMembers::find($users_data['user']['battle_member_id']);
                if($users_battle_data['available_to_change'] > 0){
                    $rand = mt_rand(0, count($users_data['user']['deck']) - 1);
                    $card_to_add = $users_data['user']['deck'][$rand];

                    unset($users_data['user']['deck'][$rand]);
                    $users_data['user']['deck'] = array_values($users_data['user']['deck']);

                    foreach($users_data['user']['hand'] as $hand_iter => $hand_card_data){
                        if(Crypt::decrypt($hand_card_data['id']) == Crypt::decrypt($msg->card)){
                            $users_data['user']['deck'][] = $users_data['user']['hand'][$hand_iter];
                            unset($users_data['user']['hand'][$hand_iter]);
                            $users_data['user']['hand'][] = $card_to_add;
                            break;
                        }
                    }
                    $users_data['user']['hand'] = array_values($users_data['user']['hand']);

                    $users_battle_data['user_deck'] = serialize($users_data['user']['deck']);
                    $users_battle_data['user_hand'] = serialize($users_data['user']['hand']);
                    $users_battle_data['available_to_change'] = $users_battle_data['available_to_change'] - 1;

                    $users_battle_data->save();

                    $users_data = self::sortDecksByStrength($users_data);

                    $result = [
                        'message'		=> 'changeCardInHand',
                        'user_deck'		=> $users_data['user']['deck'],
                        'card_to_hand'	=> $card_to_add,
                        'card_to_drop'	=> $msg->card,
                        'can_change_cards'=> $users_battle_data['available_to_change']
                    ];

                    self::sendMessageToSelf($from, $result);
                }
            break;

            //Пользователь готов
            case 'userReady':
                if($battle->fight_status == 1){
                    $ready_players_count = 0;//Количество игроков за столом готовых к игре
                    foreach($battle_members as $key => $value){
                        if($value->user_ready != 0){
                            $ready_players_count++;
                        }
                    }

                    if($ready_players_count == 2){
                        $cursed_players = [];
                        $player = 'p1';
                        if($users_data['p1']['current_deck'] == 'cursed'){
                            $cursed_players[] = $users_data['user']['player'];
                            $player = 'p1';
                        }
                        if($users_data['p2']['current_deck'] == 'cursed'){
                            $cursed_players[] = $users_data['opponent']['player'];
                            $player = 'p2';
                        }

                        if($battle->user_id_turn < 1){
                            if((count($cursed_players) == 1) && ($msg->ident->userId == $users_data[$player]['id'])){
                                if(isset($msg->turn)){
                                    $players_turn = (($users_data['user']['login'] == $msg->turn) || ($msg->turn == ''))
                                        ? $users_data['user']['id']
                                        : $users_data['opponent']['id'];
                                }else{
                                    $rand = mt_rand(0, 1);
                                    $players_turn = ($rand == 0)
                                        ? $users_data['p1']['id']
                                        : $users_data['p2']['id'];
                                }
                            }else{
                                $rand = mt_rand(0, 1);
                                $players_turn = ($rand == 0)
                                    ? $users_data['p1']['id']
                                    : $users_data['p2']['id'];
                            }
                            $battle->user_id_turn = $players_turn;
                            $battle->first_turn_user_id = $players_turn;
                            $battle->save();
                        }

                        $user_timing = \DB::table('tbl_battle_members')
                            ->select('user_id','turn_expire')
                            ->where('user_id','=',$battle->user_id_turn)
                            ->first();
                        $battle->turn_expire = $user_timing->turn_expire - $users_data[$battle->user_id_turn]['time_shift'] + time();

                        $result = [
                            'message'		=> 'allUsersAreReady',
                            'timing'		=> $battle->turn_expire,
                            'addition_data'	=> $users_data['user']['addition_data'],
                            'battleInfo'	=> $msg->ident->battleId,
                            'login'			=> $users_data[$battle->user_id_turn]['login'],
                            'round'         => $battle->round_count,
                            'users'			=> [
                                $users_data['user']['login']	=> $users_data['user']['energy'],
                                $users_data['opponent']['login']=> $users_data['opponent']['energy']
                            ],
                            'turnDescript'	=> [
                                'cardSource'	=> $users_data['opponent']['card_source'],
                                'playerSource'	=> $users_data['opponent']['player_source'],
                                'cardToPlay'	=> $users_data['opponent']['cards_to_play'],
                            ],
                        ];

                        if ($battle->fight_status <= 1) {
                            self::sendMessageToOthers($from, $result, $this->battles[$msg->ident->battleId]);
                        }
                        $battle->fight_status = 2;
                        $battle->save();

                        $result = [
                            'message'		=> 'allUsersAreReady',
                            'timing'		=> $battle->turn_expire,
                            'addition_data'	=> $users_data['user']['addition_data'],
                            'battleInfo'	=> $msg->ident->battleId,
                            'login'			=> $users_data[$battle->user_id_turn]['login'],
                            'round'         => $battle->round_count,
                            'users'			=> [
                                $users_data['user']['login']	=> $users_data['user']['energy'],
                                $users_data['opponent']['login']=> $users_data['opponent']['energy']
                            ],
                            'turnDescript'	=> [
                                'cardSource'	=> $users_data['user']['card_source'],
                                'playerSource'	=> $users_data['user']['player_source'],
                                'cardToPlay'	=> $users_data['user']['cards_to_play'],
                            ],
                        ];
                        self::sendMessageToSelf($from, $result);

                    }else{
                        $cursed_players = [];
                        $player = 'p1';
                        if($users_data['p1']['current_deck'] == 'cursed'){
                            $cursed_players[] = $users_data['user']['player'];
                            $player = 'p1';
                        }
                        if($users_data['p2']['current_deck'] == 'cursed'){
                            $cursed_players[] = $users_data['opponent']['player'];
                            $player = 'p2';
                        }

                        if((count($cursed_players) == 1) && ($msg->ident->userId == $users_data[$player]['id'])){

                            if(isset($msg->turn)){
                                $players_turn = (($users_data['p1']['login'] == $msg->turn) || ($msg->turn == ''))
                                    ? $users_data['p1']['id']
                                    : $players_turn = $users_data['p2']['id'];
                            }else{
                                $players_turn = $users_data['user']['id'];
                            }
                            $battle->user_id_turn = $players_turn;
                            $battle->save();
                        }
                    }
                }
            break;

            case 'userMadeCardAction':
                if($battle->fight_status == 2){
                    //Данные о текущем пользователе
                    $battle_field = unserialize($battle->battle_field);//Данные о поле битвы
                    $magic_usage = unserialize($battle->magic_usage);//Данные о использовании магии
                    //Установка источника хода по умолчанию
                    $users_data['user']['player_source'] = $users_data['user']['player'];
                    $users_data['user']['cards_to_play'] = [];
                    $users_data['user']['card_source'] = 'hand';
                    $addition_data = [];

                    if($users_data['opponent']['round_passed'] == 1){
                        $user_turn = $users_data['user']['login'];
                        $user_turn_id = $users_data['user']['id'];
                    }else{
                        $user_turn = $users_data['opponent']['login'];
                        $user_turn_id = $users_data['opponent']['id'];
                    }

                    if($msg->magic != ''){
                        $disable_magic = false;
                        $magic = json_decode(SiteGameController::getMagicData($msg->magic));
                        if (($users_data['user']['user_magic'][Crypt::decrypt($magic->id)]['used_times'] > 0) && ($users_data['user']['energy'] >= $magic->energy_cost)) {
                            $users_data['user']['user_magic'][Crypt::decrypt($magic->id)]['used_times'] = $users_data['user']['user_magic'][Crypt::decrypt($magic->id)]['used_times'] - 1;
                            $users_data['user']['energy'] = $users_data['user']['energy'] - $magic->energy_cost;

                            if(!isset($magic_usage[$users_data['user']['player']][$battle->round_count])){
                                $magic_usage[$users_data['user']['player']][$battle->round_count] = [
                                    'id' => $msg->magic,
                                    'allow' => '1'
                                ];
                                $current_actions = $magic->actions;
                                $this->step_status['played_magic'][$users_data['user']['player']] = $magic;
                            }else{
                                $disable_magic = true;
                            }
                        }else{
                            $disable_magic = true;
                        }

                        if($disable_magic){
                            $current_actions = [];
                        }

                        \DB::table('users')->where('id', '=', $users_data['user']['id'])->update([
                            'user_energy' => $users_data['user']['energy'],
                            'user_magic' => serialize($users_data['user']['user_magic'])
                        ]);
                    }

                    if($msg->card != ''){
                        $card = json_decode(SiteGameController::getCardData($msg->card));//Получаем данные о карте
                        $card = self::transformObjToArr($card);

                        $card_row = self::strRowToInt($msg->BFData->row);
                        $card_field = $msg->BFData->field;

                        if($card['type'] == 'special'){
                            if($card_row == 3){
                                $battle_field['mid'][] = ['card' => $card, 'strength' => $card['strength'], 'login' => $users_data['user']['login']];
                                //Если карт на поле спец карт больше 6ти
                                if(count($battle_field['mid']) > 6){
                                    //Кидает первую карту в отбой
                                    if($users_data['user']['login'] == $battle_field['mid'][0]['login']){
                                        $users_data['user']['discard'][] = $battle_field['mid'][0]['card'];
                                    }else{
                                        $users_data['opponent']['discard'][] = $battle_field['mid'][0]['card'];
                                    }
                                    //Удаляем первую карту
                                    unset($battle_field['mid'][0]);
                                }
                                //Добавляем текущую карту на поле боя и её принадлежность пользователю
                                $battle_field['mid'] = array_values($battle_field['mid']);
                            }else{
                                //Если логика карт предусматривает сразу уходить в отбой
                                foreach($card['actions'] as $i => $action){
                                    if (($action->action == '6') || ($action->action == '7') || ($action->action == '10') || ($action->action == '11') || ($action->action == '15') || ($action->action == '19')) {
                                        $users_data['user']['discard'][] = $card;
                                    }else{
                                        //Еcли в ряду уже есть спец карта
                                        if (!empty($battle_field[$card_field][$card_row]['special'])) {
                                            $users_data[$card_field]['discard'][] = $battle_field[$card_field][$card_row]['special']['card'];
                                        }
                                        $battle_field[$card_field][$card_row]['special'] = [
                                            'card' => $card,
                                            'strength' => $card['strength'],
                                            'login' => $users_data['user']['login']
                                        ];
                                    }
                                }
                            }
                        }else{
                            $battle_field[$card_field][$card_row]['warrior'][] = [
                                'card' => $card,
                                'strength' => $card['strength'],
                                'login' => $users_data['user']['login']
                            ];
                        }
                        $this->step_status['played_card'] = [
                            'card' => $card,
                            'move_to' => [
                                'player'=> $users_data['user']['player'],
                                'row'   => $card_row,
                                'user'  => $users_data['user']['login']
                            ],
                            'strength' => $card['strength']
                        ];
                        //Если был задействован МЭ "Марионетка"

                        if(
                            (isset($magic_usage[$users_data['user']['player']][$battle->round_count]['id']))
                            && (base64_decode(base64_decode($magic_usage[$users_data['user']['player']][$battle->round_count]['id'])) == '19')
                            && ($magic_usage[$users_data['user']['player']][$battle->round_count]['allow'] == 1)
                        ){
                            $magic_usage[$users_data['user']['player']][$battle->round_count]['allow'] = '0';
                            $user_type = 'opponent';
                        }else{
                            $user_type = 'user';
                        }

                        //Убираем карту из текущй колоды
                        $users_data[$user_type][$msg->source] = self::dropCardFromDeck($users_data[$user_type][$msg->source], $card);
                        //$users_data['user'][$msg->source] = self::dropCardFromDeck($users_data['user'][$msg->source], $card);
                        $current_actions = $card['actions'];
                    }

                    //Применение действий
                    $add_time = true;
                    foreach($current_actions as $action_iter => $action){
                        $action_result = self::actionProcessing($action, $battle_field, $users_data, $addition_data, $user_turn_id, $user_turn, $msg, $magic_usage, $this->step_status);
                        $this->step_status = $action_result['step_status'];
                        $battle_field   = $action_result['battle_field'];
                        $users_data     = $action_result['users_data'];
                        $addition_data  = $action_result['addition_data'];
                        $user_turn_id   = $action_result['user_turn_id'];
                        $user_turn      = $action_result['user_turn'];
                        $magic_usage    = $action_result['magic_usage'];
                        if( ($action->action == '7') || ($action->action == '10') || ($action->action == '14') || ($action->action == '15') ){
                            $add_time = false;
                        }
                    }

                    //Сортировка колод
                    $users_data = self::sortDecksByStrength($users_data);
                    //Обработка действий
                    $battle_field = self::recalculateCardsStrength($battle, $battle_field, $users_data, $magic_usage);

                    foreach($battle_field as $player => $rows) {
                        if($player != 'mid'){
                            foreach ($rows as $row => $row_data) {
                                foreach ($row_data['warrior'] as $card_iter => $card_data) {
                                    //cards_strength
                                    $this->step_status['cards_strength'][$player][$row][$card_iter] = $card_data['strength'];
                                    //added_cards
                                    foreach ($this->step_status['added_cards'] as $player_in_added => $rows_in_added) {
                                        foreach($rows_in_added as $row_source => $cards){
                                            if($row_source !== 'hand'){
                                                foreach ($cards as $iter => $card) {
                                                    if($card['card']['id'] == $card_data['card']['id']){
                                                        $this->step_status['added_cards'][$player_in_added][$row_source][$iter]['strength'] = $card_data['strength'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                    }
                    if($add_time === true){
                        $turn_expire = $msg->timing + $timing_settings['additional_time'];
                        $showTimerOfUser = 'opponent';
                    }else{
                        $turn_expire = $msg->timing;
                        $showTimerOfUser = 'user';
                    }

                    if($turn_expire > $timing_settings['max_step_time']){
                        $turn_expire = $timing_settings['max_step_time'];
                    }

                    $this->users_data = &$users_data;
                    $this->magic_usage = &$magic_usage;

                    //Сохранение данных битвы
                    $users_battle_data = \DB::table('tbl_battle_members')->where('id', '=', $users_data['user']['battle_member_id'])->update([
                        'user_deck'		=> serialize($users_data['user']['deck']),
                        'user_hand'		=> serialize($users_data['user']['hand']),
                        'user_discard'	=> serialize($users_data['user']['discard']),
                        'card_source'	=> $users_data['user']['card_source'],
                        'player_source'	=> $users_data['user']['player_source'],
                        'card_to_play'	=> serialize($users_data['user']['cards_to_play']),
                        'round_passed'	=> '0',
                        'addition_data'	=> serialize($addition_data),
                        'turn_expire'	=> $turn_expire
                    ]);
                    $opponent_battle_data = \DB::table('tbl_battle_members')->where('id', '=', $users_data['opponent']['battle_member_id'])->update([
                        'user_deck'	=> serialize($users_data['opponent']['deck']),
                        'user_hand'	=> serialize($users_data['opponent']['hand']),
                        'user_discard'=> serialize($users_data['opponent']['discard'])
                    ]);

                    //Сохраняем поле битвы
                    $battle->battle_field	= serialize($battle_field);
                    $battle->magic_usage	= serialize($magic_usage);
                    $battle->user_id_turn	= $user_turn_id;
                    $battle->turn_expire	= $turn_expire+time();
                    $battle->save();

                    self::sendUserMadeActionData($this->step_status, $msg, $SplBattleObj, $from, $battle_field, $magic_usage, $users_data, $user_turn, $addition_data, $battle->round_count, '', $showTimerOfUser);

                }
            break;

            case 'dropCard':
                var_dump($msg->player != $users_data['user']['player']);
                if($msg->player != $users_data['user']['player']){
                    $position = -1;
                    foreach($users_data[$msg->player][$msg->deck] as $card_iter => $card_data){
                        if(Crypt::decrypt($card_data['id']) == Crypt::decrypt($msg->card)){
                            $position = $card_iter;
                            break;
                        }
                    }
                    if($position >= 0){
                        $this->step_status['dropped_cards'][$msg->player][$msg->deck][] = $users_data[$msg->player][$msg->deck][$position];
                        unset($users_data[$msg->player][$msg->deck][$position]);
                        $users_data[$msg->player][$msg->deck] = array_values($users_data[$msg->player][$msg->deck]);

                        \DB::table('tbl_battle_members')->where('id','=',$users_data[$msg->player]['battle_member_id'])->update([
                            'user_'.$msg->deck => serialize($users_data[$msg->player][$msg->deck])
                        ]);
                        $result = [
                            'message' => 'dropCard',
                            'step_data' => $this->step_status
                        ];
                        self::sendMessageToSelf($from, $result);
                        self::sendMessageToOthers($from, $result, $SplBattleObj[$msg->ident->battleId]);
                    }
                }
            break;

            case 'returnCardToHand':
                $battle_field = unserialize($battle->battle_field);
                $magic_usage = unserialize($battle->magic_usage);//Данные о использовании магии
                $player = $users_data[$msg->ident->userId]['player'];

                $turn_expire = $users_data[$msg->ident->userId]['turn_expire'] + $timing_settings['additional_time'] - $users_data[$msg->ident->userId]['time_shift'];
                \DB::table('tbl_battle_members')
                    ->where('id','=',$users_data[$msg->ident->userId]['battle_member_id'])
                    ->update(['turn_expire' => $turn_expire]);

                foreach($battle_field[$player] as $row => $row_data){
                    foreach($row_data['warrior'] as $card_iter => $card_data){
                        if(Crypt::decrypt($card_data['card']['id']) == Crypt::decrypt($msg->card)){
                            $users_data[$player]['hand'][] = $card_data['card'];
                            $this->step_status['added_cards'][$player]['hand'][] = $card_data['card'];
                            unset($battle_field[$player][$row]['warrior'][$card_iter]);
                            $battle_field[$player][$row]['warrior'] = array_values($battle_field[$player][$row]['warrior']);
                            break 2;
                        }
                    }
                }
                $users_data[$player]['addition_data'] = [];
                $users_data = self::sortDecksByStrength($users_data);
                self::saveUsersDecks($users_data);

                $user_type = (0 != $users_data['opponent']['round_passed'])? 'user': 'opponent';

                $battle->battle_field = serialize($battle_field);
                $battle->user_id_turn = $users_data[$user_type]['id'];
                $battle->turn_expire = $turn_expire + time();
                $battle->save();

                self::sendUserMadeActionData($this->step_status, $msg, $SplBattleObj, $from, $battle_field, $magic_usage, $users_data, $users_data[$user_type]['login'], [], $battle->round_count, '');
            break;

            case 'userPassed':
                $battle_field = unserialize($battle->battle_field);
                $magic_usage = unserialize($battle->magic_usage);
                $addition_data = [];

                $users_battle_data = BattleMembers::find($users_data['user']['battle_member_id']);
                $users_battle_data['round_passed'] = 1;
                $users_battle_data['turn_expire'] = $msg->timing;// - $users_data['user']['time_shift'];
                $users_battle_data->save();

                $users_passed_count = $users_data['opponent']['round_passed'] + 1;

                $user_turn = $users_data['opponent']['login'];
                $user_turn_id = $users_data['opponent']['id'];

                $battle->user_id_turn = $user_turn_id;
                $battle->pass_count++;
                $battle->turn_expire = $msg->timing + time();
                $battle->save();

                //Если только один пасанувший
                if($users_passed_count == 1){
                    self::sendUserMadeActionData($this->step_status, $msg, $SplBattleObj, $from, $battle_field, $magic_usage, $users_data, $user_turn, $addition_data, $battle->round_count);
                    $result = ['message'=> 'userPassed', 'user_login'=>$users_data['user']['login']];
                    self::sendMessageToOthers($from, $result, $SplBattleObj[$msg->ident->battleId]);
                }

                //Если спасовало 2 пользователя
                if($users_passed_count == 2){
                    $battle_field = self::recalculateCardsStrength($battle, $battle_field, $users_data, $magic_usage);

                    //Подсчет результатп раунда по очкам
                    $total_str = self::calcStrByPlayers($battle_field);

                    //Статус битвы (очки раундов)
                    $round_status = unserialize($battle->round_status);
                    //Результаты раунда отдельно по игрокам
                    $user_points = $total_str[$users_data['user']['player']];
                    $opponent_points = $total_str[$users_data['opponent']['player']];

                    $gain_cards_count = ['user' => 1, 'opponent' => 1];//Количество дополнительных карт
                    //Определение выигравшего
                    if($user_points > $opponent_points){
                        $round_status[$users_data['user']['player']][] = 1;
                        $round_result = 'Выграл '.$users_data['user']['login'];

                        if($users_data['opponent']['current_deck'] == 'knight') $gain_cards_count['opponent'] = 2;
                    }
                    if($user_points < $opponent_points){
                        $round_status[$users_data['opponent']['player']][] = 1;
                        $round_result = 'Выграл '.$users_data['opponent']['login'];

                        if($users_data['user']['current_deck'] == 'knight') $gain_cards_count['user'] = 2;
                    }
                    if($user_points == $opponent_points){
                        //Если колода пользователя - нечисть и противник не играет нечистью
                        if( ( ($users_data['user']['current_deck'] == 'undead') || ($users_data['opponent']['current_deck'] == 'undead') ) && ($users_data['user']['current_deck'] != $users_data['opponent']['current_deck']) ){
                            if($users_data['user']['current_deck'] == 'undead'){
                                $round_status[$users_data['user']['player']][] = 1;
                                $round_result = 'Выграл '.$users_data['user']['login'];

                                if($users_data['opponent']['current_deck'] == 'knight') $gain_cards_count['opponent'] = 2;
                            }else{
                                $round_status[$users_data['opponent']['player']][] = 1;
                                $round_result = 'Выграл '.$users_data['opponent']['login'];

                                if($users_data['user']['current_deck'] == 'knight') $gain_cards_count['user'] = 2;
                            }
                        }else{
                            $round_status[$users_data['user']['player']][] = 1;
                            $round_status[$users_data['opponent']['player']][] = 1;
                            $round_result = 'Ничья';
                        }
                    }

                    $wins_status = [
                        $users_data['p1']['login'] => $round_status['p1'],
                        $users_data['p2']['login'] => $round_status['p2']
                    ];
                    //Отпарвка результатов пользователям
                    $result = [
                        'message'		=> 'roundEnds',
                        'battleInfo'	=> $msg->ident->battleId,
                        'roundResult'	=> $round_result,
                        'roundStatus'	=> $wins_status,
                        'user_hand'		=> $users_data['user']['hand'],
                        'user_deck'		=> $users_data['user']['deck'],
                        'user_discard'	=> $users_data['user']['discard'],
                        'opon_discard'	=> $users_data['opponent']['discard'],
                        'magicUsage'	=> $magic_usage,
                        'round'			=> $battle->round_count,
                        'deck_slug'		=> $users_data['user']['current_deck'],
                        'counts'		=> [
                            'user_deck'		=> count($users_data['user']['deck']),
                            'user_discard'	=> count($users_data['user']['discard']),
                            'opon_discard'	=> count($users_data['opponent']['discard']),
                            'opon_deck'		=> count($users_data['opponent']['deck']),
                            'opon_hand'		=> count($users_data['opponent']['hand'])
                        ]
                    ];
                    self::sendMessageToSelf($from, $result);
                    $result = [
                        'message'		=> 'roundEnds',
                        'battleInfo'	=> $msg->ident->battleId,
                        'roundResult'	=> $round_result,
                        'roundStatus'	=> $wins_status,
                        'user_hand'		=> $users_data['opponent']['hand'],
                        'user_deck'		=> $users_data['opponent']['deck'],
                        'user_discard'	=> $users_data['opponent']['discard'],
                        'opon_discard'	=> $users_data['user']['discard'],
                        'magicUsage'	=> $magic_usage,
                        'round'			=> $battle->round_count,
                        'deck_slug'		=> $users_data['user']['current_deck'],
                        'counts'		=> [
                            'user_deck'		=> count($users_data['opponent']['deck']),
                            'user_discard'	=> count($users_data['opponent']['discard']),
                            'opon_discard'	=> count($users_data['user']['discard']),
                            'opon_deck'		=> count($users_data['user']['deck']),
                            'opon_hand'	=> count($users_data['user']['hand'])
                        ],
                    ];
                    self::sendMessageToOthers($from, $result, $this->battles[$msg->ident->battleId]);

                    $clear_result	= self::clearBattleField($battle, $battle_field, $users_data, $magic_usage, $gain_cards_count, $this->step_status);
                    $battle_field	= $clear_result['battle_field'];
                    $users_data		= $clear_result['users_data'];
                    $magic_usage	= $clear_result['magic_usage'];
                    $this->step_status = $clear_result['step_status'];

                    $battle->round_count	= $battle->round_count +1;
                    $battle->round_status	= serialize($round_status);
                    $battle->battle_field	= serialize($battle_field);
                    $battle->magic_usage	= serialize($magic_usage);
                    $battle->undead_cards	= serialize($clear_result['deadless_cards']);
                    $battle->pass_count		= 0;
                    $battle->save();

                    if((count($round_status['p1']) >= 2) || (count($round_status['p2']) >= 2)){
                        $battle->fight_status = 3;
                        $battle->save();

                        if(count($round_status['p1']) > count($round_status['p2'])){
                            $game_result = 'Игру выграл '.$users_data['p1']['login'];
                            $winner = $users_data['p1']['id'];
                            $to_self = self::saveGameResults($users_data['p1']['id'], $battle, 'win');
                            $to_enemy = self::saveGameResults($users_data['p2']['id'], $battle, 'loose');
                        }

                        if(count($round_status['p1']) < count($round_status['p2'])){
                            $game_result = 'Игру выграл '.$users_data['p2']['login'];
                            $winner = $users_data['p2']['id'];
                            $to_self = self::saveGameResults($users_data['p2']['id'], $battle, 'win');
                            $to_enemy = self::saveGameResults($users_data['p1']['id'], $battle, 'loose');
                        }

                        if(count($round_status['p1']) == count($round_status['p2'])){
                            if( ( ($users_data['user']['current_deck'] == 'undead') || ($users_data['opponent']['current_deck'] == 'undead') ) && ($users_data['user']['current_deck'] != $users_data['opponent']['current_deck']) ){
                                if($users_data['user']['current_deck'] == 'undead'){
                                    $game_result = 'Игру выграл '.$users_data['user']['login'];
                                    $winner = $users_data['user']['id'];
                                    $to_self = self::saveGameResults($users_data['user']['id'], $battle, 'win');
                                    $to_enemy = self::saveGameResults($users_data['opponent']['id'], $battle, 'loose');
                                }else{
                                    $game_result = 'Игру выграл '.$users_data['opponent']['login'];
                                    $winner = $users_data['opponent']['id'];
                                    $to_self = self::saveGameResults($users_data['opponent']['id'], $battle, 'win');
                                    $to_enemy = self::saveGameResults($users_data['user']['id'], $battle, 'loose');
                                }
                            }else{
                                $game_result = 'Игра сыграна в ничью';
                                $winner = '';
                                $to_self = self::saveGameResults($users_data['user']['id'], $battle, 'draw');
                                $to_enemy = self::saveGameResults($users_data['opponent']['id'], $battle, 'draw');
                            }
                        }

                        \DB::table('users')->where('login', '=', $users_data['user']['login'])->update(['user_busy' => 0]);
                        \DB::table('users')->where('login', '=', $users_data['opponent']['login'])->update(['user_busy' => 0]);

                        $result = ['message' => 'gameEnds', 'gameResult' => $game_result, 'battleInfo' => $msg->ident->battleId];

                        if(($winner == '') || ($winner == $msg->ident->userId)){
                            $result['resources'] = $to_self;
                            self::sendMessageToSelf($from, $result);
                            $result['resources'] = $to_enemy;
                            self::sendMessageToOthers($from, $result, $this->battles[$msg->ident->battleId]);
                        }else{
                            $result['resources'] = $to_enemy;
                            self::sendMessageToSelf($from, $result);
                            $result['resources'] = $to_self;
                            self::sendMessageToOthers($from, $result, $this->battles[$msg->ident->battleId]);
                        }

                    }else{
                        if($users_data['user']['id'] == $battle->first_turn_user_id){
                            $user_turn_id = $users_data['opponent']['id'];
                            $user_turn = $users_data['opponent']['login'];
                        }else{
                            $user_turn_id = $users_data['user']['id'];
                            $user_turn = $users_data['user']['login'];
                        }
                        $users_data = self::sortDecksByStrength($users_data);
                        $battle_field = self::recalculateCardsStrength($battle, $battle_field, $users_data, $magic_usage);

                        //timing
                        foreach($users_data as $type => $user_data){
                            if(($type == 'user') || ($type == 'opponent')){
                                $timing = $users_data[$type]['turn_expire'] + $timing_settings['first_step_r'.$battle->round_count] - $users_data[$type]['time_shift'];// - $timing_settings['additional_time']
                                if($timing > $timing_settings['max_step_time']){
                                    $timing = $timing_settings['max_step_time'];
                                }
                                \DB::table('tbl_battle_members')
                                    ->where('id','=',$users_data[$type]['battle_member_id'])
                                    ->update([
                                        'turn_expire' => $timing
                                    ]);
                            }
                        }

                        $battle->first_turn_user_id = $user_turn_id;
                        $battle->user_id_turn = $user_turn_id;
                        $battle->battle_field = serialize($battle_field);
                        $battle->save();

                        foreach($users_data as $user_type => $user){
                            if(($user_type == 'user') || ($user_type == 'opponent')){
                                $battle_data = BattleMembers::find($users_data[$user_type]['battle_member_id']);
                                $battle_data['user_deck']	= serialize($users_data[$user_type]['deck']);
                                $battle_data['user_hand']	= serialize($users_data[$user_type]['hand']);
                                $battle_data['user_discard']= serialize($users_data[$user_type]['discard']);
                                $battle_data['card_source']	= 'hand';
                                $battle_data['round_passed']= '0';
                                $battle_data['card_to_play']= 'a:0:{}';
                                $battle_data['addition_data']= 'a:0:{}';
                                $battle_data['player_source']= $users_data[$user_type]['player'];
                                $battle_data->save();
                            }
                        }

                        $cursed_players = [];
                        if($users_data['p1']['current_deck'] == 'cursed'){
                            $cursed_players[] = $users_data['user']['player'];
                            $player = 'p1';
                        }
                        if($users_data['p2']['current_deck'] == 'cursed'){
                            $cursed_players[] = $users_data['opponent']['player'];
                            $player = 'p2';
                        }
                        $data_to_user = '';
                        if(count($cursed_players) == 1){
                            $addition_data = ['action' => 'activate_turn_choise'];
                            $user_turn = $users_data[$player]['login'];
                            $user_turn_id = $users_data[$player]['id'];

                            $battle->user_id_turn = $user_turn_id;
                            $battle->save();

                            $users_battle_data = BattleMembers::find($users_data[$player]['battle_member_id']);
                            $users_battle_data['addition_data'] = serialize($addition_data);
                            $users_battle_data->save();
                            $data_to_user = $player;
                        }
                        self::sendUserMadeActionData($this->step_status, $msg, $SplBattleObj, $from, $battle_field, $magic_usage, $users_data, $user_turn, $addition_data, $battle->round_count, $data_to_user);
                    }
                }
            break;

            case 'cursedWantToChangeTurn':
                $player = ($users_data['p1']['login'] == $msg->user)? 'p1': 'p2';

                $user_turn_id = $users_data[$player]['id'];

                $turn_expire = $msg->time;// - $users_data[$player]['time_shift'];
                if($turn_expire > $timing_settings['max_step_time']){
                    $turn_expire = $timing_settings['max_step_time'];
                }

                $battle->user_id_turn = $user_turn_id;
                $battle->turn_expire = $turn_expire+time();
                $battle->save();

                $battle_field = unserialize($battle->battle_field);
                $magic_usage = unserialize($battle->magic_usage);

                $addition_data = [];

                \DB::table('tbl_battle_members')
                    ->where('id', '=', $users_data[$msg->ident->userId]['battle_member_id'])
                    ->update([
                        'addition_data' => serialize([]),
                        'round_passed'  => '0',
                        'turn_expire'   => $turn_expire
                    ]);
                //$showTimerOfUser = $users_data[$user_turn_id]['player'];

                self::sendUserMadeActionData($this->step_status, $msg, $SplBattleObj, $from, $battle_field, $magic_usage, $users_data, $msg->user, $addition_data, $battle->round_count, '', $users_data[$user_turn_id]['player']);
            break;

            case 'userGivesUp':
                $battle->fight_status = 3;
                $battle->save();
                $game_result = 'Игру выграл '.$users_data['opponent']['login'];
                $winner = $users_data['opponent']['id'];
                $to_self = self::saveGameResults($users_data['opponent']['id'], $battle, 'win');
                $to_enemy = self::saveGameResults($users_data['user']['id'], $battle, 'loose');

                $result = ['message' => 'gameEnds', 'gameResult' => $game_result, 'battleInfo' => $msg->ident->battleId];

                if( ($winner == '') || ($winner == $msg->ident->userId) ){
                    $result['resources'] = $to_self;
                    self::sendMessageToSelf($from, $result);
                    $result['resources'] = $to_enemy;
                    self::sendMessageToOthers($from, $result, $this->battles[$msg->ident->battleId]);
                }else{
                    $result['resources'] = $to_enemy;
                    self::sendMessageToSelf($from, $result);
                    $result['resources'] = $to_self;
                    self::sendMessageToOthers($from, $result, $this->battles[$msg->ident->battleId]);
                }
            break;
        }
	}

	protected static function calcStrByPlayers($battle_field){
		$total_str = ['p1'=> 0, 'p2'=> 0];

		foreach($battle_field as $player => $rows){
			if($player != 'mid'){
				foreach($rows as $row => $cards){
					foreach($cards['warrior'] as $card_iter => $card_data){
						$total_str[$player] += $card_data['strength'];
					}
				}
			}
		}
		return $total_str;
	}

	protected static function clearBattleField($battle, $battle_field, $users_data, $magic_usage, $gain_cards_count, $step_status){
		$deadless_cards = unserialize($battle->undead_cards);

		//Добавление по карте из колоды каждому игроку
        $gain_cards_data = self::userGainCards($users_data['user'], $gain_cards_count['user'], $step_status);
		$users_data['user'] = $gain_cards_data['array'];
        $step_status = $gain_cards_data['step_status'];

        $gain_cards_data = self::userGainCards($users_data['opponent'], $gain_cards_count['opponent'], $step_status);
		$users_data['opponent'] = $gain_cards_data['array'];
        $step_status = $gain_cards_data['step_status'];

		//Очищение поля битвы от карт
		foreach($battle_field as $player => $rows){
			if($player != 'mid'){
				$card_to_left = [];
				//Просчет рассовой способности монстров
				if($users_data[$player]['current_deck'] == 'monsters'){
					$card_to_left = self::cardsToLeft($battle_field, $player);
				}

				foreach($rows as $row => $cards){
					if($battle_field[$player][$row]['special'] != ''){
						$users_data[$player]['discard'][] = $battle_field[$player][$row]['special']['card'];
						$battle_field[$player][$row]['special'] = '';
					}

					//Заносим карты воинов в отбой
					foreach($cards['warrior'] as $card_iter => $card_data){
						$card_is_deadless = false;
						foreach($card_data['card']['actions'] as $action_iter => $action){
							if($action->action == '2'){
								$card_is_deadless = true;
							}
						}

						if($card_is_deadless){
							//Если действие "Бессмертный" была использована в прошлом раунде
							if( (isset($deadless_cards[$player][$battle->round_count -1])) && (in_array(Crypt::decrypt($card_data['card']['id']), $deadless_cards[$player][$battle->round_count -1])) ){
								$users_data[$player]['discard'][] = $card_data['card'];
								unset($battle_field[$player][$row]['warrior'][$card_iter]);
							}else{
								$deadless_cards[$player][$battle->round_count][] = Crypt::decrypt($card_data['card']['id']);
							}
						}else{
							$users_data[$player]['discard'][] = $card_data['card'];
							unset($battle_field[$player][$row]['warrior'][$card_iter]);
						}
					}
					$battle_field[$player][$row]['warrior'] = array_values($battle_field[$player][$row]['warrior']);
				}

				if(!empty($card_to_left)){
					foreach($card_to_left as $key => $value){
						$destination = explode('_',$key);
						$battle_field[$destination[0]][$destination[1]]['warrior'][] = [
							'card'		=> $value,
							'strength'	=> $value['strength'],
							'login'		=> $users_data[$destination[0]]['login']
						];
						foreach($users_data[$destination[0]]['discard'] as $discard_iter => $discard_card){
							if($discard_card['id'] == $value['id']){
								unset($users_data[$destination[0]]['discard'][$discard_iter]);
								$users_data[$destination[0]]['discard'] = array_values($users_data[$destination[0]]['discard']);
							}
						}
					}
				}
			}else{
				foreach($battle_field[$player] as $card_iter => $card_data){
					if($card_data['login'] == $users_data['user']['login']){
						$users_data['user']['discard'][] = $battle_field['mid'][$card_iter]['card'];
					}else{
						$users_data['opponent']['discard'][] = $battle_field['mid'][$card_iter]['card'];
					}
				}
			}
		}

		$battle_field['mid'] = [];

		$battle_field = self::recalculateCardsStrength($battle, $battle_field, $users_data, $magic_usage);
		return [
			'battle_field'	=> $battle_field,
			'users_data'	=> $users_data,
			'deadless_cards'=> $deadless_cards,
			'magic_usage'	=> $magic_usage,
            'step_status'   => $step_status
		];
	}


	protected static function waitForRoundEnds($_this, $conn){
	    $battle = Battle::find($_this->battle_id);

		$round_status = unserialize($battle->round_status);
		if( (count($round_status['p1']) == 2) || (count($round_status['p2']) == 2) ){
			//Конец игры; Подсчет результатов боя; Запись рейтингов
			$battle->fight_status = 3;
			$battle->save();

			if( count($round_status['p1']) > count($round_status['p2']) ){
				self::saveGameResults($_this->users_data['p1']['id'], $battle, 'win');
				self::saveGameResults($_this->users_data['p2']['id'], $battle, 'loose');
			}

			if( count($round_status['p1']) < count($round_status['p2']) ){
				self::saveGameResults($_this->users_data['p2']['id'], $battle, 'win');
				self::saveGameResults($_this->users_data['p1']['id'], $battle, 'loose');
			}

			if( count($round_status['p1']) == count($round_status['p2']) ){
				if( ( ($_this->users_data['user']['current_deck'] == 'undead') || ($_this->users_data['opponent']['current_deck'] == 'undead') ) && ($_this->users_data['user']['current_deck'] != $_this->users_data['opponent']['current_deck']) ){
					if($_this->users_data['user']['current_deck'] == 'undead'){
						self::saveGameResults($_this->users_data['user']['id'], $battle, 'win');
						self::saveGameResults($_this->users_data['opponent']['id'], $battle, 'loose');
					}else{
						self::saveGameResults($_this->users_data['opponent']['id'], $battle, 'win');
						self::saveGameResults($_this->users_data['user']['id'], $battle, 'loose');
					}
				}else{
					self::saveGameResults($_this->users_data['user']['id'], $battle, 'draw');
					self::saveGameResults($_this->users_data['opponent']['id'], $battle, 'draw');
				}
			}
			//Закрытие соккета
			$_this->clients->detach($conn);
		}else{
			if(time() < $battle->turn_expire){
				sleep(2);
				if($battle->disconected_count == 2){
					self::waitForRoundEnds($_this, $conn);
				}
			}else{
				$timing_settings = SiteGameController::getTimingSettings();
				$current_turn_member = \DB::table('tbl_battle_members')->select('battle_id','user_id','turn_expire')
					->where('battle_id','=',$_this->battle_id)
					->where('user_id','=',$battle->user_id_turn)
					->first();
				if($current_turn_member != false){
					\DB::table('tbl_battle_members')
						->where('battle_id','=',$_this->battle_id)
						->where('user_id','=',$battle->user_id_turn)
						->update(['round_passed' => 1]);
				}

				if($_this->users_data['user']['id'] == $battle->first_turn_user_id){
					$user_turn_id = $_this->users_data['opponent']['id'];
				}else{
					$user_turn_id = $_this->users_data['user']['id'];
				}

				$next_turn_member = \DB::table('tbl_battle_members')
					->select('battle_id','user_id','turn_expire')
					->where('battle_id','=',$_this->battle_id)
					->where('user_id','=', $user_turn_id)
					->count();

				if($next_turn_member > 0){
					$turn_expire = $current_turn_member->turn_expire;
				}else{
					$turn_expire = 0;
				}

				$turn_expire = $turn_expire + $timing_settings['first_step_r'.$battle->round_count] + time();
				if($turn_expire > $timing_settings['max_step_time']){
					$turn_expire = $timing_settings['max_step_time'];
				}

				$battle->user_id_turn = $user_turn_id;
				$battle->first_turn_user_id =$user_turn_id;
				$battle->turn_expire = $turn_expire;
				$battle->pass_count = $battle->pass_count +1;

				if($battle->pass_count > 1) {
					$battle_field = unserialize($battle->battle_field);

					$battle_field = self::recalculateCardsStrength($battle, $battle_field, $_this->users_data, $_this->magic_usage);
					//Подсчет результатп раунда по очкам
					$total_str = self::calcStrByPlayers($battle_field);
					//Статус битвы (очки раундов)
					$round_status = unserialize($battle->round_status);


					//Результаты раунда отдельно по игрокам
					$user_points = $total_str[$_this->users_data['user']['player']];
					$opponent_points = $total_str[$_this->users_data['opponent']['player']];

					$gain_cards_count = ['user' => 1, 'opponent' => 1];//Количество дополнительных карт
					//Определение выигравшего
					if($user_points > $opponent_points){
						$round_status[$_this->users_data['user']['player']][] = 1;
						if($_this->users_data['opponent']['current_deck'] == 'knight') $gain_cards_count['opponent'] = 2;
					}
					if($user_points < $opponent_points){
						$round_status[$_this->users_data['opponent']['player']][] = 1;
						if($_this->users_data['user']['current_deck'] == 'knight') $gain_cards_count['user'] = 2;
					}
					if($user_points == $opponent_points){
						//Если колода пользователя - нечисть и противник не играет нечистью
						if( ( ($_this->users_data['user']['current_deck'] == 'undead') || ($_this->users_data['opponent']['current_deck'] == 'undead') ) && ($_this->users_data['user']['current_deck'] != $_this->users_data['opponent']['current_deck']) ){
							if($_this->users_data['user']['current_deck'] == 'undead'){
								$round_status[$_this->users_data['user']['player']][] = 1;
								if($_this->users_data['opponent']['current_deck'] == 'knight') $gain_cards_cout['opponent'] = 2;
							}else{
								$round_status[$_this->users_data['opponent']['player']][] = 1;
								if($_this->users_data['user']['current_deck'] == 'knight') $gain_cards_count['user'] = 2;
							}
						}else{
							$round_status[$_this->users_data['user']['player']][] = 1;
							$round_status[$_this->users_data['opponent']['player']][] = 1;
						}
					}

					$clear_result = self::clearBattleField($battle, $battle_field, $_this->users_data, $_this->magic_usage, $gain_cards_count, $_this->step_status, $_this->step_status);
					$battle_field = $clear_result['battle_field'];
					$_this->users_data	= $clear_result['users_data'];
					$_this->magic_usage	= $clear_result['magic_usage'];
                    $_this->step_status = $clear_result['step_status'];

					$battle->battle_field	= serialize($battle_field);
					$battle->magic_usage	= serialize($_this->magic_usage);
					$battle->undead_cards	= serialize($clear_result['deadless_cards']);
					$battle->pass_count		= 0;
					$battle->round_status	= serialize($round_status);
					$battle->round_count	= $battle->round_count +1;
				}

				$battle->save();
				if($battle->disconected_count == 2){
					self::waitForRoundEnds($_this, $conn);
				}else{
					$_this->clients->detach($conn);
					$_this->clients->attach($conn); //Добавление клиента
					echo 'New connection ('.$conn->resourceId.')'."\n\r";
				}
			}
		}
	}

	protected static function userGainCards($array, $card_to_gain=1, $step_status){
		if(count($array['deck']) < $card_to_gain) $card_to_gain = count($array['deck']);
		for($i=0; $i<$card_to_gain; $i++) {
			if (!empty($array['deck'])) {
				$rand = mt_rand(0, count($array['deck']) - 1);
				$array['hand'][] = $array['deck'][$rand];
                $step_status['added_cards'][$array['player']]['hand'][] = $array['deck'][$rand];
				unset($array['deck'][$rand]);
				$array['deck'] = array_values($array['deck']);
			}
		}
		return [
		    'array' => $array,
            'step_status' => $step_status
        ];
	}

	protected static function cardsToLeft($battle_field, $player){
		$rows_to_card_left = [];
		$cards_to_left = [];
		foreach($battle_field[$player] as $row => $row_data){
			if(!empty($row_data['warrior'])){
				$rows_to_card_left[] = $row;
			}
		}
		foreach($rows_to_card_left as $row){
			foreach($battle_field[$player][$row]['warrior'] as $card_iter => $card_data){
				$allow_to_count = true;
				foreach($card_data['card']['actions'] as $action_iter => $action){
					if($action->action == '2'){
						$allow_to_count = false;
					}
				}
				if($allow_to_count){
					$cards_to_left[] = [$player.'_'.$row => $card_data['card']];
				}
			}
		}

		$card_to_left = (!empty($cards_to_left))? $cards_to_left[mt_rand(0, count($cards_to_left)-1)]: [];

		return $card_to_left;
	}

	public static function actionProcessing($input_action, $battle_field, $users_data, $addition_data, $user_turn_id, $user_turn, $msg, $magic_usage, $step_status){
	    $step_status['actions'][] = $input_action->action;
		switch($input_action->action){
			//БKЛОКИРОВКА МАГИИ
			case '1':
				$magic_usage[$users_data['opponent']['player']][0] = ['id' => $msg->magic, 'allow'=>'0'];
				$magic_usage[$users_data['opponent']['player']][1] = ['id' => $msg->magic, 'allow'=>'0'];
				$magic_usage[$users_data['opponent']['player']][2] = ['id' => $msg->magic, 'allow'=>'0'];
			break;
			//END БЛОКИРОВКА МАГИИ
			//ИСЦЕЛЕНИЕ
			case '6':
			    foreach($battle_field['mid'] as $card_iter => $card_data){
			        $user_type = ($users_data['user']['login'] == $card_data['login'])? 'user': 'opponent';
			        $users_data[$user_type]['discard'][] = $card_data['card'];
                    $step_status['dropped_cards'][$user_type]['mid'][] = $card_data['card'];
                }
				$battle_field['mid'] = [];
			break;
			//END OF ИСЦЕЛЕНИЕ
			//ЛЕКАРЬ
			case '7':
				$action_data = [
					'deckChoise'	=> $input_action->healer_deckChoise,
					'typeOfCard'	=> $input_action->healer_typeOfCard,
					'cardChoise'	=> $input_action->healer_cardChoise,
					'ignoreImmunity'=> $input_action->healer_ignoreImmunity
				];
				if(isset($input_action->healer_type_singleCard))$action_data['type_singleCard'] = $input_action->healer_type_singleCard;
				if(isset($input_action->healer_type_actionRow))	$action_data['type_actionRow'] = $input_action->healer_type_actionRow;
				if(isset($input_action->healer_type_cardType))	$action_data['type_cardType'] = $input_action->healer_type_cardType;
				if(isset($input_action->healer_type_group))		$action_data['type_group'] = $input_action->healer_type_group;

				$heal_result = self::makeHealOrSummon($users_data, $action_data, 'discard', $user_turn_id, $user_turn);
//card activates after user action
				$users_data		= $heal_result['users_data'];
				$user_turn_id	= $heal_result['user_turn_id'];
				$user_turn		= $heal_result['user_turn'];
				$addition_data	= $heal_result['addition_data'];
			break;
			//END OF ЛЕКАРЬ
			//ОДУРМАНИВАНИЕ
			case '9':
				$cards_can_be_obscured = [];
				$min_strength = 999;
				$max_strength = 0;

				foreach($input_action->obscure_ActionRow as $row_iter => $row){
					foreach($battle_field[$users_data['opponent']['player']][$row]['warrior'] as $card_iter => $card_data){
						if($card_data['strength'] <= $input_action->obscure_maxCardStrength){
							$allow_obscure = self::checkForSimpleImmune($input_action->obscure_ignoreImmunity, $card_data['card']['actions']);

							if($allow_obscure){
								$max_strength = ($card_data['strength'] > $max_strength)
									? $card_data['strength']
									: $max_strength;
								$min_strength = ($card_data['strength'] < $min_strength)
									? $card_data['strength']
									: $min_strength;

								$cards_can_be_obscured[] = [
									'card'=>$card_data['card'],
									'strength' => $card_data['strength'],
									'row'=>$row
								];
							}
						}
					}
				}

				if($min_strength < 1) $min_strength = 1;

				if(!empty($cards_can_be_obscured)){
				switch($input_action->obscure_strenghtOfCard){
						case '0': $card_strength_to_obscure = $min_strength; break;//Самую слабую
						case '1': $card_strength_to_obscure = $max_strength; break;//Самую сильную
						case '2':
							$random = mt_rand(0, count($cards_can_be_obscured)-1);
							$card_strength_to_obscure = $cards_can_be_obscured[$random]['strength'];
							break;
					}
				}

				$cards_to_obscure = [];
				if(!empty($cards_can_be_obscured)){
					for($i=0; $i<$input_action->obscure_quantityOfCardToObscure; $i++){
						for($j=0; $j<count($cards_can_be_obscured); $j++){
							if($card_strength_to_obscure == $cards_can_be_obscured[$j]['strength']){
								$cards_to_obscure[] = $cards_can_be_obscured[$j];
								break;
							}
						}
					}
				}
				for($i=0; $i<count($cards_to_obscure); $i++){
					foreach($battle_field[$users_data['opponent']['player']][$cards_to_obscure[$i]['row']]['warrior'] as $j => $card_data){
						if(Crypt::decrypt($cards_to_obscure[$i]['card']['id']) == Crypt::decrypt($card_data['card']['id'])){
							$battle_field[$users_data['user']['player']][$cards_to_obscure[$i]['row']]['warrior'][] = [
								'card'		=> $card_data['card'],
								'strength'	=> $card_data['card']['strength'],
								'login'		=> $users_data['user']['login']
							];
                            $step_status['added_cards'][$users_data['opponent']['player']][$cards_to_obscure[$i]['row']][] = [
                                'card'		=> $card_data['card'],
                                'strength'	=> $card_data['card']['strength']
                            ];

							unset($battle_field[$users_data['opponent']['player']][$cards_to_obscure[$i]['row']]['warrior'][$j]);
							$battle_field[$users_data['opponent']['player']][$cards_to_obscure[$i]['row']]['warrior'] = array_values($battle_field[$users_data['opponent']['player']][$cards_to_obscure[$i]['row']]['warrior']);
							break;
						}
					}
				}
			break;
			//END OF ОДУРМАНИВАНИЕ
			//ПЕРЕГРУППИРОВКА
			case '10':
				foreach($battle_field[$users_data['user']['player']] as $row => $row_data){
					foreach($row_data['warrior'] as $card_iter =>$card_data){
						$allow_to_regroup = true;
						if($input_action->regroup_ignoreImmunity == 0){
							foreach($card_data['card']['actions'] as $action_iter => $action){
								if($action->action == '5'){
									if($action->immumity_type == 1){
										$allow_to_regroup = false;
									}
								}
							}
						}
						if($allow_to_regroup){
							$users_data['user']['cards_to_play'][] = $card_data['card'];
						}
					}
				}
				//card activates after user action
				if(count($users_data['user']['cards_to_play']) > 0){
					$user_turn_id	= $users_data['user']['id'];
					$user_turn		= $users_data['user']['login'];
					$addition_data	= ['action' => 'activate_regroup'];
				}
			break;
			//END OF ПЕРЕГРУППИРОВКА
			//ПЕЧАЛЬ
			case '11':
				$players = ($input_action->sorrow_actionTeamate == 0)? [$users_data['opponent']['player']]: ['p1', 'p2'];
				$row = self::strRowToInt($msg->BFData->row);

				foreach($players as $player_iter => $player){
					foreach($magic_usage[$player] as $activated_in_round => $magic_id){
						if($magic_id != '0'){
							$magic = json_decode(SiteGameController::getMagicData($magic_id['id']));//Данные о МЭ
							foreach($magic->actions as $action_iter => $action_data){
								if($action_data->action == '4'){
									$magic_usage[$player][$activated_in_round]['allow'] = 0;
								}
							}
						}
					}
				}

				foreach($players as $player_iter => $player){
				    $users_data[$player]['discard'][] = $battle_field[$player][$row]['special']['card'];
				    $step_status['dropped_cards'][$player][$row][] = $battle_field[$player][$row]['special']['card'];
					$battle_field[$player][$row]['special'] = '';
				}
			break;
			//END OF ПЕЧАЛЬ
			//ПОВЕЛИТЕЛЬ
			case '12':
				$addition_data = [];
				$cards_can_be_added = [];

				foreach($input_action->master_cardSource as $destination_iter => $destination){
					foreach($users_data['user'][$destination] as $card_iter => $card_data){
						if(!empty($card_data['groups'])){
							if(!empty(array_intersect($input_action->master_group, $card_data['groups']))){
								if($card_data['strength'] <= $input_action->master_maxCardsStrenght){
									$cards_can_be_added[] = [
										'card_id'	=> Crypt::decrypt($card_data['id']),
										'strength'	=> $card_data['strength'],
										'source_deck'=> $destination
									];
								}
							}
						}
					}
				}
				switch($input_action->master_summonByModificator){
					case '0': usort($cards_can_be_added, function($a, $b){return ($a['strength'] - $b['strength']);}); break;
					case '1': usort($cards_can_be_added, function($a, $b){return ($b['strength'] - $a['strength']);});break;
					case '2':
						$cards_shuffle_keys = array_keys($cards_can_be_added);
						shuffle($cards_shuffle_keys);
						array_merge( array_flip($cards_shuffle_keys), $cards_can_be_added);
					break;
				}

				$cards_to_add = ['hand'=> [], 'deck'=>[], 'discard'=>[]];
				$n = (count($cards_can_be_added) < $input_action->master_maxCardsSummon)? count($cards_can_be_added): $input_action->master_maxCardsSummon;
				for($i=0; $i<$n; $i++){
					$cards_to_add[$cards_can_be_added[$i]['source_deck']][] = $cards_can_be_added[$i]['card_id'];
				}

				if($n > 1){
					foreach($cards_to_add as $destination => $cards){
						if(!empty($cards)){
							foreach($users_data['user'][$destination] as $card_to_summon_iter => $card_to_summon){
								if(in_array(Crypt::decrypt($card_to_summon['id']), $cards)){
									if(count($card_to_summon['action_row']) > 1){
										$rand = mt_rand(0, count($card_to_summon['action_row'])-1);
										$action_row = $card_to_summon['action_row'][$rand];
									}else{
										$action_row = $card_to_summon['action_row'][0];
									}
									//Move card to battle_field
									$battle_field[$users_data['user']['player']][$action_row]['warrior'][] = [
										'card'		=> $card_to_summon,
										'strength'	=> $card_to_summon['strength'],
										'login'		=> $users_data['user']['login']
									];
									$step_status['added_cards'][$users_data['user']['player']][$action_row][] = [
                                        'card'		=> $card_to_summon,
                                        'strength'	=> $card_to_summon['strength'],
                                        'destination' => $destination
                                    ];

									unset($users_data['user'][$destination][$card_to_summon_iter]);
								}
							}
							$users_data['user'][$destination] = array_values($users_data['user'][$destination]);
						}
					}
				}else{
					foreach($cards_to_add as $destination => $cards) {
						if (!empty($cards)) {
							foreach($users_data['user'][$destination] as $card_to_summon_iter => $card_to_summon){
								if(Crypt::decrypt($card_to_summon['id']) == $cards[0]){
                                    //card activates after user action
									$users_data['user']['cards_to_play'][] = $card_to_summon;
									$user_turn_id = $users_data['user']['id'];
									$user_turn = $users_data['user']['login'];
									$addition_data = ['action' => 'activate_choise'];
									unset($users_data['user'][$destination][$card_to_summon_iter]);
									break;
								}
							}
							$users_data['user'][$destination] = array_values($users_data['user'][$destination]);
						}
					}
				}
			break;
			//END OF ПОВЕЛИТЕЛЬ
			//ПРОСМОТР КАРТ ПРОТИВНИКА
			case '14':
				$temp_hand = $users_data['opponent']['hand'];
				$n = (count($users_data['opponent']['hand']) < $input_action->overview_cardCount)? count($users_data['opponent']['hand']): $input_action->overview_cardCount;
				while(count($users_data['user']['cards_to_play']) < $n){
					$rand = mt_rand(0, count($temp_hand)-1);
					$temp_card = $temp_hand[$rand];
					$users_data['user']['cards_to_play'][] = $temp_card;
					unset($temp_hand[$rand]);
					$temp_hand = array_values($temp_hand);
				}
				$addition_data['action'] = 'activate_view';
			break;
			//ПРИЗЫВ
			case '15':
				$action_data = [
					'deckChoise'	=> $input_action->summon_deckChoise,
					'typeOfCard'	=> $input_action->summon_typeOfCard,
					'cardChoise'	=> $input_action->summon_cardChoise,
					'ignoreImmunity'=> $input_action->summon_ignoreImmunity
				];
				if(isset($input_action->summon_type_singleCard))$action_data['type_singleCard'] = $input_action->summon_type_singleCard;
				if(isset($input_action->summon_type_actionRow))	$action_data['type_actionRow'] = $input_action->summon_type_actionRow;
				if(isset($input_action->summon_type_cardType))	$action_data['type_cardType'] = $input_action->summon_type_cardType;
				if(isset($input_action->summon_type_group))		$action_data['type_group'] = $input_action->summon_type_group;

				$summon_result = self::makeHealOrSummon($users_data, $action_data, 'deck', $user_turn_id, $user_turn);
//card activates after user action
				$users_data		= $summon_result['users_data'];
				$user_turn_id	= $summon_result['user_turn_id'];
				$user_turn		= $summon_result['user_turn'];
				$addition_data	= $summon_result['addition_data'];
			break;
			//CБРОС КАРТ ПРОТИВНИКА В ОТБОЙ
			case '17':
				for($i=0; $i < $input_action->enemyDropHand_cardCount; $i++){
					$rand = mt_rand(0, count($users_data['opponent']['hand'])-1);
					$users_data['opponent']['discard'][] = $users_data['opponent']['hand'][$rand];
					unset($users_data['opponent']['hand'][$rand]);
					$users_data['opponent']['hand'] = array_values($users_data['opponent']['hand']);
				}
			break;
			//УБИЙЦА
			case '19':
				//Может ли бить своих
				$players = ( (isset($input_action->killer_atackTeamate)) && ($input_action->killer_atackTeamate == 1) )? $players = ['p1', 'p2'] : [$users_data['opponent']['player']];
				//наносит удат по группе
				$groups = $input_action->killer_group;

				$strenght_limit_to_kill = ($input_action->killer_enemyStrenghtLimitToKill < 1) ? 999: $input_action->killer_enemyStrenghtLimitToKill;

				$rows_strength = []; //Сумарная сила выбраных рядов
				$max_strenght = 0;  // максимальная сила карты
				$min_strenght = 999;// минимальная сила карты
				$card_strength_set = []; //набор силы карты для выбора случйного значения силы

				$cards_to_destroy = [];

				foreach($players as $player_iter => $player){
					foreach($input_action->killer_ActionRow as $row_iter => $row){
						//Для каждого ряда
						foreach($battle_field[$player][$row]['warrior'] as $card_iter => $card_data){
							//Сила выбраных рядов
							if(isset($rows_strength[$player][$row])){
								$rows_strength[$player][$row] += $card_data['strength'];
							}else{
								$rows_strength[$player][$row] = $card_data['strength'];
							}

							if(!empty($groups)){
								foreach($card_data['card']['groups'] as $groups_ident => $group_id){
									if(in_array($group_id, $groups)){
										$cards_to_destroy[$player][$row][] = [
											'id' => $card_data['card']['id'],
											'strength' => $card_data['strength']
										];

										if($card_data['strength'] < $strenght_limit_to_kill){
											if($player == $users_data['opponent']['player']){
												$max_strenght = ($max_strenght < $card_data['strength'])
													? $card_data['strength']
													: $max_strenght;// максимальная сила карты
												$min_strenght = ($min_strenght > $card_data['strength'])
													? $card_data['strength']
													: $min_strenght;// минимальная сила карты
												$card_strength_set[] = $card_data['strength'];
											}
										}
									}
								}
							}else{
								$allow_by_immune = self::checkForSimpleImmune($input_action->killer_ignoreKillImmunity, $card_data['card']['actions']);
								if($allow_by_immune){
									$cards_to_destroy[$player][$row][] = [
										'id' => $card_data['card']['id'],
										'strength' => $card_data['strength']
									];

									if($card_data['strength'] < $strenght_limit_to_kill){
										$max_strenght = ($max_strenght < $card_data['strength'])
											? $card_data['strength']
											: $max_strenght;// максимальная сила карты
										$min_strenght = ($min_strenght > $card_data['strength'])
											? $card_data['strength']
											: $min_strenght;// минимальная сила карты
										$card_strength_set[] = $card_data['strength'];
									}
								}
							}
						}
					}
				}

				switch($input_action->killer_killedQuality_Selector){
					case '0':	$card_strength_to_kill = $min_strenght; break;//Самую слабую
					case '1':	$card_strength_to_kill = $max_strenght; break;//Самую сильную
					case '2':	$random = mt_rand(0, count($card_strength_set)-1);
								$card_strength_to_kill = $card_strength_set[$random];
								break; //Самую Случайную
				}

				$card_to_kill = [];

				foreach($cards_to_destroy as $player => $rows){
					foreach($rows as $row => $cards){
						foreach($cards as $card_iter => $card_data){
							$allow_to_kill_by_force_amount = true;
							//Нужное для совершения убийства количество силы в ряду
							if($input_action->killer_recomendedTeamateForceAmount_OnOff != 0){//Если не выкл
								$row_summ = 0;
								foreach($input_action->killer_recomendedTeamateForceAmount_ActionRow as $i => $row_to_calculate){
									if(isset($rows_strength[$player][$row_to_calculate])){
										$row_summ += $rows_strength[$player][$row_to_calculate];
									}
								}
								switch($input_action->killer_recomendedTeamateForceAmount_Selector){
									case '0':	//Больше указаного значения
										$allow_to_kill_by_force_amount = ($input_action->killer_recomendedTeamateForceAmount_OnOff <= $row_summ) ? true : false; break;
									case '1':	//Меньше указанного значения
										$allow_to_kill_by_force_amount = ($input_action->killer_recomendedTeamateForceAmount_OnOff >= $row_summ) ? true : false; break;
									case '2':	//Равно указанному значению
										$allow_to_kill_by_force_amount = ($input_action->killer_recomendedTeamateForceAmount_OnOff == $row_summ) ? true : false; break;
								}
							}
							if( ($card_data['strength'] == $card_strength_to_kill) && ($allow_to_kill_by_force_amount) ){
								$card_to_kill[$player][$row][] = $card_data;
							}
						}
					}
				}

				foreach($card_to_kill as $player => $rows){
					foreach($rows as $row => $cards){
						foreach($cards as $card_to_kill_iter => $card_to_kill_data){
							foreach($battle_field[$player][$row]['warrior'] as $card_iter => $card_data){
								if($card_to_kill_data['id'] == $card_data['card']['id']){
									$users_data[$player]['discard'][] = $card_data['card'];
                                    $step_status['dropped_cards'][$player][$row][] = $card_data['card'];

									unset($battle_field[$player][$row]['warrior'][$card_iter]);
									$battle_field[$player][$row]['warrior'] = array_values($battle_field[$player][$row]['warrior']);

									if($input_action->killer_killAllOrSingle == 0){
										break 4;
									}
								}
							}
						}
					}
				}
			break;
			//ШПИЙОН
			case '20':
				$deck_card_count = count($users_data['user']['deck']);

                $step_status['played_card']['move_to']['player'] = ($step_status['played_card']['move_to']['player'] == 'p1')? 'p2' :'p1';
				$n = ($deck_card_count >= $input_action->spy_getCardsCount) ? $input_action->spy_getCardsCount : $deck_card_count;
				for($i=0; $i<$n; $i++){
					$rand_item = mt_rand(0, $deck_card_count-1);
					$random_card = $users_data['user']['deck'][$rand_item];
					$users_data['user']['hand'][] = $random_card;

                    $step_status['added_cards'][$users_data['user']['player']]['hand'][] = $random_card;

					unset($users_data['user']['deck'][$rand_item]);

					$users_data['user']['deck'] = array_values($users_data['user']['deck']);
					$deck_card_count = count($users_data['user']['deck']);
				}
			break;
		}

		return [
			'battle_field'	=> $battle_field,
			'users_data'	=> $users_data,
			'addition_data'	=> $addition_data,
			'user_turn_id'	=> $user_turn_id,
			'user_turn'		=> $user_turn,
			'magic_usage'	=> $magic_usage,
            'step_status'   => $step_status
		];
	}


	protected static function makeHealOrSummon($users_data, $input_action, $deck, $user_turn_id, $user_turn){
		$users_data['user']['card_source'] = $deck;
		if($input_action['deckChoise'] == 1){
			$users_data['user']['player_source'] = $users_data['opponent']['player'];
			$user = 'opponent';
		}else{
			$user = 'user';
		}
		$addition_data = ['action' => 'activate_choise'];

		$cards_to_play = [];
		switch($input_action['typeOfCard']){
			case '0':
				foreach($users_data[$user][$deck] as $card_iter => $card_data){
					if(in_array(Crypt::decrypt($card_data['id']), $input_action['type_singleCard'])){
						$allow_to_summon = ($user == 'user')
							? self::checkForFullImmune($input_action['ignoreImmunity'], $card_data['actions'])
							: self::checkForSimpleImmune($input_action['ignoreImmunity'], $card_data['actions']);

						if($allow_to_summon){
							$cards_to_play[] = Crypt::decrypt($card_data['id']);
						}
					}
				}
			break;
			case '1':
				foreach($users_data[$user][$deck] as $card_iter => $card_data){
					foreach($card_data['action_row'] as $row_iter => $card_row){
						if( (in_array($card_row, $input_action['type_actionRow'])) && ($card_data['type'] == 'race') ){
							$allow_to_summon = ($user == 'user')
								? self::checkForFullImmune($input_action['ignoreImmunity'], $card_data['actions'])
								: self::checkForSimpleImmune($input_action['ignoreImmunity'], $card_data['actions']);

							if($allow_to_summon){
								$cards_to_play[] = Crypt::decrypt($card_data['id']);
							}
						}
					}
				}
			break;
			case '2':
				foreach($users_data[$user][$deck] as $card_iter => $card_data){
					$summon_card_type = ($input_action['type_cardType'] == 0)
						? ['special']
						: ['race', 'neutrall'];

					if(in_array($card_data['type'], $summon_card_type)){
						$allow_to_summon = ($user == 'user')
							? self::checkForFullImmune($input_action['ignoreImmunity'], $card_data['actions'])
							: self::checkForSimpleImmune($input_action['ignoreImmunity'], $card_data['actions']);

						if($allow_to_summon){
							$cards_to_play[] = Crypt::decrypt($card_data['id']);
						}
					}
				}
			break;
			case '3':
				foreach($users_data[$user][$deck] as $card_iter => $card_data){
					foreach($card_data['groups'] as $group_iter => $group_id){
						$allow_by_group = false;
						if(in_array($group_id, $input_action['type_group'])){
							$allow_by_group = true;
						}
						$allow_to_summon = ($user == 'user')
							? self::checkForFullImmune($input_action['ignoreImmunity'], $card_data['actions'])
							: self::checkForSimpleImmune($input_action['ignoreImmunity'], $card_data['actions']);

						if( ($allow_to_summon) && ($allow_by_group) ){
							$cards_to_play[] = Crypt::decrypt($card_data['id']);
						}
					}
				}
			break;
			case '4':
				foreach($users_data[$user][$deck] as $card_iter => $card_data){
					$allow_to_summon = ($user == 'user')
						? self::checkForFullImmune($input_action['ignoreImmunity'], $card_data['actions'])
						: self::checkForSimpleImmune($input_action['ignoreImmunity'], $card_data['actions']);
					if($allow_to_summon){
						$cards_to_play[] = Crypt::decrypt($card_data['id']);
					}
				}
			break;
		}
		$cards_to_play = array_values($cards_to_play);

		if($input_action['cardChoise'] == 1){
			$rand = mt_rand(0, count($cards_to_play)-1);
			$random_card = $cards_to_play[$rand];
			$cards_to_play = [];
			$cards_to_play[] = $random_card;
		}

		foreach($users_data[$user][$deck] as $card_iter => $card_data){
			if(in_array(Crypt::decrypt($card_data['id']), $cards_to_play)){
				$users_data['user']['cards_to_play'][] = $card_data;//Карты приходят в попап выбора карт
			}
		}

		if(count($users_data['user']['cards_to_play']) > 0){
			$user_turn_id = $users_data['user']['id'];
			$user_turn = $users_data['user']['login'];
		}else{
			$users_data['user']['card_source'] = 'hand';
			$users_data['user']['player_source'] = $users_data['user']['player'];
			$addition_data = [];
		}

		return [
			'users_data'	=> $users_data,
			'user_turn_id'	=> $user_turn_id,
			'user_turn'		=> $user_turn,
			'addition_data'	=> $addition_data
		];
	}


	protected static function resetBattleFieldCardsStrength($battle_field){
		foreach($battle_field as $field => $rows){
			if($field != 'mid'){
				foreach($rows as $row => $cards){
					foreach($cards['warrior'] as $i => $card_data){
						$battle_field[$field][$row]['warrior'][$i]['strength'] = $card_data['card']['strength'];
					}
				}
			}
		}
		return $battle_field;
	}


	protected static function recalculateCardsStrength($battle, $battle_field, $users_data, $magic_usage){
		$battle_field = self::resetBattleFieldCardsStrength($battle_field);//Сброс значений силы

		$actions_array_support = [];//Массив действий "Поддержка"
		$actions_array_fury = [];//Массив действий "Неистовство"
		$actions_array_fear = [];//Массив действий "Страшный"
		$actions_array_brotherhood = [];//Массив действий "Боевое братство"
		$actions_array_inspiration = [];//Массив действий "Воодушевление"

		foreach($battle_field as $field => $rows){
			if($field != 'mid'){
				foreach ($rows as $row => $cards){
					foreach($cards['warrior'] as $card_iter => $card_data){
						foreach($card_data['card']['actions'] as $action_iter => $action){
							switch($action->action){
								case '3':	$actions_array_brotherhood[$field][Crypt::decrypt($card_data['card']['id'])] = $card_data; break;
								case '4':	$actions_array_inspiration[$field][$row] = $card_data['card']; break;
								case '8':	$actions_array_fury[$field.'_'.$row.'_'.$card_iter] = $card_data; break;
								case '13':	$actions_array_support[$field.'_'.$row.'_'.$card_iter] = $card_data; break;
								case '18':	$actions_array_fear[$field][uniqid()] = $card_data; break;
							}
						}
					}
					if($cards['special'] != ''){
						foreach($cards['special']['card']['actions'] as $action_iter => $action){
							switch($action->action){
								case '4':	$actions_array_inspiration[$field][$row] = $cards['special']['card']; break;
							}
						}
					}
				}
			}else{
				foreach($rows as $card_data){
					foreach($card_data['card']['actions']as $action_iter => $action){
						if($action->action == '18'){
							$card_id = Crypt::decrypt($card_data['card']['id']);
							if(!isset($actions_array_fear['mid'][$card_id])){
								$actions_array_fear['mid'][$card_id] = $card_data;
							}
						}
					}
				}
			}
		}

		//Применение "Поддержка" к картам
		foreach($actions_array_support as $card_path => $action_card){
			$player = ($action_card['login'] == $users_data['user']['login'])? $users_data['user']['player']: $users_data['opponent']['player'];

			foreach($action_card['card']['actions'] as $action_iter => $action_data){
				if($action_data->action == '13'){
					$self_cast = ($action_data->support_selfCast == 0)? false: true;
					$groups = ( (isset($action_data->support_actionToGroupOrAll)) && ($action_data->support_actionToGroupOrAll != 0))? $action_data->support_actionToGroupOrAll: [];

					foreach($action_data->support_ActionRow as $row_iter => $row){
						foreach($battle_field[$player][$row]['warrior'] as $card_iter => $card_data){
							$allow_support = true;
							if($action_data->support_ignoreImmunity == 0){
								foreach($card_data['card']['actions'] as $i => $action){
									if($action->action == '5'){
										if($action->immumity_type == 1){
											$allow_support = false;
										}
									}
								}
							}

							if($allow_support){
								if(!empty($groups)){
									foreach($card_data['card']['groups'] as $groups_ident => $group_id){
										if(in_array($group_id, $groups)){
											if(($card_data['card']['id'] == $action_card['card']['id']) && ($card_path == $player.'_'.$row.'_'.$card_iter)){
												if($self_cast){
													$strength = $card_data['strength'] + $action_data->support_strenghtValue;
												}else{
													$strength = $card_data['strength'];
													$self_cast = true;
												}
											}else{
												$strength = $card_data['strength'] + $action_data->support_strenghtValue;
											}
											$battle_field[$player][$row]['warrior'][$card_iter]['strength'] = $strength;
										}
									}
								}else{
									if(($card_data['card']['id'] == $action_card['card']['id']) && ($card_path == $player.'_'.$row.'_'.$card_iter)){
										if($self_cast){
											$strength = $card_data['strength'] + $action_data->support_strenghtValue;
										}else{
											$strength = $card_data['strength'];
											$self_cast = true;
										}
									}else{
										$strength = $card_data['strength'] + $action_data->support_strenghtValue;
									}
									$battle_field[$player][$row]['warrior'][$card_iter]['strength'] = $strength;
								}
							}
						}
					}
				}
			}
		}

		//Применение МЭ "Поддержка" к картам
		foreach($magic_usage as $player => $magic_data){
			foreach($magic_data as $activated_in_round => $magic_id){
				if($activated_in_round == $battle->round_count){
					if($magic_id['allow'] != '0'){
						$magic = json_decode(SiteGameController::getMagicData($magic_id['id']));//Данные о МЭ
						foreach($magic->actions as $action_iter => $action_data){
							if($action_data->action == '13'){
								foreach($action_data->support_ActionRow as $row_iter => $row){//Ряды действия МЭ
									//Применение МЭ к картам
									foreach($battle_field[$player][$row]['warrior'] as $card_iter => $card){
										//Если у карты есть полный иммунитет
										$allow_magic = true;
										if($action_data->support_ignoreImmunity == 0){
											foreach($card['card']['actions'] as $j => $action){
												if($action->action == '5'){
													if($action->immumity_type == 1){
														$allow_magic = false;
													}
												}
											}
										}

										if($allow_magic){
											$battle_field[$player][$row]['warrior'][$card_iter]['strength'] = $card['strength'] + $action_data->support_strenghtValue;
										}
									}
								}
							}
						}
					}
				}
			}
		}

		//Применение "Неистовость" к картам
		foreach($actions_array_fury as $card_id => $card_data){
			$enemy_player = ($card_data['login'] == $users_data['user']['login'])? $users_data['opponent']['player']: $users_data['user']['player'];

			foreach($card_data['card']['actions'] as $action_iter => $action){
				if($action->action == '8'){
					$allow_fury_by_race = false;
					$allow_fury_by_row = false;
					$allow_fury_by_group = false;
					$allow_fury_by_magic = false;
					//Колода противника вызывает у карты неистовство
					if( (in_array($users_data[$enemy_player]['current_deck'], $action->fury_enemyRace)) ){
						$allow_fury_by_race = true;
					}
					//Количество воинов в ряду/рядах вызывает неистовство
					if((isset($action->fury_ActionRow)) && (!empty($action->fury_ActionRow))){
						$row_cards_count = 0;
						for($i=0; $i<count($action->fury_ActionRow); $i++){
							$row_cards_count += count($battle_field[$enemy_player][$action->fury_ActionRow[$i]]['warrior']);
						}

						$allow_fury_by_row = ($row_cards_count >= $action->fury_enemyWarriorsCount) ? true : false;
					}
					//Карта определнной группы вызывает неистовство
					if((isset($action->fury_group)) && (!empty($action->fury_group))) {
						$player = ($card_data['login'] == $users_data['user']['login'])
							? $users_data['opponent']['player']
							: $users_data['user']['player'];
						foreach($battle_field[$player] as $row){
							foreach($row['warrior'] as $card_iter => $card_data){
								if(!empty($card_data['card']['groups'])){
									foreach($card_data['card']['groups'] as $group){
										if(in_array($group, $action->fury_group)){
											$allow_fury_by_group = true;
										}
									}
								}
							}
						}
					}

					//Магия вызывает неистовство
					if( (isset($action->fury_abilityCastEnemy)) && ($action->fury_abilityCastEnemy == 1)){
						foreach($magic_usage[$enemy_player] as $activated_in_round => $magic_data){
							if($battle->round_count == $activated_in_round){
								$allow_fury_by_magic = true;
							}
						}
					}

					if(($allow_fury_by_row) || ($allow_fury_by_race) || ($allow_fury_by_magic) || ($allow_fury_by_group)){
						$card_destination = explode('_',$card_id);
						$battle_field[$card_destination[0]][$card_destination[1]]['warrior'][$card_destination[2]]['strength'] += $action->fury_strenghtVal;
					}
				}
			}
		}

		//Применение действия "Страшный" к картам
		foreach($actions_array_fear as $source => $cards){
			foreach($cards as $card_id => $card_data){
				foreach($card_data['card']['actions'] as $action_iter => $action){
					if($action->action == '18'){
						//Карта действует на всех или только на противника
						if($action->fear_actionTeamate == 1){
							$players = ['p1', 'p2'];
						}else{
							$players = ($card_data['login'] == $users_data['user']['login'])
								? [$users_data['opponent']['player']]
								: [$users_data['user']['player']];
						}

						//Карта действует на группу
						$groups = (isset($action->fear_actionToGroupOrAll))? $action->fear_actionToGroupOrAll: [];

						foreach($players as $player_iter => $player){
							if(!in_array($users_data[$player]['current_deck'], $action->fear_enemyRace)){
								foreach($action->fear_ActionRow as $action_row_iter => $action_row){
									foreach($battle_field[$player][$action_row]['warrior'] as $card_iter => $card_data){
										$allow_fear = self::checkForSimpleImmune($action->fear_ignoreImmunity, $card_data['card']['actions']);

										if(($card_data['strength'] > 0) && ($allow_fear)){
											if(!empty($groups)){
												foreach($card_data['card']['groups'] as $groups_ident => $group_id){
													if(in_array($group_id, $groups)){
														$strength = $card_data['strength'] - $action->fear_strenghtValue;
														if($strength < 1){
															$strength = 1;
														}
														$battle_field[$player][$action_row]['warrior'][$card_iter]['strength'] = $strength;
													}
												}
											}else{
												$strength = $card_data['strength'] - $action->fear_strenghtValue;
												if($strength < 1){
													$strength = 1;
												}
												$battle_field[$player][$action_row]['warrior'][$card_iter]['strength'] = $strength;
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		//Применение МЭ "Страшный" к картам
		foreach($magic_usage as $player => $magic_data){
			$opponent_player = ($users_data['user']['player'] == $player)? $users_data['opponent']['player']: $users_data['user']['player'];

			foreach($magic_data as $activated_in_round => $magic_id){
				if($activated_in_round == $battle->round_count){
					if($magic_id['allow'] != '0'){
						$magic = json_decode(SiteGameController::getMagicData($magic_id['id']));//Данные о МЭ
						foreach($magic->actions as $action_iter => $action){
							if($action->action == '18'){
								if(!in_array($users_data[$opponent_player]['current_deck'], $action->fear_enemyRace)){
									foreach($action->fear_ActionRow as $action_row_iter => $action_row){
										foreach($battle_field[$opponent_player][$action_row]['warrior'] as $card_iter => $card_data){
											$allow_fear = self::checkForSimpleImmune($action->fear_ignoreImmunity, $card_data['card']['actions']);

											if(($card_data['strength'] > 0) && ($allow_fear)){
												$strength = $card_data['strength'] - $action->fear_strenghtValue;
												if($strength < 1){
													$strength = 1;
												}
												$battle_field[$opponent_player][$action_row]['warrior'][$card_iter]['strength'] = $strength;
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		//Применение "Боевое братство" к картам
		$cards_to_brotherhood = [];
		foreach($actions_array_brotherhood as $player => $cards_array){
			foreach($cards_array as $card_id => $card_data){
				foreach($card_data['card']['actions'] as $action_iter => $action_data){
					if($action_data->action == '3'){
						if($action_data->brotherhood_actionToGroupOrSame == 0){
							$count_same = 0;
							$mult_same = 1;
							foreach($battle_field[$player] as $rows => $cards){
								foreach($cards['warrior'] as $card_iter => $card){
									if(Crypt::decrypt($card_data['card']['id']) == Crypt::decrypt($card['card']['id'])){
										$count_same++;
									}
								}
							}
							if($count_same > 0){
								$mult_same = $count_same;
								if($mult_same > $action_data->brotherhood_strenghtMult){
									$mult_same = $action_data->brotherhood_strenghtMult;
								}
							}
							foreach($battle_field[$player] as $rows => $cards){
								foreach($cards['warrior'] as $card_iter => $card){
									if(Crypt::decrypt($card_data['card']['id']) == Crypt::decrypt($card['card']['id'])){
										$battle_field[$player][$rows]['warrior'][$card_iter]['strength'] *= $mult_same;
									}
								}
							}
						}else{
							foreach($battle_field[$player] as $rows => $cards){
								foreach($cards['warrior'] as $card_iter => $card){
									for($i=0; $i<count($card['card']['groups']); $i++){
										if(in_array($card['card']['groups'][$i], $action_data->brotherhood_actionToGroupOrSame)){
											$cards_to_brotherhood[$player][$card['card']['groups'][$i].'_'.$action_data->brotherhood_strenghtMult][] = Crypt::decrypt($card['card']['id']);
										}
									}
								}
							}
						}
					}
				}
			}
		}

		if( (isset($cards_to_brotherhood)) && (!empty($cards_to_brotherhood)) ){
			foreach($cards_to_brotherhood as $player => $group_data){
				foreach($group_data as $group_ident => $cards_ids){
					$cards_to_brotherhood[$player][$group_ident] = array_unique($cards_to_brotherhood[$player][$group_ident]);
				}
			}

			foreach($cards_to_brotherhood as $player => $group_data){
				foreach($group_data as $group_ident => $cards_ids){
					$group_data = explode('_', $group_ident);
					$count_group = 0;
					$mult_group = 1;
					foreach($battle_field[$player] as $row => $cards){
						foreach($cards['warrior'] as $card_iter => $card){
							if(in_array(Crypt::decrypt($card['card']['id']), $cards_ids)){
								$count_group++;
							}
						}
					}
					if($count_group > 0){
						$mult_group = $count_group;
						if($mult_group > $group_data[1]){
							$mult_group = $group_data[1];
						}
					}

					foreach($battle_field[$player] as $row => $cards){
						foreach($cards['warrior'] as $card_iter => $card){
							if(in_array(Crypt::decrypt($card['card']['id']), $cards_ids)){
								$battle_field[$player][$row]['warrior'][$card_iter]['strength'] *= $mult_group;
							}
						}
					}
				}
			}
		}

		//Применение Воодушевления
		foreach($actions_array_inspiration as $player => $row_data){
			foreach($row_data as $row => $cards){
				foreach($cards['actions'] as $action_iter => $action_data){
					if($action_data->action == '4'){
						foreach($battle_field[$player][$row]['warrior'] as $card_iter => $card_data){
							$allow_inspiration = true;
							if($action_data->inspiration_ignoreImmunity == 0){
								foreach($card_data['card']['actions'] as $i => $card_action){
									if($card_action->action == '5'){
										if($card_action->immumity_type == 1){
											$allow_inspiration = false;
										}
									}
								}
							}
							if($allow_inspiration){
								$battle_field[$player][$row]['warrior'][$card_iter]['strength'] *= $action_data->inspiration_multValue;
							}
						}
					}
				}
			}
		}

		//Применение МЭ "Воодушевление" к картам
		foreach($magic_usage as $player => $magic_data){
			foreach($magic_data as $activated_in_round => $magic_id){
				if($activated_in_round == $battle->round_count){
					if($magic_id['allow'] != '0'){
						$magic = json_decode(SiteGameController::getMagicData($magic_id['id']));//Данные о МЭ
						foreach($magic->actions as $action_iter => $action_data){
							if($action_data->action == '4'){
								foreach($action_data->inspiration_ActionRow as $row_iter => $row){
									if( (!isset($actions_array_inspiration[$player][$row])) || (empty($actions_array_inspiration[$player][$row])) ){
										foreach($battle_field[$player][$row]['warrior'] as $card_iter => $card_data){
											$allow_inspiration = true;
											if($action_data->inspiration_ignoreImmunity == 0){
												foreach($card_data['card']['actions'] as $i => $card_action){
													if($card_action->action == '5'){
														if($card_action->immumity_type == 1){
															$allow_inspiration = false;
														}
													}
												}
											}
											if($battle_field[$player][$row]['special'] != ''){
												foreach($battle_field[$player][$row]['special']['card'] as $i => $card_action){
													if($card_action->action == '4'){
														$allow_inspiration = false;
													}
												}
											}
											if($allow_inspiration){
												$battle_field[$player][$row]['warrior'][$card_iter]['strength'] *= $action_data->inspiration_multValue;
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return $battle_field;
	}

	protected static function dropCardFromDeck($deck, $card){
		$deck = array_values($deck);
		//Количество карт в входящей колоде
		$deck_card_count = count($deck);
		for($i=0; $i<$deck_card_count; $i++){
			$deck[$i] = self::transformObjToArr($deck[$i]);
			if(Crypt::decrypt($deck[$i]['id']) == Crypt::decrypt($card['id'])){//Если id сходятся
				unset($deck[$i]);//Сносим карту из входящей колоды
				break;
			}
		}
		$deck = array_values($deck);
		return $deck;
	}

	protected static function checkForSimpleImmune($ignoreImmunity, $card_actions){
		$allow_to_use = true;
		if($ignoreImmunity == 0){
			foreach($card_actions as $action_iter => $action){
				if($action->action == '5'){
					$allow_to_use = false;
				}
			}
		}
		return $allow_to_use;
	}

	protected static function checkForFullImmune($ignoreImmunity, $card_actions){
		$allow_to_use = true;
		if($ignoreImmunity == 0){
			foreach($card_actions as $i => $action){
				if($action->action == '5'){
					if($action->immumity_type == 1){
						$allow_to_use = false;
					}
				}
			}
		}
		return $allow_to_use;
	}

	public static function sendUserMadeActionData($step_status, $msg, $SplBattleObj, $from, $battle_field, $magic_usage, $users_data, $user_turn, $addition_data, $round_count, $data_to_user = '', $showTimerOfUser='opponent'){
		$user_discard_count = count($users_data['user']['discard']);
		$user_deck_count = count($users_data['user']['deck']);

		$users_battle_data = \DB::table('tbl_battle_members')
			->select('id','turn_expire','time_shift')
			->where('id', '=', $users_data[$showTimerOfUser]['battle_member_id'])
			->get();
		$timing = $users_battle_data[0]->turn_expire - $users_battle_data[0]->time_shift;

		$oponent_discard_count = count($users_data['opponent']['discard']);
		$oponent_deck_count = count($users_data['opponent']['deck']);
        //var_dump($step_status);

		$result = [
			'message'		=> 'userMadeAction',
			'timing'		=> $timing+time(),
			'user_hand'		=> $users_data['user']['hand'],
			'user_deck'		=> $users_data['user']['deck'],
			'user_discard'	=> $users_data['user']['discard'],
			'opon_discard'	=> $users_data['opponent']['discard'],
			'battleInfo'	=> $msg->ident->battleId,
			'magicUsage'	=> $magic_usage,
			'login'			=> $user_turn,
            'step_status'   => $step_status,
			'field_data'	=> $battle_field,
			'round'			=> $round_count,
			'deck_slug'		=> $users_data['user']['current_deck'],
			'counts'		=> [
				'user_deck'		=> $user_deck_count,
				'user_discard'	=> $user_discard_count,
				'opon_discard'	=> $oponent_discard_count,
				'opon_deck'		=> $oponent_deck_count,
				'opon_hand'		=> count($users_data['opponent']['hand'])
			],
			'turnDescript'	=> [
				'cardSource'	=> $users_data['user']['card_source'],
				'playerSource'	=> $users_data['user']['player_source'],
				'cardToPlay'	=> $users_data['user']['cards_to_play'],
			],
			'users'			=> [
				$users_data['user']['login']	=> $users_data['user']['energy'],
				$users_data['opponent']['login']=> $users_data['opponent']['energy']
			],

		];
		if(($data_to_user == '') || ($data_to_user == $users_data['user']['player'])){
			$result['addition_data'] = $addition_data;
		}

		self::sendMessageToSelf($from, $result); //Отправляем результат отправителю

        //"Прячем" от противника руку полльзователя
        foreach($step_status['added_cards'] as $player => $field){
            foreach($field as $field_type => $field_data){
                if($field_data == 'hand'){
                    unset($step_status['added_cards'][$player]['hand']);
                }
            }
        }

		$result = [
			'message'		=> 'userMadeAction',
			'timing'		=> $timing+time(),
			'user_hand'		=> $users_data['opponent']['hand'],
			'user_deck'		=> $users_data['opponent']['deck'],
			'user_discard'	=> $users_data['opponent']['discard'],
			'opon_discard'	=> $users_data['user']['discard'],
			'battleInfo'	=> $msg->ident->battleId,
			'magicUsage'	=> $magic_usage,
			'login'			=> $user_turn,
            'step_status'   => $step_status,
			'field_data'	=> $battle_field,
			'round'			=> $round_count,
			'deck_slug'		=> $users_data['opponent']['current_deck'],
			'counts'		=> [
				'user_deck'		=> $oponent_deck_count,
				'user_discard'	=> $oponent_discard_count,
				'opon_discard'	=> $user_discard_count,
				'opon_deck'		=> $user_deck_count,
				'opon_hand'		=> count($users_data['user']['hand'])
			],
			'turnDescript'	=> ['cardSource' => $users_data['opponent']['card_source']],
			'users'			=> [
				$users_data['user']['login']	=> $users_data['user']['energy'],
				$users_data['opponent']['login']=> $users_data['opponent']['energy']
			],
		];
		if($data_to_user == $users_data['opponent']['player']){
			$result['addition_data'] = $addition_data;

			$users_battle_data = BattleMembers::find($users_data['opponent']['battle_member_id']);
			$users_battle_data['addition_data'] = serialize($addition_data);
			$users_battle_data->save();
		}

		self::sendMessageToOthers($from, $result, $SplBattleObj[$msg->ident->battleId]);
	}

	protected static function saveUsersDecks($users_data){
		$users_battle_data = BattleMembers::find($users_data['user']['battle_member_id']);
			$users_battle_data['user_deck']		= serialize($users_data['user']['deck']);
			$users_battle_data['user_hand']		= serialize($users_data['user']['hand']);
			$users_battle_data['user_discard']	= serialize($users_data['user']['discard']);
			$users_battle_data['card_source']	= 'hand';
			$users_battle_data['addition_data']	= serialize($users_data['user']['addition_data']);
		$users_battle_data->save();

		$opponent_battle_data = BattleMembers::find($users_data['opponent']['battle_member_id']);
			$opponent_battle_data['user_deck']		= serialize($users_data['opponent']['deck']);
			$opponent_battle_data['user_hand']		= serialize($users_data['opponent']['hand']);
			$opponent_battle_data['user_discard']	= serialize($users_data['opponent']['discard']);
		$opponent_battle_data->save();
	}

	protected static function saveGameResults($user_id, $battle, $game_result){
		$user = \DB::table('users')
			->select('id', 'login', 'premium_activated', 'premium_expire_date', 'user_gold', 'user_silver', 'user_rating')
			->where('id', '=', $user_id)
			->first();

		$league = League::where('title', '=', $battle->league)->first();

		$user_rating = unserialize($user->user_rating);

		$games_count = $user_rating[$league['slug']]['games_count'] + 1;

		$gold = $user->user_gold;
		$silver = $user->user_silver;

		$win_count = $user_rating[$league['slug']]['win_count'];

		$expire_date = strtotime(substr($user->premium_expire_date, 0, -9));
		$current_date = strtotime(date('Y-m-d'));

		if ((($expire_date - $current_date) > 0) && ($user->premium_activated > 0)) {//if user is premium
			$resources = [
				'gold_per_win'	=> $league->prem_gold_per_win,
				'gold_per_loose'=> $league->prem_gold_per_loose,
				'silver_per_win'=> $league->prem_silver_per_win,
				'silver_per_loose'=> $league->prem_silver_per_loose,
			];
		} else {
			$resources = [
				'gold_per_win'	=> $league->gold_per_win,
				'gold_per_loose'=> $league->gold_per_loose,
				'silver_per_win'=> $league->silver_per_win,
				'silver_per_loose'=> $league->silver_per_loose,
			];
		}

		$result = [
			'gold' => 0,
			'silver' => 0,
			'user_rating' => 0,
			'gameResult' => $game_result
		];
		switch ($game_result) {
			case 'win':
				$gold = $user->user_gold + $resources['gold_per_win'];
				$silver = $user->user_silver + $resources['silver_per_win'];
				$rating = $user_rating[$league['slug']]['user_rating'] + $league->rating_per_win;
				$win_count = $user_rating[$league['slug']]['win_count'] + 1;
				$result['gold'] = $resources['gold_per_win'];
				$result['silver'] = $resources['silver_per_win'];
				$result['user_rating'] = $league->rating_per_win;
			break;
			case 'loose':
				$gold = $user->user_gold + $resources['gold_per_loose'];
				$silver = $user->user_silver + $resources['silver_per_loose'];
				$rating = $user_rating[$league['slug']]['user_rating'] + $league->rating_per_loose;
				if($gold < 0) $gold = 0;
				if($silver < 0) $silver = $league->min_amount;
				$result['gold'] = $resources['gold_per_loose'];
				$result['silver'] = $resources['silver_per_loose'];
				$result['user_rating'] = abs($league->rating_per_loose);
			break;
			case 'draw':
				$rating = $user_rating[$league['slug']]['user_rating'];
			break;
		}

		$user_rating[$league['slug']] = [
			'user_rating'	=> $rating,
			'win_count'		=> $win_count,
			'games_count'	=> $games_count
		];

		\DB::table('users')->where('id', '=', $user->id)->update([
			'user_gold'		=> $gold,
			'user_silver'	=> $silver,
			'user_rating'	=> serialize($user_rating)
		]);

		return $result;
	}
}