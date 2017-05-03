/**
 * Created by nickolaygotsliyk on 17.10.16.
 */
function radioPseudo() {
	$(document).on('click', '.popup-content-wrap .switch-user-turn-wrap label', function () {
		if($(this).find('input').prop('checked')){
			$('.popup-content-wrap .switch-user-turn-wrap .pseudo-radio').removeClass('active');
			$(this).find('.pseudo-radio').addClass('active');
		}
	});
	$(document).on('click', '#chooseUser label', function () {
		if($(this).find('input').prop('checked')){
			$('#chooseUser .pseudo-radio').removeClass('active');
			$(this).find('.pseudo-radio').addClass('active');
		}
	});
}
function openTrollPopup(popup) {
	popup.addClass('show');
	$('.new-popups-block').addClass('show');
}
//попап результатов
function resultPopupShow(message) {
	$('#successEvent').find('.result').text(message);
	openTrollPopup($('#successEvent'));
}
function closeAllTrollPopup() {
	$('div.troll-popup').removeClass('show');
	$('.new-popups-block').removeClass('show');

}
function clickCloseCross() { //закрыть попап
	$('.close-this').click(function (e) {
		e.preventDefault();
		$(this).closest('div.troll-popup').removeClass('show');
		if($('div.troll-popup.show').length<=0){closeAllTrollPopup();}
	});
}
function showPreloader() {
	$('.afterloader').css({'opacity':'1', 'z-index':'2222'});
}
function hidePreloader() {
	$('.afterloader').css({'opacity':'0', 'z-index':'-1'});
}

// ajax error message
function ajaxErrorMsg(jqXHR, exception) {
	var msg = '';
	if (jqXHR.status === 0) {
		msg = 'Not connect.\n Verify Network.';
	} else if (jqXHR.status == 404) {
		msg = 'Requested page not found. [404]';
	} else if (jqXHR.status == 500) {
		msg = 'Internal Server Error [500].';
	} else if (exception === 'parsererror') {
		msg = 'Requested JSON parse failed.';
	} else if (exception === 'timeout') {
		msg = 'Time out error.';
	} else if (exception === 'abort') {
		msg = 'Ajax request aborted.';
	} else {
		msg = 'Uncaught Error.\n' + jqXHR.responseText;
	}
	resultPopupShow(msg);
}

//Формирование стола по пользовательским данным
function buildRoomPreview(userData) {
	//очищение списков поп-апа выбора карт
	$('#selecthandCardsPopup #handCards').empty();

	//Отображаем данные пользователей

	for( var key in userData ) {

		if( key != $('.user-describer').attr('id') ) {
			$('#selecthandCardsPopup .opponent-fraction span').text(userData[key]['deck_title']);
			$('#selecthandCardsPopup .opponent-description span').text(userData[key]['deck_descr']);
			window.userImgData['opponent'] = userData[key]['deck_img'];
		} else {
			window.userImgData['user'] = userData[key]['deck_img'];
		}

		if( $('.convert-right-info #'+key).length < 1 ) {
			//Установить никнейм оппонета в правом сайдбаре
			$('.convert-right-info .oponent-describer').attr('id',key);
			$('.rounds-counts.oponent .rounds-counts-title').text(key);
			//Установить никнейм оппонента в отображение колоды
			$('.field-battle .cards-bet #card-give-more-oponent').attr('data-user', key);
			//Установить логин оппонента в его поле битвы
			$('.convert-battle-front .oponent').attr('data-user', key);
		}

		//Создать описание пользователей
		createUserDescriber(key, userData[key]['img_url'], userData[key]['deck_title']);
		//Количество карт в колоде
		$('.convert-left-info .cards-bet ul[data-user='+key+'] .deck .counter').text(userData[key]['deck_count']);
		//Если у пользователя есть магические эффекты
		if(userData[key]['magic'].length > 0){
			//Вывод текущей магии пользователей
			$('.convert-right-info #' + key + ' .useless-card').children().children('.magic-effects-wrap').empty();
			createUserMagicFieldCards(key, userData[key]['magic']);
		}
		//Если пользователь не готов (не выбраны карты для игры)
		if( 0 == parseInt(userData[key]['ready'])){
			console.log('is not_ready')
			if (userData[key]['hand'].length > 0) {
				//Вывод карт руки и колоды
				$('#selecthandCardsPopup h5 span').text(userData[key]['can_change_cards']);
				for(var i=0; i<userData[key]['hand'].length; i++){
					$('#selecthandCardsPopup #handCards').append(createFieldCardView(userData[key]['hand'][i], userData[key]['hand'][i]['strength'], true));
				}
				//Изменение ширины карт при выборе Карт "Руки"
				hidePreloader();
				openTrollPopup($('#selecthandCardsPopup'));
				if(userData[key]['current_deck'] == 'cursed'){
					var logins = '';
					for(var login in userData){
						logins += '<label><input type="radio" name="userTurn" value="'+login+'"><div class="pseudo-radio"></div>'+login+'</label>';
					}
					if(!($('#selecthandCardsPopup .for_cursed .switch-user-turn-wrap').length >0)){
						$('#selecthandCardsPopup .for_cursed').append('<div class="switch-user-turn-wrap">Выберете, кому отдать первый ход: <div>'+logins+'</div></div>');
					}
				}
				//Пользователь поменял карты
				userChangeDeck(userData[key]['can_change_cards']);
			}
		}
	}
}

function userWantsChangeCard() {
	$(document).on('click', '#selecthandCardsPopup #handCards .change-card', function(){
		showPreloader();
		var card = $(this).parent().attr('data-cardid');
		conn.send(
			 JSON.stringify({
				 action: 'changeCardInHand',
				 ident: ident,
				 card: card,
			 })
		);
	});
}

function userChangeDeck(can_change_cards) {
	//Смена карт при старте игры
	$(document).on('click', '#handCards li .content-card-item-main', function(event){
		if((!$(event.target).hasClass('ignore')) && event.which==1){
			if(parseInt($('#selecthandCardsPopup .popup-content-wrap h5 span').text()) > 0){
				var button =$(document.createElement('div'));
				button.addClass('change-card').html('<div class="change-card-start"><b>Сменить</b></div>');

				if($(this).hasClass('disactive')){
					$(this).removeClass('disactive');
					$(this).closest('li').find('.change-card').remove();

				}else{
					if($('#handCards li.disactive').length < can_change_cards){
						$(this).addClass('disactive').closest('li').append(button);
					}

				}
			}else{return;}
		}
	});

	if(parseInt($('#selecthandCardsPopup .popup-content-wrap h5 span').text()) > 0){
		userWantsChangeCard();
	}

	//Пользователь Выбрал карты и нажал "ОК"
	$('#selecthandCardsPopup .acceptHandDeck').click(function(e){
		e.preventDefault();
		userChangeCards();
		clearInterval(TimerInterval);
	});
}

function userChangeCards() {
	showPreloader();

	var token = $('.market-buy-popup input[name=_token]').val().trim();
	var turn = '';
	if($('#selecthandCardsPopup input[name=userTurn]').length > 0){
		turn = (typeof $('#selecthandCardsPopup input[name=userTurn]:checked').val() == "undefined")? $('.convert-right-info .user-describer').attr('id'): $('#selecthandCardsPopup input[name=userTurn]:checked').val();
	}

	var time = parseInt($('#selecthandCardsPopup .timer-in-popup span[data-time=minute]').text()) * 60 + parseInt($('#selecthandCardsPopup .timer-in-popup span[data-time=seconds]').text());

	$.ajax({
		url:    '/game_user_change_cards',
		type:   'PUT',
		headers:{'X-CSRF-TOKEN':token},
		data:	{time:time},
		success:function(data){
			data = JSON.parse(data);
			$('#allies-deck .jspPane').empty().append(createDeckCardPreview(data[$('.user-describer').attr('id')]['deck'].length, true, data[$('.user-describer').attr('id')]['deck']));
			$('.user-card-stash #sortableUserCards').empty();
			for(var i=0; i< data[$('.user-describer').attr('id')]['hand'].length; i++){
				$('.user-card-stash #sortableUserCards').append(createFieldCardView(data[$('.user-describer').attr('id')]['hand'][i], data[$('.user-describer').attr('id')]['hand'][i]['strength'], true));
			}
			conn.send(
				JSON.stringify({
					action: 'userReady',
					ident: ident,
					turn: turn
				})
			);
			console.log('user send Ready');
			closeAllTrollPopup();
			hidePreloader();
			calculateRightMarginCardHands();
		},
		complete:function () {
			animateHandCard();
		}
	});
}

function animateHandCard() {
	var delay = 500;

	$('#sortableUserCards li').addClass('transitiontime').removeClass('tramsitioned').css({
		'-webkit-animation-duration': delay+'ms',
		'animation-duration': delay+'ms',
		'left':'0px',
		'transform': 'none',
		'transition-delay': '0s'
	});

	var timeout3 = 0;

	$('#sortableUserCards li').each(function () {
		var k = $(this);
		setTimeout(function () {
			k.addClass('notransition');
			setTimeout(function () {
				k.removeClass('transitiontime notransition');
			},delay);
		},timeout3);
		timeout3+=100;
	});

}

function createUserDescriber(userLogin, user_img, userRace) {
	if ( user_img != '' ) {
		$('.convert-right-info #'+userLogin+' .stash-about .image-oponent-ork').css({'background':'url(/img/user_images/'+user_img+') 50% 50% no-repeat'});
	}
	$('.convert-right-info #'+userLogin+' .stash-about .naming-oponent .name').text(userLogin);
	$('.convert-right-info #'+userLogin+' .stash-about .naming-oponent .rasa').text(userRace);
}

function createUserMagicFieldCards(userLogin, magicData) {
	for(var i=0; i<magicData.length; i++){
		$('.convert-right-info #' + userLogin ).find('.magic-effects-wrap').append(createMagicEffectView(magicData[i]));
	}
}

