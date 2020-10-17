<?php
session_start();
require('dbconnect.php');

if(isset($_SESSION['id'])){

  $messages=$db->prepare('SELECT * FROM posts WHERE id=?');
  $messages->execute(array($_REQUEST['id']));
  $message=$messages->fetch();

  if($message['member_id']==$_SESSION['id']){
    $del=$db->prepare('UPDATE posts SET is_deleted=1 WHERE id=?');
    $del->execute(array($_REQUEST['id']));
  }
}
header('Location:index.php');
exit();

?>
