<?php
require_once 'config.php';
define('ROOT','');
requireLogin();
$me=me();
$allSubjs=getAllSubjectsList();
$err='';

if($_SERVER['REQUEST_METHOD']==='POST'&&checkToken()){
    $name=trim($_POST['name']??'');
    $pass=$_POST['password']??'';
    $pass2=$_POST['password2']??'';
    $selectedSubjects=isset($_POST['subjects'])&&is_array($_POST['subjects'])?$_POST['subjects']:null;

    if(!$name){$err='Ismni kiriting.';}
    elseif($pass&&strlen($pass)<6){$err='Parol kamida 6 ta belgi.';}
    elseif($pass&&$pass!==$pass2){$err='Parollar mos kelmadi.';}
    elseif($me['role']==='teacher'&&$selectedSubjects!==null&&empty($selectedSubjects)){$err='Kamida bitta fan tanlang.';}
    else {
        $subJson=$me['role']==='teacher'&&$selectedSubjects!==null?json_encode($selectedSubjects):$me['teacher_subjects'];
        if($pass){
            db()->prepare("UPDATE users SET name=?,password=?,teacher_subjects=? WHERE id=?")->execute([$name,password_hash($pass,PASSWORD_DEFAULT),$subJson,$me['id']]);
        } else {
            db()->prepare("UPDATE users SET name=?,teacher_subjects=? WHERE id=?")->execute([$name,$subJson,$me['id']]);
        }
        flash('success','Profil yangilandi!');
        redirect('profile.php');
    }
}

