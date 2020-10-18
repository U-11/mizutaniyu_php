<?php
session_start();
require('dbconnect.php');

if(isset($_SESSION['id']) && is_numeric($_SESSION['id'])){
  if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){

    $messages=$db->prepare('SELECT * FROM posts WHERE id=?');
    $messages->bindParam(1,$_REQUEST['id'],PDO::PARAM_INT);
    $messages->execute();
    $message=$messages->fetch();
    
    if($message['member_id']==$_SESSION['id']){
      $del=$db->prepare('UPDATE posts SET is_deleted=1 WHERE id=?');
      $del->execute(array($_REQUEST['id']));
    }
  }
}
header('Location:index.php');
exit();

