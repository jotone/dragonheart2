@extends('layouts.game')
@section('content')

@if(!empty($errors->all()))
	@foreach($errors->all() as $key => $value)
		{{ $value }}
	@endforeach
	{{ die() }}
@endif

<?php
function cardView($card, $quantity = 1){
	if(isset($card['card'])){
		if(!is_array($card['card'])) $card['card'] = get_object_vars($card['card']);
		$card_data  = $card['card'];
	}else{
		if(!is_array($card)) $card = get_object_vars($card);
		$card_data = $card;
	}
	$strength = $card['strength'];

	$race_class = '';
	$leader_class='';
	$special_class='';
	if($card_data['type'] == 'special'){$special_class = 'special-type';}
	if($card_data['is_leader'] == 1 ){$leader_class = 'leader-type';}

	switch($card_data['fraction']){
		case 'knight':      $race_class = 'knight-race'; break;
		case 'highlander':  $race_class = 'highlander-race'; break;
		case 'monsters':    $race_class = 'monsters-race'; break;
		case 'undead':      $race_class = 'undead-race'; break;
		case 'cursed':      $race_class = 'cursed-race'; break;
		case 'forest':      $race_class = 'forest-race'; break;
		default:
			if($card_data['type'] == 'neutrall'){
				$race_class = 'neutrall-race';
			}
	}
	$has_immune = 'false';
	$has_full_immune = 'false';
	foreach($card_data['actions'] as $action){
		if($action->action == '5'){
			$has_immune = 'true';
			$has_full_immune = ($action->immumity_type == '1')? 'true': 'false';
		}
	}
	$card_view = '
	<li class="content-card-item disable-select show" data-cardid="'.$card_data['id'].'" data-relative="'.$card_data['type'].'" data-immune="'.$has_immune.'" data-full-immune="'.$has_full_immune.'">';
	if($quantity > 1){
		$card_view .= '<div class="count">'.$quantity.'</div>';
	}
	$card_view .= '
		<div class="content-card-item-main '.$race_class.' '.$leader_class.' '.$special_class.'" style="background-image: url('.URL::asset('/img/card_images/'.$card_data['img_url']).')" data-leader="'.$card_data['is_leader'].'" data-type="'.$card_data['type'].'">
			<div class="card-load-info card-popup">
				<div class="info-img">
					<img class="ignore" src="/images/info-icon.png" alt="">
					<span class="card-action-description">Инфо о карте</span>
				</div>';

	if($card_data['is_leader'] == 1){
		$card_view .= '
				<div class="leader-flag">
					<span class="card-action-description">Карта Лидера</span>
				</div>';
	}
	$card_view .= '
				<div class="label-power-card">
					<span class="label-power-card-wrap">
						<span class="buff-debuff-value"></span>
						<span class="card-current-value">'.$strength.'</span>
					</span>
					<span class="card-action-description">Сила карты</span>
				</div>
				<div class="hovered-items">
					<div class="card-game-status">
						<div class="card-game-status-role">';
	if($card_data['type'] != 'special'){
		foreach($card_data['row_txt'] as $i => $dist){
			if(!is_array($dist)) $dist = get_object_vars($dist);
			$card_view .= '
				<img src="'.URL::asset($dist['image']).'" alt="">
				<span class="card-action-description">'.$dist['title'].'</span>';
		}
	}

	$card_view .= '
				</div>
				<div class="card-game-status-wrap">
	';
	if(!empty($card_data['action_txt'])){
		foreach($card_data['action_txt'] as $i => $act){
			if(!is_array($act)) $act = get_object_vars($act);
			$card_view .= '
				<span class="card-action">
					 <img src="'.URL::asset($act['img']).'" alt="">
					<span class="card-action-description">'.$act['title'].'</span>
				</span>';
		}
	}
	$card_view .= '
						</div>
					</div>
					<div class="card-name-property">
						<p>'.$card_data['title'].'</p>
					</div>
					<div class="card-description-hidden">
						<div class="jsp-cont-descr">
							<p class="txt">'.$card_data['descript'].'</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</li>';
	return $card_view;
}

$user = Auth::user();
$players = ['enemy' => [], 'allied' => []];
$battle_field = unserialize($battle_data->battle_field);
$magic_usage = unserialize($battle_data->magic_usage);

if($user['id'] == $battle_data->creator_id){
	$user_field_identificator = 'p1';
	$opponent_field_identificator = 'p2';
}else{
	$user_field_identificator = 'p2';
	$opponent_field_identificator = 'p1';
}

$round_status = unserialize($battle_data->round_status);

