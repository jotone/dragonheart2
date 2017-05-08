<header class="header">
	<div class="mbox">

		<div class="header-box cfix">
			<div class="convert-header">
				<div class="user preload">
					<div class="preloader">
						<img src="{{ URL::asset('images/359.gif') }}" alt="">
					</div>
					<div class="user-image"></div>
					<div class="user-name">{{ $user['login'] }}</div>
				</div>
				<div class="convert-stats">
					<div class="stats ">
						<div class="time-box">
							<img src="{{ URL::asset('images/header_logo_time.png') }}" alt="" />
							<div class="time"> 04:36:22 </div>
						</div>
						<div class="people-box preload">
							<div class="preload-peoples">
								<img src="{{ URL::asset('images/379.gif') }}" alt="">
							</div>
							<img src="{{ URL::asset('images/header_logo_man.png') }}" alt="" />
							<div class="people"></div>
						</div>
					</div>
				</div>
				<div class="rating">
					<p>РЕЙТИНГ</p>
					<div class="convert-resurses preload">
						<div class="preload-resurses">
							<img src="{{ URL::asset('images/76.gif') }}" alt="">
						</div>

						<div class="resurses">
							<a href="#buy-gold" class="button-plus buy-more-gold"></a>
							<img src="{{ URL::asset('images/header_logo_gold.png') }}" alt="" />

							<div class="gold"></div>
						</div>
						<div class="resurses">
							<a href="#buy-silver" class="button-plus buy-more-silver"></a>
							<img src="{{ URL::asset('images/header_logo_silver.png') }}" alt="" />
							<div class="silver"></div>
						</div>
						<div class="resurses ">
							<a href="#buy-energy" class="button-plus buy-more-energy"></a>
							<img src="{{ URL::asset('images/header_logo_lighting.png') }}" alt="" />
							<div class="lighting"></div>
						</div>
					</div>
				</div>
				<div class="button-PRO-convert @if($user['premium_activated'] == 1) active @endif">
					@if($user['premium_activated'] == 1)
					<div class="prem-info"><p>Дата окончания: <b>{{ substr($user['premium_expire_date'],0, -6)}}</b> </p></div>
					@endif
					<a href="#" class="button-push">
						<div class="button-PRO"> <p> PREMIUM </p></div>
					</a>
				</div>
			</div>
		</div>
	</div>
</header>