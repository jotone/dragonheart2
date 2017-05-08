@extends('layouts.default')
@section('content')
<?php
$errors = $errors->all();
?>
@if(isset($user))
	@include('layouts.top')

	<div class="main">
		<div class="mbox">
			<div class="main_page cfix rase">
				<div class="content-top-wrap disable-select">
					<div class="dragon-image cfix">

							<div class="dragon-middle">
								<img src="{{ URL::asset('images/dragon_glaz.png') }}" alt=""  class="glaz" />
								<img src="{{ URL::asset('images/header_dragon_gold.png') }}" alt="" />
							</div>

					</div>
					<div class="tabulate-image"></div>
				</div>
				<div class="pager-wrapper">
					@include('layouts.sidebar')

					<div class="content-wrap main-bg" style="background-image: url({{$bg_img}});"></div>
				</div>

			</div>
		</div>
	</div>

@else

	<div class="main form-block one-screen-login">
		<div class="mbox login-page">

			<div class="forms-header"></div>
			<div class="about-wrapper">
				<div class="convert-about">
					<div class="contein">
						<div class="title">
							<h2>{!! $page_content->title !!}</h2>
						</div>
						<div class="ctext">
							{!! $page_content->text !!}
						</div>
					</div>
				</div>
				<div class="button-dropdown">
					<a href="" class="drop-menu-open button-buy-next">
						<span class="form-button-hover"></span>
						<span class="form-button-text show"> ОБ ИГРЕ </span>
						<span class="form-button-text back"> Свернуть </span>
					</a>
				</div>
			</div>

			<div class="forms-main vfix">

				<div class="conteiner-title">
					<div class="geib-text">
						<img src="{{ URL::asset('images/gayb-say.png') }}" alt="">
					</div>
				</div>

				<div class="conteiner-form">
					<div class="form-wrap">
						<div class="form-wrap-main">
							<div class="form-description">Войдите или зарегистрируйтесь</div>
							<div class="form-wrap-item placeholder-form">

								{{ Form::open(['route' => 'user-login', 'class' => 'forget-pass-form', 'method' => 'POST', 'autocomplete' => 'off']) }}
								<input name="_token" type="hidden" value="{{ csrf_token() }}">
								<input type="hidden" name="action" value="user_login" class="typesubmit" />
								<div class="form-wrap-for-rows" @if(!empty($errors)) style="display: block;" @endif>
									<div class="form-wrap-row form_row">
										<div class="form-wrap-value">
											<div class="form-wrap-input form_input">
												<p>Логин</p>
												<input type="text" name="login" required="required"/>
											</div>
										</div>
									</div>
									<div class="form-wrap-row form_row">
										<div class="form-wrap-value">
											<div class="form-wrap-input form_input">
												<p>Пароль</p>
												<input type="password" name="password" required="required"  />
											</div>
										</div>
									</div>
									<div class="form-links cfix">
										<div class="form-links-item right">
											<a href="forget_pass.html">Забыли пароль ?</a>
										</div>
									</div>
									<div class="form-wrap-row error-text" @if(!empty($errors)) style="display: block;" @endif>
										@if(!empty($errors))
											@foreach($errors as $error)
												<p>{!! $error !!}</p>
											@endforeach
										@endif
									</div>

								</div>
								<div class="form-wrap-row submit">
									<div class="form-wrap-value">
										<div class="form-wrap-input">
											<button class="button-buy-next  @if(empty($errors)) show-form-please @endif" type="submit">
												<span class="form-button-hover"></span>
												<span class="form-button-text">Вход</span>
											</button>
										</div>

									</div>
								</div>
								{{ Form::close() }}
							</div>

							<a href="{{ route('user-registration') }}" class="button-buy-next">
								<span class="form-button-hover"></span>
								<span class="form-button-text">Регистрация</span>
							</a>
						</div>
					</div>
				</div>

				<div class="row-with-rase">
					<div class="conteiner">

						@foreach($fractions as $fraction)
							<div class="item-rise">
								<a href="" class="rase-ric">
									<img src="{{ URL::asset('img/fractions_images/'.$fraction['img_url']) }}" alt="">
									<span>{{ $fraction['title'] }}</span>
								</a>
								<div class="hovered-block">
									<div class="close-this"></div>
									<div class="contein">
										<div class="top-img">
											<img src="{{ URL::asset('img/fractions_images/'.$fraction['img_url']) }}" alt="">
										</div>
										<div class="description">
											<div class="title-rase">
												{!! $fraction['title'] !!}

											</div>
											<div class="des-text">
												{!! $fraction['description'] !!}
												<br><br>
												<strong>Особенность фракции:</strong>
												{!! $fraction['short_description'] !!}
											</div>

										</div>
										<a href="{{ route('user-registration', ['fraction' => $fraction['slug']]) }}" class="button-troll">
											<b class="form-button-text"> Играть за фракцию {{ $fraction['title'] }}</b>
										</a>
									</div>
								</div>
							</div>
						@endforeach

					</div>
				</div>
			</div>
		</div>
	</div>
@endif
@stop
