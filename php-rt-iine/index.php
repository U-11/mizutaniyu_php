<?php
if(!isset($_SESSION)){
  session_start();
}
require('dbconnect.php');

if(isset($_SESSION['id']) && $_SESSION['time']+3600>time()){
  $_SESSION['time']=time();

  $members=$db->prepare('SELECT * FROM members WHERE id=?');
  $members->execute(array($_SESSION['id']));
  $member=$members->fetch();
}else{
  header('Location:login.php');
  exit();
}

$newPost=$db->query('SELECT MAX(id) AS new FROM posts');
$np=$newPost->fetch();
$new=$np['new']+1;

// 通常の投稿の登録
if(!empty($_POST['message'])){
  if(empty($_POST['rt_post_id'])){
    if($_POST['message']!=''){
      $message=$db->prepare('INSERT INTO posts SET message=?,member_id=?,created=NOW()');
      $message->execute(array(
        $_POST['message'],
        $member['id']
      ));
      header('Location:index.php');
      exit();
    }
  }else{
    // コメントありリツイートをDBに登録
    if($_POST['message']!=''){
      $com_rt=$db->prepare('INSERT INTO posts SET message=?,member_id=?,created=NOW()');
      $com_rt->bindParam(1,$_POST['message'],PDO::PARAM_STR);
      $com_rt->bindParam(2,$member['id'],PDO::PARAM_INT);
      $com_rt->execute();
      
      $com_rt_cnt=$db->prepare('UPDATE posts SET rt_count=rt_count+1 WHERE id=?');
      $com_rt_cnt->bindParam(1,$_POST['rt_post_id'],PDO::PARAM_INT);
      $com_rt_cnt->execute();

      $comRt=$db->prepare('INSERT INTO retweets SET rt_member_id=?,rt_id=?,post_member_id=?,post_id=?,post=?,created=NOW()');
      $comRt->bindParam(1,$member['id'],PDO::PARAM_INT);
      $comRt->bindParam(2,$new,PDO::PARAM_INT);
      $comRt->bindParam(3,$_POST['post_member_id'],PDO::PARAM_INT);
      $comRt->bindParam(4,$_POST['rt_post_id'],PDO::PARAM_INT);
      $comRt->bindParam(5,$_POST['post'],PDO::PARAM_STR);
      $comRt->execute();
    }
    header('Location:index.php');
    exit();
  }
}

// ページング(１ページ５投稿)
$page=$_REQUEST['page'] ?? NULL;
if($page==''){
  $page=1;
}
$page=max($page,1);

$counts=$db->query('SELECT COUNT(*) AS cnt FROM posts WHERE is_deleted=0');
$cnt=$counts->fetch();
$maxPage=ceil($cnt['cnt']/5);
$page=min($page,$maxPage);

$start=($page-1)*5;
// RT元のTL表示用&&投稿のTL表示用
$posts=$db->prepare('SELECT m.name,m.picture,p.*,r.rt_member_id,r.rt_id,r.post_member_id,r.post_id,r.post FROM members m,posts p LEFT JOIN retweets r ON p.id=r.rt_id WHERE m.id=p.member_id AND p.is_deleted=0 ORDER BY p.created DESC LIMIT ?,5');
$posts->bindParam(1,$start,PDO::PARAM_INT);
$posts->execute();
// ページングおわり


// 返信機能
if(isset($_REQUEST['res'])){
  $response=$db->prepare('SELECT m.name,m.picture,p.* FROM members m,posts p WHERE m.id=p.member_id AND p.id=? AND is_deleted=0 ORDER BY p.created DESC');
  $response->execute(array($_REQUEST['res']));
  $table=$response->fetch();
  $message='@'.$table['name'].' '.$table['message'];
}

