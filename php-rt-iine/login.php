<?php
if(!isset($_SESSION)){
  session_start();
}
require('dbconnect.php');

if($_COOKIE['email']!='' && $_COOKIE['password']!=''){
  $_POST['email']=$_COOKIE['email'];
  $_POST['password']=$_COOKIE['password'];
  $_POST['save']='on';
}

if(!empty($_POST)){
  if($_POST['email']!='' && $_POST['password']!=''){
    $login=$db->prepare('SELECT * FROM members WHERE email=? AND password=?');
    $login->execute(array(
      $_POST['email'],
      sha1($_POST['password'])
    ));
    $member=$login->fetch();

    if($member){
      $_SESSION['id']=$member['id'];
      $_SESSION['time']=time();
    
      if(isset($_POST) && $_POST['save']=='on'){
        setcookie('email',$_POST['email'],time()+60*60*24*14);
        setcookie('password',$_POST['password'],time()+60*60*24*14);
      }

      header('location:index.php');
      exit();
    }else{
      $error['login']='failed';
    }
  }else{
    $error['login']='blank';
  }
}

// ファンクションの省略
function h($value){
  return htmlspecialchars($value,ENT_QUOTES);
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="./style.css" />
</head>

<body>
<div id="wrap">
  <div id="head">
    <h1>ログインする</h1>
  </div>
  <div id="content">
    <p>メールアドレスとパスワードを記入してログインしてください。</p>
    <p>入会手続きがまだの方はこちらからどうぞ。</p>
    <p><a href="./join/index.php">&laquo;入会手続きをする</a></p>
    <form action="" method="post">
      <dl>
        <?php if(isset($error['login']) && $error['login']=='blank'): ?>
        <p class="error">メールアドレスとパスワードが入力されていません。</p>
        <?php endif; ?>

        <?php if(isset($error['login']) && $error['login']=='failed'): ?>
        <p class="error">ログインに失敗しました。ただしく入力してください。</p>
        <?php endif; ?>
      
        <dt><label for="email">メールアドレス</label></dt>
        <dd><input type="email" id="email" name="email" value="<?php echo h($_POST['email']) ?? NULL; ?>"></dd>
        <dt><label for="password">パスワード</label></dt>
        <dd><input type="password" id="password" name="password" value="<?php echo h($_POST['password']) ?? NULL; ?>"></dd>

        <dt>ログイン情報の記録</dt>
        <dd><input type="checkbox" id="save" name="save" value="on"><label for="save">次回からは自動的にログインする</label></dd>
      </dl>
      <input type="submit" value="ログインする">

    </form>
  </div>

</div>
</body>
</html>
