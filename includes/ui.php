<?php
if(!defined('ROOT')) define('ROOT','');
$_theme = theme();
$_me    = me();
$_fs    = getFlash('success');
$_fe    = getFlash('error');
$_fi    = getFlash('info');
?>
<!DOCTYPE html>
<html lang="uz" data-theme="<?= $_theme ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($pageTitle??'SmartTest') ?> — SmartTest</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ═══════════ TEMA TOKENS ═══════════ */
:root{
  --bg:#0a0e1a;--bg2:#0f1526;--bg3:#141c30;
  --card:#161d30;--card2:#1c2540;--card3:#20294a;
  --border:rgba(99,140,255,.14);--border2:rgba(99,140,255,.3);
  --accent:#4f7cff;--accent2:#7b9fff;--accent3:#a8c0ff;
  --teal:#00d4aa;--teal2:#00b894;
  --amber:#ffb347;--danger:#ff6b6b;--success:#51cf66;--purple:#da77f2;
  --text:#e8edff;--text2:#8fa3cc;--text3:#4d6494;
  --shadow:0 8px 32px rgba(0,0,0,.4);
  --glow:0 0 24px rgba(79,124,255,.2);
}
[data-theme="light"]{
  --bg:#f0f4ff;--bg2:#fff;--bg3:#f5f7ff;
  --card:#fff;--card2:#eef1ff;--card3:#e4e9ff;
  --border:rgba(79,124,255,.14);--border2:rgba(79,124,255,.3);
  --text:#0d1b3e;--text2:#3d5a99;--text3:#7a9acc;
  --shadow:0 4px 16px rgba(0,0,0,.08);
}

/* ═══════════ RESET ═══════════ */
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Space Grotesk',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;transition:background .3s,color .3s}
body::before{content:'';position:fixed;inset:0;
  background-image:linear-gradient(rgba(79,124,255,.025) 1px,transparent 1px),
    linear-gradient(90deg,rgba(79,124,255,.025) 1px,transparent 1px);
  background-size:40px 40px;pointer-events:none;z-index:0}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:var(--bg3);border-radius:3px}

/* ═══════════ SIDEBAR LAYOUT ═══════════ */
.app{position:relative;z-index:1;display:flex;min-height:100vh}
.sidebar{
  width:234px;background:var(--bg2);border-right:1px solid var(--border);
  position:fixed;top:0;left:0;bottom:0;z-index:200;
  display:flex;flex-direction:column;overflow:hidden;
  transition:transform .3s;
}
.logo{padding:18px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-shrink:0}
.logo-icon{width:34px;height:34px;background:linear-gradient(135deg,var(--accent),var(--teal));border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px;box-shadow:var(--glow)}
.logo-text{font-weight:700;font-size:16px;letter-spacing:-.4px}
.logo-text em{color:var(--accent);font-style:normal}
.nav{flex:1;padding:10px 8px;overflow-y:auto;display:flex;flex-direction:column;gap:2px}
.nav-label{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--text3);padding:12px 10px 4px}
.nav-item{
  display:flex;align-items:center;gap:9px;padding:8px 10px;
  border-radius:9px;cursor:pointer;color:var(--text2);font-size:13px;
  font-weight:500;transition:all .15s;border:1px solid transparent;text-decoration:none;
}
.nav-item:hover{background:var(--bg3);color:var(--text)}
.nav-item.active{background:rgba(79,124,255,.12);color:var(--accent2);border-color:rgba(79,124,255,.2)}
.nav-item.danger:hover{background:rgba(255,107,107,.08);color:var(--danger)}
.nav-ico{font-size:15px;width:18px;text-align:center;flex-shrink:0}
.nav-badge{margin-left:auto;background:var(--accent);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:8px}
.sidebar-foot{padding:10px 8px;border-top:1px solid var(--border);flex-shrink:0}
.user-chip{
  display:flex;align-items:center;gap:8px;padding:8px 10px;
  background:var(--bg3);border-radius:9px;border:1px solid var(--border);
}
.ava{width:30px;height:30px;background:linear-gradient(135deg,var(--accent),var(--teal));border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0}
.ava img{width:30px;height:30px;border-radius:8px;object-fit:cover}
.u-name{font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.u-role{font-size:10px;color:var(--text3)}
.theme-pill{font-family:'JetBrains Mono',monospace;font-size:9px;background:rgba(79,124,255,.15);color:var(--accent2);padding:2px 6px;border-radius:5px;border:1px solid var(--border);cursor:pointer;flex-shrink:0}

/* ═══════════ MAIN CONTENT ═══════════ */
.main{margin-left:234px;flex:1;min-height:100vh;display:flex;flex-direction:column}
.page-wrap{padding:26px 28px;flex:1}

/* ═══════════ TOPBAR ═══════════ */
.topbar{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;gap:12px;flex-wrap:wrap}
.page-title{font-size:21px;font-weight:700;letter-spacing:-.5px;line-height:1.3}
.page-sub{font-size:12px;color:var(--text2);margin-top:3px}
.tb-right{display:flex;gap:8px;align-items:center;flex-wrap:wrap}

/* ═══════════ CARDS ═══════════ */
.card{background:var(--card);border:1px solid var(--border);border-radius:13px;padding:18px}
.card-h{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:10px}
.card-t{font-size:14px;font-weight:600}
.card-body{padding:18px}
.card-hover{transition:all .2s;cursor:pointer}
.card-hover:hover{border-color:var(--border2);transform:translateY(-2px);box-shadow:0 8px 28px rgba(0,0,0,.25)}
.card-glow:hover{box-shadow:var(--glow)}

/* ═══════════ STAT CARDS ═══════════ */
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:22px}
.stat{background:var(--card);border:1px solid var(--border);border-radius:13px;padding:16px 18px;position:relative;overflow:hidden}
.stat::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px}
.stat.c1::after{background:linear-gradient(90deg,var(--accent),transparent)}
.stat.c2::after{background:linear-gradient(90deg,var(--teal),transparent)}
.stat.c3::after{background:linear-gradient(90deg,var(--amber),transparent)}
.stat.c4::after{background:linear-gradient(90deg,var(--purple),transparent)}
.stat-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text3);margin-bottom:8px}
.stat-val{font-size:24px;font-weight:700;letter-spacing:-1px;line-height:1}
.stat-val.mono{font-family:'JetBrains Mono',monospace;font-size:20px}
.stat-hint{font-size:11px;color:var(--text2);margin-top:4px}
.stat-ico{position:absolute;top:14px;right:14px;font-size:22px;opacity:.1}