// リツイート機能
if(!empty($_POST['retweet'])){
  // RTできる状態か重複チェック
  $rtCheck=$db->prepare('SELECT * FROM retweets WHERE post_id=? AND is_deleted=0');
  $rtCheck->bindParam(1,$_POST['retweet'],PDO::PARAM_INT);
  $rtCheck->execute();
  $rc=$rtCheck->fetch();
  
  if(!$rc ||($_POST['retweet']!=$rc['post_id'] && $member['id']!=$rc['rt_member_id'])){
    // 重複なしの場合、プレビュー表示
    $retweets=$db->prepare('SELECT m.name,m.picture,p.* FROM members m,posts p WHERE m.id=p.member_id AND p.id=? AND p.is_deleted=0 ORDER BY p.created DESC');
    $retweets->bindParam(1,$_POST['retweet'],PDO::PARAM_INT);
    $retweets->execute();
    $rt=$retweets->fetch();
    
  }else{
    $error['post']='duplicate';
    // header('Location:index.php');
    // exit();
  }
    // リツイートのDB登録
  if(!empty($_POST['rt_post_id'])){
    // リツイートカウント
    $rtCount=$db->prepare('UPDATE posts SET rt_count=rt_count+1 WHERE id=?');
    $rtCount->bindParam(1,$_POST['rt_post_id'],PDO::PARAM_INT);
    $rtCount->execute();

    if($_POST['message']==''){
      // コメントなしリツイートの登録
      $nom_rt=$db->prepare('INSERT INTO posts SET message="RT",member_id=?,created=NOW()');
      $nom_rt->bindParam(1,$member['id'],PDO::PARAM_INT);
      $nom_rt->execute();
      
      $nom_rt_cnt=$db->prepare('UPDATE posts SET rt_count=rt_count+1 WHERE id=?');
      $nom_rt_cnt->bindParam(1,$_POST['rt_post_id'],PDO::PARAM_INT);
      $nom_rt_cnt->execute();

      $nomRt=$db->prepare('INSERT INTO retweets SET rt_member_id=?,rt_id=?,post_member_id,post_id=?,post=?,created=NOW()');
      $nomRt->bindParam(1,$member['id'],PDO::PARAM_INT);
      $nomRt->bindParam(2,$new,PDO::PARAM_INT);
      $nomRt->bindParam(3,$_POST['post_member_id'],PDO::PARAM_INT);
      $nomRt->bindParam(4,$_POST['rt_post_id'],PDO::PARAM_INT);
      $nomRt->bindParam(5,$_POST['post'],PDO::PARAM_STR);
      $nomRt->execute();
    }
    header('Location:index.php');
    exit();
  }
}

// いいね！機能 DB登録
if(!empty($_POST['like_add'])){
  $likeCount=$db->prepare('UPDATE posts SET like_count=like_count+1 WHERE id=?');
  $likeCount->bindParam(1,$_POST['like_post'],PDO::PARAM_INT);
  $likeCount->execute();

  $likeSet=$db->prepare('INSERT INTO likes SET like_member_id=?,post_id=?,created=NOW()');
  $likeSet->bindParam(1,$_POST['like_member'],PDO::PARAM_INT);
  $likeSet->bindParam(2,$_POST['like_post'],PDO::PARAM_INT);
  $likeSet->execute();

  header('Location:index.php');
  exit();
}

// いいね！ 取り消し
if(!empty($_POST['delete_like'])){
  $likeDec=$db->prepare('UPDATE posts SET like_count=like_count-1 WHERE id=?');
  $likeDec->bindParam(1,$_POST['like_post'],PDO::PARAM_INT);
  $likeDec->execute();

  $likeDelete=$db->prepare('UPDATE likes SET is_deleted=1 WHERE like_member_id=? AND post_id=? AND is_deleted=0');
  $likeDelete->bindParam(1,$_POST['like_member'],PDO::PARAM_INT);
  $likeDelete->bindParam(2,$_POST['like_post'],PDO::PARAM_INT);
  $likeDelete->execute();

  header('Location:index.php');
  exit();
}

// 省略ファンクション
function h($value){
  return htmlspecialchars($value,ENT_QUOTES);
}
function makeLink($value){
  return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)",'<a href="\1\2">\1\2</a>',$value);
}

?>
<pre>
<?php
  var_dump($member['id']);
?>
</pre>

