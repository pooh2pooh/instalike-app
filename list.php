<?php session_start();

  include_once "config.inc.php";
  
  # Если и логин и пароль пришли,
  # сверяем их с теми что хранятся в базе
  # ; НЕ ЗАБУДЬ УКАЗАТЬ ПРАВИЛЬНЫЕ ДАННЫЕ ДЛЯ ПОДКЛЮЧЕНИЯ К БАЗЕ!

  $conn = new mysqli($config['server'], $config['user'], $config['password'], $config['database']); # <-- ДАННЫЕ ДЛЯ ПОДКЛЮЧЕНИЯ К БАЗЕ ПРОПИСЫВАТЬ В config.inc.php

  # Проверим подключение к базе,
  # если нет его, ломаем всё нахер

  if ($conn->connect_error)
    die ('Connection failed: ' . $conn->connect_error);



  # Сохраняем отправленную картинку
  # Проверяем её тип и размер
  # В данном примере разрешены только JPEG и PNG картинки,
  # Размером от 250 килобайт до 5 мегабайт
  # Разрешение не больше 1500х1500

  if (isset($_FILES['image_upload'])) {

    #var_dump($_FILES['image_upload']);

    if (!isset($_SESSION['image_uploaded_timer']))
      $_SESSION['image_uploaded_timer'] = 1;
    #else if ($_SESSION['image_uploaded_timer'] + (3 * 60) > time())
    #  die ('Wait 3 min.!');

    $path = 'images/full/';
    list($width, $height) = getimagesize($_FILES['image_upload']['tmp_name']);


    if (exif_imagetype($_FILES['image_upload']['tmp_name']) != IMAGETYPE_JPEG && exif_imagetype($_FILES['image_upload']['tmp_name']) != IMAGETYPE_PNG)
      die ('Wrong Image Type!');

    else if (intval($_FILES['image_upload']['size']/1024) < 200 || intval($_FILES['image_upload']['size']/1024) > 5242880)
      die ('Wrong Image Size!<br>SIZE: ' . $_FILES['image_upload']['size']/1024);

    else if ($width > 4000 || $height > 4000)
      die ('Wrong Width or Height!');

    $safe_author_id = $_SESSION['id'];
    $safe_image_name = $_FILES['image_upload']['name'];

    $images_query_old = "SELECT * FROM images WHERE name = '$safe_image_name'";
    $images_result_old = $conn->query($images_query_old);
    $images_row_old = $images_result_old->fetch_row();

    if ($images_row_old) {
      if (time() < strtotime($images_row_old[2]) + (15 * 60))
        die ('Image Such! Wait 15 min.');
    }

    copy($_FILES['image_upload']['tmp_name'], $path. $safe_image_name);
    resize($safe_image_name, 400);

    $images_query = "INSERT INTO images (name, author_id, views) VALUES ('$safe_image_name', '$safe_author_id', 0)";
    $images_result = $conn->query($images_query);

    $_SESSION['image_uploaded_timer'] = time();

    if (!$images_result)
        die ($conn->error);

  }



  # Сохраняем отправленный комментарий
  # Проверяем его на запрещённые слова:
  # лес, поляна, озеро

  if (isset($_POST['message']) && isset($_POST['id'])) {

    $safe_author_id = $_SESSION['id'];
    $safe_image_id = intval($_POST['id']);
    $safe_message = htmlentities($_POST['message']);
    if (preg_match('/(' . implode('|', ['\bлес\b', '\bозеро\b', '\bполяна\b']) . ')/iu', $safe_message))
      die ('Censored Comment!');
    $comment_query = "INSERT INTO comments (author_id, image_id, message) VALUES ('$safe_author_id', '$safe_image_id', '$safe_message')";
    $comment_result = $conn->query($comment_query);

    if (!$comment_result)
        die ($conn->error);

  }





  if (isset($_POST['login']) && isset($_POST['password'])) {

    

    # Если к базе подключились,
    # читаем правильные логин и пароль из неё

    $safe_login = htmlentities($_POST['login']);
    $query = "SELECT * FROM users WHERE login = '$safe_login'";
    $result = $conn->query($query);
    $row = $result->fetch_array(MYSQLI_ASSOC);

    # Если такого логина нет,
    # создаём его. Иначе проверяем пароль

    if(!$row) {

      $salt = randomSalt();
      $hash = md5($_POST['password'] . $salt);
      $reg_query = "INSERT INTO users (login, password, salt) VALUES ('$safe_login', '$hash', '$salt')";
      $reg_result = $conn->query($reg_query);

      # Если не удалось создать пользователя,
      # ломаем всё нахер. Иначе авторизуем пользователя
      # и показываем сообщение об успешной регистрации

      if (!$reg_result)
        die ($conn->error);
      else {
        $query = "SELECT * FROM users WHERE login = '$safe_login'";
        $result = $conn->query($query);
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $_SESSION['id'] = $row['id'];
        $_SESSION['last_action_time'] = time();
        echo '<p class="bg-success text-white fw-bold m-0 p-0">Поздравляем с успешной регистрацией!</p>';
      }
    }

    else {
      
      if (!strcmp($row['password'], md5($_POST['password'] . $row['salt']))) {
        $_SESSION['last_action_time'] = time();
        $_SESSION['id'] = $row['id'];
      }
      else
        header('Location: index.php?auth_error=true');

    }
  }

  else if (isset($_GET['delete'])) {

    # Возвращаем на авторизацию,
    # если что :)

    if (!isset($_SESSION['id']))
      header('Location: index.php?error=true');

    # Проверяем прошёл ли час с момента последнего действия пользователя,
    # если да, просим его авторизоваться повторно

    if (isset($_SESSION['last_action_time']) && time() >= $_SESSION['last_action_time'] + (60*60))
      header('Location: index.php?timeout=true');

    # Если пользователь не авторизован,
    # не пускаем его к нашим картинкам

    else if (!isset($_SESSION['last_action_time']))
      header('Location: index.php?error=true');

    $safe_image_id = intval($_GET['delete']);
    $image_query = "DELETE FROM images WHERE id = '$safe_image_id'";
    $image_result = $conn->query($image_query);

  }

  # Страница ПОДРОБНО

  else if (isset($_GET['view'])) {

    # Возвращаем на авторизацию,
    # если что :)

    if (!isset($_SESSION['id']))
      header('Location: index.php?error=true');

    # Проверяем прошёл ли час с момента последнего действия пользователя,
    # если да, просим его авторизоваться повторно

    if (isset($_SESSION['last_action_time']) && time() >= $_SESSION['last_action_time'] + (60*60))
      header('Location: index.php?timeout=true');

    # Если пользователь не авторизован,
    # не пускаем его к нашим картинкам

    else if (!isset($_SESSION['last_action_time']))
      header('Location: index.php?error=true');

    $safe_image_id = intval($_GET['view']);
    $image_query = "SELECT * FROM images WHERE id = '$safe_image_id'";
    $image_result = $conn->query($image_query);
    $image_row = $image_result->fetch_array(MYSQLI_ASSOC);

    if (!$image_row)
      die ('Image ID No Such!');
    else {
      $safe_views = intval($image_row['views']) + 1;
      $image_update_views_query = "UPDATE images SET views = '$safe_views' WHERE id = '$safe_image_id'";
      $image_update_views_result = $conn->query($image_update_views_query);
    }

?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="">
  <meta name="author" content="Pooh Pooh">
  <meta name="generator" content="Hugo 0.83.1">
  <title>More ID <?=$image_row['id']?> Image</title>

  <!-- Bootstrap core CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous">

  <!-- Favicons -->
  <link rel="apple-touch-icon" href="apple-touch-icon.png" sizes="180x180">
  <link rel="icon" href="favicon-32x32.png" sizes="32x32" type="image/png">
  <link rel="icon" href="favicon-16x16.png" sizes="16x16" type="image/png">
  <!-- <link rel="manifest" href="/docs/5.0/assets/img/favicons/manifest.json"> -->
  <!-- <link rel="mask-icon" href="/docs/5.0/assets/img/favicons/safari-pinned-tab.svg" color="#7952b3"> -->
  <link rel="icon" href="favicon.ico">
  <meta name="theme-color" content="#7952b3">


  <style>
    .bd-placeholder-img {
    font-size: 1.125rem;
    text-anchor: middle;
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
    }

    @media (min-width: 768px) {
    .bd-placeholder-img-lg {
      font-size: 3.5rem;
    }
    }
  </style>
  </head>
  <body>
  <main>

    <div class="container-fluid">
      <div class="row py-3">
        <div class="col text-center">
          <a class="lead text-decoration-none text-muted p-5" href="list.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-left" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5z"/>
            </svg>
            Вернуться назад к списку картинок
          </a>
        </div>
      </div>
    </div>

    <div class="album py-5 bg-light">

      <div class="container">

        <div class="row">
          <div class="col text-center">
            
            <img class="img-fluid" src="get_thumb.php?full=<?=$image_row['name']?>">

          </div>
        </div>

        <div class="row">
      
          <div class="col text-center">
            <div class="btn-group">
              <button class="btn btn-light" disabled>Автор: <?=getAuthor($conn, $image_row['author_id'])?></button>
              <button class="btn btn-light" disabled>Добавлено: <?=$image_row['date']?></button>
              <button class="btn btn-light" disabled>Комментарии: <?=getCommentCount($conn, $image_row['id'])?></button>
              <button class="btn btn-light" disabled>Просмотры: <?=$image_row['views']?></button>
            </div>
          
          </div>

        </div>


      </div>
    </div>

    <div class="py-5">
      <div class="container">

        <div class="row">
          <form action="list.php?view=<?=$image_row['id']?>" method="post">
            <div class="input-group mb-3">
                <input type="hidden" name="id" value="<?=$image_row['id']?>">
                <input type="text" name="message" class="form-control" placeholder="Напиши сюда текст комментария..." aria-label="Recipient's username" aria-describedby="button-addon2">
                <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Отправить</button>
            </div>
          </form>
          <div class="col">
            
            <?php

              # Получаем все комментарии из базы,
              # и выводим их пользователю

              $comm_query = "SELECT * FROM comments WHERE image_id = '$safe_image_id' ORDER BY id DESC";
              $comm_result = $conn->query($comm_query);

              # Сохраняем отредактированный коммент

              if (isset($_POST['cid']) && isset($_POST['cmessage'])) {

                $safe_cid = intval($_POST['cid']);
                $safe_cmessage = htmlentities($_POST['cmessage']);
                $safe_history = htmlentities($_POST['history']);
                $update_comm_query = "UPDATE comments SET message = '$safe_cmessage', history = '$safe_history' WHERE id = '$safe_cid'";
                $update_comm_result = $conn->query($update_comm_query);
                $comm_result = $conn->query($comm_query);

                if(!$update_comm_result)
                  die ('Error! Comment Not Edited');

              }

              # Удаляем коммент

              if (isset($_GET['delete_comment'])) {

                $safe_delete_id = intval($_GET['delete_comment']);
                $delete_comm_query = "DELETE FROM comments WHERE id = '$safe_delete_id'";
                $delete_comm_result = $conn->query($delete_comm_query);
                $comm_result = $conn->query($comm_query);

                if(!$delete_comm_result)
                  die ('Error! Comment Not Deleted');

              }


              while ($comm_row = $comm_result->fetch_array(MYSQLI_ASSOC)) {


            ?>
            <div class="card">
              <div class="card-body">
                <span class="small text-muted"><?=$comm_row['date']?></span>
                <span class="fw-bold"><?=getAuthor($conn, $comm_row['author_id'])?></span>:
                <span><?=$comm_row['message']?></span>
                <p class="float-end m-0 p-0">
                  <?php
                    if ($comm_row['history']) {
                  ?>
                    <span class="small text-muted">[Edited]</span>
                  <?php
                    }

                    if ($comm_row['author_id'] == $_SESSION['id']) {
                      if (time() < strtotime($comm_row['date']) + (5 * 60)) {
                  ?>
                    <a href="?edit_comment=<?=$comm_row['id']?>">edit</a>
                  <?php
                      }
                  ?>
                    <a href="?view=<?=$image_row['id']?>&delete_comment=<?=$comm_row['id']?>">delete</a>
                  <?php
                    }
                  ?>
                </p>
              </div>
            </div>

            <?php

              }
            ?>
          </div>
        </div>


      </div>
    </div>

  </main>


  
  

</body>
</html>

<?php

    return true;
  }

  else if (isset($_GET['edit_comment'])) {

    # Возвращаем на авторизацию,
    # если что :)

    if (!isset($_SESSION['id']))
      header('Location: index.php?error=true');

    $safe_comment_id = intval($_GET['edit_comment']);
    $comment_query = "SELECT * FROM comments WHERE id = '$safe_comment_id'";
    $comment_result = $conn->query($comment_query);
    $comment_row = $comment_result->fetch_array(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="">
  <meta name="author" content="Pooh Pooh">
  <meta name="generator" content="Hugo 0.83.1">
  <title>Edit ID <?=$comment_row['id']?> Comment</title>

  <!-- Bootstrap core CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous">

  <!-- Favicons -->
  <link rel="apple-touch-icon" href="apple-touch-icon.png" sizes="180x180">
  <link rel="icon" href="favicon-32x32.png" sizes="32x32" type="image/png">
  <link rel="icon" href="favicon-16x16.png" sizes="16x16" type="image/png">
  <!-- <link rel="manifest" href="/docs/5.0/assets/img/favicons/manifest.json"> -->
  <!-- <link rel="mask-icon" href="/docs/5.0/assets/img/favicons/safari-pinned-tab.svg" color="#7952b3"> -->
  <link rel="icon" href="favicon.ico">
  <meta name="theme-color" content="#7952b3">


  <style>
    .bd-placeholder-img {
    font-size: 1.125rem;
    text-anchor: middle;
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
    }

    @media (min-width: 768px) {
    .bd-placeholder-img-lg {
      font-size: 3.5rem;
    }
    }
  </style>
  </head>
  <body>
  <main>


<?php

  if (!$comment_row)
    $safe_return_url = 'list.php';
  else
    $safe_return_url = 'list.php?view=' . $comment_row['image_id'];

?>

    <div class="container-fluid">
      <div class="row py-3">
        <div class="col text-center">
          <a class="lead text-decoration-none text-muted p-5" href="<?=$safe_return_url?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-left" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5z"/>
            </svg>
            Вернуться назад к просмотру картинки
          </a>
        </div>
      </div>
    </div>

<?php

    if (!$comment_row || $comment_row['author_id'] != $_SESSION['id']) {

?>

      <p class="lead fw-bold text-center text-danger">Нет такого комментария, или он не твой!</p>



<?php
    }

    else {
?>
      <div class="container">
<?php

      $history = explode('/', $comment_row['history']);
      
      for ($i = 0; $i < sizeof($history); $i++) {
        if ($i == 0) continue;

?>


        <div class="card bg-light text-dark">
          <div class="card-body">
            <span class="small text-muted"><?=$history[$i]?></span>
            <?php $i++; ?>
            <span><?=$history[$i]?></span>
          </div>
        </div>

<?php
      }
?>
      </div>

    <div class="py-5">
      <div class="container">

        <div class="row">
          <form action="list.php?view=<?=$comment_row['image_id']?>" method="post">
            <div class="input-group mb-3">
                <input type="hidden" name="cid" value="<?=$comment_row['id']?>">
                <input type="hidden" name="history" value="<?=$comment_row['history'].'/'.$comment_row['date'].'/'.$comment_row['message']?>">
                <input type="text" name="cmessage" class="form-control" placeholder="Напиши сюда текст комментария..." aria-label="Recipient's username" aria-describedby="button-addon2" value="<?=$comment_row['message']?>">
                <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Отправить</button>
            </div>
          </form>
        </div>


      </div>
    </div>

<?php
    }
?>

  </main>


  
  

</body>
</html>



<?php

    return true;

  }

  else if (isset($_GET['upload_image'])) {


?>


<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="">
  <meta name="author" content="Pooh Pooh">
  <meta name="generator" content="Hugo 0.83.1">
  <title>Upload Image</title>

  <!-- Bootstrap core CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous">

  <!-- Favicons -->
  <link rel="apple-touch-icon" href="apple-touch-icon.png" sizes="180x180">
  <link rel="icon" href="favicon-32x32.png" sizes="32x32" type="image/png">
  <link rel="icon" href="favicon-16x16.png" sizes="16x16" type="image/png">
  <!-- <link rel="manifest" href="/docs/5.0/assets/img/favicons/manifest.json"> -->
  <!-- <link rel="mask-icon" href="/docs/5.0/assets/img/favicons/safari-pinned-tab.svg" color="#7952b3"> -->
  <link rel="icon" href="favicon.ico">
  <meta name="theme-color" content="#7952b3">


  <style>
    .bd-placeholder-img {
    font-size: 1.125rem;
    text-anchor: middle;
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
    }

    @media (min-width: 768px) {
    .bd-placeholder-img-lg {
      font-size: 3.5rem;
    }
    }
  </style>
  </head>
  <body>
  <main>

    <div class="container-fluid">
      <div class="row py-3">
        <div class="col text-center">
          <a class="lead text-decoration-none text-muted p-5" href="list.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-return-left" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5z"/>
            </svg>
            Вернуться назад к списку картинок
          </a>
        </div>
      </div>
    </div>


    <div class="py-5">
      <div class="container">

        <div class="row">
          <form enctype="multipart/form-data" action="list.php" method="post">
            <div class="input-group mb-3">
            
                <input type="file" name="image_upload" class="form-control" placeholder="Напиши сюда текст комментария..." aria-label="Recipient's username" aria-describedby="button-addon2">
                <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Отправить</button>
            </div>
          </form>
        </div>


      </div>
    </div>

  </main>


  
  

</body>
</html>





<?php




    return true;

  }

  else {

    # Проверяем прошёл ли час с момента последнего действия пользователя,
    # если да, просим его авторизоваться повторно

    if (isset($_SESSION['last_action_time']) && time() >= $_SESSION['last_action_time'] + (60*60))
      header('Location: index.php?timeout=true');

    # Если пользователь не авторизован,
    # не пускаем его к нашим картинкам

    else if (!isset($_SESSION['last_action_time']))
      header('Location: index.php?error=true');
  }
?>

  
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="">
  <meta name="author" content="Pooh Pooh">
  <meta name="generator" content="Hugo 0.83.1">
  <title>List</title>

  <!-- Bootstrap core CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-wEmeIV1mKuiNpC+IOBjI7aAzPcEZeedi5yW5f2yOq55WWLwNGmvvx4Um1vskeMj0" crossorigin="anonymous">

  <!-- Favicons -->
  <link rel="apple-touch-icon" href="apple-touch-icon.png" sizes="180x180">
  <link rel="icon" href="favicon-32x32.png" sizes="32x32" type="image/png">
  <link rel="icon" href="favicon-16x16.png" sizes="16x16" type="image/png">
  <!-- <link rel="manifest" href="/docs/5.0/assets/img/favicons/manifest.json"> -->
  <!-- <link rel="mask-icon" href="/docs/5.0/assets/img/favicons/safari-pinned-tab.svg" color="#7952b3"> -->
  <link rel="icon" href="favicon.ico">
  <meta name="theme-color" content="#7952b3">


  <style>
    .bd-placeholder-img {
    font-size: 1.125rem;
    text-anchor: middle;
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
    }

    @media (min-width: 768px) {
    .bd-placeholder-img-lg {
      font-size: 3.5rem;
    }
    }
  </style>
  </head>
  <body class="text-center">
  <main>

    <div class="container-fluid">
      <div class="row py-3">
        <div class="col text-center">
          <a class="lead text-decoration-none text-muted p-5" href="?upload_image">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-cloud-plus" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M8 5.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V10a.5.5 0 0 1-1 0V8.5H6a.5.5 0 0 1 0-1h1.5V6a.5.5 0 0 1 .5-.5z"/>
            <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383zm.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/>
            </svg>
            Добавить картинку
          </a>
        </div>
      </div>
    </div>


    <div class="album py-5 bg-light">
    <div class="container">

      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">

        <?php

          # Получаем все картинки из базы,
          # и выводим их пользователю

          $images_query = "SELECT * FROM images ORDER BY ID DESC";
          $images_result = $conn->query($images_query);

          while ($images_row = $images_result->fetch_array(MYSQLI_ASSOC)) {
        ?>

        <div class="col">
          <div class="card shadow-sm h-100">
            
            <img class="mx-auto" src="get_thumb.php?thumb=<?=$images_row['name']?>">

            <div class="card-body d-flex align-items-end">
              <div class="btn-group mx-auto">
                <a href="?view=<?=$images_row['id']?>" class="btn btn-sm btn-light">Подробнее</a>
                <button type="button" class="btn btn-sm" disabled>
                  <?=getCommentCount($conn, $images_row['id'])?>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat" viewBox="0 0 16 16">
                  <path d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z"/>
                  </svg>
                </button>
                <button type="button" class="btn btn-sm" disabled>
                  <?=$images_row['views']?>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                  <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                  </svg>
                </button>
                <?php if ($images_row['author_id'] == $_SESSION['id']) { ?>
                  <a href="?delete=<?=$images_row['id']?>" class="btn btn-sm btn-light">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                      <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                      <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                    </svg>
                  </a>
                <?php } ?>
                
              </div>
            </div>
          </div>
        </div>

        <?php
          }

        ?>

      </div>
  </main>


  
  

</body>
</html>


<?php

  # Функция для генерации "соли"

  function randomSalt($len = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789`~!@#$%^&*()-=_+';
    $l = strlen($chars) - 1;
    $str = '';
    for ($i = 0; $i < $len; ++$i) {
      $str .= $chars[rand(0, $l)];
    }
    return $str;
  }

  # Функция сжатия картинок

  /*
  $w_o и h_o - ширина и высота выходного изображения
  */
  function resize($image, $w_o = false, $h_o = false) {
    if (($w_o < 0) || ($h_o < 0)) {
      echo "Некорректные входные параметры";
      return false;
    }
    list($w_i, $h_i, $type) = getimagesize('images/full/' . $image); // Получаем размеры и тип изображения (число)
    $types = array("", "gif", "jpeg", "png"); // Массив с типами изображений
    $ext = $types[$type]; // Зная "числовой" тип изображения, узнаём название типа
    if ($ext) {
      $func = 'imagecreatefrom'.$ext; // Получаем название функции, соответствующую типу, для создания изображения
      $img_i = $func('images/full/' . $image); // Создаём дескриптор для работы с исходным изображением
    } else {
      echo 'Некорректное изображение'; // Выводим ошибку, если формат изображения недопустимый
      return false;
    }
    /* Если указать только 1 параметр, то второй подстроится пропорционально */
    if (!$h_o) $h_o = $w_o / ($w_i / $h_i);
    if (!$w_o) $w_o = $h_o / ($h_i / $w_i);
    $img_o = imagecreatetruecolor($w_o, $h_o); // Создаём дескриптор для выходного изображения
    imagecopyresampled($img_o, $img_i, 0, 0, 0, 0, $w_o, $h_o, $w_i, $h_i); // Переносим изображение из исходного в выходное, масштабируя его
    $func = 'image'.$ext; // Получаем функция для сохранения результата
    return $func($img_o, 'images/thumbs/' . $image); // Сохраняем изображение в тот же файл, что и исходное, возвращая результат этой операции
  }

  # Функция возвращает имя пользователя по ID

  function getAuthor($conn, $id) {
    $id = intval($id);
    $query = "SELECT * FROM users WHERE id = '$id'";
    $result = $conn->query($query);
    $row = $result->fetch_array(MYSQLI_ASSOC);
    if(!$row)
      return 'Unknown Author!';
    return $row['login'];
  }

  # Функция возвращает количество комментариев по ID картинки

  function getCommentCount($conn, $img_id) {
    $id = intval($img_id);
    $query = "SELECT COUNT(*) FROM comments WHERE image_id = '$id'";
    $result = $conn->query($query);
    $row = $result->fetch_row();
    if(!$row)
      return 'Unknown Image ID!';
    return $row[0];
  }
  
?>