//Создание отображения карты в списке
function createFieldCardView(cardData, strength, titleView) {

	var immune=false;
	var full_immune = false;

	cardData.actions.forEach(function(item) {
		if ( item.hasOwnProperty('immumity_type') ) {

			if ( item.immunity_type == "1" ) {
				full_immune = true;
			} else {
				immune = true;
			}
		}
	});

	return '' +
		'<li class="content-card-item disable-select loading animation" data-cardid="'+cardData['id']+'" data-relative="'+cardData['type']+'" data-immune=' + immune + ' data-full-immune=' + full_immune + ' >'+
		createCardDescriptionView(cardData, strength, titleView)+
		'</li>';
}

//Созднаие Отображения маг. еффекта
function createMagicEffectView(magicData) {
	return  '' +
		'<li data-cardid="' + magicData['id'] + '">' +
		'<img src="/img/card_images/' + magicData['img_url']+'" alt="' + magicData['slug'] +'" title="' + magicData['title'] +'">'+
		'<div class="magic-description">'+ magicData['description']+'</div>'+
		'<div class="info-img"><img class="ignore" src="/images/info-icon.png" alt=""><span class="card-action-description">Инфо о магии</span></div>'+
		'</li>';
}

//Создание отображения карты
function createCardDescriptionView(cardData, strength, titleView) {

	var result = '<div class="content-card-item-main';
	if(cardData['type'] == 'special'){
		result += ' special-type';
	}
	if(cardData['is_leader'] == 1){
		result += ' leader-type';
	}

	switch (cardData['fraction']) {
		case 'highlander':	result += ' highlander-race'; break;
		case 'monsters':	result += ' monsters-race'; break;
		case 'undead':		result += ' undead-race'; break;
		case 'cursed':		result += ' cursed-race'; break;
		case 'knight':		result += ' knight-race'; break;
		case 'forest':		result += ' forest-race'; break;
		default:
			if(cardData['type'] == 'neutrall'){result += ' neutrall-race';}
	}
	result +=' " style="background-image: url(/img/card_images/'+cardData['img_url']+')" data-leader="'+cardData['is_leader']+'" data-type="'+cardData['type']+'" data-weight="'+cardData['weight']+'">' +
		'<div class="card-load-info card-popup"><div class="info-img"><img class="ignore" src="/images/info-icon.png" alt=""><span class="card-action-description">Инфо о карте</span></div>';
	if(cardData['is_leader'] == 1){
		result += '<div class="leader-flag"><span class="card-action-description">Карта Лидера</span></div>';
	}
	result +='<div class="label-power-card"><span class="label-power-card-wrap"><span class="buff-debuff-value"></span><span class="card-current-value">'+strength+'</span></span><span class="card-action-description">';
	if(cardData['type'] == 'special'){
		result += 'Специальная карта';
	}else{
		result += 'Сила карты';
	}
	result +=    '</span></div>' +
		'<div class="hovered-items">' +
			'<div class="card-game-status">' +
				'<div class="card-game-status-role">' ;
				if(cardData['type'] != 'special'){
					for (var j = 0; j < cardData['row_txt'].length; j++) {
						result +='<img src="'+cardData['row_txt'][j].image+'" alt=""><span class="card-action-description">'+cardData['row_txt'][j].title+'</span>';
					}
				}

	result += '</div><div class="card-game-status-wrap">';
	if(cardData['action_txt'].length>0){
		for (var i = 0; i < cardData['action_txt'].length; i++) {
			result = result + '<span class="card-action" style="animation-delay: '+ (i + 0.5) +'s;"><img src="' + cardData['action_txt'][i].img+'" alt=""><span class="card-action-description">'+cardData['action_txt'][i].title+'</span></span>';

		}
	}

	var cardDescription = '<div class="card-description-hidden"><div class="jsp-cont-descr">' +
							//'<p class="txt">'+cardData['descript']+'</p></div></div> '+
							cardData['descript']+'</div></div> ';

	if ( titleView == 'without-description' ) {
		cardDescription = '';
	}

	result = result + '</div>' +
		'</div>' +
		'<div class="card-name-property"><p>'+cardData['title']+'</p></div>' + cardDescription +
		'</div>' +
		'</div>' +
		'</div>';

	return result;
}

//Информация о карте
function infoCardStart() {
	$(document).on('click', '.info-img',function () {
		var popup = $('#card-info');
		$('#card-info .content-card-info').empty();
		popup.removeClass('mdesc');
		if($(this).closest('ul').hasClass('magic-effects-wrap')){
			popup.addClass('mdesc');
			$(this).closest('li').clone().appendTo('#card-info .content-card-info');
			openTrollPopup(popup);
		}else{
			var content =  $(this).closest('.content-card-item-main').parent().html();
			popup.find('.content-card-info').html(content);
			openTrollPopup(popup);
			setTimeout(function () {
				var jsp = popup.find('.jsp-cont-descr').jScrollPane();
			}, 100);
		}
	});
}

//Функция проведения действия картой / МЭ / Пас
function userMakeAction(conn, turnDescript, allowToAction) {
	$('.convert-battle-front .convert-stuff, .mezhdyblock .bor-beutifull-box').unbind();
	if(allowToAction){

		$('.convert-battle-front .convert-stuff, .mezhdyblock .bor-beutifull-box').on('click', '.active', function(){
			clearInterval(TimerInterval);
			var time = parseInt($('.info-block-with-timer span[data-time=minute]').text()) * 60 + parseInt($('.info-block-with-timer span[data-time=seconds]').text());

			if ( $('.summonCardPopup').hasClass('show') ) {
				var card = $('#summonWrap li').attr('data-cardid');
				$('.summonCardPopup').removeClass('show');
			} else {
				var card = $('#sortableUserCards li.active').attr('data-cardid');
			}

			var magic = $('.user-describer .magic-effects-wrap .active').attr('data-cardid');
			var BFData = '{"row":"'+$(this).attr('id')+'", "field": "'+$(this).parents('.convert-cards').attr('id')+'"}';
			if(typeof magic != "undefined"){
				card = '';
			}else{
				magic = '';
			}

			if(allowToAction){
				conn.send(
					JSON.stringify({
						action: 'userMadeCardAction',
						ident: ident,
						card: card,
						magic: magic,
						BFData: JSON.parse(BFData),
						source: turnDescript['cardSource'],
						timing: time
					})
				);
				allowToAction = false;
			}
		});
		//Пользователь нажал "Пас"
		$('.buttons-block-play button[name=userPassed]').unbind();
		$('.buttons-block-play button[name=userPassed]').click(function(){
			if ( allowToAction ) {
				clearInterval(TimerInterval);
				var time = parseInt($('.info-block-with-timer span[data-time=minute]').text()) * 60 + parseInt($('.info-block-with-timer span[data-time=seconds]').text());
				conn.send(
					JSON.stringify({
						action: 'userPassed',
						ident: ident,
						timing: time
					})
				);
				allowToAction = false;
			}
		});
	}
}

function cardCase(turnDescript, allowToAction) {
	hidePreloader();
	$('#sortableUserCards li').unbind();

	if( (turnDescript['cardSource'] == 'hand') && (allowToAction) ){
		$('#sortableUserCards li').click(function(event){
			if((!$(event.target).hasClass('ignore')) && event.which==1){
				$('.user-describer .magic-effects-wrap li').removeClass('active');
				if($(this).hasClass('active')){
					//$(this).removeClass('active');
					clearRowSelection();
				}else{
					$(this).parents('ul').children('li').removeClass('active');
					$(this).addClass('active');
				}
				if($(this).hasClass('active')){
					showCardActiveRow($(this).attr('data-cardid'), 'card', conn, ident);
				}
			}
		});

		$('.user-describer .magic-effects-wrap li').unbind();
		$('.user-describer .magic-effects-wrap li:not(.disactive)').click(function(event){
			if((!$(event.target).hasClass('ignore')) && event.which==1){
				$('#sortableUserCards li').removeClass('active');
				if($(this).hasClass('active')){
					//$(this).removeClass('active');
					clearRowSelection();
				}else{
					$(this).parents('ul').children('li').removeClass('active');
					$(this).addClass('active');
				}
				if($(this).hasClass('active')){
					showCardActiveRow($(this).attr('data-cardid'), 'magic', conn, ident);
				}
			}
		});
	}
	calculateRightMarginCardHands();
}

//END OF cardCase

//Отображение активных полей действия карты
function showCardActiveRow( card, type, conn, ident ) {

	if ( type == 'card' ) {

		var url = '/game_get_card_data';

	} else {

		var url = '/game_get_magic_data';

	}

	$.ajax({
		url:	url,
		type:	'GET',
		data:	{ card: card },
		success: function(data) {

			data = JSON.parse(data);

			var dataType = ''; // Тип карты race или special
			var dataStrength = ''; //Сила карты

			if ( type == 'card' ) {

				dataType = 'data-type="'+data['type']+'"';
				dataStrength = '<div class="label-power-card"><span class="label-power-card-wrap"><span class="buff-debuff-value"></span><span class="card-current-value">'+data['strength']+'</span></span></span></div>';

			}

			clearRowSelection();//Очистить активные ряды действия карты

			//Если карта
			if ( type == 'card' ) {
				//Если тип карты определен
				if ( typeof data['type'] != "undefined" ) {

					if ( data['type'] == 'special' ) {
						//Для "Специальных" карт
						for ( var i in data['actions'] ) {
							var action = ''+data['actions'][i]['action'];
							//По порядку действия: 9 - "Одурманивание", 11 - "Печаль", 19 - "Убийца"
							if ( (action == '9') || (action == '11') || (action == '19') ) {
								illuminateOpponent(); //Подсветить поле оппонента
							}
							//4 - "Воодушевление", 6 - "Исцеление", 7 - "Лекарь", 15 - "Призыв"
							if ( (action == '4') || (action == '6') || (action == '7') || (action == '15') ) {
								illuminateCustom({ parent: '.user', row: data['action_row'] });//Подсветить поля указанные в действии карты
							}
							//18 - "Страшный"
							if ( action == '18' ) {

								illuminateAside(); //Подсветить среднее поле

								var actionObj = data['actions'][i];

								var fieldDebuf = actionObj.fear_ActionRow;
								var params = {
									debuff: true,
									debuffRow: fieldDebuf
								};

								illuminateCustom(params); // подсветить поля дебафа

							}
							//10 - "Перегруппировка"
							if ( action == '10' ) {
								illuminateSelf();//Подсветить свое поле
							}
						}
					} else {//Для карт-воинов

						//Если есть у карты особые действия
						if ( data['actions'].length > 0 ) {

							for ( var i in data['actions'] ) {

								var parent = '.user';
								var params = {};

								var action = ''+data['actions'][i]['action'];

								if ( action == '20' ) {//Действие "Шпион"/"Разведчик"

									//spy_fieldChoise = 0 - подсветка на своем поле; 1 - подсветка на поле оппонента

									if ( data['actions'][i]['spy_fieldChoise'] == '0' ) {
										parent = '.user';
									} else {
										parent = '.oponent';
									}

								}
								else if ( action == '18' ) {

									params['debuff'] = true;
									params['debuffRow'] = data['actions'][i].fear_ActionRow;

								}
								else {
									parent = '.user';
								}
							}

							params['parent'] = parent;
							params['row'] = data['action_row'];

							illuminateCustom(params);//Подсветить поля указанные в действии карты с учетом поля spy_fieldChoise

						} else {

							illuminateCustom({ parent: '.user', row: data['action_row'] });//Подсветить поля указанные в действии карты

						}

					}
				} else {
					for ( var i in data['actions'] ) {

						var action = ''+data['actions'][i]['action'];

						if(action == '19'){
							//Действие "Убийца"
							illuminateCustom({ parent: '.oponent', row: data['action'][i]['killer_ActionRow'] });

						} else {

							illuminateOpponent();
							illuminateSelf();

						}
					}
				}
				//Активирован МЭ
			} else {

				if ( action == '11' ) {

					illuminateOpponent();

				} else {

					illuminateOpponent();
					illuminateSelf();

				}

			}
		}
	});
}
//END OF showCardActiveRow