<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>ひとこと掲示板</title>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div id="wrap">
      <div id="head">
        <h1>ひとこと掲示板</h1>
      </div>
      <div id="content">
        <div style="text-align:right;"><a href="logout.php">ログアウト</a></div>
        <form action="" method="post">
          <dl>
            <dt><?php echo h($member['name']); ?>さん、メッセージをどうぞ</dt>
            <dd><textarea name="message" cols="50" rows="5" <?php if(isset($_POST['retweet']) && $error['post']!='duplicate'){echo 'placeholder="コメントをつけてリツイート"';} ?>><?php echo h($message) ?? NULL; ?></textarea>
            <input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>"></dd>
          </dl>
          <!-- リツイート投稿プレビュー -->
          <?php if(isset($_POST['retweet']) && $error['post']!='duplicate'): ?>
            <div class="rt-container">
              <p>RT</p>
              <a class="rt-link" href="view.php?id=<?php echo h($rt['id']); ?>">
                <img src="member_picture/<?php echo h($rt['picture']); ?>" width="48" height="48" alt="<?php echo h($rt['name']); ?>">
                <p><span class="name">(<?php echo h($rt['name']); ?>)</span></p>
                <?php echo mb_substr(h($rt['message']),0,40); ?><?php echo mb_strlen(h($rt['message']))>40 ? '...' : ''; ?></p>
              </a>
              <input type="hidden" name="rt_post_id" value="<?php echo h($_POST['retweet']); ?>">
              <input type="hidden" name="post_member_id" value="<?php echo h($_POST['rt_member']); ?>">
              <input type="hidden" name="post" value="<?php echo h($_POST['rt_src']); ?>">
            </div>
            <?php endif; ?>
            <!-- リツイート投稿プレビュー終わり -->
            
          <div class="submit"><input type="submit" value="投稿する">
          <?php if($_POST['retweet']): ?>
            <a class="cancel" href="index.php?page=<?php echo $page; ?>">やめる</a>
          <?php endif; ?></div>
        </form>
  
      <!-- 投稿タイムライン -->
      <?php foreach($posts as $post): ?>
        <div class="msg">
          <?php ?>
          <img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>">
          <p>
          <span class="name">(<?php echo h($post['name']); ?>)</span><br>
          <a href="index.php?res=<?php echo h($post['id']); ?>">[Re:]</a>
          <?php echo makeLink(h($post['message'])); ?>
          </p>
          
          <div class="under">
            <p class="day">
              <a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>
              <?php if($post['reply_post_id']>0): ?>
              <a href="view.php?id=<?php echo h($post['reply_post_id']); ?>">返信元のメッセージ</a>
              <?php endif; ?>
              <?php if($_SESSION['id']==$post['member_id']): ?>
              <a href="delete.php?id=<?php echo h($post['id']); ?>" style="color:#f33;">削除</a>
                <?php endif; ?>
            </p>
       
        <!-- リツイート元 -->
        <?php if($post['rt_id']): ?>
        <div class="rt">
          <a class="rt-link" href="view.php?id=<?php echo h($post['post_id']); ?>">
          <?php
          $postMember=$db->prepare('SELECT name,picture FROM members WHERE id=?');
          $postMember->execute(array($post['post_member_id']));
          $pm=$postMember->fetch();
          ?>
          <img src="member_picture/<?php echo h($pm['picture']); ?>" width="48" height="48" alt="<?php echo h($pm['name']); ?>">
          <p><span class="name">(<?php echo h($pm['name']); ?>)</span></p>

          <?php echo mb_substr(h($post['post']),0,40); ?><?php echo mb_strlen(h($post['post']))>40 ? '...' : ''; ?></p>
          </a>
        </div>
        <?php endif; ?>
        <!-- リツイート元ここまで -->

          <!-- いいね重複チェック -->
          <ul class="res">
            <?php
            $likes=$db->prepare('SELECT * FROM likes WHERE post_id=? AND is_deleted=0');
            $likes->execute(array($post['id']));
            $like=$likes->fetch();
            ?>
            <?php if(empty($like) || ($member['id']!=$like['like_member_id'] && $post['id']!=$like['post_id'])): ?>

            <!-- いいね!登録 -->
            <li><form action="" method="post"><input type="submit" name="like_add" value="いいね！"></li>
            <li><?php if($post['like_count']>0){echo $like_cnt['like_count'];} ?><input type="hidden" name="like_member" value="<?php echo h($member['id']); ?>"><input type="hidden" name="like_post" value="<?php echo h($post['id']); ?>"></form></li>
            <?php else: ?>

            <!-- いいね!済（取消し） -->
            <li><form action="" method="post"><input  class="delete" type="submit" name="delete_like" value="いいね！"><input type="hidden" name="like_member" value="<?php echo h($member['id']); ?>"><input type="hidden" name="like_post" value="<?php echo h($post['id']); ?>"></li>
            <li><?php if($post['like_count']>0){echo h($post['like_count']);} ?></li>
            <?php endif; ?>

            <!-- リツイートボタン -->
            <li><form action="" method="post"><input type="submit" name="retweet-button" value="リツイート"></li>
            <li><?php if($post['rt_count']>0){echo h($post['rt_count']);} ?>
            <input type="hidden" name="retweet" value="<?php echo h($post['id']); ?>">
            <input type="hidden" name="rt_member" value="<?php echo h($post['member_id']); ?>">
            <input type="hidden" name="rt_src" value="<?php echo h($post['message']); ?>">
            </form>
            </li>
          </ul>
        <?php endforeach; ?>
      </div>
      <!-- 投稿タイムライン終わり -->
      
      <ul class="paging">
        <?php if($page>1): ?>
        <li><a href="index.php?page=<?php echo $page-1 ?>"><?php echo $page-1; ?>ページ目へ</a></li>
        <?php endif; ?>
        <?php if($page<$maxPage): ?>
        <li><a href="index.php?page=<?php echo $page+1; ?>"><?php echo $page+1; ?>ページ目へ</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>
</body>
</html>
