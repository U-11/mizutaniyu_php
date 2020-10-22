<?php
if(!isset($_SESSION)){
  session_start();
}
require('dbconnect.php');

if(isset($_SESSION['id']) && is_numeric($_SESSION['id']) && $_SESSION['time']+3600>time()){
  $_SESSION['time']=time();

  $members=$db->prepare('SELECT * FROM members WHERE id=?');
  $members->execute(array($_SESSION['id']));
  $member=$members->fetch();
}else{
  header('Location:login.php');
  exit();
}

// ページ
$page=isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) ? $page=$_REQUEST['page'] : 1;

$counts=$db->query('SELECT COUNT(*) AS cnt FROM posts WHERE is_deleted=0');
$cnt=$counts->fetch();
$maxPage=ceil($cnt['cnt']/5);
$page=min($page,$maxPage);

$url='index.php?page='.$page;

$newPost=$db->query('SELECT MAX(id) AS new FROM posts');
$np=$newPost->fetch();
$new=$np['new']+1;

if(!empty($_POST['message'])){
  if(is_numeric($_POST['rt_post_id']) && is_numeric($_POST['post_member_id'])){
    // コメントありリツイートをDBに登録
    $com_rt=$db->prepare('INSERT INTO posts SET message=?,member_id=?,created=NOW()');
    $com_rt->bindParam(1,$_POST['message'],PDO::PARAM_STR);
    $com_rt->bindParam(2,$member['id'],PDO::PARAM_INT);
    $com_rt->execute();
      
    // リツイートカウントプラス
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

    header('Location:index.php');
    exit();
  }else{
    header('Location:index.php');
    exit();
  }
}else{
// コメントなしリツイートのDB登録
  if(isset($_POST['rt_post_id']) && is_numeric($_POST['rt_post_id']) && is_numeric($_POST['post_member_id'])){
    $nom_rt=$db->prepare('INSERT INTO posts SET message="RT",member_id=?,created=NOW()');
    $nom_rt->bindParam(1,$member['id'],PDO::PARAM_INT);
    $nom_rt->execute();
      
    // リツイートカウント
    $nom_rt_cnt=$db->prepare('UPDATE posts SET rt_count=rt_count+1 WHERE id=?');
    $nom_rt_cnt->bindParam(1,$_POST['rt_post_id'],PDO::PARAM_INT);
    $nom_rt_cnt->execute();
    
    $nomRt=$db->prepare('INSERT INTO retweets SET rt_member_id=?,rt_id=?,post_member_id=?,post_id=?,post=?,created=NOW()');
    $nomRt->bindParam(1,$member['id'],PDO::PARAM_INT);
    $nomRt->bindParam(2,$new,PDO::PARAM_INT);
    $nomRt->bindParam(3,$_POST['post_member_id'],PDO::PARAM_INT);
    $nomRt->bindParam(4,$_POST['rt_post_id'],PDO::PARAM_INT);
    $nomRt->bindParam(5,$_POST['post'],PDO::PARAM_STR);
    $nomRt->execute();

    header('Location:index.php');
    exit();
  }
}

// リツイート 取り消し
if(!empty($_POST['delete_retweet'])){
  if(is_numeric($_POST['post_id']) && is_numeric($_POST['rt_destination'])){
    // RTカウントのマイナス
    $rtDec=$db->prepare('UPDATE posts SET rt_count=rt_count-1 WHERE id=?');
    $rtDec->bindParam(1,$_POST['post_id'],PDO::PARAM_INT);
    $rtDec->execute();

    $rtMessageDelete=$db->prepare('UPDATE posts SET is_deleted=1 WHERE id=?');
    $rtMessageDelete->bindParam(1,$_POST['rt_destination'],PDO::PARAM_INT);
    $rtMessageDelete->execute();
    
    $rtDelete=$db->prepare('UPDATE retweets SET is_deleted=1 WHERE rt_id=?');
    $rtDelete->bindParam(1,$_POST['rt_destination'],PDO::PARAM_INT);
    $rtDelete->execute();
    
    header('Location:'.$url);
    exit();
  }else{
    header('Location:index.php');
    exit();
  }
}else{
  header('Location:index.php');
  exit();
}
