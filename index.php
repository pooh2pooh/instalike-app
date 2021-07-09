<?php session_start();

  # Проверяем авторизован ли пользователь,
  # если да, направляем его сразу к нашим картинкам

  if (isset($_SESSION['last_action_time']) && time() < $_SESSION['last_action_time'] + (60*60))
    header('Location: list.php');
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Pooh Pooh">
    <meta name="generator" content="Hugo 0.83.1">
    <title>Login</title>

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

    
    <!-- Custom styles for this template -->
    <link href="signin.css" rel="stylesheet">
  </head>
  <body class="text-center">
    
    <main class="form-signin">
      <form action="list.php" method="post">
        <img class="mb-4" src="android-chrome-512x512.png" alt="" width="72" height="72">
        <h1 class="h3 mb-3 fw-normal">Please sign in</h1>

        <span class="text-danger fw-bold">
        <?php

          $err = '';

          if (isset($_GET['error']))
            $err = 'error! required input\'s';
          else if (isset($_GET['timeout']))
            $err = 'error! timeout (1h)';
          else if (isset($_GET['auth_error']))
            $err = 'error! invalid login or password';

          echo $err;
        ?>
        </span>

        <div class="form-floating">
          <input name="login" type="text" class="form-control" id="floatingInput" placeholder="Pooh">
          <label for="floatingInput">Login</label>
        </div>
        <div class="form-floating">
          <input name="password" type="password" class="form-control" id="floatingPassword" placeholder="NoPassword">
          <label for="floatingPassword">Password</label>
        </div>

        <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
        <p class="mt-5 mb-3 text-muted">© coding with love by pooh, 2021</p>
      </form>
    </main>


    
  

</body>
</html>