//Функиця отправки выбраных карт для призыва на поле
function incomeOneCardSelection(card) {
	var content='<li class="content-card-item disable-select" data-cardid="'+card['id']+'" data-relative="'+card['type']+'">'+
		createCardDescriptionView(card, card['strength'])+
		'</li>';
		$('.summonCardPopup').removeClass('show');
		$('#summonWrap').html(content);
		$('.summonCardPopup').addClass('show');
}

function incomeCardSelection(conn, ident, turnDescript) {

	$('#selectNewCardsPopup .button-troll.acceptNewCards').click(function(e) {
		e.preventDefault();
		if ( $('#selectNewCardsPopup #handNewCards .glow') ) {
			createPseudoCard( $('#selectNewCardsPopup #handNewCards .glow') );
		} else {
			return;
		}
	});

	function createPseudoCard(obj) {
		$('#summonWrap').empty();
		$('.summonCardPopup').removeClass('show');
		obj.clone().appendTo('#summonWrap');
		$('.summonCardPopup').addClass('show');
		closeAllTrollPopup();
		finalAction();
	}

	function finalAction() {
		cardCase(turnDescript,false);
		var card = $('#selectNewCardsPopup #handNewCards .glow').attr('data-cardid');
		showCardActiveRow(card, 'card', conn, ident);
		conn.send(
			JSON.stringify({
				action: 'dropCard',
				ident: ident,
				card: card,
				player: turnDescript['playerSource'],
				deck: turnDescript['cardSource']
			})
		);

	}
}

//Функиця отправки в руку карт для перегруппировки
function cardReturnToHand(conn, ident) {
	$('#selectNewCardsPopup .button-troll.acceptRegroupCards').click(function(e){
		e.preventDefault();
		if($('#selectNewCardsPopup #handNewCards .glow')){
			var card = $('#selectNewCardsPopup #handNewCards .glow').attr('data-cardid');
			conn.send(
				JSON.stringify({
					action: 'returnCardToHand',
					ident: ident,
					card: card
				})
			);
			closeAllTrollPopup();
			//calculateRightMarginCardHands();
		}
	});
}

//Отмена подсветки ряда действий карты
function clearRowSelection() {

	$('.mezhdyblock .bor-beutifull-box #sortable-cards-field-more').removeClass('active');

	$('.convert-stuff .field-for-cards').each(function() {

		$(this).removeClass('active can-debuff');
		$(this).children('.fields-for-cards-wrap').children('.cards-row-wrap').children('li').removeClass('glow');

	});

}

//Подсветка рядов действия карты
function illuminateAside() {
	$('.mezhdyblock .bor-beutifull-box #sortable-cards-field-more').addClass('active');
}//Средний блок

function illuminateOpponent() {
	$('.oponent .convert-stuff .field-for-cards').addClass('active');
}//Поле оппонента

function illuminateSelf() {
	$('.user .convert-stuff .field-for-cards').addClass('active');
}//Свое поле

function illuminateCustom(params) {
	//Поле действия карты по-умолчанию
	var options = {};

	$.extend( options, params );
	if ( options.hasOwnProperty('parent') ) {
		for ( var i = 0; i < options.row.length; i++ ) {
			var field = intRowToField(options.row[i]);
			$('.convert-battle-front ' + options.parent + ' .convert-one-field ' + field).addClass('active');
		}
	}
	if ( options.hasOwnProperty('debuff') ) {
		options.debuffRow.forEach(function(item) {
			var field = intRowToField(item);
			$('.convert-battle-front .oponent .convert-one-field ' + field).addClass('can-debuff');
		});
	}
}

//Перевод значения названия поля в id ряда
function intRowToField(row) {

	var field;

	switch( row.toString() ) {

		case '0': field = '#meele'; break;
		case '1': field = '#range'; break;
		case '2': field = '#superRange'; break;
		case '3': field = '#sortable-cards-field-more';

	}

	return field;

}

//Пересчет Силы рядов
function recalculateBattleField() {
	var players = {
		oponent: {
			meele:0,
			range:0,
			superRange:0
		},
		user:{
			meele:0,
			range:0,
			superRange:0
		}
	};
	var total = {
		oponent:0,
		user:0
	};
	//подсчет силы на столе
	$('.convert-battle-front .convert-stuff .field-for-cards').each(function() {

		if( $(this).parents('.convert-cards').hasClass('user') ) {
			calc($(this), 'user');
		}else{
			calc($(this), 'oponent');
		}

		function calc(row, parent) {
			var dist = row.attr('id');
			var str = 0;
			row.find('ul.cards-row-wrap li').each(function () {
				str += parseInt($(this).find('.label-power-card-wrap .card-current-value').text());
			});
			players[parent][dist] = str;
			total[parent] +=str;
			$('.convert-cards.'+parent+' #'+dist).parent().next().text(str);
			$('.power-text-'+parent).text(total[parent]);
		}
	});
}

//Отображение колод пользователей
function recalculateDecks(result) {

	if ( typeof result.counts != "undefined" ) {
		//колода противника
		if ( typeof result.counts['opon_deck'] != "undefined" ) {
			if ( parseInt(result.counts['opon_deck']) > 0 ) {
				$('#card-give-more-oponent li[data-field=deck]').empty().append( createDeckCardPreview( result.counts['opon_deck'], false ) );
			} else {
				$('#card-give-more-oponent li[data-field=deck]').empty().append('<div class="nothinh-for-swap"></div>');
			}
		}
		//колода игрока
		if ( typeof result.counts['user_deck'] != "undefined" ) {
			if ( parseInt(result.counts['user_deck']) > 0 ) {
				if ( typeof result.user_deck != "undefined" ) {
					$('#allies-deck .jspPane').empty().append( createDeckCardPreview( result.counts['user_deck'], true, result.user_deck ) );
				}
				$('#card-give-more-user li[data-field=deck]').empty().append( createDeckCardPreview( result.counts['user_deck'], true ) );
			} else {
				$('#card-give-more-user li[data-field=deck]').empty().append('<div class="nothinh-for-swap"></div>');
			}
		}
		//отбой игрока
		if ( typeof result.counts['user_discard'] != "undefined" ) {
			if ( parseInt(result.counts['user_discard']) > 0 ) {
				if ( typeof result.user_discard != "undefined" ) {
					if ( $('#allies-discard .jspPane').length > 0 ) {
						$('#allies-discard .jspPane').empty().append( createDeckCardPreview( result.counts['user_discard'], true, result.user_discard ) );
					} else {
						$('#allies-discard .deck-cards-list').empty().append( createDeckCardPreview( result.counts['user_discard'], true, result.user_discard ) );
						$('#allies-discard .deck-cards-list').jScrollPane();
						var api = $('#allies-discard .deck-cards-list').data('jsp');
						var throttleTimeout;
						$(window).bind('resize', function () {
							if ( !throttleTimeout ) {
								throttleTimeout = setTimeout(function () {
									api.reinitialise();
									throttleTimeout = null;
								}, 50);
							}
						});
					}
					$('#card-give-more-user li[data-field=discard]').empty().append( createDeckCardPreview( result.counts['user_discard'], true ) );
				}
			} else {
				$('#card-give-more-user li[data-field=discard]').empty().append('<div class="nothinh-for-swap"></div>');
			}
		}
		//отбой противника
		if ( typeof result.counts['opon_discard'] != "undefined" ) {
			if (parseInt(result.counts['opon_discard']) > 0) {
				if($('#enemy-discard .jspPane').length > 0){
					$('#enemy-discard .jspPane').empty().append(createDeckCardPreview(result.counts['opon_discard'], false, result['opon_discard']));
				}else{
					$('#enemy-discard .deck-cards-list').empty().append(createDeckCardPreview(result.counts['opon_discard'], false, result['opon_discard']));
					$('#enemy-discard .deck-cards-list').jScrollPane();
					var api = $('#enemy-discard .deck-cards-list').data('jsp');
					var throttleTimeout;
					$(window).bind('resize', function () {
						if (!throttleTimeout) {
							throttleTimeout = setTimeout(function () {
								api.reinitialise();
								throttleTimeout = null;
							}, 50);
						}
					});
				}
				$('#card-give-more-oponent li[data-field=discard]').empty().append(createDeckCardPreview(result.counts['opon_discard'], false));
			}else{
				$('#card-give-more-oponent li[data-field=discard]').empty().append('<div class="nothinh-for-swap"></div>');
			}
		}

		if(typeof result.counts['opon_hand'] != "undefined"){
			$('.oponent-stats .greencard-num').text( result.counts['opon_hand'] );
		}

	}

	if ( typeof result.user_hand != "undefined" ) {
		$('.user-stats .greencard-num').text( result.user_hand.length );
	}
	hidePreloader();
}