foreach($battle_members as $key => $value){
	//Создание сторон противников и союзников
	$player_data = \DB::table('users')->select('id','login','img_url')->where('id', '=', $value -> user_id)->get();
	$fraction_name = \DB::table('tbl_fraction')->select('slug', 'title', 'card_img')->where('slug', '=', $value -> user_deck_race)->get();

	$temp_magic = unserialize($value->magic_effects);
	$user_magic = [];
	foreach($temp_magic as $id => $quantity){
		$magic_data = \DB::table('tbl_magic_effect')->select('id', 'title', 'slug', 'img_url', 'description')->where('id','=',$id)->get();
		$user_magic[] = $magic_data[0];
	}

	$user_hand = unserialize($value -> user_hand);
	if($user['id'] == $value->user_id){
		$temp = \DB::table('tbl_battles')->select('id','disconected_count')->where('id','=',$battle_data->id)->get();
		if($temp[0]->disconected_count > 0){
			$temp[0]->disconected_count = $temp[0]->disconected_count -1;
			\DB::table('tbl_battles')->where('id','=',$battle_data->id)->update([
				'disconected_count' => $temp[0]->disconected_count
			]);
		}

		$players['allied'] = [
			'user_deck'		=> unserialize($value -> user_deck),
			'user_discard'	=> unserialize($value -> user_discard),
			'user_deck_race'=> $fraction_name[0] -> title,
			'user_deck_slug'=> $fraction_name[0] -> slug,
			'card_img'		=> $fraction_name[0] -> card_img,
			'user_energy'	=> $value -> user_energy,
			'user_hand'		=> $user_hand,
			'user_hand_count'=>count($user_hand),
			'user_img'		=> $player_data[0] -> img_url,
			'user_magic'	=> $user_magic,
			'user_nickname'	=> $player_data[0] -> login,
			'user_ready'	=> $value -> user_ready,
			'wins_count'	=> count($round_status[$user_field_identificator]),
			'fear_rows'		=> [['','',0],['','',0],['','',0]],
			'inspir_rows'	=> [['',''],['',''],['','']],
			'support_rows'	=> [['',''],['',''],['','']]
		];
		$players[$user_field_identificator] = &$players['allied'];
	}else{
		$players['enemy'] = [
			'user_deck'		=> unserialize($value -> user_deck),
			'user_discard'	=> unserialize($value -> user_discard),
			'user_deck_race'=> $fraction_name[0] -> title,
			'user_deck_slug'=> $fraction_name[0] -> slug,
			'card_img'		=> $fraction_name[0] -> card_img,
			'user_energy'	=> $value -> user_energy,
			'user_hand_count'=> count($user_hand),
			'user_img'		=> $player_data[0] -> img_url,
			'user_magic'	=> $user_magic,
			'user_nickname'	=> $player_data[0] -> login,
			'wins_count'	=> count($round_status[$opponent_field_identificator]),
			'fear_rows'		=> [['','',0],['','',0],['','',0]],
			'inspir_rows'	=> [['',''],['',''],['','']],
			'support_rows'	=> [['',''],['',''],['','']]
		];
		$players[$opponent_field_identificator] = &$players['enemy'];
	}
}
$buff_classes = [
	'terrify_wrap' => 'terrify-debuff-wrap debuff',
	'terrify' => 'terrify-debuff',
	'insp_wrap' => 'inspiration-buff-wrap buff',
	'insp' => 'inspiration-buff',
	'support_wrap' => 'support-buff-wrap buff',
	'support' => 'support-buff'
];

//fear counts
foreach($battle_field as $field => $data){
	if($field == 'mid'){
		foreach($data as $card_iter => $card){
			foreach($card['card']['actions'] as $action){
				if($action->action == 18){
					foreach($action->fear_ActionRow as $fear_row){
						$count_enemy = $players['enemy']['fear_rows'][$fear_row][2] +1;
						$count_allied = $players['allied']['fear_rows'][$fear_row][2] +1;
						$players['enemy']['fear_rows'][$fear_row] = [
							$buff_classes['terrify_wrap'],
							$buff_classes['terrify'],
							$count_enemy
						];
						$players['allied']['fear_rows'][$fear_row] = [
							$buff_classes['terrify_wrap'],
							$buff_classes['terrify'],
							$count_allied
						];
					}
				}
			}
		}
	}else{
		foreach($data as $row => $row_data){
			if(!empty($row_data['special'])){
				foreach($row_data['special']['card']['actions'] as $action){
					if($action->action == 4){
						$players[$field]['inspir_rows'][$row] = [$buff_classes['insp_wrap'],$buff_classes['insp']];
					}
				}
			}

			foreach($row_data['warrior'] as $card){
				foreach($card['card']['actions'] as $action){
					if($action->action == 18){
						foreach($action->fear_ActionRow as $fear_row){
							if($action->fear_actionTeamate == 0){
								if($field == $opponent_field_identificator){
									$count = $players[$user_field_identificator]['fear_rows'][$fear_row][2] +1;
									$players[$user_field_identificator]['fear_rows'][$fear_row] = [
										$buff_classes['terrify_wrap'],
										$buff_classes['terrify'],
										$count
									];
								}else{
									$count = $players[$opponent_field_identificator]['fear_rows'][$fear_row][2] +1;
									$players[$opponent_field_identificator]['fear_rows'][$fear_row] = [$buff_classes['terrify_wrap'],$buff_classes['terrify'], $count];
								}
							}else{
								$count_enemy = $players['enemy']['fear_rows'][$fear_row][2] +1;
								$count_allied = $players['allied']['fear_rows'][$fear_row][2] +1;
								$players['enemy']['fear_rows'][$fear_row] = [
									$buff_classes['terrify_wrap'],
									$buff_classes['terrify'],
									$count_enemy
								];
								$players['allied']['fear_rows'][$fear_row] = [
									$buff_classes['terrify_wrap'],
									$buff_classes['terrify'],
									$count_allied
								];
							}
						}
					}
					if($action->action == 13){
						$players[$field]['support_rows'][$row] = [$buff_classes['support_wrap'],$buff_classes['support']];
					}
					if($action->action == 4){
						$players[$field]['inspir_rows'][$row] = [$buff_classes['insp_wrap'],$buff_classes['insp']];
					}
				}
			}
		}
	}
}
?>

