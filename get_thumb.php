<?php session_start();
	
	# Проверяем прошёл ли час с момента последнего действия пользователя,
    # если да, просим его авторизоваться повторно

    if (isset($_SESSION['last_action_time']) && time() >= $_SESSION['last_action_time'] + (24*60*60))
      header('Location: index.php?timeout=true');

    # Если пользователь не авторизован,
    # не пускаем его к нашим картинкам

    else if (!isset($_SESSION['last_action_time']))
      header('Location: index.php?error=true');


  	# Показываем нашу миниатюру

  	header('Content-type: image');

  	if (isset($_GET['thumb']))
		$file = file_get_contents('images/thumbs/' . $_GET['thumb'], true);

  	else if (isset($_GET['full']))
		$file = file_get_contents('images/full/' . $_GET['full'], true);

  	
	echo $file;