<?php

if (array_key_exists('url', $_GET)
	&& ! empty ($_GET['url']))
{
	if (empty($_COOKIE['reddit_session']) && strpos($_GET['url'], '/r/') !== 0)
	{
		header('HTTP/1.1 500 Please Login');
		exit();
	}
	header('Content-Type: application/json');
	$url = 'https://www.reddit.com' . $_GET['url'];
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_COOKIE, 'reddit_session=' .$_COOKIE['reddit_session']);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	// curl_setopt($curl, CURLOPT_HEADER, true);
	$response = curl_exec($curl);
	strlen($response) < 10 && header('HTTP/1.1 500 Please Login');
	exit($response);
}
if (array_key_exists('username', $_POST)
	&& ! empty ($_POST['username']))
{
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, 'https://www.reddit.com/api/login');
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
		'api_type'=> 'json',
		'passwd'  => $_POST['password'],
		'rem'     => 0,
		'user'    => $_POST['username']
	)));
	// curl_setopt($curl, CURLOPT_HEADER, true);
	$response = curl_exec($curl);
	$response = json_decode($response, true);
	setcookie('reddit_session', $response['json']['data']['cookie']);
	// exit($response);
}
?>
<html>
	<head>
		<title>Gallery</title>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script> <!-- 33 KB -->
		<!-- fotorama.css & fotorama.js. -->
		<link  href="http://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.3/fotorama.css" rel="stylesheet"> <!-- 3 KB -->
		<script src="http://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.3/fotorama.js"></script> <!-- 16 KB -->
	</head>
	<body style="background-color: #A6A6A6;">
		<div class="fotorama"></div>
		<div class="login" style="display:none;">
			<form method="post" action="">
				<input type="text" placeholder="Username" name="username" />
				<input type="password" placeholder="Password" name="password" />
				<input type="submit">
			</form>
		</div>
		<script type="text/javascript">
			var topic = document.location.hash.replace('#', '')
				, counter = $('<div class="fotorama__count">').css({'color': 'white'})
				, fotorama = $('.fotorama').fotorama({
				allowfullscreen: 'native',
				transition: 'crossfade',
				loop: true,
				autoplay: true,
				stopautoplayontouch: false,
				shuffle: true,
				nav: false
			}).data('fotorama');

			function poll(url) {
				console.log(new Date(), url);
				$.getJSON(url, function (data) {
					console.log(data);
					var _data = [];
					$.each(data.data.children, function(i, el) {
						_data.push({img:el.data.url, caption:el.data.title});
					});
					if (fotorama.data) {
						$.each(_data, function(i, el) {
							var exist = false;
							$.each(fotorama.data, function(ii, ell) {
								if (ell.img == el.img) exist = true;
							});
							exist || fotorama.data.push(el);
						});
						fotorama.shuffle.apply(fotorama);
					} else {
						fotorama.load(_data);
					}
					$('.fotorama__count').length || $('.fotorama__fullscreen-icon').before(counter);
					$('.fotorama__count').html(fotorama.size);
				}).fail(function (jqXhr, textStatus, errorThrown) {
					if (errorThrown == 'Please Login') {
						$('.fotorama').html(errorThrown);
						$('.login').show();
					}
				}).always(function (jqXhr) {
					setTimeout(function (){
						poll(url);
					}, 1000 * 60 * 30);
				});
			}
			poll('?url=/user/me/liked.json%3Flimit%3D100');
			topic && poll('?url=/r/' + topic + '.json%3Flimit%3D100');
		</script>
	</body>
</html>