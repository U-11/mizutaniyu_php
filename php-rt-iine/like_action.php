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

// いいね！機能 DB登録
if(!empty($_POST['like_add'])){
  if(is_numeric($_POST['like_member']) && is_numeric($_POST['like_post'])){
    // いいねカウントプラス
    $likeCount=$db->prepare('UPDATE posts SET like_count=like_count+1 WHERE id=?');
    $likeCount->bindParam(1,$_POST['like_post'],PDO::PARAM_INT);
    $likeCount->execute();
    
    $likeSet=$db->prepare('INSERT INTO likes SET like_member_id=?,post_id=?,created=NOW()');
    $likeSet->bindParam(1,$_POST['like_member'],PDO::PARAM_INT);
    $likeSet->bindParam(2,$_POST['like_post'],PDO::PARAM_INT);
    $likeSet->execute();
    
    header('Location:'.$url);
    exit();
  }else{
    header('Location:index.php');
    exit();
  }
}

// いいね！ 取消し
if(!empty($_POST['delete_like'])){
  if(is_numeric($_POST['like_member']) && is_numeric($_POST['like_post'])){
    // いいねカウントマイナス
    $likeDec=$db->prepare('UPDATE posts SET like_count=like_count-1 WHERE id=?');
    $likeDec->bindParam(1,$_POST['like_post'],PDO::PARAM_INT);
    $likeDec->execute();
    
    $likeDelete=$db->prepare('UPDATE likes SET is_deleted=1 WHERE like_member_id=? AND post_id=? AND is_deleted=0');
    $likeDelete->bindParam(1,$_POST['like_member'],PDO::PARAM_INT);
    $likeDelete->bindParam(2,$_POST['like_post'],PDO::PARAM_INT);
    $likeDelete->execute();
    
    header('Location:'.$url);
    exit();
  }else{
    header('Location:index.php');
    exit();
  }
}
