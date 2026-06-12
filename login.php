<?php
require_once 'config.php';
define('ROOT','');
if(isLoggedIn()) redirect('index.php');
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'&&checkToken()){
    $email=trim($_POST['email']??''); $pass=$_POST['password']??'';
    if($email&&$pass){
        $s=db()->prepare("SELECT * FROM users WHERE email=?");
        $s->execute([$email]); $u=$s->fetch();
        if($u&&password_verify($pass,$u['password'])){
            $_SESSION['uid']=$u['id'];
            flash('success','Xush kelibsiz, '.$u['name'].'!');
            $after=$_SESSION['after_login']??'';unset($_SESSION['after_login']);
            redirect($after?:($u['role']==='teacher'?'teacher/':($u['role']==='admin'?'admin/':'index.php')));
        }else $err='Email yoki parol noto\'g\'ri.';
    }else $err='Barcha maydonlarni to\'ldiring.';
}
$pageTitle='Kirish';
?>
<!DOCTYPE html>
<html lang="uz" data-theme="<?= theme() ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SmartTest — Kirish</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0e1a;--bg2:#0f1526;--bg3:#141c30;--card:#161d30;--border:rgba(99,140,255,.15);--border2:rgba(99,140,255,.3);--accent:#4f7cff;--accent2:#7b9fff;--teal:#00d4aa;--text:#e8edff;--text2:#8fa3cc;--text3:#4d6494;--danger:#ff6b6b;--success:#51cf66;--glow:0 0 24px rgba(79,124,255,.2)}
[data-theme="light"]{--bg:#f0f4ff;--bg2:#fff;--bg3:#f5f7ff;--card:#fff;--border:rgba(79,124,255,.15);--text:#0d1b3e;--text2:#3d5a99;--text3:#7a9acc}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Space Grotesk',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(79,124,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(79,124,255,.025) 1px,transparent 1px);background-size:40px 40px;pointer-events:none}
.wrap{width:100%;max-width:400px;position:relative;z-index:1}
.brand{text-align:center;margin-bottom:28px}
.brand-ico{width:52px;height:52px;background:linear-gradient(135deg,var(--accent),var(--teal));border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 12px;box-shadow:var(--glow)}
.brand-name{font-size:22px;font-weight:700;letter-spacing:-.5px}
.brand-name em{color:var(--accent);font-style:normal}
.brand-sub{font-size:13px;color:var(--text2);margin-top:4px}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:28px}
.err{background:rgba(255,107,107,.08);border:1px solid rgba(255,107,107,.2);color:var(--danger);border-radius:9px;padding:10px 13px;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.google-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:11px;border-radius:9px;border:1.5px solid var(--border2);background:var(--bg3);color:var(--text2);font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;font-family:'Space Grotesk',sans-serif;transition:all .18s;margin-bottom:18px}
.google-btn:hover{background:var(--card);color:var(--text);border-color:var(--accent)}
.divider{display:flex;align-items:center;gap:10px;margin-bottom:18px;color:var(--text3);font-size:11px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border)}
.fg{margin-bottom:14px}
.fl{display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:6px}
.fi{width:100%;padding:10px 13px;background:var(--bg3);border:1.5px solid var(--border);border-radius:9px;font-size:13px;font-family:'Space Grotesk',sans-serif;color:var(--text);outline:none;transition:all .18s}
.fi:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,124,255,.12);background:var(--card)}
.fi::placeholder{color:var(--text3)}
.btn-p{width:100%;padding:11px;background:var(--accent);color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;font-family:'Space Grotesk',sans-serif;transition:all .18s;margin-top:4px}
.btn-p:hover{background:var(--accent2);transform:translateY(-1px)}
.foot{text-align:center;font-size:13px;color:var(--text2);margin-top:16px}
.foot a{color:var(--accent);text-decoration:none;font-weight:600}
.foot a:hover{text-decoration:underline}
.theme-btn{position:fixed;top:16px;right:16px;width:36px;height:36px;background:var(--card);border:1px solid var(--border);border-radius:9px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;z-index:100}
</style>
</head>
<body>
<div class="theme-btn" onclick="toggleTheme()" id="themeBtn"><?= theme()==='dark'?'☀️':'🌙' ?></div>
<div class="wrap">
  <div class="brand">
    <div class="brand-ico">⚡</div>
    <div class="brand-name">Smart<em>IQ</em></div>
    <div class="brand-sub">Adaptiv test platformasiga xush kelibsiz</div>
  </div>
  <div class="card">
    <?php if($err): ?><div class="err">✕ <?= h($err) ?></div><?php endif; ?>
    <a href="auth_google.php" class="google-btn">
      <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      Google orqali kirish
    </a>
    <div class="divider">yoki email bilan</div>
    <form method="POST">
      <input type="hidden" name="_token" value="<?= token() ?>">
      <div class="fg"><label class="fl">Email manzil</label><input type="email" name="email" class="fi" placeholder="siz@example.com" value="<?= h($_POST['email']??'') ?>" required></div>
      <div class="fg"><label class="fl">Parol</label><input type="password" name="password" class="fi" placeholder="••••••••" required></div>
      <button type="submit" class="btn-p">Kirish →</button>
    </form>
  </div>
  <div class="foot">Hisobingiz yo'qmi? <a href="register.php">Ro'yxatdan o'ting</a></div>
</div>
<script>
function toggleTheme(){
  const h=document.documentElement,n=h.dataset.theme==='dark'?'light':'dark';
  h.dataset.theme=n;document.cookie=`theme=${n};path=/;max-age=31536000`;
  document.getElementById('themeBtn').textContent=n==='dark'?'☀️':'🌙';
}
</script>
</body></html>
