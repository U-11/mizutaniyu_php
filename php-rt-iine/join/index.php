<?php
session_start();
require('../dbconnect.php');

// 入力内容確認
if(!empty($_POST)){
  if(isset($_POST['name']) && $_POST['name']==''){
    $error['name']='blank';
  }
  if(isset($_POST['email']) && $_POST['email']==''){
    $error['email']='blank';
  }
  if(strlen($_POST['password']) < 7 ){
    $error['password']='length';
  }
  if(isset($_POST['password']) && $_POST['password']==''){
    $error['password']='blank';
  }
  if(isset($_FILES['image']['name'])){
    $fileName=$_FILES['image']['name'];
  }
  // 画像拡張子の確認
  if(!empty($fileNames)){
    $ext=substr($fileName,-3);
    if($ext!='jpg' && $ext!='git' && $ext!='png'){
      $error['image']='type';
    }
  }
  // メールアドレスの重複確認
  if(empty($error)){
    $member=$db->prepare('SELECT COUNT(*) AS cnt FROM members WHERE email=?');
    $member->execute(array($_POST['email']));
    $record=$member->fetch();
    if($record['cnt']>0){
      $error['email']='duplicate';
    }
    // 画像に命名
    $image=date('YmdHis').$_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'],'../member_picture/'.$image);
    $_SESSION['join']=$_POST;
    $_SESSION['join']['image']=$image;

    header('Location:check.php');
    exit();
  }
  
}

// リライト
if(isset($_REQUEST['action']) && $_REQUEST['action']=='rewrite'){
  $_POST=$_SESSION['join'];
  $error['rewrite']='true';
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
	<link rel="stylesheet" href="../style.css" />
</head>

<body>
<div id="wrap">
  <div id="head">
    <h1>会員登録</h1>
  </div>
  <div id="content">
  <p style="text-align:right;"><a href="../login.php">ログイン</a></p>
    <p>次のフォームに必要事項をご記入ください</p>
    <form action="" method="post" enctype="multipart/form-data">
      <dl>
        <dt><label for="name">ニックネーム</label><span class="required">必須</span></dt>
        <dd><input type="test" id="name" name="name" value="<?php echo h($_POST['name']) ?? NULL; ?>"></dd>
        <?php if(isset($error['name']) && $error['name']=='blank'): ?>
        <p class="error">ニックネームを入力してください</p>
        <?php endif; ?>

        <dt><label for="email">メールアドレス</label><span class="required">必須</span></dt>
        <dd><input type="email" id="email" name="email" value="<?php echo h($_POST['email']) ?? NULL; ?>"></dd>
        <?php if(isset($error['email']) && $error['email']=='blank'): ?>
        <p class="error">メールアドレスを入力してください</p>
        <?php endif; ?>
        <?php if(isset($_POST['email']) && $error['email']=='duplicate'): ?>
          <p class="error">メールアドレスが既に登録されています
          </p>
        <?php endif; ?>
        <dt><label for="password">パスワード</label><span class="required">必須</span></dt>
        <dd><input type="password" id="password" name="password" value="<?php echo h($_POST['password']) ?? NULL; ?>"></dd>
        <?php if(isset($error['password']) && $error['password']=='blank'): ?>
        <p class="error">パスワードを入力してください</p>
        <?php endif; ?>
        <?php if(isset($error['password']) && $error['password']=='length'): ?>
        <p class="error">パスワードを8文字以上で入力してください</p>
        <?php endif; ?>

        <dt>写真など</dt>
        <dd><input type="file" name="image" size="35"></dd>
        <?php if(isset($error['image']) && $error['image']=='type'): ?>
        <p class="error">写真などは「.gif」「.jpg」または「.ng」を指定してください</p>
        <?php endif; ?>
        <?php if(!empty($error)): ?>
        <p class="error">恐れ入りますが、改めて画像を指定してください。</p>
        <?php endif; ?>
      </dl>
      <div><input type="submit" value="入力内容を確認する"></div>

    </form>
  </div>
</div>
</body>
</html>
