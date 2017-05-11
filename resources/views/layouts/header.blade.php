<?php
$user = Auth::user();
$gold_exch = \DB::table('tbl_etc_data')->select('meta_key','meta_value')->where('meta_key','=','usd_to_gold')->get();
$a = unserialize('a:12:{s:17:"notification_type";s:12:"p2p-incoming";s:6:"amount";s:6:"570.96";s:8:"datetime";s:20:"2017-05-08T14:27:51Z";s:7:"codepro";s:5:"false";s:6:"sender";s:11:"41001000040";s:9:"sha1_hash";s:40:"fd4da4df835faf6833d039e506f109877069a806";s:17:"test_notification";s:4:"true";s:15:"operation_label";s:0:"";s:12:"operation_id";s:17:"test-notification";s:8:"currency";s:3:"643";s:5:"label";s:0:"";s:4:"_url";s:15:"/ya-mon/success";}');
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="description" content="" />
	<meta name="keywords" content="" />
	<link rel="icon" href="{{ URL::asset('favicon.ico') }}" type="image/x-icon" />
	<title>DragonHeart</title>

	<!-- build:css -->

	<link rel="stylesheet" href="{{ URL::asset('css/jquery.fancybox.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/jquery.formstyler.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/jquery.ui.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/jquery.ui.datepicker.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/0_reset.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/slick-theme.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/slick.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/jquery.jscrollpane.css') }}">

	<!-- add new file here -->

	<link rel="stylesheet" href="{{ URL::asset('css/zdev_0_basic.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/zdev_2.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/zdev_2_adapt.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/zdev_4.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/zdev_4_adapt.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/zdev_5.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/zdev_5_adapt.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/zdev_6.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/zdev_6_adapt.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/zdev_10.css') }}">
	<link rel="stylesheet" href="{{ URL::asset('css/zdev_10_adapt.css') }}">
	<!-- endbuild -->

	<script src="{{ URL::asset('js/jquery-2.min.js') }}"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
	<!-- SCRIPTS -->

	<!-- build:js -->
	<script type="text/javascript" src="{{ URL::asset('js/device.js') }}" ></script>
	<script type="text/javascript" src="{{ URL::asset('js/jquery.fancybox.pack.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('js/jquery.formstyler.min.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('js/jquery.validate.min.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('js/maskInput.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('js/slick.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('js/jquery.mousewheel.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('js/mwheelIntent.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('js/jquery.jscrollpane.min.js') }}"></script>


	<script type="text/javascript" src="{{ URL::asset('js/scenario.js') }}"></script>
	<!-- endbuild -->

	<!-- <script src="@{{ URL::asset('js/validate_script.js') }}"></script>-->

	<script src='https://www.google.com/recaptcha/api.js'></script>

	<!--[if lt IE 10]>
	<link rel="stylesheet" href="https://rawgit.com/codefucker/finalReject/master/reject/reject.css" media="all" />
	<script type="text/javascript" src="https://rawgit.com/codefucker/finalReject/master/reject/reject.min.js"></script>
	<![endif]-->
	<!--[if lt IE 9]>
	<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

</head>
<body>
<div class="global-wrapper">