<div class="troll-popup" id="allies-discard">
	<div class="close-this"></div>
	<div class="popup-content-wrap">
		<h5>Карты в вашем отбое:</h5>
		<div class="pop-row">
			<ul class="deck-cards-list">
			@if( (isset($players['allied']['user_discard'])) and (count($players['allied']['user_discard']) != 0) )
				@if(!empty($players['allied']['user_discard']))
					@foreach($players['allied']['user_discard'] as $i => $card)
						{!! cardView($card) !!}
					@endforeach
				@endif
			@endif
			</ul>
		</div>
	</div>
</div>
<div class="troll-popup" id="enemy-discard">
	<div class="close-this"></div>
	<div class="popup-content-wrap">
		<h5>Карты в отбое противника:</h5>
		<div class="pop-row">
			<ul class="deck-cards-list">
				@if( (isset($players['enemy']['user_discard'])) and (count($players['enemy']['user_discard']) != 0) )
					@if(!empty($players['enemy']['user_discard']))
						@foreach($players['enemy']['user_discard'] as $i => $card)
							{!! cardView($card) !!}
						@endforeach
					@endif
				@endif
			</ul>
		</div>
	</div>
</div>
<div class="troll-popup" id="allies-deck">
	<div class="close-this"></div>
	<div class="popup-content-wrap">
		<h5>Карты в колоде:</h5>
		<div class="pop-row">
			@if( (isset($players['allied']['user_deck'])) and (count($players['allied']['user_deck']) != 0) )
				<!--Список карт колоды -->
				@if(!empty($players['allied']['user_deck']))
					<ul class="deck-cards-list">
						@foreach($players['allied']['user_deck'] as $i => $card)
							{!! cardView($card) !!}
						@endforeach
					</ul>
				@endif
			@endif
		</div>
	</div>
</div>
</div> <!--//закрывающий тег обертки попапов который открыт в другом файле не удалять!)-->

