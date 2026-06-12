<?php
require_once 'config.php';
define('ROOT','');
if(isLoggedIn()) redirect('index.php');

$err=''; $step=(int)($_POST['step']??1); $role=$_POST['role']??'student';
$allSubjects=getAllSubjectsList();

if($_SERVER['REQUEST_METHOD']==='POST'&&checkToken()){
    $role=$_POST['role']??'student';

    // 2-qadam: to'liq ro'yxatdan o'tish
    if(isset($_POST['register'])){
        $name=trim($_POST['name']??'');
        $email=trim($_POST['email']??'');
        $pass=$_POST['password']??'';
        $pass2=$_POST['password2']??'';
        $selectedSubjects=isset($_POST['subjects'])&&is_array($_POST['subjects'])?$_POST['subjects']:[];

        if(!$name||!$email||!$pass) $err='Barcha maydonlarni to\'ldiring.';
        elseif(strlen($pass)<6) $err='Parol kamida 6 ta belgi bo\'lishi kerak.';
        elseif($pass!==$pass2) $err='Parollar mos kelmadi.';
        elseif($role==='teacher'&&empty($selectedSubjects)) $err='Kamida bitta fan tanlang.';
        else {
            $chk=db()->prepare("SELECT id FROM users WHERE email=?");
            $chk->execute([$email]);
            if($chk->fetch()) $err='Bu email allaqachon ro\'yxatdan o\'tgan.';
            else {
                $subJson=$role==='teacher'?json_encode($selectedSubjects):null;
                $ins=db()->prepare("INSERT INTO users(name,email,password,role,teacher_subjects)VALUES(?,?,?,?,?)");
                $ins->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role,$subJson]);
                $_SESSION['uid']=db()->lastInsertId();
                flash('success','Hisob yaratildi! Xush kelibsiz!');
                redirect($role==='teacher'?'teacher/':'index.php');
            }
        }
        if($err) $step=2; // xato bo'lsa 2-qadamda qol
    }
}
$pageTitle="Ro'yxatdan o'tish";
?>
<!DOCTYPE html>
<html lang="uz" data-theme="<?= theme() ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SmartTest — Ro'yxatdan o'tish</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0e1a;--bg2:#0f1526;--bg3:#141c30;--card:#161d30;--card2:#1c2540;--border:rgba(99,140,255,.15);--border2:rgba(99,140,255,.3);--accent:#4f7cff;--accent2:#7b9fff;--teal:#00d4aa;--teal2:#00b894;--text:#e8edff;--text2:#8fa3cc;--text3:#4d6494;--danger:#ff6b6b;--success:#51cf66;--amber:#ffb347;--purple:#da77f2;--glow:0 0 24px rgba(79,124,255,.2)}
[data-theme="light"]{--bg:#f0f4ff;--bg2:#fff;--bg3:#f5f7ff;--card:#fff;--card2:#eef1ff;--border:rgba(79,124,255,.15);--text:#0d1b3e;--text2:#3d5a99;--text3:#7a9acc}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Space Grotesk',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;padding:24px 16px;position:relative}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(79,124,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(79,124,255,.025) 1px,transparent 1px);background-size:40px 40px;pointer-events:none}
.wrap{max-width:900px;margin:0 auto;position:relative;z-index:1}
.brand{text-align:center;margin-bottom:24px}
.brand-ico{width:44px;height:44px;background:linear-gradient(135deg,var(--accent),var(--teal));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 10px;box-shadow:var(--glow)}
.brand-name{font-size:20px;font-weight:700;letter-spacing:-.4px}
.brand-name em{color:var(--accent);font-style:normal}
/* STEPS */
.steps{display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:24px}
.step{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--text3)}
.step.active{color:var(--accent2)}
.step.done{color:var(--success)}
.step-num{width:26px;height:26px;border-radius:50%;border:2px solid var(--text3);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;transition:all .2s}
.step.active .step-num{border-color:var(--accent);background:var(--accent);color:#fff}
.step.done .step-num{border-color:var(--success);background:var(--success);color:#fff}
.step-line{width:40px;height:2px;background:var(--border);margin:0 8px}
.step.done+.step-line{background:var(--success)}
/* CARD */
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px}
.err{background:rgba(255,107,107,.08);border:1px solid rgba(255,107,107,.2);color:var(--danger);border-radius:9px;padding:10px 13px;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
/* ROL TANLASH */
.role-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px}
.role-card{border:2px solid var(--border);border-radius:12px;padding:20px 16px;text-align:center;cursor:pointer;transition:all .18s;background:var(--bg3)}
.role-card:hover{border-color:var(--border2);background:var(--card2)}
.role-card.selected{border-color:var(--accent);background:rgba(79,124,255,.08)}
.role-ico{font-size:36px;margin-bottom:10px}
.role-name{font-size:15px;font-weight:700;margin-bottom:4px}
.role-desc{font-size:11px;color:var(--text3);line-height:1.5}
/* FANLAR TANLASH */
.subj-tabs{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap}
.subj-tab{padding:6px 14px;border-radius:20px;border:1px solid var(--border);background:var(--bg3);color:var(--text2);font-size:12px;font-weight:600;cursor:pointer;transition:all .15s}
.subj-tab.active{background:rgba(79,124,255,.15);color:var(--accent2);border-color:rgba(79,124,255,.3)}
.subj-panel{display:none}
.subj-panel.active{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px}
.subj-item{border:1.5px solid var(--border);border-radius:10px;padding:10px 12px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:13px;transition:all .15s;background:var(--bg3)}
.subj-item:hover{border-color:var(--border2);background:var(--card2)}
.subj-item.checked{border-color:var(--accent);background:rgba(79,124,255,.08)}
.subj-item input{display:none}
.subj-ico{font-size:18px;flex-shrink:0}
.subj-dot{width:16px;height:16px;border-radius:50%;border:2px solid var(--text3);margin-left:auto;flex-shrink:0;transition:all .15s}
.subj-item.checked .subj-dot{border-color:var(--accent);background:var(--accent)}
.sel-count{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--text2);margin-bottom:12px;padding:6px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:20px}
.sel-count strong{color:var(--accent);font-family:monospace}
/* FORM */
.fg{margin-bottom:14px}
.fl{display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:6px}
.fi{width:100%;padding:10px 13px;background:var(--bg3);border:1.5px solid var(--border);border-radius:9px;font-size:13px;font-family:'Space Grotesk',sans-serif;color:var(--text);outline:none;transition:all .18s}
.fi:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,124,255,.12);background:var(--card)}
.fi::placeholder{color:var(--text3)}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 20px;border-radius:9px;font-family:'Space Grotesk',sans-serif;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .18s;text-decoration:none}
.btn-p{background:var(--accent);color:#fff;width:100%}
.btn-p:hover{background:var(--accent2)}
.btn-g{background:var(--bg3);color:var(--text2);border:1px solid var(--border)}
.btn-g:hover{background:var(--card2);color:var(--text)}
.google-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:10px;border-radius:9px;border:1.5px solid var(--border2);background:var(--bg3);color:var(--text2);font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;font-family:'Space Grotesk',sans-serif;transition:all .18s;margin-bottom:14px}
.google-btn:hover{background:var(--card);color:var(--text);border-color:var(--accent)}
.divider{display:flex;align-items:center;gap:10px;margin-bottom:14px;color:var(--text3);font-size:11px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border)}
.foot{text-align:center;font-size:13px;color:var(--text2);margin-top:14px}
.foot a{color:var(--accent);text-decoration:none;font-weight:600}
.theme-btn{position:fixed;top:16px;right:16px;width:36px;height:36px;background:var(--card);border:1px solid var(--border);border-radius:9px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;z-index:100}
</style>
</head>
<body>
<div class="theme-btn" onclick="toggleTheme()" id="themeBtn"><?= theme()==='dark'?'☀️':'🌙' ?></div>
<div class="wrap">
  <div class="brand">
    <div class="brand-ico">⚡</div>
    <div class="brand-name">Smart<em>IQ</em></div>
  </div>

  <!-- STEPS indicator -->
  <div class="steps">
    <div class="step <?= $step>=1?'active':'' ?> <?= $step>1?'done':'' ?>">
      <div class="step-num"><?= $step>1?'✓':'1' ?></div> Rol tanlash
    </div>
    <div class="step-line"></div>
    <div class="step <?= $step>=2?'active':'' ?>">
      <div class="step-num">2</div> Ma'lumotlar
    </div>
  </div>

  <?php if($step===1): ?>
  <!-- ══ 1-QADAM: ROL TANLASH ══ -->
  <div class="card">
    <h2 style="font-size:17px;font-weight:700;margin-bottom:6px">Siz kim sifatida ro'yxatdan o'tasiz?</h2>
    <p style="font-size:13px;color:var(--text2);margin-bottom:20px">Rolingizga qarab farqli imkoniyatlar beriladi.</p>
    <form method="POST" id="roleForm">
      <input type="hidden" name="_token" value="<?= token() ?>">
      <input type="hidden" name="step" value="2">
      <input type="hidden" name="role" value="student" id="roleInput">
      <div class="role-grid">
        <div class="role-card selected" id="rc-student" onclick="selectRole('student')">
          <div class="role-ico">🎓</div>
          <div class="role-name">O'quvchi</div>
          <div class="role-desc">O'qituvchilar yaratgan testlarni yeching va bilimingizni sinab ko'ring</div>
        </div>
        <div class="role-card" id="rc-teacher" onclick="selectRole('teacher')">
          <div class="role-ico">👨‍🏫</div>
          <div class="role-name">O'qituvchi</div>
          <div class="role-desc">Fan tanlang, savollar va testlar yarating, o'quvchilar natijalarini kuzating</div>
        </div>
      </div>
      <button type="submit" class="btn btn-p">Davom etish →</button>
    </form>
  </div>

  <?php else: ?>
  <!-- ══ 2-QADAM: MA'LUMOTLAR ══ -->
  <div class="card">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
      <div style="font-size:28px"><?= $role==='teacher'?'👨‍🏫':'🎓' ?></div>
      <div>
        <div style="font-size:16px;font-weight:700"><?= $role==='teacher'?"O'qituvchi":"O'quvchi" ?> hisobi</div>
        <div style="font-size:12px;color:var(--text2)">Ma'lumotlaringizni kiriting</div>
      </div>
      <a href="register.php" class="btn btn-g btn-sm" style="margin-left:auto;padding:6px 12px;font-size:12px;text-decoration:none">← Orqaga</a>
    </div>

    <?php if($err): ?><div class="err">✕ <?= h($err) ?></div><?php endif; ?>

    <a href="auth_google.php?role=<?= $role ?>" class="google-btn">
      <svg width="16" height="16" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
      Google orqali ro'yxatdan o'tish
    </a>
    <div class="divider">yoki email bilan</div>

    <form method="POST">
      <input type="hidden" name="_token" value="<?= token() ?>">
      <input type="hidden" name="step" value="2">
      <input type="hidden" name="role" value="<?= h($role) ?>">
      <input type="hidden" name="register" value="1">

      <div class="g2">
        <div class="fg">
          <label class="fl">To'liq ism *</label>
          <input type="text" name="name" class="fi" placeholder="Ism Familiya" value="<?= h($_POST['name']??'') ?>" required>
        </div>
        <div class="fg">
          <label class="fl">Email manzil *</label>
          <input type="email" name="email" class="fi" placeholder="siz@example.com" value="<?= h($_POST['email']??'') ?>" required>
        </div>
      </div>
      <div class="g2">
        <div class="fg">
          <label class="fl">Parol *</label>
          <input type="password" name="password" class="fi" placeholder="Kamida 6 ta belgi" required>
        </div>
        <div class="fg">
          <label class="fl">Parolni tasdiqlash *</label>
          <input type="password" name="password2" class="fi" placeholder="Parolni takrorlang" required>
        </div>
      </div>

      <?php if($role==='teacher'): ?>
      <!-- ══ FAN TANLASH (O'qituvchi uchun) ══ -->
      <div style="border-top:1px solid var(--border);padding-top:18px;margin-top:4px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
          <div>
            <div style="font-size:14px;font-weight:700">O'qitiladigan fanlar *</div>
            <div style="font-size:12px;color:var(--text2)">O'qitmoqchi bo'lgan fanlaringizni tanlang (keyinroq o'zgartirishingiz mumkin)</div>
          </div>
          <div class="sel-count" id="selCount"><strong id="selNum">0</strong> fan tanlandi</div>
        </div>

        <!-- Tabs: Maktab / Universitet / Magistratura -->
        <div class="subj-tabs">
          <?php foreach($allSubjects as $key=>$cat): ?>
          <div class="subj-tab <?= $key==='school'?'active':'' ?>" onclick="switchTab('<?= $key ?>')">
            <?= h($cat['label']) ?>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Fan panellari -->
        <?php foreach($allSubjects as $key=>$cat): ?>
        <div class="subj-panel <?= $key==='school'?'active':'' ?>" id="panel-<?= $key ?>">
          <?php foreach($cat['items'] as $subj): ?>
          <label class="subj-item <?= in_array($subj['id'],(array)($_POST['subjects']??[]))?'checked':'' ?>"
                 id="si-<?= $subj['id'] ?>"
                 onclick="toggleSubj(this,'<?= $subj['id'] ?>')">
            <input type="checkbox" name="subjects[]" value="<?= $subj['id'] ?>"
                   <?= in_array($subj['id'],(array)($_POST['subjects']??[]))?'checked':'' ?>>
            <span class="subj-ico"><?= $subj['icon'] ?></span>
            <span style="font-size:12px;font-weight:500;flex:1"><?= h($subj['name']) ?></span>
            <span class="subj-dot"></span>
          </label>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-p" style="margin-top:18px">
        <?= $role==='teacher'?"👨‍🏫 O'qituvchi hisob yaratish":"🎓 O'quvchi hisob yaratish" ?>
      </button>
    </form>
  </div>
  <?php endif; ?>

  <div class="foot">Hisobingiz bormi? <a href="login.php">Kirish</a></div>
</div>

<script>
function selectRole(r){
  document.getElementById('roleInput').value=r;
  ['student','teacher'].forEach(x=>{
    document.getElementById('rc-'+x).classList.toggle('selected',x===r);
  });
}

function switchTab(key){
  document.querySelectorAll('.subj-tab').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('.subj-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.subj-tab')[['school','university','masters'].indexOf(key)]?.classList.add('active');
  document.getElementById('panel-'+key)?.classList.add('active');
}

function toggleSubj(el,id){
  el.classList.toggle('checked');
  const cb=el.querySelector('input[type=checkbox]');
  if(cb) cb.checked=el.classList.contains('checked');
  updateCount();
}

function updateCount(){
  const n=document.querySelectorAll('.subj-item.checked').length;
  const el=document.getElementById('selNum');
  if(el) el.textContent=n;
}

function toggleTheme(){
  const h=document.documentElement,n=h.dataset.theme==='dark'?'light':'dark';
  h.dataset.theme=n;document.cookie=`theme=${n};path=/;max-age=31536000`;
  document.getElementById('themeBtn').textContent=n==='dark'?'☀️':'🌙';
}

// Tabs onclick to'g'irlash
document.querySelectorAll('.subj-tab').forEach((tab,i)=>{
  const keys=['school','university','masters'];
  tab.onclick=()=>switchTab(keys[i]);
});

updateCount();
</script>
</body>
</html>