/* ═══════════ BUTTONS ═══════════ */
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:6px;
  padding:8px 15px;border-radius:9px;font-family:'Space Grotesk',sans-serif;
  font-size:13px;font-weight:500;cursor:pointer;border:none;
  transition:all .18s;text-decoration:none;white-space:nowrap;
}
.btn:disabled{opacity:.4;cursor:not-allowed}
.btn-p{background:var(--accent);color:#fff}
.btn-p:hover:not(:disabled){background:var(--accent2);transform:translateY(-1px)}
.btn-t{background:var(--teal);color:#071210}
.btn-t:hover:not(:disabled){background:var(--teal2)}
.btn-g{background:var(--bg3);color:var(--text2);border:1px solid var(--border)}
.btn-g:hover:not(:disabled){background:var(--card2);color:var(--text);border-color:var(--border2)}
.btn-d{background:rgba(255,107,107,.12);color:var(--danger);border:1px solid rgba(255,107,107,.25)}
.btn-d:hover:not(:disabled){background:rgba(255,107,107,.2)}
.btn-xs{padding:4px 10px;font-size:11px;border-radius:7px}
.btn-sm{padding:6px 12px;font-size:12px}
.btn-lg{padding:12px 22px;font-size:15px;font-weight:600}
.btn-fw{width:100%}

/* ═══════════ BADGES ═══════════ */
.badge{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600}
.badge-easy{background:rgba(81,207,102,.12);color:#51cf66;border:1px solid rgba(81,207,102,.25)}
.badge-medium{background:rgba(255,179,71,.12);color:#ffb347;border:1px solid rgba(255,179,71,.25)}
.badge-hard{background:rgba(255,107,107,.12);color:#ff6b6b;border:1px solid rgba(255,107,107,.25)}
.badge-blue{background:rgba(79,124,255,.12);color:var(--accent2);border:1px solid rgba(79,124,255,.25)}
.badge-teal{background:rgba(0,212,170,.12);color:var(--teal);border:1px solid rgba(0,212,170,.25)}
.badge-purple{background:rgba(218,119,242,.12);color:var(--purple);border:1px solid rgba(218,119,242,.25)}
.badge-amber{background:rgba(255,179,71,.12);color:var(--amber);border:1px solid rgba(255,179,71,.25)}

/* ═══════════ FORMS ═══════════ */
.fg{margin-bottom:16px}
.fl{display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:6px}
.fi,.fs,.ft{width:100%;padding:10px 13px;background:var(--bg3);border:1.5px solid var(--border);border-radius:9px;font-size:13px;font-family:'Space Grotesk',sans-serif;color:var(--text);transition:all .18s;outline:none}
.fi:focus,.fs:focus,.ft:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,124,255,.12);background:var(--card)}
.fi::placeholder{color:var(--text3)}
.ft{resize:vertical;min-height:90px}
.fh{font-size:11px;color:var(--text3);margin-top:4px}
.fe{font-size:11px;color:var(--danger);margin-top:4px}
.fi-ico{position:relative}
.fi-ico .ico{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text3);pointer-events:none}
.fi-ico .fi{padding-left:34px}

/* ═══════════ TABLE ═══════════ */
.tbl{width:100%;border-collapse:collapse;font-size:13px}
.tbl th{text-align:left;padding:9px 13px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text3);border-bottom:1px solid var(--border);background:var(--bg3)}
.tbl td{padding:10px 13px;border-bottom:1px solid rgba(99,140,255,.06);color:var(--text2);vertical-align:middle}
.tbl tr:last-child td{border-bottom:none}
.tbl tbody tr:hover td{background:var(--bg3);color:var(--text)}
.tbl-wrap{border:1px solid var(--border);border-radius:13px;overflow:hidden}

/* ═══════════ PROGRESS ═══════════ */
.progress{height:4px;background:var(--bg3);border-radius:3px;overflow:hidden}
.pf{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--accent),var(--teal));transition:width .5s cubic-bezier(.4,0,.2,1)}

