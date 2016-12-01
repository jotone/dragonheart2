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
function openTrollPopup(popup){
	popup.addClass('show');
	$('.new-popups-block').addClass('show');
}
//попап результатов
function resultPopupShow(message){
	$('#successEvent').find('.result').text(message);
	openTrollPopup($('#successEvent'));
}
function closeAllTrollPopup(){
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
function buildRoomPreview(userData){
	//очищение списков поп-апа выбора карт
	$('#selecthandCardsPopup #handCards').empty();

	//Отображаем данные пользователей
	for(var key in userData){
		if(key != $('.user-describer').attr('id')){
			$('#selecthandCardsPopup .opponent-fraction span').text(userData[key]['deck_title']);
			$('#selecthandCardsPopup .opponent-description span').text(userData[key]['deck_descr']);
		}
		if( $('.convert-right-info #'+key).length <1){
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
function userWantsChangeCard(){
	$(document).on('click', '#selecthandCardsPopup #handCards .change-card', function(){
		showPreloader();
		var card = $(this).parent().attr('data-cardid');
		conn.send(
			 JSON.stringify({
				 action: 'changeCardInHand',
				 ident: ident,
				 card: card
			 })
		);
	});
}
function userChangeDeck(can_change_cards){
	//Смена карт при старте игры

	$(document).on('click', '#handCards li .content-card-item-main', function(event){
		if((!$(event.target).hasClass('ignore')) && event.which==1){
			if(parseInt($('#selecthandCardsPopup .popup-content-wrap h5 span').text()) > 0){
				var button =$(document.createElement('div'));
				button.addClass('change-card').html('<span>—CМЕНИТЬ—</span>');

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

	//Пользователь Выбрал карты для сноса и нажал "ОК"
	$('#selecthandCardsPopup .acceptHandDeck').click(function(e){
		e.preventDefault();
        userChangeCards();
		clearInterval(TimerInterval);
	});
}

function userChangeCards(){
    showPreloader();

    var token = $('.market-buy-popup input[name=_token]').val().trim();
    var turn = '';
    if($('#selecthandCardsPopup input[name=userTurn]').length > 0){
        turn = ($('#selecthandCardsPopup input[name=userTurn]:checked').val() == undefined)? $('.convert-right-info .user-describer').attr('id'): $('#selecthandCardsPopup input[name=userTurn]:checked').val();
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
        }
    });
}

function createUserDescriber(userLogin, user_img, userRace){
	if(user_img != ''){
		$('.convert-right-info #'+userLogin+' .stash-about .image-oponent-ork').css({'background':'url(/img/user_images/'+user_img+') 50% 50% no-repeat'});
	}
	$('.convert-right-info #'+userLogin+' .stash-about .naming-oponent .name').text(userLogin);
	$('.convert-right-info #'+userLogin+' .stash-about .naming-oponent .rasa').text(userRace);
}
function createUserMagicFieldCards(userLogin, magicData){
	for(var i=0; i<magicData.length; i++){
		$('.convert-right-info #' + userLogin ).find('.magic-effects-wrap').append(createMagicEffectView(magicData[i]));
	}
}
//Создание отображения карты в списке
function createFieldCardView(cardData, strength, titleView){

	return '' +
		'<li class="content-card-item disable-select" data-cardid="'+cardData['id']+'" data-relative="'+cardData['type']+'">'+
		createCardDescriptionView(cardData, strength, titleView)+
		'</li>';
}
//Созднаие Отображения маг. еффекта
function createMagicEffectView(magicData){
	return  '' +
		'<li data-cardid="' + magicData['id'] + '">' +
		'<img src="/img/card_images/' + magicData['img_url']+'" alt="' + magicData['slug'] +'" title="' + magicData['title'] +'">'+
		'<div class="magic-description">'+ magicData['description']+'</div>'+
		'<div class="info-img"><img class="ignore" src="/images/info-icon.png" alt=""><span class="card-action-description">Инфо о магии</span></div>'+
		'</li>';
}
//Создание отображения карты
function createCardDescriptionView(cardData, strength, titleView){
	var result = '<div class="content-card-item-main';
	if(cardData['type'] == 'special'){
		result += ' special-type';}
	if(cardData['is_leader'] == 1){
		result += ' leader-type';}

	switch (cardData['fraction']) {
		case 'highlander':  result += ' highlander-race'; break;
		case 'monsters':    result += ' monsters-race'; break;
		case 'undead':      result += ' undead-race'; break;
		case 'cursed':      result += ' cursed-race'; break;
		case 'knight':      result += ' knight-race'; break;
		case 'forest':      result += ' forest-race'; break;
		default:
			if(cardData['type'] == 'neutrall'){result += ' neutrall-race';}
	}
	result +=' " style="background-image: url(/img/card_images/'+cardData['img_url']+')" data-leader="'+cardData['is_leader']+'" data-type="'+cardData['type']+'" data-weight="'+cardData['weight']+'">' +
		'<div class="card-load-info card-popup"><div class="info-img"><img class="ignore" src="/images/info-icon.png" alt=""><span class="card-action-description">Инфо о карте</span></div>';
	if(cardData['is_leader'] == 1){
		result += '<div class="leader-flag"><span class="card-action-description">Карта Лидера</span></div>';
	}
	result +='<div class="label-power-card"><span class="label-power-card-wrap">'+strength+'</span><span class="card-action-description">';
	if(cardData['type'] == 'special'){
		result += 'Специальная карта';
	}else{
		result += 'Сила карты';
	}
	result +=    '</span></div>' +
		'<div class="hovered-items">' +
			'<div class="card-game-status">' +
				'<div class="card-game-status-role">' ;
					for (var j = 0; j < cardData['row_txt'].length; j++) {
						result +='<img src="'+cardData['row_txt'][j].image+'" alt=""><span class="card-action-description">'+cardData['row_txt'][j].title+'</span>';
					}

	result += '</div><div class="card-game-status-wrap">';
	if(cardData['action_txt'].length>0){
		for (var i = 0; i < cardData['action_txt'].length; i++) {
			result = result + '<span class="card-action"><img src="' + cardData['action_txt'][i].img+'" alt=""><span class="card-action-description">'+cardData['action_txt'][i].title+'</span></span>';
  
		}
	}
	result = result + '</div>' +
		'</div>' +
		'<div class="card-name-property"><p>'+cardData['title']+'</p></div>' +
		'<div class="card-description-hidden"><div class="jsp-cont-descr">' +
			  '<p class="txt">'+cardData['descript']+'</p></div></div> '+
		'</div>' +
		'</div>' +
		'</div>';

	return result;
}
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
function userMakeAction(conn, turnDescript, allowToAction){
	$('.convert-battle-front .convert-stuff, .mezhdyblock .bor-beutifull-box').unbind();
	if(allowToAction){

		$('.convert-battle-front .convert-stuff, .mezhdyblock .bor-beutifull-box').on('click', '.active', function(){
			clearInterval(TimerInterval);
			var time = parseInt($('.info-block-with-timer span[data-time=minute]').text()) * 60 + parseInt($('.info-block-with-timer span[data-time=seconds]').text());

			if($('.summonCardPopup').hasClass('show')){
				var card = $('#summonWrap li').attr('data-cardid');
				$('.summonCardPopup').removeClass('show');
			}else {
				var card = $('#sortableUserCards li.active').attr('data-cardid');
			}

			var magic = $('.user-describer .magic-effects-wrap .active').attr('data-cardid');
			var BFData = '{"row":"'+$(this).attr('id')+'", "field": "'+$(this).parents('.convert-cards').attr('id')+'"}';
			if(magic !== undefined){
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
			if(allowToAction){
				conn.send(
					JSON.stringify({
						action: 'userPassed',
						ident: ident
					})
				);
				allowToAction = false;
			}
		});
	}
}
function cardCase(turnDescript, allowToAction){
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
function showCardActiveRow(card, type, conn, ident){
	if(type == 'card'){
		var url = '/game_get_card_data';
	}else{
		var url = '/game_get_magic_data';
	}
	$.ajax({
		url:     url,
		type:    'GET',
		data:    {card:card},
		success: function(data){
			data = JSON.parse(data);
			var dataType = ''; // Тип карты race или special
			var dataStrength = ''; //Сила карты
			if(type == 'card'){
				dataType = 'data-type="'+data['type']+'"';
				dataStrength = '<div class="label-power-card"><span class="label-power-card-wrap"><span>'+data['strength']+'</span></span></div>';
			}

			clearRowSelection();//Очистить активные ряды действия карты
			//Если карта
			if(type == 'card'){
				//Если тип карты определен
				if(data['type'] != undefined){

					if(data['type'] == 'special'){
						//Для "Специальных" карт
						for(var i in data['actions']){
							var action = ''+data['actions'][i]['action'];
							//По порядку действия: 9 - "Одурманивание", 11 - "Печаль", 19 - "Убийца"
							if( (action == '9') || (action == '11') || (action == '19') ) illuminateOpponent(); //Подсветить поле оппонента
							//4 - "Воодушевление", 6 - "Исцеление", 7 - "Лекарь", 15 - "Призыв"
							if( (action == '4') || (action == '6') || (action == '7') || (action == '15') ) illuminateCustom('.user', data['action_row']);//Подсветить поля указанные в действии карты
							//18 - "Страшный"
							if(action == '18') illuminateAside(); //Подсветить среднее поле
							//10 - "Перегруппировка"
							if(action == '10') illuminateSelf();//Подсветить свое поле
						}
					}else{//Для карт-воинов
						//Если есть у карты особые действия
						if(data['actions'].length > 0){
							for(var i in data['actions']){
								var action = ''+data['actions'][i]['action'];
								if(action == '20'){//Действие "Шпион"/"Разведчик"
									//spy_fieldChoise = 0 - подсветка на своем поле; 1 - подсветка на поле оппонента
									if(data['actions'][i]['spy_fieldChoise'] == '0'){
										var parent = '.user';
									}else{
										var parent = '.oponent';
									}
								}else{
									var parent = '.user';
								}
							}
							illuminateCustom(parent, data['action_row']);//Подсветить поля указанные в действии карты с учетом поля spy_fieldChoise
						}else{
							illuminateCustom('.user', data['action_row']);//Подсветить поля указанные в действии карты
						}
					}
				}else{
					for(var i in data['actions']){
						var action = ''+data['actions'][i]['action'];
						if(action == '19'){//Действие "Убийца"
							illuminateCustom('.oponent', data['action'][i]['killer_ActionRow']);
						}else{
							illuminateOpponent();
							illuminateSelf();
						}
					}
				}
				//Активирован МЭ
			}else{
				if(action == '11'){
					illuminateOpponent();
				}else{
					illuminateOpponent();
					illuminateSelf();
				}
			}
		}
	});
}
//END OF showCardActiveRow

//Функиця отправки выбраных карт для призыва на поле
function incomeOneCardSelection(card){
	var content='<li class="content-card-item disable-select" data-cardid="'+card['id']+'" data-relative="'+card['type']+'">'+
		createCardDescriptionView(card, card['strength'])+
		'</li>';

		$('.summonCardPopup').removeClass('show');
		$('#summonWrap').html(content);
		$('.summonCardPopup').addClass('show');
}
function incomeCardSelection(conn, ident, turnDescript){
	$('#selectNewCardsPopup .button-troll.acceptNewCards').click(function(e){
		e.preventDefault();
		if($('#selectNewCardsPopup #handNewCards .glow')){
			createPseudoCard($('#selectNewCardsPopup #handNewCards .glow'));
		}else{
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
function cardReturnToHand(conn, ident){
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
			calculateRightMarginCardHands();
		}
	});
}


//Отмена подсветки ряда действий карты
function clearRowSelection(){
	$('.mezhdyblock .bor-beutifull-box #sortable-cards-field-more').removeClass('active');
	$('.convert-stuff .field-for-cards').each(function(){
		$(this).removeClass('active')
		$(this).children('.fields-for-cards-wrap').children('.cards-row-wrap').children('li').removeClass('glow');
	});
}

//Подсветка рядов действия карты
function illuminateAside(){$('.mezhdyblock .bor-beutifull-box #sortable-cards-field-more').addClass('active');}//Средний блок
function illuminateOpponent(){$('.oponent .convert-stuff .field-for-cards').addClass('active');}//Поле оппонента
function illuminateSelf(){$('.user .convert-stuff .field-for-cards').addClass('active');}//Свое поле
function illuminateCustom(parent, row){//Поле действия карты по-умолчанию
	for(var i=0;i<row.length; i++){
		var field = intRowToField(row[i]);
		$('.convert-battle-front '+parent+' .convert-one-field '+field).addClass('active');
	}
}

//Перевод значения названия поля в id ряда
function intRowToField(row){
	switch(row.toString()){
		case '0': var field = '#meele'; break;
		case '1': var field = '#range'; break;
		case '2': var field = '#superRange'; break;
	}
	return field;
}


//Пересчет Силы рядов
function recalculateBattleField(){
	var players ={
		oponent:{
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
	//подщет силы на столе
	$('.convert-battle-front .convert-stuff .field-for-cards').each(function(){
		if($(this).parents('.convert-cards').hasClass('user')){
			calc($(this), 'user');
		}else{
			calc($(this), 'oponent');
		}

		function calc(row, parent) {
			var dist = row.attr('id');
			var str = 0;
			row.find('ul.cards-row-wrap li').each(function () {
				str += parseInt($(this).find('.label-power-card-wrap').text());
			});
			players[parent][dist] = str;
			total[parent] +=str;
			$('.convert-cards.'+parent+' #'+dist).parent().next().text(str);
			$('.power-text-'+parent).text(total[parent]);
		}

	});

}

//Отображение колод пользователей
function recalculateDecks(result){
	//колода противника
	if(result.counts['opon_deck'] !== undefined){
		if(parseInt(result.counts['opon_deck']) > 0){
			$('#card-give-more-oponent li[data-field=deck]').empty().append(createDeckCardPreview(result.counts['opon_deck'], false));
		}else{
			$('#card-give-more-oponent li[data-field=deck]').empty().append('<div class="nothinh-for-swap"></div>');
		}
	}

	//колода игрока
	if(result.counts['user_deck'] !== undefined){
		if(parseInt(result.counts['user_deck']) > 0){
			if(result.user_deck != undefined){
				$('#allies-deck .jspPane').empty().append(createDeckCardPreview(result.counts['user_deck'], true, result.user_deck));
			}
			$('#card-give-more-user li[data-field=deck]').empty().append(createDeckCardPreview(result.counts['user_deck'], true));
		}else{
			$('#card-give-more-user li[data-field=deck]').empty().append('<div class="nothinh-for-swap"></div>');
		}
	}
	//отбой игрока
	if(result.counts['user_discard'] !== undefined){
		if(parseInt(result.counts['user_discard']) > 0){
			if(result.user_discard != undefined){
				if($('#allies-discard .jspPane').length>0){
					$('#allies-discard .jspPane').empty().append(createDeckCardPreview(result.counts['user_discard'], true, result.user_discard));
				}else{
					$('#allies-discard .deck-cards-list').empty().append(createDeckCardPreview(result.counts['user_discard'], true, result.user_discard));
					$('#allies-discard .deck-cards-list').jScrollPane();
					var api = $('#allies-discard .deck-cards-list').data('jsp');
					var throttleTimeout;
					$(window).bind('resize', function(){
						if (!throttleTimeout) {
							throttleTimeout = setTimeout(function(){
								api.reinitialise();
								throttleTimeout = null;
							}, 50);
						}
					});
				}
				$('#card-give-more-user li[data-field=discard]').empty().append(createDeckCardPreview(result.counts['user_discard'], true));
			}
		}else{
			$('#card-give-more-user li[data-field=discard]').empty().append('<div class="nothinh-for-swap"></div>');
		}
	}
	//отбой противника
	if(result.counts['opon_discard'] !== undefined){
		if(parseInt(result.counts['opon_discard']) > 0){
			if($('#enemy-discard .jspPane').length>0){
				$('#enemy-discard .jspPane').empty().append(createDeckCardPreview(result.counts['opon_discard'], false, result['opon_discard']));
			}else{
				$('#enemy-discard .deck-cards-list').empty().append(createDeckCardPreview(result.counts['opon_discard'], false, result['opon_discard']));
				$('#enemy-discard .deck-cards-list').jScrollPane();
				var api = $('#enemy-discard .deck-cards-list').data('jsp');
				var throttleTimeout;
				$(window).bind('resize', function(){
					if (!throttleTimeout) {
						throttleTimeout = setTimeout(function(){
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
	if(result.user_hand != undefined){
		$('.user-stats .greencard-num').text(result.user_hand.length);
	}
	if(result.counts['opon_hand'] != undefined){
		$('.oponent-stats .greencard-num').text(result.counts['opon_hand']);
	}

	//рука игрока
	if(result.user_hand !== undefined){
		$('.user-card-stash #sortableUserCards').empty();
		for(var i in result.user_hand){
			$('.user-card-stash #sortableUserCards').append(createFieldCardView(result.user_hand[i], result.user_hand[i]['strength'], true));
		}
		//Убираем сыграную карту из руки
		if( (result.turnDescript !== undefined) && (result.login == $('.user-describer').attr('id')) ){
			if(result.turnDescript['cardSource'] != 'hand'){
				$('#sortableUserCards .active').remove();
				$('#sortableUserCards li').removeClass('active');
			}
		}
	}
	hidePreloader();
}



//Смена идентификатора хода пользователя
function changeTurnIndicator(login){
	if(login == $('.user-describer').attr('id')){
		$('.user-turn-wrap .turn-indicator').addClass('active');
	}else{
		$('.user-turn-wrap .turn-indicator').removeClass('active');
	}
}

//Создание отображения колоды
function createDeckCardPreview(count, is_user, deck){
	var divClass = (is_user) ? 'card-my-init cards-take-more' : 'card-init';
	var cardList = '';
	if(deck != undefined){
		for(var i=0; i<deck.length; i++){
			cardList += createFieldCardView(deck[i], deck[i]['strength'], true);
		}
	}else{
		cardList += '<div class="'+divClass+'"><div class="card-otboy-counter deck">'+count+'</div></div>';
	}

	return cardList;
}

function buildBattleField(fieldData){
	//Очищение полей
	$('.mezhdyblock #sortable-cards-field-more, .convert-battle-front #p1 .cards-row-wrap, .convert-battle-front #p1 .image-inside-line, .convert-battle-front #p2 .cards-row-wrap, .convert-battle-front #p2 .image-inside-line').empty();
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
	calculateRightMarginCardHands();
	console.log('battleField id builted');
}
//Закрытие popup-окна
$('.market-buy-popup .close-popup').click(function(){
	$(this).parents('.market-buy-popup').hide();
});

recalculateBattleField();


//Отображение Колоды или Отбоя
$('.convert-left-info .cards-bet #card-give-more-user').on('click', '.card-my-init', function(){
	if($(this).css('pointer-events') != 'none'){
		$(this).children('ul.deck-cards-list').toggleClass('active');
	}
});

function convertTimeToStr(seconds){
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
function startTimer(login){
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
							ident: ident
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

function magicReview(result){
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
		}
	}
}

function startBattle() {
	conn = new WebSocket('ws://' + socketResult['dom'] + ':8080');//Создание сокет-соединения
	//Создание сокет-соединения
	conn.onopen = function (data) {
		console.log('Соединение установлено');

		conn.send(
			JSON.stringify({
				action: 'userJoinedToRoom',//Отправка сообщения о подключения пользователя к столу
				ident: ident
			})
		);
	}

	conn.onclose = function (event) {}
	conn.onerror = function (e) {
		showPopup('Socket error');
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
					data: {battle_id: result.battleInfo},
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
				//resultPopupShow('Противник '+result.user_login+' пасанул');
				resultPopupShow('Противник пасует');
			break;
			case 'changeCardInHand':
				$('#selecthandCardsPopup #handCards li[data-cardid="'+result.card_to_drop+'"]').remove();
				$('#selecthandCardsPopup h5 span').text(result.can_change_cards);
				$('#selecthandCardsPopup #handCards').append(createFieldCardView(result.card_to_hand, result.card_to_hand['strength'], true));
				hidePreloader();
			break;
			//Все пользователи готовы к игре
			case 'allUsersAreReady':
				changeTurnIndicator(result.login);//смена индикатора хода
				recalculateBattleField();
			break;

			//Пользователь сделал действие
			case 'userMadeAction':
				if(result.turnDescript !== undefined) turnDescript = result.turnDescript;

				changeTurnIndicator(result.login);//смена индикатора хода

				buildBattleField(result.field_data);//Отображение поля битвы

				recalculateDecks(result);//Пересчет колод пользователя и противника
				calculateRightMarginCardHands();

				//Обработка Маг. Эффектов (МЭ)
				if(result.magicUsage !== undefined){
					magicReview(result)
				}
			break;

			//Пользователь использовал карты с возможностью призыва карт
			/*
			 * @result
			 * user_deck    //Массив карт колоды игрока
			 * user_discard //Массив карт отбоя игрока
			 * counts [     //Счетчики
			 *      user_deck       - Количество карт колоды отправителя
			 *      user_discard    - Количество карт отбоя отправителя
			 *      opon_discard    - Количество карт отбоя противника
			 *      opon_deck       - Количество карт колоды противника
			 *      opon_hand'      - Количество карт руки противника
			 * ]
			 * turnDescript         - описания действий по умолчанию
			 * (field_data)         - данные о поле битвы (необязательный)
			 */
			case 'dropCard':
				if(result.field_data != undefined) buildBattleField(result.field_data);//Если возыращаются данные о поле битвы - перестраиваем его
				recalculateDecks(result);//Пересчет колод пользователя и противника
				if(result.login == $('.user-describer').attr('id')){
					cardCase(turnDescript, allowToAction);
				}
				calculateRightMarginCardHands();
			break;

			//Раунд окончен
			case 'roundEnds':

				var win_status = [0,0];

				for(var login in result.roundStatus){
					if(login == $('.user-describer').attr('id')){
						win_status[0] = result.roundStatus[login].length;
					}else{
						win_status[1] = result.roundStatus[login].length;
					}
				}
				$('.rounds-counts.user .rounds-counts-count').text(win_status[0]);
				$('.rounds-counts.oponent .rounds-counts-count').text(win_status[1]);

				recalculateDecks(result);
				circleRoundIndicator();

				if(result.magicUsage !== undefined){
					magicReview(result)
				}

				if(result.field_data !== undefined) buildBattleField(result.field_data);
				resultPopupShow(result.roundResult+'! Подождите, идет подготовка нового раунда.');
				allowToAction = false;
				turnDescript = {"cardSource" : "hand"};
				changeTurnIndicator(null);

				setTimeout(function () {
					$('#successEvent').removeClass('show');
					if($('div.troll-popup.show').length<=0){closeAllTrollPopup();}
					hidePreloader();
				}, 3000);

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

            if(result.users != undefined){
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
			if( (result.addition_data !== undefined) && (!$.isEmptyObject(result.addition_data)) ){
				/*
				 * @result.addition_data[
				 *      action :
				 *          activate_choise - произведено действие призыва карт
				 *          activate_view   - произведено действие просмотра карт противника
				 *          activate_regroup- произведено действие "перегруппировка"
				 * ]
				 */
				if(allowPopups){
					switch(result.addition_data['action']){
						//Задействовать popup выбора карт
						case 'activate_choise':
							$('#selectNewCardsPopup .button-troll').hide();//Скрыть все кнопки на в popup-окне
							$('#selectNewCardsPopup .button-troll.acceptNewCards').show(); //Показать кнопку "Готово" для выбора призваных карт

							$('#selectNewCardsPopup #handNewCards').empty();//Очистка списка карт popup-окна
							//если карт отыгрыша пришло больше 1й
							if(result.turnDescript['cardToPlay'].length > 1){
								//Вывод карт в список в popup-окне
								for(var i in result.turnDescript['cardToPlay']){
									$('#selectNewCardsPopup #handNewCards').append(createFieldCardView(result.turnDescript['cardToPlay'][i], result.turnDescript['cardToPlay'][i]['strength'], true));
								}
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
							for(var i in result.turnDescript['cardToPlay']){
								$('#selectNewCardsPopup #handNewCards').append(createFieldCardView(result.turnDescript['cardToPlay'][i], result.turnDescript['cardToPlay'][i]['strength'], true));
							}
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

							for(var i in result.turnDescript['cardToPlay']){
								$('#selectNewCardsPopup #handNewCards').append(createFieldCardView(result.turnDescript['cardToPlay'][i], result.turnDescript['cardToPlay'][i]['strength'], true));
							}
							openTrollPopup($('#selectNewCardsPopup'));


							$('#selectNewCardsPopup #handNewCards li, #selectNewCardsPopup .button-troll.acceptRegroupCards').unbind();
							$('#selectNewCardsPopup #handNewCards li').click(function(event){
								if((!$(event.target).hasClass('ignore')) && event.which==1){
									$('#selectNewCardsPopup #handNewCards li').removeClass('glow');
									$(this).addClass('glow');
								}

							});
							//Функция отправки сообщения на соккет о перегруппировки выбраной карты
							cardReturnToHand(conn, ident);
							break;
					}
				}
			}
			/*
			 * @result.login  - указатель пользотеля что проводит текущий ход
			 */
			if(result.login == $('.user-describer').attr('id')){
				$('.info-block-with-timer .title-timer').text('Ваш ход').addClass('user-turn-green');
				allowToAction = true;
			}else{
				$('.info-block-with-timer .title-timer').text('ход противника:').removeClass('user-turn-green');
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
function circleRoundIndicator(){
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
var socketResult;
var ident;
var allowToAction = false;
var turnDescript = {"cardSource": "hand"};
var conn;
	$.get('/get_socket_settings', function (data) {
		socketResult = JSON.parse(data); //Получение данных настроек соккета
		//Формирование начального пакета идентификации битвы
		ident = {
			battleId: socketResult['battle'],
			userId: socketResult['user'],
			hash: socketResult['hash']
		};

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