function fieldBuilding(step_status, recalcCallback) {
	//Рука игрока
	if( typeof step_status != "undefined" ) {
		//убрать карту из руки
		if( (typeof step_status.played_card != "undefined") && (step_status.played_card['move_to']['user'].length > 0) ){
			$('#sortableUserCards .active').remove();
			$('#sortableUserCards li').removeClass('active');
			var card = step_status.played_card;
			var rowId = intRowToField(card['move_to']['row']);
			if ( card['move_to']['row'] != 3 ) {
				if ( card['card']['type'] == 'special' ) {
					if ( card['self_drop'] == 0 ) {
						$('.convert-battle-front #'+card['move_to']['player']+'.convert-cards '+rowId+' .image-inside-line').empty().append(createFieldCardView(card['card'], card['strength'], false));
					}
				} else {
					$('.convert-battle-front #'+card['move_to']['player']+'.convert-cards '+rowId+' .cards-row-wrap').append(createFieldCardView(card['card'], card['strength'], false));
				}
			} else {
				$('.mezhdyblock '+rowId).append(createFieldCardView(card['card'], card['strength'], false));
			}
		}

		if ( typeof step_status['added_cards'] != "undefined" ) {
			var player = $('.convert-cards[data-user='+$('.user-describer').attr('id')+']').attr('id');
			if ( typeof step_status.added_cards[player] != "undefined" ) {

				//добавление карт в руку
				if ( (typeof step_status.added_cards[player]['hand'] != "undefined") && (step_status.added_cards[player]['hand'].length > 0) ) {
					for ( var i in step_status.added_cards[player]['hand'] ) {
						$('.user-card-stash #sortableUserCards').append( createFieldCardView( step_status.added_cards[player]['hand'][i], step_status.added_cards[player]['hand'][i]['strength'], true ) );
						$('.user-card-stash #sortableUserCards li').last().addClass('added-by-effect waiting-for-animation');
					}
					console.log('adding');
					sortCards();
				}

				//Дополнительные карты
				for ( var row in step_status.added_cards[player] ) {
					if ( row != 'hand' ) {
						//убирание карт из руки
						var row_data = step_status.added_cards[player][row];
						for(var i in row_data){
							if(row_data[i]['destination'] == 'hand'){
								$('.user-card-stash #sortableUserCards li').each(function(){
									if($(this).attr('data-cardid') == row_data[i]['card']['id']){
										$(this).remove();
									}
								});
							}
						}
					}
				}
			}
			for(var player in step_status.added_cards){
				for(var row in step_status.added_cards[player]){
					if(row != 'hand'){
						for(var i in step_status.added_cards[player][row]){
							var rowId = intRowToField(row);
							var card = step_status.added_cards[player][row][i];
							$('.convert-battle-front #'+player+'.convert-cards '+rowId+' .cards-row-wrap').append(createFieldCardView(card['card'], card['strength'], false));
						}
					}
				}
			}
		}

		//удаление карт
		if ( typeof step_status.dropped_cards != "undefined" ) {
			for(var player in step_status.dropped_cards){
				for(var row in step_status.dropped_cards[player]){
					if(row == 'mid'){
						$('.mezhdyblock #sortable-cards-field-more').children().fadeOut(500,function(){
							$('.mezhdyblock #sortable-cards-field-more').empty();
						})
					}
					console.log('удаление карт');
					for(var i in step_status.dropped_cards[player][row]){
						var card = step_status.dropped_cards[player][row][i];
						switch(row) {
							case 'hand':
								var targetPlayer = $('.convert-cards[data-user=' + $('.user-describer').attr('id') + ']').attr('id');
								if(targetPlayer == player){
									$('.user-card-stash #sortableUserCards li[data-cardid="'+card['id']+'"]').fadeOut(500,function(){
										$('.user-card-stash #sortableUserCards li[data-cardid="'+card['id']+'"]').remove();
									})
								}
							break;
							case 'mid':
								$('.mezhdyblock #sortable-cards-field-more').children().fadeOut(500,function(){
									$('.mezhdyblock #sortable-cards-field-more').empty();
								})
							break;
							default:
								var rowId = intRowToField(row);

								if(card['type'] == 'special'){

									animationDeleteSpecialCard(player,rowId);

								}else{

									// Узнаю какие карты нужно удалить и даю им класс ready-to-die
									var currentCardDelate = $('.convert-battle-front #'+player+'.convert-cards '+rowId+' .cards-row-wrap li[data-cardid="'+card['id']+'"]:not(.ready-to-die)').first();
									currentCardDelate.addClass('ready-to-die');
									console.log('currentCardDelate',currentCardDelate);
								}
						}
					}
				}
			}
			// После всех циклов запускаю функцию анимации удаления карты

			if(typeof step_status.actions != "undefined"){
				step_status.actions.forEach(function(item){
					switch(item){
						case '9':
							//console.log("одурманивание");
							//animationBurningCardEndDeleting('fade');

						break;
						case '7':
							console.log("Лекарь");

						break;
						case '19':
							console.log("Убийца");
							animationBurningCardEndDeleting();
						break;
					}
				})
			}else{

				animationBurningCardEndDeleting();
			}
		}

		//Обновление силы карт
		if ( typeof recalcCallback === 'function' ) {
			recalcCallback(step_status);
		}

	}
	recalculateBattleField();
}

function recalculateCardsStrength(step_status) {

	if (typeof step_status.cards_strength != "undefined"){
		for (var player in step_status.cards_strength) {
			for (var row in step_status.cards_strength[player]) {
				for (var pos in step_status.cards_strength[player][row]) {
					var rowId = intRowToField(row);
					$('.convert-battle-front #'+player+'.convert-cards '+rowId+' .cards-row-wrap li:eq('+pos+') .label-power-card-wrap .card-current-value').text(step_status.cards_strength[player][row][pos]);
				}
			}
		}
	}

}

function sortCards() {
	var arrayToSort = {
		special: [],
		other: []
	};
	$('#sortableUserCards li').each(function(){
		if ( $(this).attr('data-relative') == 'special' ) {
			arrayToSort.special.push( $(this) );
		} else {
			var temp = {
				card: $(this),
				strength: parseInt( $(this).find('.label-power-card-wrap').text() )
			}
			arrayToSort.other.push(temp);
		}
	});
	arrayToSort.other.sort(function (a, b){
		return b.strength >= a.strength;
	});

	$('#sortableUserCards').empty();

	for (var i in arrayToSort.other) {
		$('#sortableUserCards').append(arrayToSort.other[i].card);
	}
	for (var i in arrayToSort.special) {
		$('#sortableUserCards').append(arrayToSort.special[i]);
	}
	calculateRightMarginCardHands();

	if ( $('.added-by-effect').length ) {
		cardMovingFromTo( 'user', 'deck', $('.added-by-effect').length );
	}

}

//Анимация возвтращения своих карт в в колоду - работает вместе с animateHandCard()
function animationCardReturnToOutage() {
	var outageHolder = $('#card-give-more-user [data-field="discard"]');
	var outageHolderLeft = outageHolder.offset().left;

	var transitionDelay = 0;
	var userCards = $('#sortableUserCards');
	userCards.find('li').addClass('tramsitioned');
	userCards.find('li').each(function(index,item){
		var positionLeft = +($(item).offset().left).toFixed(0);
		var shiftLeft = positionLeft - outageHolderLeft + 15;// 15 - корректировка на сдвиг скейлом
		$(item).css({
			'left':'-'+shiftLeft+'px',
			'transform': 'scale3d(0.7,0.7,0.7)',
			'transition-delay':transitionDelay+'s'
		})
		transitionDelay+=0.1;
	});
	var cardTransitionDuration = parseFloat(userCards.find('li').css('transition-duration'));
	var timeout = (cardTransitionDuration + transitionDelay)*1000;

	return timeout;
}

function animationBurningCardEndDeleting(action) {
	var cardAll = $('.content-card-item.ready-to-die');
	cardAll.each(function(index,elemet){
		var card = $(elemet);

		if (!card.parents('.field-for-cards').hasClass('overflow-visible') ) {

			card.parents('.field-for-cards').addClass('overflow-visible');
			card.parents('.convert-stuff').css({
				'z-index':'10'
			});

		}

		switch(action){
			case 'fade':
				card.removeClass('show');
				setTimeout(function() {
					card.remove();
				}, 500);
			break;
			default:
				//console.log('default');
				card.append('<span class="card-burning-item-main"><img src="/images/card-burning-item-main-2.gif" alt="" /></span');
				setTimeout(function(){
					card.addClass('card-burning');
					setTimeout(function(){
						card.find('.content-card-item-main').fadeOut(900,function(){
							setTimeout(function(){
								card.removeClass('card-burning');
								setTimeout(function(){

									if ( (cardAll.length - 1) == index ){
										card.parents('.field-for-cards').removeClass('overflow-visible');
										card.parents('.convert-stuff').removeAttr('style');
									}

									card.remove();

								},1000)
							},500)
						});
					},2500)
				},300)
		}

	})
}
//animationBurningCard($('.convert-cards .content-card-item[data-cardid="131"]'))