/* ═══════════ ALERTS ═══════════ */
.alert{display:flex;align-items:flex-start;gap:9px;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:16px}
.a-ok{background:rgba(81,207,102,.08);color:#51cf66;border:1px solid rgba(81,207,102,.2)}
.a-err{background:rgba(255,107,107,.08);color:#ff6b6b;border:1px solid rgba(255,107,107,.2)}
.a-info{background:rgba(79,124,255,.08);color:var(--accent2);border:1px solid rgba(79,124,255,.2)}
.a-warn{background:rgba(255,179,71,.08);color:var(--amber);border:1px solid rgba(255,179,71,.2)}

/* ═══════════ GRIDS ═══════════ */
.g2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.ga{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px}

/* ═══════════ EMPTY ═══════════ */
.empty{text-align:center;padding:48px 20px}
.empty-ico{font-size:44px;margin-bottom:12px;opacity:.4}
.empty-txt{font-size:13px;color:var(--text3)}

/* ═══════════ TOAST ═══════════ */
.toast-wrap{position:fixed;top:18px;right:18px;z-index:9999;display:flex;flex-direction:column;gap:8px}
.toast{
  padding:11px 16px;border-radius:11px;font-size:13px;font-weight:500;
  background:var(--card2);border:1px solid var(--border2);
  box-shadow:var(--shadow);display:flex;align-items:center;gap:8px;
  min-width:240px;max-width:340px;
  animation:toastIn .3s ease;
}
.toast-ok{border-color:rgba(81,207,102,.35);color:#51cf66}
.toast-err{border-color:rgba(255,107,107,.35);color:#ff6b6b}
@keyframes toastIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}

/* ═══════════ DIVIDER ═══════════ */
.divider{display:flex;align-items:center;gap:10px;margin:14px 0;color:var(--text3);font-size:11px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border)}

/* ═══════════ RESPONSIVE ═══════════ */
@media(max-width:900px){
  .sidebar{transform:translateX(-100%)}
  .main{margin-left:0}
  .g4{grid-template-columns:repeat(2,1fr)}
  .stat-grid{grid-template-columns:repeat(2,1fr)}
  .g2{grid-template-columns:1fr}
}

/* ═══════════ UTIL ═══════════ */
.mono{font-family:'JetBrains Mono',monospace}
.fw-600{font-weight:600}.fw-700{font-weight:700}
.c-p{color:var(--accent)}.c-t{color:var(--teal)}.c-a{color:var(--amber)}
.c-d{color:var(--danger)}.c-s{color:var(--success)}.c-m{color:var(--text2)}.c-dim{color:var(--text3)}
.text-xs{font-size:11px}.text-sm{font-size:13px}.text-lg{font-size:17px}
.mt-1{margin-top:4px}.mt-2{margin-top:8px}.mt-3{margin-top:12px}.mt-4{margin-top:16px}.mt-6{margin-top:24px}
.mb-4{margin-bottom:16px}.mb-6{margin-bottom:24px}
.flex{display:flex}.items-c{align-items:center}.justify-b{justify-content:space-between}
.gap-2{gap:8px}.gap-3{gap:12px}.gap-4{gap:16px}
.trunc{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.w-full{width:100%}
</style>
</head>
<body>

<?php if($_me): ?>
<div class="app">
<!-- SIDEBAR -->
<nav class="sidebar">
  <div class="logo">
    <div class="logo-icon">⚡</div>
    <div class="logo-text">Smart<em>IQ</em></div>
  </div>
  <div class="nav">
    <?php if(isStudent()): ?>
    <div class="nav-label">O'quvchi</div>
    <a href="<?= ROOT ?>index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'index.php')!==false&&!isset($_GET['page'])?'active':'' ?>">
      <span class="nav-ico">🏠</span> Dashboard
    </a>
    <a href="<?= ROOT ?>tests_list.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'tests_list')!==false?'active':'' ?>">
      <span class="nav-ico">🎯</span> Testlar
    </a>
    <a href="<?= ROOT ?>my_results.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'my_results')!==false?'active':'' ?>">
      <span class="nav-ico">📊</span> Natijalarim
    </a>
    <?php elseif(isTeacher()): ?>
    <div class="nav-label">O'qituvchi</div>
    <a href="<?= ROOT ?>teacher/" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'teacher/index')!==false||($_SERVER['PHP_SELF']===ROOT.'teacher/')?'active':'' ?>">
      <span class="nav-ico">🏠</span> Dashboard
    </a>
    <a href="<?= ROOT ?>teacher/subjects.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'subjects')!==false?'active':'' ?>">
      <span class="nav-ico">📚</span> Fanlar
    </a>
    <a href="<?= ROOT ?>teacher/questions.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'questions')!==false?'active':'' ?>">
      <span class="nav-ico">❓</span> Savollar
    </a>
    <a href="<?= ROOT ?>teacher/tests.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'tests.php')!==false?'active':'' ?>">
      <span class="nav-ico">📝</span> Testlar
    </a>
    <a href="<?= ROOT ?>teacher/results.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'results')!==false?'active':'' ?>">
      <span class="nav-ico">📈</span> Natijalar
    </a>
    <?php elseif(isAdmin()): ?>
    <div class="nav-label">Admin</div>
    <a href="<?= ROOT ?>admin/" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'admin/index')!==false?'active':'' ?>">
      <span class="nav-ico">🏠</span> Dashboard
    </a>
    <a href="<?= ROOT ?>admin/users.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'users')!==false?'active':'' ?>">
      <span class="nav-ico">👥</span> Foydalanuvchilar
    </a>
    <a href="<?= ROOT ?>admin/tests.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'tests')!==false?'active':'' ?>">
      <span class="nav-ico">📝</span> Testlar
    </a>
    <a href="<?= ROOT ?>admin/sessions.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'],'sessions')!==false?'active':'' ?>">
      <span class="nav-ico">🎯</span> Sessiyalar
    </a>
    <?php endif; ?>
    <div class="nav-label">Profil</div>
    <a href="<?= ROOT ?>profile.php" class="nav-item">
      <span class="nav-ico">👤</span> Profil
    </a>
    <a href="<?= ROOT ?>logout.php" class="nav-item danger">
      <span class="nav-ico">🚪</span> Chiqish
    </a>
  </div>
  <div class="sidebar-foot">
    <div class="user-chip">
      <?php if($_me['avatar']): ?>
      <div class="ava"><img src="<?= h($_me['avatar']) ?>" alt=""></div>
      <?php else: ?>
      <div class="ava"><?= strtoupper(mb_substr($_me['name'],0,2)) ?></div>
      <?php endif; ?>
      <div style="flex:1;min-width:0">
        <div class="u-name"><?= h(explode(' ',$_me['name'])[0]) ?></div>
        <div class="u-role"><?= $_me['role']==='teacher'?"O'qituvchi":($_me['role']==='admin'?'Admin':"O'quvchi") ?></div>
      </div>
      <div class="theme-pill" onclick="toggleTheme()" title="Tema"><?= $_theme==='dark'?'☀':'🌙' ?></div>
    </div>
  </div>
</nav>
<main class="main">
<?php endif; ?>

<!-- TOASTS -->
<div class="toast-wrap">
  <?php if($_fs): ?><div class="toast toast-ok">✓ <?= h($_fs) ?></div><?php endif; ?>
  <?php if($_fe): ?><div class="toast toast-err">✕ <?= h($_fe) ?></div><?php endif; ?>
  <?php if($_fi): ?><div class="toast a-info" style="padding:11px 16px"><?= h($_fi) ?></div><?php endif; ?>
</div>
<script>
function toggleTheme(){
  const h=document.documentElement,n=h.dataset.theme==='dark'?'light':'dark';
  h.dataset.theme=n;document.cookie=`theme=${n};path=/;max-age=31536000`;
  document.querySelectorAll('.theme-pill').forEach(p=>p.textContent=n==='dark'?'☀':'🌙');
}
setTimeout(()=>document.querySelectorAll('.toast').forEach(t=>{
  t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(()=>t.remove(),400);
}),3500);
</script>
