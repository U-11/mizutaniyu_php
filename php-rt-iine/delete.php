<?php
session_start();
require('dbconnect.php');

if(isset($_SESSION['id']) && is_numeric($_SESSION['id'])){
  if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){

    $messages=$db->prepare('SELECT * FROM posts WHERE id=?');
    $messages->bindParam(1,$_REQUEST['id'],PDO::PARAM_INT);
    $messages->execute();
    $message=$messages->fetch();

    $retweets=$db->prepare('SELECT * FROM retweets WHERE rt_id=? AND rt_member_id=? AND is_deleted=0');
    $retweets->bindParam(1,$_REQUEST['id'],PDO::PARAM_INT);
    $retweets->bindParam(2,$_SESSION['id'],PDO::PARAM_INT);
    $retweets->execute();
    $retweet=$retweets->fetch();
    
    if($message['member_id']==$_SESSION['id']){
      $del=$db->prepare('UPDATE posts SET is_deleted=1 WHERE id=?');
      $del->execute(array($_REQUEST['id']));

      if(!empty($retweet)){
        // RTカウントのマイナス
        $rtDec=$db->prepare('UPDATE posts SET rt_count=rt_count-1 WHERE id=?');
        $rtDec->bindParam(1,$retweet['post_id'],PDO::PARAM_INT);
        $rtDec->execute();

        $rtDelete=$db->prepare('UPDATE retweets SET is_deleted=1 WHERE rt_id=?');
        $rtDelete->bindParam(1,$_REQUEST['id'],PDO::PARAM_INT);
        $rtDelete->execute();
      }
    }
  }
}
header('Location:index.php');
exit();