function animationDeleteSpecialCard(player,rowId) {

	var card = $('#'+player+'.convert-cards '+rowId+' .image-inside-line li'),
		userName = $('#'+player).attr('data-user'),
		otboy = $('.cards-bet [data-user="'+userName+'"] [data-field="discard"]'),
		otboyOffset = otboy.offset(),
		cardOffset = card.offset(),
		zIndexHolder = 0;

	setTimeout(function() {

		card.css({
			'position':'fixed',
			'width':'auto',
			'z-index':'1000',
			'transition':'opacity ease .4s',
			'transform':'translateZ(0)',
			'left':cardOffset.left+'px',
			'top':cardOffset.top - $(window).scrollTop()+'px'
		}).animate({
			left: otboyOffset.left,
			top: otboyOffset.top - $(window).scrollTop()
		},{
			duration: 2500,
			progress: function (animation, number,remainingMs) {
				if(number >= 0.65 && number <= 0.67){
					card.css({'opacity':'0'});
				}
			},
			start: function(){
				card.parents('.convert-stuff').css({'z-index':'2'})
				zIndexHolder = card.parent().css('z-index');
				card.parent().css({'z-index':'100'})
			},
			complete: function() {
				card.parents('.convert-stuff').removeAttr('style');
				card.parent().css({'z-index':zIndexHolder});
				card.fadeOut(500,function(){
					card.remove();
				})
			}
		  })

	}, 1000);

}
//animationDeleteSpecialCard('p1','#meele')

//Смена идентификатора хода пользователя
function changeTurnIndicator(login){
	if(login == $('.user-describer').attr('id')){
		$('.user-turn-wrap .turn-indicator').addClass('active');
	}else{
		$('.user-turn-wrap .turn-indicator').removeClass('active');
	}
}

//Создание отображения колоды
function createDeckCardPreview(count, is_user, deck, user_type) {
	var divClass = (is_user) ? 'card-my-init cards-take-more' : 'card-init';
	var deckBG = (is_user) ? 'user' : 'opponent';
	var deckBG = 'style="background-image: url(/img/fractions_images/'+window.userImgData[deckBG]+') !important"';
	var cardList = '';
	if(typeof deck != "undefined"){
		for(var i=0; i<deck.length; i++){
			cardList += createFieldCardView(deck[i], deck[i]['strength'], true);
		}
	}else{
		cardList += '<div class="'+divClass+'" '+deckBG+'><div class="card-otboy-counter deck">'+count+'</div></div>';
	}

	return cardList;
}

function buildBattleField(fieldData) {
	for(var fieldType in fieldData){
		if(fieldType == 'mid'){
			for(var i=0; i<fieldData['mid'].length; i++){
				$('.mezhdyblock #sortable-cards-field-more').append(createFieldCardView(fieldData['mid'][i]['card'], 0, false));
			}
		}else{
			for(var i=0; i<fieldData[fieldType].length; i++){
				var row = intRowToField(i);
				for(var j=0; j<fieldData[fieldType][i]['warrior'].length; j++){
					$('.convert-battle-front #'+fieldType+' .convert-stuff '+row+' .cards-row-wrap').append(createFieldCardView(fieldData[fieldType][i]['warrior'][j]['card'], fieldData[fieldType][i]['warrior'][j]['strength'], false));
				}
				if(fieldData[fieldType][i]['special'] != ''){
					$('.convert-battle-front #'+fieldType+' .convert-stuff '+row+' .image-inside-line').append(createCardDescriptionView(fieldData[fieldType][i]['special']['card'], 0, false));
				}
			}
		}
	}

	recalculateBattleField();//Пересчет значений силы
}

//Закрытие popup-окна
$('.market-buy-popup .close-popup').click(function() {
	$(this).parents('.market-buy-popup').hide();
});

recalculateBattleField();

//Показ попапа с картой которой ходит игрок( открываеться при начале хода )
function detailCardPopupOnStartStep(card, strength) {
	var holder = $('#card-start-step');
	holder.find('.content-card-info').empty();
	var popContent = createCardDescriptionView(card, strength, 'without-description');

	holder.find('.content-card-info').append(popContent);
	openSecondTrollPopup(holder,null);

	setTimeout(function(){
		closeSecondTrollPopup(holder,null);//закрываю попап с детальной инфой карты
		setTimeout(function(){
			showCardOnDesc();//показываю сыгранную карту на столе
		},500)
	},2000);
}

//открыть попап (даже если уже открыт еще однин)
function openSecondTrollPopup(id,customClass) {
	id.addClass('show troll-popup-custom');
	if(customClass != null){
		id.addClass(customClass);
	}
	$('.new-popups-block').addClass('show-second');
}
// закрыть попап по id
function closeSecondTrollPopup(id,customClass) {
	id.removeClass('show troll-popup-custom');
	if(customClass != null){
		id.removeClass(customClass);
	}
	$('.new-popups-block').removeClass('show-second');
}
//показать карты анимированно на столе
function showCardOnDesc(action) {

	var card = $('.content-card-item.loading');

	switch(action){
		case 'mini-scale':
			card.addClass('show').removeClass('loading');
			setTimeout(function(){
				if (!card.parents('.field-for-cards').hasClass('overflow-visible') ) {
					card.parents('.field-for-cards').addClass('overflow-visible');
				}

				card.addClass('mini-scale');
				setTimeout(function() {

					card.removeClass('mini-scale');

					setTimeout(function() {
						card.parents('.field-for-cards').removeClass('overflow-visible');
					}, 300);

				}, 500);
			},1000);
			break;
		default:
			card.addClass('show').removeClass('loading');
	}

}

// При открытом попапе если мы нажимаем на любую область документа - попап закрываеться
$(document).on('click',function(){
	if ( $('.troll-popup').hasClass('troll-popup-custom') ){
		var id = $('.troll-popup.troll-popup-custom').attr('id');
		closeSecondTrollPopup($('#'+id));
	}
});

//Показать попап при перегрупировке
function detailCardPopupOnOverloading(cardDetailOverloadingMarkup,card,strength,otherFunc) {
	console.log('detailCardPopupOnOverloading');
	var holder = $('#card-start-step');
	holder.find('.content-card-info').empty().append(cardDetailOverloadingMarkup);
	var popContent = createCardDescriptionView(card, strength, 'without-description');
	holder.find('.content-card-info').addClass('overloading-animation').append(popContent).end().addClass('overloading');
	openSecondTrollPopup(holder,null);

	setTimeout(function(){
		holder.find('.content-card-info').removeClass('overloading-animation');
		setTimeout(function(){
			closeSecondTrollPopup(holder,null);
			setTimeout(function(){

				holder.removeClass('overloading');

				if( otherFunc == 'show-and-delate-card' ){
					showCardOnDesc('mini-scale');
					animationBurningCardEndDeleting('fade');
				}
			},1000)
		},2000)
	},2000)
}

function secondTrollPopupCustomImgAndTitle(text,imgSrc) {
	var holder = $('#card-start-step');
	//holder.find('.content-card-info').empty();
	holder.find('.content-card-info').empty().append('<div class="custom-img-and-title-wrap"><div class="custom-title"><span>'+text+'</span></div><div class="custom-img"><img src="'+imgSrc+'" alt=""></div></div>');

	openSecondTrollPopup(holder,'custom-img-and-title');
	setTimeout(function(){
		closeSecondTrollPopup(holder);

	},2000);
}

//Отображение Колоды или Отбоя
$('.convert-left-info .cards-bet #card-give-more-user').on('click', '.card-my-init', function(){
	if($(this).css('pointer-events') != 'none'){
		$(this).children('ul.deck-cards-list').toggleClass('active');
	}
});

function convertTimeToStr(seconds) {
	if(seconds > timeOut){
		seconds = timeOut;
	}
	if(seconds >= 0){
		var time = {'m':Math.floor(seconds / 60), 's':seconds % 60};
		for(var i in time){
			if(time[i] < 10) time[i] = '0'+time[i];
		}
		$('.troll-popup .timer-in-popup, .info-block-with-timer').find('span[data-time=minute]').text(time['m']);
		$('.troll-popup .timer-in-popup, .info-block-with-timer').find('span[data-time=seconds]').text(time['s']);
	}
}

var TimerInterval;
function startTimer(login) {
	TimerInterval = setInterval(function () {
		var time = {'m':0, 's':0};
		time['m'] = parseInt($('.info-block-with-timer span[data-time=minute]').text());
		time['s'] = parseInt($('.info-block-with-timer span[data-time=seconds]').text());

		if(time['s'] == 0){
			time['m']--;
			time['s'] = 59;
		}else{
			time['s']--;
		}

		if( (time['m']<=0) && (time['s'] <= 0) ){
			clearInterval(TimerInterval);
			if($('#selecthandCardsPopup').hasClass('show')){
				userChangeCards();
			}else{
				if(login == $('.user-describer').attr('id')){
					conn.send(
						JSON.stringify({
							action: 'userPassed',
							ident: ident,
							timing: 0
						})
					);
				}
			}
		}
		for(var i in time){
			if(time[i] < 10) time[i] = '0'+time[i];
		}

		$('.troll-popup .timer-in-popup, .info-block-with-timer').find('span[data-time=minute]').text(time['m']);
		$('.troll-popup .timer-in-popup, .info-block-with-timer').find('span[data-time=seconds]').text(time['s']);
	}, 1000);
}

function phpTime(){
	return Math.floor(Date.now()/ 1000);
}

function count(arr){
	var i = 0;
	for(var key in arr) i++;
	return i;
}

function magicReview(result) {
	$('.convert-right-info .magic-effects-wrap li').removeClass('disactive');
	for(var player in result.magicUsage){

		var magicUsingTimes = (result.deck_slug == 'forest')? 2: 1;

		for(var activatedInRound in result.magicUsage[player]){
			if( (activatedInRound == result.round) || (count(result.magicUsage[player]) >= magicUsingTimes) ){
				$('.convert-right-info .magic-effects-wrap[data-player='+player+'] li').removeClass('active').addClass('disactive');
			}
			if(activatedInRound <= result.round){
				$('.convert-right-info .magic-effects-wrap[data-player='+player+'] li[data-cardid="'+result.magicUsage[player][activatedInRound]['id']+'"]').removeClass('active').addClass('disactive');
			}
			$('.convert-right-info .magic-effects-wrap[data-player='+player+'] li').removeClass('used');
			$('.convert-right-info .magic-effects-wrap[data-player='+player+'] li[data-cardid="'+result.magicUsage[player][activatedInRound]['id']+'"]').addClass('used');
		}
	}
}

var currentRound = 1;