<div class="wrap-play disable-select">
	<div class="field-battle">
		<!-- Поле битвы -->
		<div class="convert-battle-front">
			<!-- Колода и отбой противника -->
			<div class="convert-left-info">
				<div class="cards-bet cards-oponent">
					<ul id="card-give-more-oponent" @if(isset($players['enemy']['user_nickname'])) data-user="{{ $players['enemy']['user_nickname'] }}"@endif >
						<!-- Колода противника -->
						<li data-field="deck">
							@if( (isset($players['enemy']['user_deck'])) and (count($players['enemy']['user_deck']) != 0) )
								<div class="card-init" @if((isset($players['enemy'])) && (!empty($players['enemy']['card_img']))) style="background-image: url('../img/fractions_images/{{$players['enemy']['card_img']}}') !important;" @endif>
									<div class="card-otboy-counter deck">
										<div class="counter">{{ count($players['enemy']['user_deck'])}}</div>
									</div>
								</div>
							@else
								<div class="nothinh-for-swap"></div>
							@endif
						</li>
						<!-- Отбой противника -->
						<li data-field="discard">
							@if( (isset($players['enemy']['user_discard'])) and (count($players['enemy']['user_discard']) != 0) )
								<div class="card-init" @if((isset($players['enemy'])) && (!empty($players['enemy']['card_img']))) style="background-image: url('../img/fractions_images/{{$players['enemy']['card_img']}}') !important;" @endif>
									<div class="card-otboy-counter deck">
										<div class="counter">{{ count($players['enemy']['user_discard'])}}</div>
									</div>
								</div>
							@else
								<div class="nothinh-for-swap"></div>
							@endif
						</li>
					</ul>
				</div>
			</div>
			<!--END OF Колода и отбой противника -->
			<div class="rounds-counter-wrapper">
				<div class="rounds-counter-container">
					<div class="rounds-counts user">
						<div class="rounds-counts-count">
							@if( isset($players['allied']['wins_count']))
								{{$players['allied']['wins_count']}}
							@else
								0
							@endif
						</div>
						<div class="rounds-counts-title">{{$players['allied']['user_nickname']}}</div>
					</div>
					<div class="vs">vs</div>
					<div class="rounds-counts oponent">
						<div class="rounds-counts-count">
							@if( isset($players['enemy']['wins_count']))
								{{$players['enemy']['wins_count']}}
							@else
								0
							@endif
						</div>
						<div class="rounds-counts-title">@if(isset($players['enemy']['user_nickname'])){{$players['enemy']['user_nickname']}}@endif</div>
					</div>
				</div>
			</div>
			<!-- Поле противника -->
			<div class="convert-cards oponent" @if(isset($players['enemy']['user_nickname']))data-user="{{ $players['enemy']['user_nickname'] }}"@endif id="{{$opponent_field_identificator}}">
				<div class="convert-card-box">
					<!-- Сверхдальние Юниты противника -->
					<?php
					$classes = '';
					if(isset($players['enemy']['user_nickname'])){
						$classes .= $players['enemy']['fear_rows'][2][0].' '.$players['enemy']['inspir_rows'][2][0].' '.$players['enemy']['support_rows'][2][0];
					}
					?>
					<div class="convert-stuff {{$classes}}">
						<div class="convert-one-field">
							<div class="field-for-cards" id="superRange">
								<div class="image-inside-line">
									@if(!empty($battle_field[$opponent_field_identificator][2]['special']))
										{!! cardView($battle_field[$opponent_field_identificator][2]['special']) !!}
									@endif
								</div>
								<!-- Поле размещения сверхдальних карт -->
								<div class="inputer-field-super-renge fields-for-cards-wrap">
									<div class="bg-img-super-renge fields-for-cards-img"><!-- Картинка пустого сверхдальнего ряда --></div>
									<ul class="cards-row-wrap">
									@foreach($battle_field[$opponent_field_identificator][2]['warrior'] as $i => $card)
										{!! cardView($card) !!}
									@endforeach
									</ul>
									<!-- END OF Список сверхдальних карт-->
								</div>
								@if( (isset($players['enemy']['user_nickname'])) && ($players['enemy']['fear_rows'][2][1] != '') )
									<div class="debuff-or-buff-anim {{$players['enemy']['fear_rows'][2][1]}} active" data-count="{{$players['enemy']['fear_rows'][2][2]}}"></div>
								@endif
								@if( (isset($players['enemy']['user_nickname'])) && ($players['enemy']['inspir_rows'][2][1] != '') )
									<div class="debuff-or-buff-anim {{$players['enemy']['inspir_rows'][2][1]}} active"></div>
								@endif
								@if( (isset($players['enemy']['user_nickname'])) && ($players['enemy']['support_rows'][2][1] != '') )
									<div class="debuff-or-buff-anim {{$players['enemy']['support_rows'][2][1]}} active"></div>
								@endif
								<!-- END OF Поле размещения сверхдальних карт -->
							</div>
						</div>
						<div class="field-for-sum"><!-- Сумарная сила воинов в сверхдальнем ряду --></div>
					</div>
					<!-- END OF Сверхдальние Юниты противника -->

					<!-- Дальние Юниты противника -->
					<?php
					$classes = '';
					if(isset($players['enemy']['user_nickname'])){
						$classes .= $players['enemy']['fear_rows'][1][0].' '.$players['enemy']['inspir_rows'][1][0].' '.$players['enemy']['support_rows'][1][0];
					}
					?>
					<div class="convert-stuff {{$classes}}">
						<div class="convert-one-field">
							<div class="field-for-cards" id="range">
								<div class="image-inside-line">
									@if(!empty($battle_field[$opponent_field_identificator][1]['special']))
										{!! cardView($battle_field[$opponent_field_identificator][1]['special']) !!}
									@endif
								</div>
								<!-- Поле размещения дальних карт -->
								<div class="inputer-field-range fields-for-cards-wrap">
									<div class="bg-img-range fields-for-cards-img"><!-- Картинка пустого дальнего ряда --></div>
									<!-- Список дальних карт-->
									<ul class="cards-row-wrap">
									@foreach($battle_field[$opponent_field_identificator][1]['warrior'] as $i => $card)
										{!! cardView($card) !!}
									@endforeach
									</ul>
									<!-- END OF Список дальних карт-->
								</div>
								@if( (isset($players['enemy']['user_nickname'])) && ($players['enemy']['fear_rows'][1][1] != '') )
									<div class="debuff-or-buff-anim {{$players['enemy']['fear_rows'][1][1]}} active" data-count="{{$players['enemy']['fear_rows'][1][2]}}"></div>
								@endif
								@if( (isset($players['enemy']['user_nickname'])) && ($players['enemy']['inspir_rows'][1][1] != '') )
									<div class="debuff-or-buff-anim {{$players['enemy']['inspir_rows'][1][1]}} active"></div>
								@endif
								@if( (isset($players['enemy']['user_nickname'])) && ($players['enemy']['support_rows'][1][1] != '') )
									<div class="debuff-or-buff-anim {{$players['enemy']['support_rows'][1][1]}} active"></div>
								@endif
								<!-- END OF Поле размещения дальних карт -->
							</div>
						</div>
						<div class="field-for-sum"><!-- Сумарная сила воинов в дальнем ряду --></div>
					</div>
					<!-- END OF Дальние Юниты противника -->

					<!-- Ближние Юниты противника -->
					<?php
					$classes = '';
					if(isset($players['enemy']['user_nickname'])){
						$classes .= $players['enemy']['fear_rows'][0][0].' '.$players['enemy']['inspir_rows'][0][0].' '.$players['enemy']['support_rows'][0][0];
					}
					?>
					<div class="convert-stuff {{$classes}}">
						<div class="convert-one-field">
							<div class="field-for-cards" id="meele">
								<div class="image-inside-line">
									@if(!empty($battle_field[$opponent_field_identificator][0]['special']))
										{!! cardView($battle_field[$opponent_field_identificator][0]['special']) !!}
									@endif
								</div>
								<div class="inputer-field-meele fields-for-cards-wrap">
									<div class="bg-img-meele fields-for-cards-img"><!-- Картинка пустого ближнего ряда --></div>
									<!-- Список ближних карт-->
									<ul class="cards-row-wrap">
									@foreach($battle_field[$opponent_field_identificator][0]['warrior'] as $i => $card)
										{!! cardView($card) !!}
									@endforeach
									</ul>
									<!-- END OF Список ближних карт-->
								</div>
								@if( (isset($players['enemy']['user_nickname'])) && ($players['enemy']['fear_rows'][0][1] != '') )
									<div class="debuff-or-buff-anim {{$players['enemy']['fear_rows'][0][1]}} active" data-count="{{$players['enemy']['fear_rows'][0][2]}}"></div>
								@endif
								@if( (isset($players['enemy']['user_nickname'])) && ($players['enemy']['inspir_rows'][0][1] != '') )
									<div class="debuff-or-buff-anim {{$players['enemy']['inspir_rows'][0][1]}} active"></div>
								@endif
								@if( (isset($players['enemy']['user_nickname'])) && ($players['enemy']['support_rows'][0][1] != '') )
									<div class="debuff-or-buff-anim {{$players['enemy']['support_rows'][0][1]}} active"></div>
								@endif
							</div>
						</div>
						<div class="field-for-sum"><!-- Сумарная сила воинов в ближнем ряду --></div>
					</div>
					<!-- END OF Ближние Юниты противника -->
				</div>
			</div>
			<!--END OF Поле противника -->

			<div class="mezdyline"></div>

			<!-- Поле пользователя -->
			<div class="convert-cards user" data-user="{{ (isset($players['allied']['user_nickname'])?$players['allied']['user_nickname']:'') }}" id="{{$user_field_identificator}}">
				<div class="convert-card-box">
					<!-- Ближние Юниты пользователя -->
					<?php
					$classes = '';
					if(isset($players['allied']['user_nickname'])){
						$classes .= $players['allied']['fear_rows'][0][0].' '.$players['allied']['inspir_rows'][0][0].' '.$players['allied']['support_rows'][0][0];
					}
					?>
					<div class="convert-stuff {{$classes}}">
						<div class="convert-one-field">
							<div class="field-for-cards" id="meele">
								<div class="image-inside-line">
									@if(!empty($battle_field[$user_field_identificator][0]['special']))
										{!! cardView($battle_field[$user_field_identificator][0]['special']) !!}
									@endif
								</div><!-- Место для спецкарты -->
								<div class="inputer-field-meele fields-for-cards-wrap">
									<div class="bg-img-meele fields-for-cards-img"></div>
									<!-- Список ближних карт-->
									<ul class="cards-row-wrap">
									@foreach($battle_field[$user_field_identificator][0]['warrior'] as $i => $card)
										{!! cardView($card) !!}
									@endforeach
									</ul>
									<!-- END OF Список ближних карт-->
								</div>
								@if( (isset($players['allied']['user_nickname'])) && ($players['allied']['fear_rows'][0][1] != '') )
									<div class="debuff-or-buff-anim {{$players['allied']['fear_rows'][0][1]}} active" data-count="{{$players['allied']['fear_rows'][0][2]}}"></div>
								@endif
								@if( (isset($players['allied']['user_nickname'])) && ($players['allied']['inspir_rows'][0][1] != '') )
									<div class="debuff-or-buff-anim {{$players['allied']['inspir_rows'][0][1]}} active"></div>
								@endif
								@if( (isset($players['allied']['user_nickname'])) && ($players['allied']['support_rows'][0][1] != '') )
									<div class="debuff-or-buff-anim {{$players['allied']['support_rows'][0][1]}} active"></div>
								@endif
							</div>
						</div>
						<div class="field-for-sum"><!-- Сила воинов в ближнем ряду--></div>
					</div>
					<!-- END OF Ближние Юниты пользователя -->

					<!-- Дальние Юниты пользователя -->
					<?php
					$classes = '';
					if(isset($players['allied']['user_nickname'])){
						$classes .= $players['allied']['fear_rows'][1][0].' '.$players['allied']['inspir_rows'][1][0].' '.$players['allied']['support_rows'][1][0];
					}
					?>
					<div class="convert-stuff @if( (isset($players['allied']['user_nickname'])) && ($players['allied']['fear_rows'][1] === true) ) terrify-debuff debuff @endif">
						<div class="convert-one-field">
							<div class="field-for-cards" id="range">
								<div class="image-inside-line">
									@if(!empty($battle_field[$user_field_identificator][1]['special']))
										{!! cardView($battle_field[$user_field_identificator][1]['special']) !!}
									@endif
								</div><!-- Место для спецкарты -->
								<div class="inputer-field-range fields-for-cards-wrap">
									<div class="bg-img-range fields-for-cards-img"><!-- Картинка пустого ближнего ряда --></div>
									<!-- Список дальних карт-->
									<ul class="cards-row-wrap">
									@foreach($battle_field[$user_field_identificator][1]['warrior'] as $i => $card)
										{!! cardView($card) !!}
									@endforeach
									</ul>
									<!-- END OF Список дальних карт-->
								</div>
								@if( (isset($players['allied']['user_nickname'])) && ($players['allied']['fear_rows'][1][1] != '') )
									<div class="debuff-or-buff-anim {{$players['allied']['fear_rows'][1][1]}} active" data-count="{{$players['allied']['fear_rows'][1][2]}}"></div>
								@endif
								@if( (isset($players['allied']['user_nickname'])) && ($players['allied']['inspir_rows'][1][1] != '') )
									<div class="debuff-or-buff-anim {{$players['allied']['inspir_rows'][1][1]}} active"></div>
								@endif
								@if( (isset($players['allied']['user_nickname'])) && ($players['allied']['support_rows'][1][1] != '') )
									<div class="debuff-or-buff-anim {{$players['allied']['support_rows'][1][1]}} active"></div>
								@endif
							</div>
						</div>
						<div class="field-for-sum"></div>
					</div>
					<!-- END OF Дальние Юниты пользователя -->

					<!-- Сверхдальние юниты пользователя -->
					<?php
					$classes = '';
					if(isset($players['allied']['user_nickname'])){
						$classes .= $players['allied']['fear_rows'][2][0].' '.$players['allied']['inspir_rows'][2][0].' '.$players['allied']['support_rows'][2][0];
					}
					?>
					<div class="convert-stuff {{$classes}}">
						<div class="convert-one-field">
							<div class="field-for-cards" id="superRange">
								<div class="image-inside-line">
									@if(!empty($battle_field[$user_field_identificator][2]['special']))
										{!! cardView($battle_field[$user_field_identificator][2]['special']) !!}
									@endif
								</div><!-- Место для спецкарты -->
								<div class="inputer-field-super-renge fields-for-cards-wrap">
									<div class="bg-img-super-renge fields-for-cards-img"><!-- Картинка пустого ближнего ряда --></div>
									<!-- Список сверхдальних карт-->
									<ul class="cards-row-wrap">
									@foreach($battle_field[$user_field_identificator][2]['warrior'] as $i => $card)
										{!! cardView($card) !!}
									@endforeach
									</ul>
									<!-- END OF Список сверхдальнихдальних карт-->
								</div>
								@if( (isset($players['allied']['user_nickname'])) && ($players['allied']['fear_rows'][2][1] != '') )
									<div class="debuff-or-buff-anim {{$players['allied']['fear_rows'][2][1]}} active" data-count="{{$players['allied']['fear_rows'][2][2]}}"></div>
								@endif
								@if( (isset($players['allied']['user_nickname'])) && ($players['allied']['inspir_rows'][2][1] != '') )
									<div class="debuff-or-buff-anim {{$players['allied']['inspir_rows'][2][1]}} active"></div>
								@endif
								@if( (isset($players['allied']['user_nickname'])) && ($players['allied']['support_rows'][2][1] != '') )
									<div class="debuff-or-buff-anim {{$players['allied']['support_rows'][2][1]}} active"></div>
								@endif
							</div>
						</div>
						<div class="field-for-sum"></div>
					</div>
					<!-- END OF Сверхдальние юниты пользователя -->
				</div>
			</div>
			<!-- END OF Поле пользователя -->
			<div class="convert-left-info">
				<div class="cards-bet cards-main">
					<!-- Колода и отбой игрока-->
					<ul id="card-give-more-user" data-user="{{ (isset($players['allied']['user_nickname'])?$players['allied']['user_nickname']:'') }}">
						<li data-field="deck">
							@if( (isset($players['allied']['user_deck'])) and (count($players['allied']['user_deck']) != 0) )
								<div class="card-my-init cards-take-more" @if((isset($players['allied'])) && (!empty($players['allied']['card_img']))) style="background-image: url('../img/fractions_images/{{$players['allied']['card_img']}}') !important;" @endif>
									<!-- Количество карт в колоде -->
									<div class="card-take-more-counter deck">
										<div class="counter">{{ count($players['allied']['user_deck'])}}</div>
									</div>
									<!--END OF Количество карт в колоде -->
								</div>
							@else
								<div class="nothinh-for-swap"></div>
							@endif
						</li>
						<li data-field="discard">
							@if( (isset($players['allied']['user_discard'])) and (count($players['allied']['user_discard']) != 0) )
								<div class="card-my-init cards-take-more" @if((isset($players['allied'])) && (!empty($players['allied']['card_img']))) style="background-image: url('../img/fractions_images/{{$players['allied']['card_img']}}') !important;" @endif>
									<!--Список карт отбоя -->

									<!-- Количество карт в отбое -->
									<div class="card-take-more-counter deck">
										<div class="counter">{{ count($players['allied']['user_discard'])}}</div>
									</div>
									<!--END OF Количество карт в отбое -->
								</div>
							@else
								<div class="nothinh-for-swap"></div>
							@endif
					</ul>
					<!--END OF Колода и отбой игрока-->
				</div>
			</div>
			<div class="user-card-stash">

				<!-- Карты руки пользователя -->
				<ul id="sortableUserCards" class="user-hand-cards-wrap cfix">
				@if(isset($players['allied']['user_ready']) && $players['allied']['user_ready'] > 0)
					@foreach($players['allied']['user_hand'] as $i => $card)
						{!! cardView($card) !!}
					@endforeach
				@endif
				</ul>
				<!-- END OF Карты руки пользователя -->

			</div>
			<div class="buttons-block-play cfix pass">
				<button class="button-push" name="userPassed">
					<div class="button-pass"> <p> ПАС </p></div>
				</button>
			</div>
		</div>
		<!-- END OF Поле битвы -->
	</div>

	<!-- Правый сайдбар -->
	<div class="convert-right-info">
		<div class="block-with-exit">
			<div class="buttons-block-play">
				<button class="button-push" name="userGiveUpRound">
					<div class="button-giveup"> <p> СДАТЬСЯ </p></div>
				</button>
			</div>
		</div>
		<div class="oponent-describer" @if(isset($players['enemy']['user_nickname']))id="{{ $players['enemy']['user_nickname'] }}"@endif>

			<div class="useless-card">
				<div class="inside-for-some-block" style="">
					<ul class="magic-effects-wrap" data-player="{{ $opponent_field_identificator }}">
					<!-- Активная магия -->
					@if(isset($players['enemy']['user_magic']) && !empty($players['enemy']['user_magic']))
						<?php
						$disable_by_over = '';
						$magic_using_times = ($players['enemy']['user_deck_slug'] == 'forest')? 2:1;
						if(count($magic_usage[$opponent_field_identificator]) >= $magic_using_times){
							$disable_by_over = 'disactive';
						}
						?>
						@foreach($players['enemy']['user_magic'] as $i => $value)
							<?php $disactive = ''; ?>
							@foreach($magic_usage[$opponent_field_identificator] as $activated_in_round => $magic_id)
								@if($magic_id != 0)
									<?php
									if( ($value->id == $magic_id['id'] ) || ($battle_data->round_count == $activated_in_round) ){
										$disactive = 'disactive';
									}
									?>
								@else
									<?php $disactive = 'disactive'; ?>
								@endif
							@endforeach
							<li data-cardid="{{ $value->id }}" class="{{ $disable_by_over }} {{ $disactive }}">
								<img src="/img/card_images/{{ $value->img_url }}" alt="{{ $value->slug }}" title="{{ $value->title }}">
								<div class="magic-description">{{ $value ->description }}</div>
								<div class="info-img">
									<img class="ignore" src="/images/info-icon.png" alt="">
									<span class="card-action-description">Инфо о магии</span>
								</div>
							</li>
						@endforeach
					@endif
					</ul>
				</div>
			</div>

			<!-- Данные попротивника -->
			<div class="stash-about" >
				<div class="power-element">
					<div class="power-text power-text-oponent"><!-- Сумарная сила воинов во всех рядах противника --></div>
				</div>
				<div class="oponent-discribe">

					<div class="image-oponent-ork"
						@if( (isset($players['enemy']['user_img']) ) && (!empty($players['enemy']['user_img'])) )
						style="background-image: url('/img/user_images/{{$players['enemy']['user_img']}}')"
						@endif
					>

					</div><!-- Аватар игрока -->

					<!-- Количество выиграных раундов (скорее всего) n из 3х -->
					<div class="circle-status" data-pct="25">
						<svg id="svg" width='140px'  viewPort="0 0 100 100" version="1.1" xmlns="http://www.w3.org/2000/svg">
							<filter id="MyFilter" filterUnits="userSpaceOnUse" x="0" y="0" width="200" height="200">
								<feGaussianBlur in="SourceAlpha" stdDeviation="4" result="blur"/>
								<feOffset in="blur" dx="4" dy="4" result="offsetBlur"/>
								<feSpecularLighting in="blur" surfaceScale="5" specularConstant=".75" specularExponent="20" lighting-color="#bbbbbb" result="specOut">
										<fePointLight x="-5000" y="-10000" z="20000"/>
								</feSpecularLighting>
								<feComposite in="specOut" in2="SourceAlpha" operator="in" result="specOut"/>
								<feComposite in="SourceGraphic" in2="specOut" operator="arithmetic" k1="0" k2="1" k3="1" k4="0" result="litPaint"/>
								<feMerge>
										<feMergeNode in="offsetBlur"/>
										<feMergeNode in="litPaint"/>
								</feMerge>
							</filter>
							<circle filter="url(#MyFilter)" id="bar-oponent" r="65" cx="71" cy="71" fill="transparent" stroke-dasharray="409" stroke-dashoffset="100px" stroke-linecap="round"></circle>
						</svg>
					</div>

					<div class="naming-oponent">
						<div class="name">@if(isset($players['enemy']['user_nickname'])){{$players['enemy']['user_nickname']}}@endif<!-- Имя противника --></div>
						<div class="rasa">
						@if(isset($players['enemy']['user_deck_race']))
							{{$players['enemy']['user_deck_race']}}
						@endif
						<!-- Колода противника-->
						</div>
					</div>
				</div>

				<div class="oponent-stats">
					<div class="stats-power">
						<div class="pover-greencard">
							<img src="{{ URL::asset('images/greencard.png') }}" alt="">
							<div class="greencard-num">@if(isset($players['enemy']['user_hand_count'])){{$players['enemy']['user_hand_count']}}@endif</div>
						</div>
					</div>
					<div class="stats-shit"></div>
					<div class="stats-energy">
					@if(isset($players['enemy']['user_energy']))
						{{$players['enemy']['user_energy']}}
					@endif
					<!-- Количество Энергии противника -->
					</div>
				</div>
			</div>
		</div>

		<div class="mezhdyblock">
			<div class="bor-beutifull-box">
				<ul id="sortable-cards-field-more" class="can-i-use-useless sort">
					<?php
					$mid_cards = [];
					foreach($battle_field['mid'] as $i => $card){
						if(!isset($mid_cards[$card['card']['id']])){
							$mid_cards[$card['card']['id']] = [
								'card' => $card['card'],
								'strength' => $card['strength'],
								'login' => $card['login'],
								'quantity' => 1
							];
						}else{
							$mid_cards[$card['card']['id']]['quantity']++;
						}
					}
					?>
				@foreach($mid_cards as $i => $card)
					{!! cardView($card, $card['quantity']) !!}
				@endforeach
				</ul>
			</div>
		</div>

		<!-- Данные пользователя -->
		<div class="user-describer" id="{{ (isset($players['allied']['user_nickname'])?$players['allied']['user_nickname']:'') }}">
			<div class="stash-about">
				<div class="power-element">
					<div class="power-text  power-text-user"><!-- Сумарная сила воинов во всех рядах противника --></div>
				</div>
				<div class="oponent-discribe">
					<div class="image-oponent-ork" @if(!empty($players['allied']['user_img']))style="background-image: url('/img/user_images/{{$players['allied']['user_img']}}');"@endif></div><!-- Аватар игрока -->
					<div class="circle-status">
						<svg id="svg" width='140px'  viewPort="0 0 100 100" version="1.1" xmlns="http://www.w3.org/2000/svg">
							<filter id="MyFilter" filterUnits="userSpaceOnUse" x="0" y="0" width="200" height="200">
								<feGaussianBlur in="SourceAlpha" stdDeviation="4" result="blur"/>
								<feOffset in="blur" dx="4" dy="4" result="offsetBlur"/>
								<feSpecularLighting in="blur" surfaceScale="5" specularConstant=".75" specularExponent="20" lighting-color="#bbbbbb" result="specOut">
									<fePointLight x="-5000" y="-10000" z="20000"/>
								</feSpecularLighting>
								<feComposite in="specOut" in2="SourceAlpha" operator="in" result="specOut"/>
								<feComposite in="SourceGraphic" in2="specOut" operator="arithmetic" k1="0" k2="1" k3="1" k4="0" result="litPaint"/>
								<feMerge>
									<feMergeNode in="offsetBlur"/>
									<feMergeNode in="litPaint"/>
								</feMerge>
							</filter>
							<circle filter="url(#MyFilter)" id="bar-user" r="65" cx="71" cy="71" fill="transparent" stroke-dasharray="409" stroke-dashoffset="100px" stroke-linecap="round"></circle>
						</svg>
					</div>

					<div class="naming-user">
						<div class="name">{{ (isset($players['allied']['user_nickname'])?$players['allied']['user_nickname']:'') }}<!-- Имя игрока --></div>
						<div class="rasa">{{ (isset($players['allied']['user_deck_race'])?$players['allied']['user_deck_race']:'') }}<!-- Колода игрока --></div>
					</div>

				</div>
				<div class="user-stats">
					<div class="stats-power">
						<div class="pover-greencard">
							<img src="{{ URL::asset('images/greencard.png') }}" alt="">
							<div class="greencard-num">@if(isset($players['allied']['user_hand_count'])){{$players['allied']['user_hand_count']}}@endif</div>
						</div>
					</div>
					<div class="stats-shit"></div>
					<div class="stats-energy">{{ (isset($players['allied']['user_energy'])?$players['allied']['user_energy']:'') }}<!-- Количество Энергии игрока --></div>
				</div>
			</div>
			<div class="useless-card">
				<div class="inside-for-some-block">
					<ul class="magic-effects-wrap" data-player="{{ $user_field_identificator }}">
					<!-- Активная магия -->
					@if(isset($players['allied']['user_magic']) && !empty($players['allied']['user_magic']))
						<?php
						$disable_by_over = '';
						$magic_using_times = ($players['allied']['user_deck_slug'] == 'forest')? 2:1;
						if(count($magic_usage[$user_field_identificator]) >= $magic_using_times){
							$disable_by_over = 'disactive';
						}
						$used = '';
						?>
						@foreach($players['allied']['user_magic'] as $i => $value)
							<?php $disactive = ''; ?>
							@foreach($magic_usage[$user_field_identificator] as $activated_in_round => $magic_id)
								@if($magic_id != 0)
									<?php
									if( ($value->id == $magic_id['id'] ) || ($battle_data->round_count == $activated_in_round) ){
										$disactive = 'disactive';
									}
									$used = ($value->id == $magic_id['id'])? 'used': '';
									?>
								@else
									<?php $disactive = 'disactive'; ?>
								@endif
							@endforeach
							<li data-cardid="{{ $value->id }}" class="{{ $disable_by_over }} {{ $disactive }} {{ $used }}">
								<img src="/img/card_images/{{ $value->img_url }}" alt="{{ $value->slug }}" title="{{ $value->title }}">
								<div class="magic-description">{{ $value ->description }}</div>
								<div class="info-img">
									<img class="ignore" src="/images/info-icon.png" alt="">
									<span class="card-action-description">Инфо о магии</span>
								</div>
							</li>
						@endforeach
					@endif
					</ul>
				</div>
			</div>
		</div>
		<div class="info-block-with-timer">
			<div class="timer-for-play cfix">
				<div class="title-timer"><span>ход противника:</span></div>
				<div class="timer-tic-tac-convert">
					<div class="tic-tac">
						<div class="tic-tac-wrap">
							<span class="tic" data-time="minute">00</span>
							<span>:</span>
							<span class="tac" data-time="seconds">00</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@stop
