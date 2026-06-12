<?php
require_once 'config.php';
define('ROOT','');
if(!GOOGLE_CLIENT_ID){flash('error','Google OAuth sozlanmagan. config.php ga GOOGLE_CLIENT_ID kiriting.');redirect('login.php');}
$code=$_GET['code']??'';
if(!$code){
    $state=bin2hex(random_bytes(16));
    $_SESSION['oauth_state']=$state;
    $_SESSION['oauth_role']=$_GET['role']??'';
    $params=http_build_query(['client_id'=>GOOGLE_CLIENT_ID,'redirect_uri'=>GOOGLE_REDIRECT,'response_type'=>'code','scope'=>'openid email profile','state'=>$state,'access_type'=>'online']);
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?'.$params);exit;
}
if(($_GET['state']??'')!==($_SESSION['oauth_state']??'')){flash('error','OAuth xavfsizlik xatosi.');redirect('login.php');}
$tok=@file_get_contents('https://oauth2.googleapis.com/token',false,stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/x-www-form-urlencoded','content'=>http_build_query(['code'=>$code,'client_id'=>GOOGLE_CLIENT_ID,'client_secret'=>GOOGLE_CLIENT_SECRET,'redirect_uri'=>GOOGLE_REDIRECT,'grant_type'=>'authorization_code'])]]));
if(!$tok){flash('error','Google token xatosi.');redirect('login.php');}
$tok=json_decode($tok,true);
$parts=explode('.',$tok['id_token']??'');
if(count($parts)!==3){flash('error','Token xato.');redirect('login.php');}
$pl=json_decode(base64_decode(str_pad(strtr($parts[1],'-_','+/'),strlen($parts[1])%4,'=',STR_PAD_RIGHT)),true);
$gid=$pl['sub']??''; $email=$pl['email']??''; $name=$pl['name']??'Google'; $avatar=$pl['picture']??'';
if(!$gid||!$email){flash('error','Google ma\'lumot xatosi.');redirect('login.php');}
$s=db()->prepare("SELECT * FROM users WHERE google_id=? OR email=?");$s->execute([$gid,$email]);$u=$s->fetch();
if($u){
    db()->prepare("UPDATE users SET google_id=?,avatar=? WHERE id=?")->execute([$gid,$avatar,$u['id']]);
    $_SESSION['uid']=$u['id'];flash('success','Xush kelibsiz!');
    redirect($u['role']==='teacher'?'teacher/':($u['role']==='admin'?'admin/':'index.php'));
}else{
    $_SESSION['google_pending']=['gid'=>$gid,'email'=>$email,'name'=>$name,'avatar'=>$avatar,'role'=>$_SESSION['oauth_role']??''];
    redirect('google_role.php');
}