// начать бой / вызов еффектов карт
function startBattle() {

	conn = new WebSocket('ws://' + socketResult['dom'] + ':8080');//Создание сокет-соединения
	console.log(conn);
	//Создание сокет-соединения
	conn.onopen = function (data) {
		console.log('Соединение установлено');
		conn.send(
			JSON.stringify({
				action: 'userJoinedToRoom',//Отправка сообщения о подключения пользователя к столу
				ident: ident
			})
		);
	};

	conn.onclose = function (event) {}
	conn.onerror = function (e) {
		alert('Socket error');
	};
	conn.onmessage = function (e) {
		var result = JSON.parse(e.data);

		console.log(result);
		var allowPopups = true;
		switch(result.message) {
			//Пользователи присоединились к игре
			case 'usersAreJoined':
				var token = $('.market-buy-popup input[name=_token]').val().trim();
				//Запрос на формирование изначальной колоды и руки пользователя
				$.ajax({
					url: '/game_start',
					type: 'PUT',
					headers: {'X-CSRF-TOKEN': token},
					data: {battle_id: result.battleInfo, time:phpTime()},
					success: function (data) {
						data = JSON.parse(data);
						if (data['message'] == 'success') {
							//Формирование данных пользователей и окна выбора карт
							buildRoomPreview(data['userData']);
							hidePreloader();
							console.log('room builded');
						}

						var expireTime = result.turn_expire - phpTime();
						convertTimeToStr(expireTime);
						if(expireTime > 0){
							startTimer();
						}

					},
					error: function (jqXHR, exception) {
						ajaxErrorMsg(jqXHR, exception);
					}
				});
			break;

			case 'userPassed':
				resultPopupShow('Противник пасует');
			break;

			case 'changeCardInHand':
				hidePreloader();
				$('#selecthandCardsPopup #handCards li[data-cardid="'+result.card_to_drop+'"]').addClass('animator-out');
				setTimeout(function(){
					$('#selecthandCardsPopup #handCards li[data-cardid="'+result.card_to_drop+'"] .disactive').closest('.content-card-item').remove();
					$('#selecthandCardsPopup h5 span').text(result.can_change_cards);
					$('#selecthandCardsPopup #handCards').append(createFieldCardView(result.card_to_hand, result.card_to_hand['strength'], true));
					$('#selecthandCardsPopup #handCards li:last-child').addClass('animator-in');
					$('#selecthandCardsPopup #handCards li:last-child').addClass('go');
					setTimeout(function () {
						$('#selecthandCardsPopup #handCards li:last-child').removeClass('animator-in go');
					},700);
				},700);
			break;

			//Все пользователи готовы к игре
			case 'allUsersAreReady':
				changeTurnIndicator(result.login);//смена индикатора хода
				recalculateBattleField();
				currentRound = result['round'];
				setTimeout(function () {
					var userImg=$('#card-give-more-user .card-my-init').attr('style');
					var a = userImg.match(/\/([^\/]*)('|")/);
					window.userImgData['user'] = a[1];
					var oponImg=$('#card-give-more-oponent .card-init').attr('style');
					if(typeof oponImg != "undefined"){
						var b = oponImg.match(/\/([^\/]*)('|")/);
						window.userImgData['opponent'] = b[1];
					}
				},300);
			break;

			//Пользователь сделал действие
			case 'userMadeAction':
				if( currentRound != result['round'] ) {
					/*$('.convert-cards .content-card-item').addClass('transition');
					$('.field-for-cards').addClass('visible');
					var timeout1=0;
					var timeout2=0;
					$('.convert-cards.oponent .image-inside-line .content-card-item-main').addClass('oponent-animatet');
					$('.convert-cards.user .image-inside-line .content-card-item-main').addClass('user-animatet');
					$('.convert-cards.oponent .content-card-item').each(function () {
						var k = $(this);
						setTimeout(function () {
							k.addClass('oponent-animate');
						},timeout1);
						timeout1+=50;
					});

					$('.convert-cards.user .content-card-item').each(function () {
						var k = $(this);
						setTimeout(function () {
							k.addClass('user-animate');
						},timeout2);
						timeout2+=50;
					});*/
					setTimeout(function () {
						$('.field-for-cards').removeClass('visible');
						$('.convert-cards .content-card-item').removeClass('transition');
						if(typeof result.turnDescript != "undefined") turnDescript = result.turnDescript;
						changeTurnIndicator(result.login); //смена индикатора хода

						fieldBuilding(result.step_status, recalculateCardsStrength);

						recalculateDecks(result); //Пересчет колод пользователя и противника
						calculateRightMarginCardHands();
						//Обработка Маг. Эффектов (МЭ)
						if(typeof result.magicUsage != "undefined"){
							magicReview(result)
						}

						currentRound = result['round'];
						if(result.login == $('.user-describer').attr('id')){
							$('.info-block-with-timer .title-timer').find('span').text('Ваш ход').end().addClass('user-turn-green');
							allowToAction = true;
						}else{
							$('.info-block-with-timer .title-timer').find('span').text('ход противника:').end().removeClass('user-turn-green');
							allowToAction = false;
						}

						var animateHandTime = animationCardReturnToOutage();

						setTimeout(function(){

							animateHandCard();

							cardCase(turnDescript, allowToAction);//Функция выбора карт
							userMakeAction(conn, turnDescript, allowToAction);//Функция разрешает пользователю действие
							clearRowSelection();//Очистка активированых рядов действий карт

						},animateHandTime);

					},1000)

				} else {

					if(typeof result.turnDescript != "undefined") turnDescript = result.turnDescript;

					changeTurnIndicator(result.login);//смена индикатора хода

					var resultLogin  = result.login;
					var thisUser = $('.user-describer .name').text();

					fieldBuilding(result.step_status);

					if ( result.step_status.played_card['card'] ) {

						var actions = result.step_status.actions;
						var playedCard = result.step_status.played_card.card;
						//console.log(result);

						if (actions.length) {

							actions.forEach(function(item) {
								//Анимация и функционал перегрупировки
								if ( item == '10' ) {

									if ( resultLogin != thisUser ) {

										window.card_overloading = createCardDescriptionView( result.step_status.played_card['card'],  result.step_status.played_card['strength'], 'without-description' );

									}

								}
								// Анимация и функционал дебафов
								else if ( item == '18' ) {

									var debuffRows = [];
									var debuffValue = 0;

									for ( var i = 0; i < playedCard.actions.length; i++ ) {

										if ( playedCard.actions[i].action == item ) {
											debuffRows = playedCard.actions[i].fear_ActionRow;
											debuffValue = playedCard.actions[i].fear_strenghtValue;
										}

									}

									if ( resultLogin == thisUser ) {

										buffingDebuffingAnimOnRows( 'user', debuffRows, debuffValue, 'debuff', 'terrify' );
										console.log('enemy turn', result);
									} else {

										buffingDebuffingAnimOnRows( 'oponent', debuffRows, debuffValue, 'debuff', 'terrify' );
										console.log('your turn', result);
									}

									detailCardPopupOnStartStep( result.step_status.played_card['card'],  result.step_status.played_card['strength'] );

								}
								//Анимация лекаря ( ефект лечения)
								else if ( item == '6' ) {

									secondTrollPopupCustomImgAndTitle('Исцеление!','/img/card_images/magic_istselenie_582b19299d5e2.png');

									recalculateCardsStrength(result.step_status);

								}
								//Функционал карты одурманивание
								else if ( item == '9' ) {

									var intoxicationCard = createCardDescriptionView( result.step_status.played_card['card'],  result.step_status.played_card['strength'], 'without-description' );

									for ( var player in result.step_status.added_cards ) {
										for(var row in result.step_status.added_cards[player]){
											if(row != 'hand'){
												for(var i in result.step_status.added_cards[player][row]){

													var card = result.step_status.added_cards[player][row][i];
													detailCardPopupOnOverloading(
														intoxicationCard,
														card['card'],
														card['strength'],
														'show-and-delate-card'
													);
													console.log('Функционал карты одурманивание');

												}
											}
										}
									}

									//check if we not take a card width intoxication - we only show popup width this card
									if ( result.step_status.added_cards.length == 0 && result.step_status.dropped_cards.length == 0 ) {
										detailCardPopupOnStartStep( result.step_status.played_card['card'],  result.step_status.played_card['strength'] );
									}

									recalculateCardsStrength(result.step_status);

								}
								// Карта лекаря
								else if ( item == '7' ) {

									var user_discard = result.counts.user_discard;
									var user_discard_cards = 0;

									result.user_discard.forEach(function(item) {
										var item_fraction = item.fraction;
										if ( item_fraction != 'special' ) {
											user_discard_cards++;
										}
									});

									if ( resultLogin == thisUser ) {
										console.log('healer turn');
										if ( user_discard_cards == 0 ) {
											console.log('norm with user discard');
											detailCardPopupOnStartStep( result.step_status.played_card['card'],  result.step_status.played_card['strength'] );
										}

									} else if ( resultLogin != thisUser ) {
										console.log('oponent turn');
										detailCardPopupOnStartStep( result.step_status.played_card['card'],  result.step_status.played_card['strength'] );

									}

									recalculateCardsStrength(result.step_status);
									showCardOnDesc();

								}
								// Шпион задействован
								else if ( item == '20' ) {

									detailCardPopupOnStartStep( result.step_status.played_card['card'],  result.step_status.played_card['strength'] );

								}
								 else {

									detailCardPopupOnStartStep( result.step_status.played_card['card'],  result.step_status.played_card['strength'] );

									recalculateCardsStrength(result.step_status);

								}

							});

						} else {

							recalculateCardsStrength(result.step_status);

							detailCardPopupOnStartStep( result.step_status.played_card['card'],  result.step_status.played_card['strength'] );

						}

					}

					//проверяю есть ли действие карты и существует ли переменная card_overloading
					if( !result.step_status.actions.length && window.hasOwnProperty('card_overloading') ){
						//Проверяю есть ли карты для добавления пользователю и(!) список карт для удаления
						if( !$.isEmptyObject(result.step_status.added_cards) && !$.isEmptyObject(result.step_status.dropped_cards) ){
							//ПОказывать только противнику
							if ( resultLogin == thisUser ) {

								detailCardPopupOnOverloading (
									window.card_overloading,
									result.step_status.added_cards[Object.keys(result.step_status.added_cards)[0]].hand[0],
									result.step_status.added_cards[Object.keys(result.step_status.added_cards)[0]].hand[0].strength,
									null
								);

							}
						}
						delete window['card_overloading'];
					}

					recalculateDecks(result);//Пересчет колод пользователя и противника
					calculateRightMarginCardHands();

					//Обработка Маг. Эффектов (МЭ)
					if(typeof result.magicUsage != "undefined"){ //always defined, don't know for what it need
						magicReview(result);
					}
				}
			break;

			//Пользователь использовал карты с возможностью призыва карт
			case 'dropCard':
				if(typeof result.field_data != "undefined"){
					fieldBuilding(result.step_status, recalculateCardsStrength);
				}
				recalculateDecks(result);//Пересчет колод пользователя и противника
				if(result.login == $('.user-describer').attr('id')) {
					cardCase(turnDescript, allowToAction);
				}
				//calculateRightMarginCardHands();
			break;

			//Раунд окончен
			case 'roundEnds':
				var win_status = [0, 0];
				for (var login in result.roundStatus) {
					if (login == $('.user-describer').attr('id')) {
						win_status[0] = result.roundStatus[login].length;
					} else {
						win_status[1] = result.roundStatus[login].length;
					}
				}
				$('.rounds-counts.user .rounds-counts-count').text(win_status[0]);
				$('.rounds-counts.oponent .rounds-counts-count').text(win_status[1]);

				recalculateDecks(result);

				circleRoundIndicator();

				if (typeof result.magicUsage != "undefined") {
					magicReview(result)
				}

				//Очищение полей
				$('.mezhdyblock #sortable-cards-field-more, .convert-battle-front .image-inside-line, .convert-battle-front .cards-row-wrap').children().fadeOut(500,function(){
					$('.mezhdyblock #sortable-cards-field-more, .convert-battle-front .image-inside-line, .convert-battle-front .cards-row-wrap').empty();
				});

				setTimeout(function() {

					if(typeof result.field_data != "undefined"){
						buildBattleField(result.field_data);
						showCardOnDesc();
					}

					resultPopupShow(result.roundResult + '! Подождите, идет подготовка нового раунда.');
					allowToAction = false;
					turnDescript = {"cardSource": "hand"};
					changeTurnIndicator(null);

					setTimeout(function () {
						$('#successEvent').removeClass('show');
						if ($('div.troll-popup.show').length <= 0) {
							closeAllTrollPopup();
						}
						hidePreloader();
					}, 3000);

				}, 501);

			break;
			//Игра закончена
			case 'gameEnds':
				var res = {
					gold:0,
					silver:0,
					ranking:0,
					win:"Поздравляем! Вы победили!",
					lose:"К сожалению Вы проиграли!",
					draw:"Игра окончилась вничью!"
				};
				if(result.resources['gold'] != '0') res.gold = result.resources['gold'];
				if(result.resources['silver'] != '0') res.silver = Math.abs(result.resources['silver']);
				if(result.resources['user_rating'] != '0') res.ranking = Math.abs(result.resources['user_rating']);

				var resPop = $('#endGamePopup');
				var resMessage = 'По результатам боя Вы ';
				switch(result.resources.gameResult){
					case 'loose':
						resPop.find('h5').text(res.lose);
						resMessage += 'получили <img class="resource" src="/images/header_logo_silver.png" alt="">'+res.silver+' серебра, но потеряли '+res.ranking+' очков рейтинга.';
						resPop.find('.result-game').html(resMessage);
						break;
					case 'win':
						resPop.find('h5').text(res.win);
						resMessage += 'получили <img class="resource" src="/images/header_logo_silver.png" alt="">'+res.silver+' серебра, и '+res.ranking+' очков рейтинга.';
						resPop.find('.result-game').html(resMessage);
						break;
					case 'draw':
						resPop.find('h5').text(res.draw);
						break;
				}

				closeAllTrollPopup();
				openTrollPopup(resPop);
				$('#successEvent').removeClass('show');
				allowToAction = false;
				turnDescript = {"cardSource" : "hand"};
				changeTurnIndicator(null);
				allowPopups = false;
			break;
		}

		if( (result.message == 'allUsersAreReady') || (result.message == 'userMadeAction') ){
			calculateRightMarginCardHands();
			hidePreloader();

			if(typeof result.users != "undefined"){
				 for(var login in result.users){
					$('.convert-right-info #'+login+' .stats-energy').text(result.users[login]);
				}
			}

			var expireTime = result.timing - phpTime();
			convertTimeToStr(expireTime);
			clearInterval(TimerInterval);
			if(expireTime > 0){
				startTimer(result.login);
			}
			//Произошло действие призыва или лекарь
			if( (typeof result.addition_data != "undefined") && (!$.isEmptyObject(result.addition_data)) ) {
				if(allowPopups){
					switch(result.addition_data['action']){
						//Задействовать popup выбора карт
						case 'activate_choise':
							$('#selectNewCardsPopup .button-troll').hide(); //Скрыть все кнопки на в popup-окне
							$('#selectNewCardsPopup .button-troll.acceptNewCards').show(); //Показать кнопку "Готово" для выбора призваных карт

							$('#selectNewCardsPopup #handNewCards').empty();//Очистка списка карт popup-окна
							//если карт отыгрыша пришло больше 1й
							if(result.turnDescript['cardToPlay'].length > 1){
								//Вывод карт в список в popup-окне

								var card_in_popup_count = 0;
								for(var i in result.turnDescript['cardToPlay']){
									$('#selectNewCardsPopup #handNewCards').append(
										createFieldCardView(result.turnDescript['cardToPlay'][i],
										result.turnDescript['cardToPlay'][i]['strength'],
										true));
									card_in_popup_count++;
								}
								setMinWidthInPop(card_in_popup_count,$('#selectNewCardsPopup'));

								openTrollPopup($('#selectNewCardsPopup'));//Открытие popup-окна пользователю

								$('#selectNewCardsPopup #handNewCards li, #selectNewCardsPopup .button-troll.acceptNewCards').unbind();
								$('#selectNewCardsPopup #handNewCards li:first').addClass('glow');
								$('#selectNewCardsPopup #handNewCards li').click(function(event){
									if((!$(event.target).hasClass('ignore')) && event.which==1){
										$('#selectNewCardsPopup #handNewCards li').removeClass('glow');
										$(this).addClass('glow');
									}
								});

								incomeCardSelection(conn, ident, result.turnDescript); //Отслеживание нажатия кнопки "Готово"
							}else{//Если карта одна показываем её в боковом окне
								incomeOneCardSelection(result.turnDescript['cardToPlay'][0]);
								showCardActiveRow(result.turnDescript['cardToPlay'][0]['id'], 'card', conn, ident);//Подсветка ряда действия карты
							}
							break;
						//Задействовать popup выбора хода игрока
						case 'activate_turn_choise':
							$('#selectCurrentTurn #chooseUser').empty();
							for(var login in result.users){
								$('#selectCurrentTurn #chooseUser').append('' +
									'<label>' +
									'<input type="radio" name="usersTurn" value="'+login+'">' +
									'<div class="pseudo-radio"></div> - '+login+
									'</label>');
							}
							$('#selectCurrentTurn #chooseUser input[name=usersTurn]:first').prop('checked', true).next().addClass('active');
							openTrollPopup($('#selectCurrentTurn'));
							$('#selectCurrentTurn button').unbind();
							$('#selectCurrentTurn button').click(function(){
								clearInterval(TimerInterval);
								var time = parseInt($('.info-block-with-timer span[data-time=minute]').text()) * 60 + parseInt($('.info-block-with-timer span[data-time=seconds]').text());
								var userTurn = $('#selectCurrentTurn input[name=usersTurn]:checked').val();
								conn.send(
									JSON.stringify({
										action: 'cursedWantToChangeTurn',//Отправка сообщения о подключения пользователя к столу
										ident: ident,
										user: userTurn,
										time: time
									})
								);
								closeAllTrollPopup();
							});
							break;
						//Задействовать popup просмотра карт
						case 'activate_view':
							$('#selectNewCardsPopup .button-troll').hide();//Скрыть все кнопки на в popup-окне
							$('#selectNewCardsPopup .button-troll.closeViewCards').show();//Показать кнопку "Закрыть" после просмотра карт

							$('#selectNewCardsPopup #handNewCards').empty();//Очистка списка карт popup-окна
							//Вывод карт в список в popup-окне
							var card_in_popup_count = 0;
							for(var i in result.turnDescript['cardToPlay']){
								$('#selectNewCardsPopup #handNewCards').append(createFieldCardView(result.turnDescript['cardToPlay'][i], result.turnDescript['cardToPlay'][i]['strength'], true));
								card_in_popup_count++;
							}
							setMinWidthInPop(card_in_popup_count,$('#selectNewCardsPopup'));

							openTrollPopup($('#selectNewCardsPopup'));//Открытие popup-окна пользователю

							//Закрытие popup-окна
							$('#selectNewCardsPopup .button-troll.closeViewCards').click(function(e){
								e.preventDefault();
								closeAllTrollPopup();
							});
							break;
						//Задействовать popup перегруппировки карт
						case 'activate_regroup':
							$('#selectNewCardsPopup .button-troll').hide();
							$('#selectNewCardsPopup .button-troll.acceptRegroupCards').show();

							$('#selectNewCardsPopup #handNewCards').empty();

							var card_in_popup_count = 0;
							for(var i in result.turnDescript['cardToPlay']){
								$('#selectNewCardsPopup #handNewCards').append(createFieldCardView(result.turnDescript['cardToPlay'][i], result.turnDescript['cardToPlay'][i]['strength'], true));
								card_in_popup_count++;
							}

							setMinWidthInPop(card_in_popup_count,$('#selectNewCardsPopup'));
							openTrollPopup($('#selectNewCardsPopup'));

							$('#selectNewCardsPopup #handNewCards li, #selectNewCardsPopup .button-troll.acceptRegroupCards').unbind();
							$('#selectNewCardsPopup #handNewCards li:first').addClass('glow');
							$('#selectNewCardsPopup #handNewCards li').click(function(event){
								if((!$(event.target).hasClass('ignore')) && event.which==1){
									$('#selectNewCardsPopup #handNewCards li').removeClass('glow');
									$(this).addClass('glow');
								}

							});
							//Функция отправки сообщения на соккет о перегруппировки выбраной карты
							cardReturnToHand(conn, ident);

							sortCards();

						break;
					}
				}
			}

			if(result.login == $('.user-describer').attr('id')){
				$('.info-block-with-timer .title-timer').find('span').text('Ваш ход').end().addClass('user-turn-green');
				allowToAction = true;
			}else{
				$('.info-block-with-timer .title-timer').find('span').text('ход противника:').end().removeClass('user-turn-green');
				allowToAction = false;
			}

			cardCase(turnDescript, allowToAction);//Функция выбора карт
			userMakeAction(conn, turnDescript, allowToAction);//Функция разрешает пользователю действие
			clearRowSelection();//Очистка активированых рядов действий карт
		}

		//Пользователь сдается
		$('.convert-right-info button[name=userGiveUpRound]').unbind();
		$('.convert-right-info button[name=userGiveUpRound]').click(function(){
			var surrenderResult = confirm('Вы действительно хотите сдаться?');
			if(surrenderResult){
				conn.send(
					JSON.stringify({
						action: 'userGivesUp',//Отправка сообщения о подключения пользователя к столу
						ident: ident
					})
				);
			}else{
				return ;
			}
		});
	}
}

/* Dmitry scripts */

	/*
	* buffing or debuffing row animation
	* side - oponent or user
	* rows - array of rows
	* type - 'buff' or 'debuff'
	* effectName - any effect class that gona be added to .convert-one-field
	* (you must write it animation in scss or js)
	*/
	function buffingDebuffingAnimOnRows( side, rows, value, type, effectName ) {

		rows.forEach(function( item ) {
			var rowId = intRowToField(item);
			var row = $('.' + side + ' .field-for-cards' + rowId);
			var parent = row.parents('.convert-stuff');
			var pointsSum = parent.find('.field-for-sum');
			parent.addClass(effectName + '-' + type);
			var effectMarkup = '<div class="debuff-or-buff-anim"></div>';
			row.append(effectMarkup);
			var effectObjects = row.find('.debuff-or-buff-anim');
			var effectObjectAdded = row.find('.debuff-or-buff-anim:not(active)');

			var timer = setInterval(function() {
				if ( !$('.troll-popup.show').length ) {

					effectObjectAdded.addClass('active');
					var cards = row.find('.content-card-item');

					setTimeout(function() {
						pointsSum.addClass('pulsed');
						setTimeout(function() {
							pointsSum.removeClass('pulsed');
						}, 500);
					}, 0);

					cards.each(function() {
						var card = $(this);
						if (
							( type == 'debuff' && !card.is('[data-immune=true]') && !card.is('[data-full-immune=true]') ) ||
							( type == 'buff' && !card.is('.full-immune') )
						) {
							setTimeout(function() {
								card.addClass('pulsed');
								var cardStrength = card.find('.label-power-card-wrap .card-current-value').text();
								var cardStrengthNew = cardStrength;

								if ( type == 'buff' ) {
									cardStrengthNew = cardStrength + value;
									card.find('.buff-debuff-value').attr('data-math-simb', '+');
								} else {
									cardStrengthNew = cardStrength - value;
									if ( cardStrengthNew < 1 ) {
										cardStrengthNew = 1;
									}
									card.find('.buff-debuff-value').attr('data-math-simb', '-');
								}

								card.find('.buff-debuff-value').text(value);
								card.find('.card-current-value').text(cardStrengthNew);

								setTimeout(function() {
									card.removeClass('pulsed');
								},2000);

								recalculateBattleField();

							}, 500);
						}
					});

					parent.addClass(type);
					clearInterval(timer);

				}
			}, 500);
			if ( effectObjects.length > 1 ) {
				setTimeout(function() {
					effectObjects.each(function(index) {
						if (index != 0) {
							$(this).remove();
						}
					});
				},1000)
			}
		});

	}

	function cardMovingFromTo( side, from, count ) {

		var wrapper = null;
		var part = null;
		var cardsPosition = $('.convert-battle-front');

		if (side == 'opponent') {
			wrapper = "#card-give-more-oponent";
		}
		else if (side == 'user') {
			wrapper = '#card-give-more-user';
		}

		if (from == 'deck') {
			part = '[data-field=deck]';
		}
		else if (from == 'discard') {
			part = '[data-field=discard]';
		}

		var cardsStackObject = $(wrapper + ' ' + part);
		var cardsStackPosition = cardsStackObject.offset();
		var cardsStackParams = {
			width: cardsStackObject.width(),
			height: cardsStackObject.height(),
			background: cardsStackObject.find('.card-my-init').css('background-image')
		};

		var styles = {
			'width': cardsStackParams.width,
			'height': cardsStackParams.height,
			'background-image': cardsStackParams.background,
			'top': cardsStackPosition.top,
			'left': cardsStackPosition.left
		}

		var cardWhatGonaBeMoving = $('<div class="moving-card"></div>').css(styles);

		var cardsDistonation = [];

		var cardWidth = 103; // card width by default css
		var paramToLeft = cardWidth/2;

		if ( $('#sortableUserCards li').length ) {
			cardWidth = $('#sortableUserCards li .content-card-item-main').width();
			paramToLeft = parseInt( $('.content-card-item:not(.added-by-effect)').width()/2);
		}

		$('.added-by-effect').each(function() {
			console.log($(this).offset().left, paramToLeft);
			var addedParams = {
				width: cardWidth,
				height: $(this).height(),
				top: $(this).offset().top - 10, // VERTICAL-ALIGN OF DECK - THEIR FAULT
				left: $(this).offset().left - paramToLeft - 10 // NOT GOOD, BUT DON'T KNOW WHAT DO
			};
			cardsDistonation.push(addedParams);
		});

		for ( var i = 0; i < count; i++ ) {
			var clonedCardMarkup = cardWhatGonaBeMoving.clone();
			cardsPosition.append(clonedCardMarkup);
		}

		var point = 0;

		var timer = setInterval(function() {

			var cardDistonationParam = cardsDistonation[point];

			var style = {
				width: cardDistonationParam.width,
				height: cardDistonationParam.height,
				top: cardDistonationParam.top,
				left: cardDistonationParam.left
			};

			$('.moving-card').eq(point).css(style).addClass('move');
			$('.added-by-effect').eq(point).removeClass('waiting-for-animation');
			point++;

			if (point == count) {
				clearInterval(timer);

				setTimeout(function() {
					$('.moving-card').remove();
					$('.added-by-effect').removeClass('added-by-effect');
				}, 1300);

			}

		}, 300);

	};

/* /Dmitry scripts */

function setMinWidthInPop(count,popup) {
	if (count>0){
		var holder = popup.find('.cards-select-wrap li');
		var card_in_poup_min_width = ( holder.width() * count ) + 300;//300 - magic count
		popup.css({
			'width':card_in_poup_min_width+'px'
		});
	}
}

function circleRoundIndicator() {
	var opon = parseInt($('.rounds-counts.oponent .rounds-counts-count').text());
	var user = parseInt($('.rounds-counts.user .rounds-counts-count').text());
	if(user > 0){$('#svg #bar-oponent').css('stroke-dashoffset', '205px');}else{$('#svg #bar-oponent').css('stroke-dashoffset', '0');}
	if(opon > 0){$('#svg #bar-user').css('stroke-dashoffset', '205px');}else{$('#svg #bar-user').css('stroke-dashoffset', '0');}
}

function calculateRightMarginCardHands() {
	calculate($('#sortableUserCards'));
	calculate($('#sortable-cards-field-more'));
	calculate($('.cards-row-wrap'));
	function calculate(obj){
		var count = obj.find('li').length + 1;
		var itemW = obj.find('li').width();
		var container = obj.width();
		var rightMargin = ((itemW * count) - container)/count;
		if(container < (itemW * count)){
			obj.find('li').css('margin-right','-'+rightMargin+'px');
		}
	}
}

function pleaseShowMePopupWithDeckCards() {
	$('ul.deck-cards-list').jScrollPane();
	var api = $('ul.deck-cards-list').data('jsp');
	var throttleTimeout;
	$(window).bind('resize', function(){
		if (!throttleTimeout) {
			throttleTimeout = setTimeout(function(){
				api.reinitialise();
				throttleTimeout = null;
			}, 50);
		}
	});
	$(document).on('click', '#card-give-more-user li[data-field=deck] .card-my-init.cards-take-more', function () {
		openTrollPopup($('#allies-deck'));
	});
	$(document).on('click', '#card-give-more-user li[data-field=discard] .card-my-init.cards-take-more', function () {
		openTrollPopup($('#allies-discard'));
	});
	$(document).on('click', '#card-give-more-oponent li[data-field=discard] .card-init', function () {
		openTrollPopup($('#enemy-discard'));
	});
}

var oponentHandCounter='';
window.userImgData = {'opponent':'', 'user': ''};
var socketResult;
var ident;
var allowToAction = false;
var turnDescript = {"cardSource": "hand"};
var timeOut;
var conn;

$.get('/get_socket_settings', function (data) {
	socketResult = JSON.parse(data); //Получение данных настроек соккета
	//Формирование начального пакета идентификации битвы
	ident = {
		battleId: socketResult['battle'],
		userId: socketResult['user'],
		hash: socketResult['hash']
	};
	timeOut = socketResult['timeOut'];

	$(document).ready(function () {
		startBattle();
	});
});

$(window).resize(function () {
	calculateRightMarginCardHands();
});

$(document).ready(function () {
	radioPseudo();
	showPreloader();
	infoCardStart();
	clickCloseCross();
	calculateRightMarginCardHands();
	pleaseShowMePopupWithDeckCards();
	circleRoundIndicator();
});
