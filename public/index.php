<?php

if (array_key_exists('url', $_GET)
	&& ! empty ($_GET['url']))
{
	if (empty($_COOKIE['reddit_session']) && strpos($_GET['url'], '/r/') !== 0)
	{
		header('HTTP/1.1 403 Please Login');
		exit();
	}
	header('Content-Type: application/json');
	$url = 'https://www.reddit.com' . $_GET['url'];
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_COOKIE, 'reddit_session=' .$_COOKIE['reddit_session']);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	if (! empty($_POST['POST']))
	{
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_POST));
	}
	// curl_setopt($curl, CURLOPT_HEADER, true);
	$response = curl_exec($curl);
	strlen($response) < 10 && header('HTTP/1.1 403 Please Login');
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

header('Cache-Control: private, max-age=86400');
?>
<html>
	<head>
		<title>Gallery</title>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script> <!-- 33 KB -->
		<!-- fotorama.css & fotorama.js. -->
		<link  href="http://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.3/fotorama.css" rel="stylesheet"> <!-- 3 KB -->
		<script src="http://cdnjs.cloudflare.com/ajax/libs/fotorama/4.6.3/fotorama.js"></script> <!-- 16 KB -->
	</head>
	<body style="background-color: #A6A6A6; font-family: verdana;">
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
				, lastTimeout = {}
				, r = {config: {}}
				, fotorama = $('.fotorama').fotorama({
				allowfullscreen: 'native',
				transition: 'crossfade',
				loop: true,
				autoplay: true,
				stopautoplayontouch: false,
				shuffle: true,
				arrows: false,
				nav: false
			}).data('fotorama');

			function deleteImage(activeFrameId) {
				$.each(fotorama.data, function (index, element) {
					if (typeof element !== "object") return;
					if (element.id === activeFrameId) {
						console.log(index, element);
						fotorama.splice(index, 1);
					}
				});
				persistToLocalStorage('fotorama', fotorama.data);
			}

			function uniqFotoramaCollection() {
				var holder = {};
				$.each(fotorama.data, function (index, element) {
					if (typeof element !== "object") return;
					if (holder[element.id]) {
						console.log(index, holder[element.id].index, element);
						fotorama.splice(index, 1);
					} else {
						element.index = index;
						holder[element.id] = element;
					}
				});
				persistToLocalStorage('fotorama', fotorama.data);
			}

			function persistToLocalStorage(key, data) {
				var newData = [];
				$.each(data, function (index, element) {
					if (typeof element !== "object") return;
					newData.push({id:element.id, img:element.img, caption:element.caption});
				});
				localStorage.setItem(key, JSON.stringify(newData));
			}

			function updateCounter() {
				if (! $('.fotorama__count').length) {
					$('.fotorama__fullscreen-icon')
						.before($('<div class="fotorama__count">')
						.css({'color': 'white'}));
				}
				return $('.fotorama__count').html(fotorama.size);
			}

			function addUpvote() {
				$.getJSON('?url=/api/me.json', function(data) {
					r.config.modhash = data.data.modhash;
				});
				$('.fotorama__wrap').prepend(
					$('<div>^^</div>').css({'color': 'white', 'float':'right'})
						.click(function(event){
							console.log("clicked on "+fotorama.activeFrame.id);
							if (! r.config.modhash) return;
							$.ajax({
								  url: '?url=/api/vote'
								, method: 'POST'
								, data: {
									  dir:1
									, id: fotorama.activeFrame.id
									, uh: r.config.modhash
									, vh: 'VVoteHash'
								}
								, success: function(data) {
									console.log("successful", data);
								}
								, error: function(jqXhr, textStatus, errorThrown){
									console.log("fail", textStatus, errorThrown);
								}
							});
						})
				);
			}

			function poll(url) {
				console.log(new Date(), url);
				var timeout = 0;
				$.getJSON(url, function (data) {
					var _data = [];
					$.each(data.data.children, function(i, el) {
						// if media_embed not empty, do something else
						// if url has /a/, show collection
						if (el.data.url.match(/\/a\//)) return;
						_data.push({id:el.data.id, img:el.data.url.replace(/gifv$/, 'gif'), caption:el.data.title});
					});
					if (fotorama.data) {
						$.each(_data, function(i, el) {
							if (typeof el !== "object") return;
							var exist = false;
							$.each(fotorama.data, function(ii, ell) {
								if (typeof ell !== "object") return;
								if (ell.img == el.img) exist = true;
							});
							if (! exist) {
								fotorama.push(el);
							}
						});
						fotorama.shuffle.apply(fotorama);
					} else {
						fotorama.load(_data);
					}
					persistToLocalStorage('fotorama', fotorama.data);
				}).fail(function (jqXhr, textStatus, errorThrown) {
					if (errorThrown == 'Please Login') {
						$('.fotorama').html(errorThrown);
						$('.login').show();
					}
					timeout = 5;
				}).always(function (jqXhr) {
					timeout = timeout || 30;
					console.log('trying again in ' + timeout + ' minutes');
					lastTimeout[url] = setTimeout(function (){
						poll(url);
					}, 1000 * 60 * timeout);
					updateCounter();
				});
			}

			fotorama.load(JSON.parse(localStorage.getItem('fotorama')));
			updateCounter();
			poll('?url=/user/me/liked.json%3Flimit%3D100');
			topic && poll('?url=/r/' + topic + '.json%3Flimit%3D100%26sort%3Dtop');
		</script>
	</body>
</html>
