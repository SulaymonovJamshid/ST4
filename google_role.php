<?php
require_once 'config.php';
define('ROOT','');
if(empty($_SESSION['google_pending'])) redirect('login.php');
$gp=$_SESSION['google_pending'];
$allSubjects=getAllSubjectsList();
$err='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $role=in_array($_POST['role']??'',['student','teacher'])?$_POST['role']:'student';
    $selectedSubjects=isset($_POST['subjects'])&&is_array($_POST['subjects'])?$_POST['subjects']:[];
    if($role==='teacher'&&empty($selectedSubjects)){$err='Kamida bitta fan tanlang.';}
    else{
        $subJson=$role==='teacher'?json_encode($selectedSubjects):null;
        $ins=db()->prepare("INSERT INTO users(name,email,google_id,avatar,role,teacher_subjects)VALUES(?,?,?,?,?,?)");
        $ins->execute([$gp['name'],$gp['email'],$gp['gid'],$gp['avatar'],$role,$subJson]);
        $_SESSION['uid']=db()->lastInsertId();
        unset($_SESSION['google_pending']);
        flash('success','Xush kelibsiz, '.$gp['name'].'!');
        redirect($role==='teacher'?'teacher/':'index.php');
    }
}
$preRole=$gp['role']??'student';
?>
<!DOCTYPE html>
<html lang="uz" data-theme="<?= theme() ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SmartTest — Rol tanlash</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0e1a;--bg2:#0f1526;--bg3:#141c30;--card:#161d30;--card2:#1c2540;--border:rgba(99,140,255,.15);--border2:rgba(99,140,255,.3);--accent:#4f7cff;--accent2:#7b9fff;--teal:#00d4aa;--text:#e8edff;--text2:#8fa3cc;--text3:#4d6494;--danger:#ff6b6b;--glow:0 0 24px rgba(79,124,255,.2)}
[data-theme="light"]{--bg:#f0f4ff;--bg2:#fff;--bg3:#f5f7ff;--card:#fff;--card2:#eef1ff;--border:rgba(79,124,255,.15);--text:#0d1b3e;--text2:#3d5a99;--text3:#7a9acc}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Space Grotesk',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;padding:24px 16px}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(79,124,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(79,124,255,.025) 1px,transparent 1px);background-size:40px 40px;pointer-events:none}
.wrap{max-width:860px;margin:0 auto;position:relative;z-index:1}
.brand{text-align:center;margin-bottom:24px}
.brand-ico{width:44px;height:44px;background:linear-gradient(135deg,var(--accent),var(--teal));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 10px;box-shadow:var(--glow)}
.brand-name{font-size:19px;font-weight:700}
.brand-name em{color:var(--accent);font-style:normal}
.ava-wrap{text-align:center;margin-bottom:20px}
.ava{width:64px;height:64px;border-radius:16px;border:3px solid rgba(79,124,255,.4);margin:0 auto 10px}
.ava-text{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,var(--accent),var(--teal));display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#fff;margin:0 auto 10px}
.hi-name{font-size:18px;font-weight:700}
.hi-email{font-size:12px;color:var(--text3);margin-top:3px}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px}
.err{background:rgba(255,107,107,.08);border:1px solid rgba(255,107,107,.2);color:var(--danger);border-radius:9px;padding:10px 13px;font-size:13px;margin-bottom:16px}
.role-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px}
.role-card{border:2px solid var(--border);border-radius:12px;padding:18px;text-align:center;cursor:pointer;transition:all .18s;background:var(--bg3)}
.role-card:hover{border-color:var(--border2);background:var(--card2)}
.role-card.selected{border-color:var(--accent);background:rgba(79,124,255,.08)}
.role-ico{font-size:32px;margin-bottom:8px}
.role-name{font-size:14px;font-weight:700;margin-bottom:3px}
.role-desc{font-size:11px;color:var(--text3);line-height:1.5}
/* Fan tanlash */
.subj-section{border-top:1px solid var(--border);padding-top:18px;margin-top:4px}
.subj-tabs{display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap}
.subj-tab{padding:5px 12px;border-radius:20px;border:1px solid var(--border);background:var(--bg3);color:var(--text2);font-size:12px;font-weight:600;cursor:pointer;transition:all .15s}
.subj-tab.active{background:rgba(79,124,255,.15);color:var(--accent2);border-color:rgba(79,124,255,.3)}
.subj-panel{display:none}
.subj-panel.active{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:7px}
.subj-item{border:1.5px solid var(--border);border-radius:9px;padding:9px 11px;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:12px;transition:all .15s;background:var(--bg3)}
.subj-item:hover{border-color:var(--border2);background:var(--card2)}
.subj-item.checked{border-color:var(--accent);background:rgba(79,124,255,.08)}
.subj-item input{display:none}
.subj-ico{font-size:16px;flex-shrink:0}
.subj-dot{width:14px;height:14px;border-radius:50%;border:2px solid var(--text3);margin-left:auto;flex-shrink:0;transition:all .15s}
.subj-item.checked .subj-dot{border-color:var(--accent);background:var(--accent)}
.sel-cnt{font-size:12px;color:var(--text2);margin-bottom:10px}
.sel-cnt strong{color:var(--accent)}
.btn{display:flex;align-items:center;justify-content:center;gap:6px;padding:11px;border-radius:9px;font-family:'Space Grotesk',sans-serif;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:all .18s;margin-top:18px;width:100%}
.btn-p{background:var(--accent);color:#fff}
.btn-p:hover{background:var(--accent2)}
</style>
</head>
<body>
<div class="wrap">
  <div class="brand">
    <div class="brand-ico">⚡</div>
    <div class="brand-name">Smart<em>IQ</em></div>
  </div>

  <div class="ava-wrap">
    <?php if($gp['avatar']): ?>
    <img src="<?= h($gp['avatar']) ?>" class="ava" alt="">
    <?php else: ?>
    <div class="ava-text"><?= strtoupper(mb_substr($gp['name'],0,2)) ?></div>
    <?php endif; ?>
    <div class="hi-name">Salom, <?= h(explode(' ',$gp['name'])[0]) ?>!</div>
    <div class="hi-email"><?= h($gp['email']) ?></div>
  </div>

  <div class="card">
    <h2 style="font-size:16px;font-weight:700;margin-bottom:5px">Platformada kim sifatida foydalanasiz?</h2>
    <p style="font-size:12px;color:var(--text2);margin-bottom:18px">Bir marta tanlaysiz, keyin admin orqali o'zgartirishingiz mumkin.</p>

    <?php if($err): ?><div class="err">✕ <?= h($err) ?></div><?php endif; ?>

    <form method="POST">
      <div class="role-grid">
        <div class="role-card <?= $preRole!=='teacher'?'selected':'' ?>" id="rc-student" onclick="sel('student')">
          <div class="role-ico">🎓</div>
          <div class="role-name">O'quvchi</div>
          <div class="role-desc">Testlarni yechaman va bilimimni sinab ko'raman</div>
        </div>
        <div class="role-card <?= $preRole==='teacher'?'selected':'' ?>" id="rc-teacher" onclick="sel('teacher')">
          <div class="role-ico">👨‍🏫</div>
          <div class="role-name">O'qituvchi</div>
          <div class="role-desc">Fan tanlayan, savollar va testlar yarataman</div>
        </div>
      </div>
      <input type="hidden" name="role" id="roleInp" value="<?= $preRole==='teacher'?'teacher':'student' ?>">

      <!-- Fan tanlash (faqat o'qituvchi uchun) -->
      <div class="subj-section" id="subjSec" style="display:<?= $preRole==='teacher'?'block':'none' ?>">
        <div style="font-size:14px;font-weight:700;margin-bottom:4px">O'qitiladigan fanlar *</div>
        <div style="font-size:12px;color:var(--text2);margin-bottom:12px">Qaysi fanlarni o'qitasiz?</div>
        <div class="sel-cnt">Tanlangan: <strong id="selN">0</strong> ta fan</div>
        <div class="subj-tabs">
          <?php $si=0; foreach($allSubjects as $k=>$cat): ?>
          <div class="subj-tab <?= $si===0?'active':'' ?>" data-tab="<?= $k ?>"><?= h($cat['label']) ?></div>
          <?php $si++; endforeach; ?>
        </div>
        <?php $si=0; foreach($allSubjects as $k=>$cat): ?>
        <div class="subj-panel <?= $si===0?'active':'' ?>" id="pan-<?= $k ?>">
          <?php foreach($cat['items'] as $s): ?>
          <label class="subj-item" id="si-<?= $s['id'] ?>" onclick="togS(this,'<?= $s['id'] ?>')">
            <input type="checkbox" name="subjects[]" value="<?= $s['id'] ?>">
            <span class="subj-ico"><?= $s['icon'] ?></span>
            <span style="flex:1;font-weight:500"><?= h($s['name']) ?></span>
            <span class="subj-dot"></span>
          </label>
          <?php endforeach; ?>
        </div>
        <?php $si++; endforeach; ?>
      </div>

      <button type="submit" class="btn btn-p" id="submitBtn">🎓 O'quvchi sifatida kirish</button>
    </form>
  </div>
</div>

<script>
const btnLabels={student:"🎓 O'quvchi sifatida kirish",teacher:"👨‍🏫 O'qituvchi sifatida kirish"};
let curRole='<?= $preRole==="teacher"?"teacher":"student" ?>';

function sel(r){
  curRole=r;
  document.getElementById('roleInp').value=r;
  ['student','teacher'].forEach(x=>{
    document.getElementById('rc-'+x).classList.toggle('selected',x===r);
  });
  document.getElementById('subjSec').style.display=r==='teacher'?'block':'none';
  document.getElementById('submitBtn').textContent=btnLabels[r];
}

document.querySelectorAll('.subj-tab').forEach(tab=>{
  tab.addEventListener('click',()=>{
    document.querySelectorAll('.subj-tab').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.subj-panel').forEach(p=>p.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('pan-'+tab.dataset.tab)?.classList.add('active');
  });
});

function togS(el,id){
  el.classList.toggle('checked');
  el.querySelector('input').checked=el.classList.contains('checked');
  document.getElementById('selN').textContent=document.querySelectorAll('.subj-item.checked').length;
}
</script>
</body></html>