<div class="new-popups-block disable-select">
	<div class="troll-popup" id="card-info">
		<div class="close-this"></div>
		<div class="content-card-info"></div>
	</div>
	<!-- Окно custom Confirm -->
	<div class="troll-popup" id="confirm-popup-with-number-input">
		<div class="close-this"></div>
		<div class="popup-content-wrap">
			<h5>Подтвердите действие:</h5>
			<div class="pop-row">
			   <div class="confirm-txt"></div>
			</div>
			<div class="pop-row">
				<div class="input-type-number">
					<input name="quant" type="text"  required="required" autocomplete="off" value="1" min="0">
					<div class="increment clckAnim"></div>
					<div class="decrement clckAnim"></div>
				</div>
			</div>
			<div class="pop-row">
				<a class="button-troll" data-value="true" href="#"><b>Подтвердить</b></a>
				<a class="button-troll" data-value="false" href="#"><b>Отменить</b></a>

			</div>
		</div>
	</div>
	<div class="troll-popup" id="confirm-popup">
		<div class="close-this"></div>
		<div class="popup-content-wrap">
			<h5>Подтвердите действие:</h5>
			<div class="pop-row">
				<div class="confirm-txt"></div>
			</div>

			<div class="pop-row">
				<a class="button-troll" data-value="true" href="#"><b>Подтвердить</b></a>
				<a class="button-troll" data-value="false" href="#"><b>Отменить</b></a>

			</div>
		</div>
	</div>
	<!-- Окно покупки пермиум аккаунта-->
	<div class="troll-popup" id="buyingPremium">
		<div class="close-this"></div>
		<div class="popup-content-wrap">
			<h5>Покупка Премиум аккаунта</h5>
			{{ Form::open(['route' => 'user-buying-prem-acc', 'method' => 'POST']) }}
			{{ Form::hidden('_method', 'PUT') }}

			@foreach($exchange_options as $key => $value)
			<div class="pop-row">
				<label>
					<span>{{ $value->meta_value }}</span>
					<img class="resource" src="{{ URL::asset('images/header_logo_gold.png') }}" alt="">

					{{ Form::radio('premiumType', $value->meta_key) }}
					<span class="pseudo-radio"></span>
					<span>{{ $value->meta_key_title }}</span>
				</label>
			</div>
			@endforeach

			<div class="pop-row">
				<a href="#" class="button-troll" type="submit"><b>Купить</b></a>
			</div>
			{{ Form::close() }}
		</div>
	</div>

	<!-- Окно успешного действия -->
	<div class="troll-popup" id="successEvent">
		<div class="close-this"></div>
		<div class="popup-content-wrap">
			<h5>Результат:</h5>
			<div class="pop-row">
				<span class="result">

				</span>
			</div>
		</div>
	</div>

	<!-- Окно ошибки -->
	<div class="market-buy-popup troll-popup" id="buyingCardOrmagic">
		<div class="close-this"></div>
		<input name="_token" type="hidden" value="{{ csrf_token() }}">

		<div class="popup-content-wrap">

		</div>
	</div>
	<!-- Покупка серебра -->
	<div class="market-buy-popup troll-popup" id="buySomeSilver">
		<div class="close-this"></div>
		<div class="popup-content-wrap">
			<h5>Обмен</h5>
			<div class="pop-row">

				<img class="resource" src="{{ URL::asset('images/header_logo_gold.png') }}" alt="">

				<div class="input-type-number">
					<input name="goldToSell" type="text"  required="required" autocomplete="off" value="0" min="0">
					<div class="increment clckAnim"></div>
					<div class="decrement clckAnim"></div>
				</div>
				<span> = </span>
				<img class="resource" src="{{ URL::asset('images/header_logo_silver.png') }}" alt="">
				<b id="silverToBuy">0</b>
			</div>
			<div class="pop-row">
				<div class="error">
					<span>Ошибка.Ведите числовое значение</span>
				</div>
			</div>
			<div class="pop-row">
				<a class="button-troll" href="#"><b>Обменять</b></a>
			</div>

		</div>
	</div>
	<!-- Покупка золота -->
	<div class="market-buy-popup troll-popup" id="buySomeGold">
		<div class="close-this"></div>
		<div class="popup-content-wrap">
			<h5>Пополнение</h5>
			<div class="pop-row">
				<p>Пополнение баланса золота, золото зачисляеться автоматически после оплаты.</p>
			</div>
			<div class="pop-row" style="display:flex; align-items: center; justify-content: space-around">
				<div class="input-type-number">
					<input name="goldToBuy" type="number" required="required" autocomplete="off" value="{{ $gold_exch[0]->meta_value }}" min="{{ $gold_exch[0]->meta_value }}" step="{{ $gold_exch[0]->meta_value }}">
					<div class="increment clckAnim"></div>
					<div class="decrement clckAnim"></div>
				</div>
				<img class="resource" src="{{ URL::asset('images/header_logo_gold.png') }}" alt="">

				<span> = </span>
				<div class="input-type-number">
					<input name="goldToUsd" type="number" required="required" autocomplete="off" value="1" min="1" step="1">
					<div class="increment clckAnim"></div>
					<div class="decrement clckAnim"></div>
				</div>
				<span>&nbsp;$</span>
			</div>
			<div class="pop-row">
				<div class="error">
					<span>Ошибка.Ведите числовое значение</span>
				</div>
			</div>
			@if($user)
				<div class="pay-buttons-wrap">
					<label style="background-image: url(https://money.yandex.ru/b/_/qTOToHuhW55WKrD_UAZ0KUl6PiI.svg)" title="Оплата из кошелька в Яндекс.Деньгах" class="active">
						<input type="radio" name="paymentType" value="PC" checked="checked">
					</label>
					<label style="background-image: url(https://money.yandex.ru/b/_/FRYVMdQY0d--xOk1KmZTYRe4LAc.svg)" title="С банковской карты;">
						<input type="radio" name="paymentType" value="AC">
					</label>
				</div>
				<div class="pop-row">
					<a class="button-troll" href="#"><b>Пополнить</b></a>
				</div>
			@endif
		</div>
	</div>
	<!-- Покупка Энергии-->
	<div class="market-buy-popup troll-popup" id="buySomeEnergy">
		<?php
		$exchange_options = \DB::table('tbl_etc_data')->select('label_data','meta_key','meta_value')->where('label_data', '=', 'exchange_options')->get();
		$prices = [];
		foreach($exchange_options as $key => $value){
			$prices[$value->meta_key] = $value->meta_value;
		}
		?>
		<div class="close-this"></div>
		<div class="popup-content-wrap">
			<h5>Обмен</h5>
			<div class="pop-row">
				<img class="resource" src="{{ URL::asset('images/header_logo_gold.png') }}" alt="">
				<span>{{ $prices['gold_to_100_energy'] }}</span><span> = </span>
				<img class="resource" src="{{ URL::asset('images/header_logo_lighting.png') }}" alt="">
				<span>100</span>
			</div>
			<div class="pop-row hr-bot">
				<a class="button-troll" data-name="gold_to_100_energy" href="#"><b>Обменять</b></a>
			</div>
			<div class="pop-row">
				<img class="resource" src="{{ URL::asset('images/header_logo_silver.png') }}" alt="">
				<span>{{ $prices['silver_to_100_energy'] }}</span><span> = </span>
				<img class="resource" src="{{ URL::asset('images/header_logo_lighting.png') }}" alt="">
				<span>100</span>
			</div>
			<div class="pop-row hr-bot">
				<a class="button-troll" data-name="silver_to_100_energy" href="#"><b>Обменять</b></a>
			</div>
			<div class="pop-row">
				<img class="resource" src="{{ URL::asset('images/header_logo_gold.png') }}" alt="">
				<span>{{ $prices['gold_to_200_energy'] }}</span><span> = </span>
				<img class="resource" src="{{ URL::asset('images/header_logo_lighting.png') }}" alt="">
				<span>200</span>
			</div>
			<div class="pop-row hr-bot">
				<a class="button-troll" data-name="gold_to_200_energy" href="#"><b>Обменять</b></a>
			</div>
			<div class="pop-row">
				<img class="resource" src="{{ URL::asset('images/header_logo_silver.png') }}" alt="">
				<span>{{ $prices['silver_to_200_energy'] }}</span><span> = </span>
				<img class="resource" src="{{ URL::asset('images/header_logo_lighting.png') }}" alt="">
				<span>200</span>
			</div>
			<div class="pop-row hr-bot">
				<a class="button-troll" data-name="silver_to_200_energy" href="#"><b>Обменять</b></a>
			</div>
		</div>
	</div>
	<!-- Меню->Играть всплывающее окно -->
	<div id="choose-rase-block" class="troll-popup">

		<div class="close-this"></div>
		<div class="popup-content-wrap">
			<h5>Выберите фракцию</h5>
			<div class="pop-row">
				{{ Form::open(['route' => 'user-active-games', 'method' => 'POST', 'id' => 'gameForm']) }}
				<input type="hidden" name="currentRace">
				<ul>
					@foreach($fractions as $key => $value)
						@if($value['type'] == 'race')

							<li>
								<div class="description">
									<div class="txt"> {!! $value['description'] !!}</div>

								</div>

								<div class="image-conteiner">
									<img src="{{ URL::asset('img/fractions_images/'.$value['img_url']) }}" alt="">
								</div>
								<a class="button-troll" data-name="{{ $value['slug'] }}" href="#"><b>{{ $value['title'] }}</b></a>
							</li>

						@endif
					@endforeach
				</ul>
				{{ Form::close() }}
			</div>
		</div>
	</div>
	<div class="afterloader">
		<img src="{{ URL::asset('images/379.gif') }}" alt="">
	</div>
	<div class="tyman"></div>
</div>