<?php
session_start();
require('dbconnect.php');

if(empty($_REQUEST['id'])){
  header('Location:index.php');
  exit();
}
if(is_numeric($_REQUEST['id'])){
  $posts=$db->prepare('SELECT m.name,m.picture,p.*,r.rt_member_id,r.rt_id,r.post_member_id,r.post_id,r.post FROM members m,posts p LEFT JOIN retweets r ON p.id=r.rt_id WHERE m.id=p.member_id AND p.id=? AND p.is_deleted=0');
  $posts->bindParam(1,$_REQUEST['id'],PDO::PARAM_INT);
  $posts->execute();
  $post=$posts->fetch();
}else{
  header('Location:index.php');
  exit();
}

// ファンクションの省略
function h($value){
  return htmlspecialchars($value,ENT_QUOTES);
}
function makeLink($value){
  return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)",'<a href="\1\2">\1\2</a>',$value);
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
    <h1>ひとこと掲示板</h1>
  </div>
  <div id="content">
  <p>&laquo;<a href="index.php">一覧へ戻る</a></p>
  <?php if(isset($post)): ?>
  <div class="msg">
    <img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>">
    <p>
      <span class="name">(<?php echo h($post['name']);  ?>)</span><br>
      <a href="index.php?res=<?php echo h($post['id']); ?>">[Re:]</a>
      <?php echo makeLink(h($post['message'])); ?>
    </p>
    <p class="day"><?php echo h($post['created']); ?></a></p>

    <!-- リツイート元 -->
    <?php if($post['rt_id']): ?>
    <div class="rt">
      <a class="rt-link" href="view.php?id=<?php echo h($post['post_id']); ?>">
      <?php
      $postMember=$db->prepare('SELECT name,picture FROM members WHERE id=?');
      $postMember->execute(array($post['post_member_id']));
      $pm=$postMember->fetch();

      $postdate=$db->prepare('SELECT created FROM posts WHERE id=?');
      $postdate->execute(array($post['post_id']));
      $pd=$postdate->fetch();
      ?>
        <img src="member_picture/<?php echo h($pm['picture']); ?>" width="48" height="48" alt="<?php echo h($pm['name']); ?>">
        <p><span class="name">(<?php echo h($pm['name']); ?>)</span></p>
        <?php echo mb_substr(h($post['post']),0,40); ?><?php echo mb_strlen(h($post['post']))>40 ? '...' : ''; ?></p>
        <p class="day"><?php echo h($pd['created']); ?></p>
      </a>
    </div>
    <?php endif; ?>
    <!-- リツイート元ここまで -->
    <?php else: ?>
    <p>その投稿は削除されたかURLが間違えています</p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