$teacherSubjects=json_decode($me['teacher_subjects']??'[]',true);
$pageTitle='Profil';
include 'includes/ui.php';
?>
<div class="page-wrap">
  <div class="topbar">
    <div><div class="page-title">👤 Profil</div><div class="page-sub">Shaxsiy ma'lumotlaringizni tahrirlash</div></div>
  </div>

  <div class="g2" style="align-items:start">
    <!-- Info card -->
    <div class="card" style="text-align:center;padding:28px">
      <?php if($me['avatar']): ?>
      <img src="<?= h($me['avatar']) ?>" style="width:76px;height:76px;border-radius:18px;object-fit:cover;margin-bottom:14px;border:3px solid rgba(79,124,255,.3)">
      <?php else: ?>
      <div style="width:76px;height:76px;border-radius:18px;background:linear-gradient(135deg,var(--accent),var(--teal));display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:#fff;margin:0 auto 14px;box-shadow:var(--glow)">
        <?= strtoupper(mb_substr($me['name'],0,2)) ?>
      </div>
      <?php endif; ?>
      <div style="font-size:18px;font-weight:700;margin-bottom:4px"><?= h($me['name']) ?></div>
      <div class="c-dim text-sm mb-3"><?= h($me['email']) ?></div>
      <?php
      $roleColors=['student'=>'var(--success)','teacher'=>'#38bdf8','admin'=>'var(--amber)'];
      $roleLabels=['student'=>"🎓 O'quvchi",'teacher'=>"👨‍🏫 O'qituvchi",'admin'=>'⚙️ Admin'];
      $rc=$roleColors[$me['role']]??'var(--text3)';
      ?>
      <span style="background:<?= str_replace('var(--','rgba(',str_replace(')',',.15)',$rc)) ?>;color:<?= $rc ?>;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;border:1px solid <?= str_replace('var(--','rgba(',str_replace(')',',.25)',$rc)) ?>">
        <?= $roleLabels[$me['role']]??$me['role'] ?>
      </span>
      <?php if($me['google_id']): ?>
      <div class="c-dim text-xs mt-3">🔗 Google orqali ulangan</div>
      <?php endif; ?>

      <?php if($me['role']==='student'):
        $st=db()->prepare("SELECT COUNT(*) t,SUM(status='completed') d,AVG(CASE WHEN status='completed' THEN score END) av FROM test_sessions WHERE student_id=?");
        $st->execute([$me['id']]);$st=$st->fetch();
      ?>
      <div style="border-top:1px solid var(--border);padding-top:16px;margin-top:16px">
        <div class="g2" style="gap:10px">
          <?php foreach([['📝','Urinish',(int)$st['t']],['✅','Yakunlangan',(int)$st['d']]] as [$i,$l,$v]): ?>
          <div style="background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:10px;text-align:center">
            <div style="font-size:18px;margin-bottom:4px"><?= $i ?></div>
            <div style="font-size:18px;font-weight:700"><?= $v ?></div>
            <div class="c-dim text-xs"><?= $l ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Edit form -->
    <div class="card">
      <div class="card-h"><span class="card-t">✏️ Ma'lumotlarni tahrirlash</span></div>
      <div class="card-body">
        <?php if($err): ?><div class="alert a-err">✕ <?= h($err) ?></div><?php endif; ?>
        <form method="POST">
          <input type="hidden" name="_token" value="<?= token() ?>">
          <div class="fg">
            <label class="fl">To'liq ism</label>
            <input type="text" name="name" class="fi" value="<?= h($me['name']) ?>" required>
          </div>
          <div class="fg">
            <label class="fl">Email</label>
            <input type="email" class="fi" value="<?= h($me['email']) ?>" disabled style="opacity:.5">
            <div class="fh">Email o'zgartirib bo'lmaydi</div>
          </div>
          <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:4px;margin-bottom:14px">
            <div class="text-xs c-dim mb-3">Parolni o'zgartirish (ixtiyoriy)</div>
            <div class="g2">
              <div class="fg"><label class="fl">Yangi parol</label><input type="password" name="password" class="fi" placeholder="Bo'sh qoldiring"></div>
              <div class="fg"><label class="fl">Tasdiqlash</label><input type="password" name="password2" class="fi" placeholder="Takrorlang"></div>
            </div>
          </div>

          <?php if($me['role']==='teacher'): ?>
          <!-- O'qituvchi fanlarini tahrirlash -->
          <div style="border-top:1px solid var(--border);padding-top:14px;margin-bottom:14px">
            <div style="font-size:13px;font-weight:700;margin-bottom:4px">📚 O'qitiladigan fanlar</div>
            <div class="text-xs c-dim mb-3">Fanlar ro'yxatini yangilang</div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
              <span class="text-xs c-dim">Tanlangan: <strong id="selN" style="color:var(--accent)"><?= count($teacherSubjects) ?></strong> ta fan</span>
            </div>
            <!-- Tabs -->
            <div style="display:flex;gap:5px;margin-bottom:12px;flex-wrap:wrap">
              <?php $si=0; foreach($allSubjs as $k=>$cat): ?>
              <div class="p-tab <?= $si===0?'active':'' ?>" data-tab="<?= $k ?>" style="padding:4px 11px;border-radius:20px;border:1px solid var(--border);background:<?= $si===0?'rgba(79,124,255,.15)':'var(--bg3)' ?>;color:<?= $si===0?'var(--accent2)':'var(--text2)' ?>;font-size:11px;font-weight:600;cursor:pointer;transition:all .15s">
                <?= h($cat['label']) ?>
              </div>
              <?php $si++; endforeach; ?>
            </div>
            <!-- Panellar -->
            <?php $si=0; foreach($allSubjs as $k=>$cat): ?>
            <div class="p-panel <?= $si===0?'active':'' ?>" id="pp-<?= $k ?>" style="display:<?= $si===0?'grid':'none' ?>;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:6px">
              <?php foreach($cat['items'] as $subj):
                $isChk=in_array($subj['id'],$teacherSubjects);
              ?>
              <label class="si <?= $isChk?'checked':'' ?>" id="psi-<?= $subj['id'] ?>" onclick="togS(this,'<?= $subj['id'] ?>')" style="display:flex;align-items:center;gap:7px;padding:7px 10px;border:1.5px solid <?= $isChk?'var(--accent)':'var(--border)' ?>;border-radius:8px;cursor:pointer;background:<?= $isChk?'rgba(79,124,255,.08)':'var(--bg3)' ?>;transition:all .15s;font-size:11px;font-weight:500">
                <input type="checkbox" name="subjects[]" value="<?= $subj['id'] ?>" <?= $isChk?'checked':'' ?> style="display:none">
                <span><?= $subj['icon'] ?></span>
                <span style="flex:1"><?= h($subj['name']) ?></span>
                <span style="width:13px;height:13px;border-radius:50%;border:2px solid <?= $isChk?'var(--accent)':'var(--text3)' ?>;background:<?= $isChk?'var(--accent)':'transparent' ?>;flex-shrink:0;transition:all .15s" class="si-dot"></span>
              </label>
              <?php endforeach; ?>
            </div>
            <?php $si++; endforeach; ?>
          </div>
          <?php endif; ?>

          <button type="submit" class="btn btn-p btn-fw">Saqlash</button>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
.si:hover{border-color:var(--border2)!important}
.si.checked{border-color:var(--accent)!important;background:rgba(79,124,255,.08)!important}
.si.checked .si-dot{border-color:var(--accent)!important;background:var(--accent)!important}
</style>
<script>
document.querySelectorAll('.p-tab').forEach(tab=>{
  tab.addEventListener('click',()=>{
    document.querySelectorAll('.p-tab').forEach(t=>{t.style.background='var(--bg3)';t.style.color='var(--text2)';t.classList.remove('active');});
    document.querySelectorAll('.p-panel').forEach(p=>{p.style.display='none';p.classList.remove('active');});
    tab.style.background='rgba(79,124,255,.15)';tab.style.color='var(--accent2)';tab.classList.add('active');
    const panel=document.getElementById('pp-'+tab.dataset.tab);
    if(panel){panel.style.display='grid';panel.classList.add('active');}
  });
});
function togS(el,id){
  el.classList.toggle('checked');
  el.querySelector('input').checked=el.classList.contains('checked');
  const n=document.querySelectorAll('.si.checked').length;
  document.getElementById('selN')&&(document.getElementById('selN').textContent=n);
}
</script>
<?php include 'includes/foot.php'; ?>
