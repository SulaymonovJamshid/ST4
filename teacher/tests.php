<?php
require_once '../config.php';
define('ROOT','../');
requireRole('teacher');
$me=me();
$action=$_GET['action']??'list';

if($action==='delete'&&isset($_GET['id'])){
    db()->prepare("DELETE FROM tests WHERE id=? AND teacher_id=?")->execute([(int)$_GET['id'],$me['id']]);
    flash('success','Test o\'chirildi.');redirect('tests.php');
}
if($action==='toggle'&&isset($_GET['id'])){
    db()->prepare("UPDATE tests SET is_active=1-is_active WHERE id=? AND teacher_id=?")->execute([(int)$_GET['id'],$me['id']]);
    redirect('tests.php');
}

if($_SERVER['REQUEST_METHOD']==='POST'&&checkToken()){
    $title=trim($_POST['title']??'');$subId=(int)($_POST['subject_id']??0);
    $diff=(int)($_POST['difficulty']??1);$cnt=min(100,max(1,(int)($_POST['question_count']??10)));
    $pts=min(10,max(1,(int)($_POST['points_per_q']??1)));$tpq=min(10,max(1,(int)($_POST['time_per_q']??1)));
    if(!$title||!$subId){flash('error','Barcha maydonlarni to\'ldiring.');}
    else {
        // Savollar yetarliligini tekshirish
        if($diff===0){
            $av=db()->prepare("SELECT COUNT(*) FROM questions WHERE subject_id=? AND teacher_id=?");
        }else{
            $av=db()->prepare("SELECT COUNT(*) FROM questions WHERE subject_id=? AND teacher_id=? AND difficulty=?");
        }
        $av->execute($diff===0?[$subId,$me['id']]:[$subId,$me['id'],$diff]);
        $avail=(int)$av->fetchColumn();
        if($avail<$cnt){flash('error',"Savollar yetarli emas. Mavjud: $avail ta (kerak: $cnt ta).");}
        else {
            if(!empty($_POST['id'])){
                db()->prepare("UPDATE tests SET subject_id=?,title=?,difficulty=?,question_count=?,points_per_q=?,time_per_q=? WHERE id=? AND teacher_id=?")
                    ->execute([$subId,$title,$diff,$cnt,$pts,$tpq,(int)$_POST['id'],$me['id']]);
                flash('success','Test yangilandi.');
            }else{
                db()->prepare("INSERT INTO tests(teacher_id,subject_id,title,difficulty,question_count,points_per_q,time_per_q) VALUES(?,?,?,?,?,?,?)")
                    ->execute([$me['id'],$subId,$title,$diff,$cnt,$pts,$tpq]);
                flash('success','Test yaratildi!');
            }
            redirect('tests.php');
        }
    }
}

$tests=db()->prepare("SELECT t.*,s.name sn,s.icon si,
    (SELECT COUNT(*) FROM test_sessions ts WHERE ts.test_id=t.id) att,
    (SELECT COUNT(*) FROM test_sessions ts WHERE ts.test_id=t.id AND ts.status='completed') done
    FROM tests t JOIN subjects s ON s.id=t.subject_id WHERE t.teacher_id=? ORDER BY t.id DESC");
$tests->execute([$me['id']]);$tests=$tests->fetchAll();

$subjects=db()->prepare("SELECT * FROM subjects WHERE teacher_id=? ORDER BY name");
$subjects->execute([$me['id']]);$subjects=$subjects->fetchAll();

$editItem=null;
if($action==='edit'&&isset($_GET['id'])){
    $e=db()->prepare("SELECT * FROM tests WHERE id=? AND teacher_id=?");
    $e->execute([(int)$_GET['id'],$me['id']]);$editItem=$e->fetch();
}
$pageTitle='Testlar';
include '../includes/ui.php';
?>
<div class="page-wrap">
  <div class="topbar">
    <div><div class="page-title">📝 Testlar</div><div class="page-sub">Testlarni yaratish va boshqarish</div></div>
    <div class="tb-right">
      <a href="index.php" class="btn btn-g">← Dashboard</a>
      <a href="?action=create" class="btn btn-p">➕ Yangi test</a>
    </div>
  </div>

  <?php if($action==='create'||$action==='edit'): ?>
  <div class="card mb-4" style="max-width:560px">
    <div class="card-h"><span class="card-t"><?= $action==='edit'?'Testni tahrirlash':'Yangi test yaratish' ?></span></div>
    <div class="card-body">
      <?php if(empty($subjects)): ?>
      <div class="alert a-warn">⚠️ Avval fan yarating. <a href="subjects.php?action=create" style="color:var(--amber)">Fan qo'shish →</a></div>
      <?php else: ?>
      <form method="POST">
        <input type="hidden" name="_token" value="<?= token() ?>">
        <?php if($editItem): ?><input type="hidden" name="id" value="<?= $editItem['id'] ?>"><?php endif; ?>
        <div class="fg">
          <label class="fl">Test nomi *</label>
          <input type="text" name="title" class="fi" placeholder="Matematika — 1-bob" value="<?= h($editItem['title']??'') ?>" required>
        </div>
        <div class="fg">
          <label class="fl">Fan *</label>
          <select name="subject_id" class="fs" required>
            <option value="">Fan tanlang...</option>
            <?php foreach($subjects as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($editItem['subject_id']??0)==$s['id']?'selected':'' ?>><?= h($s['icon'].' '.$s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label class="fl">Savollar qiyinligi</label>
          <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:7px">
            <?php foreach([0=>'🔀 Aralash',1=>'🟢 Oson',2=>"🟡 O'rta",3=>'🔴 Qiyin'] as $d=>$lbl): ?>
            <label style="cursor:pointer">
              <input type="radio" name="difficulty" value="<?= $d ?>" <?= ($editItem['difficulty']??1)==$d?'checked':'' ?> style="display:none" class="dr">
              <div class="dc" style="border:2px solid <?= ($editItem['difficulty']??1)==$d?'var(--accent)':'var(--border)' ?>;background:<?= ($editItem['difficulty']??1)==$d?'rgba(79,124,255,.1)':'var(--bg3)' ?>;border-radius:9px;padding:9px 6px;text-align:center;transition:all .18s;font-size:11px;font-weight:600;color:var(--text)">
                <?= $lbl ?>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:11px">
          <div class="fg">
            <label class="fl">Savollar soni</label>
            <input type="number" name="question_count" class="fi" min="1" max="100" value="<?= $editItem['question_count']??10 ?>" required oninput="upd()">
            <div class="fh">1–100 ta</div>
          </div>
          <div class="fg">
            <label class="fl">Ball/savol</label>
            <input type="number" name="points_per_q" class="fi" min="1" max="10" value="<?= $editItem['points_per_q']??1 ?>" required oninput="upd()">
            <div class="fh">1–10 ball</div>
          </div>
          <div class="fg">
            <label class="fl">Vaqt (daq/savol)</label>
            <input type="number" name="time_per_q" class="fi" min="1" max="10" value="<?= $editItem['time_per_q']??1 ?>" required oninput="upd()">
            <div class="fh">1–10 daqiqa</div>
          </div>
        </div>
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:9px;padding:11px 13px;margin-bottom:16px;font-size:13px;color:var(--text2)">
          📊 Jami: <strong id="tq" style="color:var(--accent2)">?</strong> savol ·
          ⭐ Maks: <strong id="tp" style="color:var(--amber)">?</strong> ball ·
          ⏱ Vaqt: <strong id="tt" style="color:var(--teal)">?</strong> daqiqa
        </div>
        <div class="flex gap-2">
          <button type="submit" class="btn btn-p"><?= $action==='edit'?'Saqlash':'Test yaratish' ?></button>
          <a href="tests.php" class="btn btn-g">Bekor</a>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if(empty($tests)): ?>
  <div class="card"><div class="empty"><div class="empty-ico">📭</div><div class="empty-txt">Testlar yo'q</div></div></div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Test</th><th>Qiyinlik</th><th>Savollar</th><th>Ball/Vaqt</th><th>Urinish</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach($tests as $t): ?>
      <tr>
        <td><div class="flex items-c gap-2"><span style="font-size:18px"><?= h($t['si']) ?></span><div><div style="font-weight:600;font-size:13px"><?= h($t['title']) ?></div><div class="c-dim text-xs"><?= h($t['sn']) ?></div></div></div></td>
        <td><?= $t['difficulty']==0?'<span class="badge badge-blue">Aralash</span>':diffBadge($t['difficulty']) ?></td>
        <td><strong><?= $t['question_count'] ?></strong> <span class="c-dim text-xs">ta</span></td>
        <td><div class="text-xs">⭐ <?= $t['points_per_q'] ?>ball/savol</div><div class="c-dim text-xs">⏱ <?= $t['question_count']*$t['time_per_q'] ?>daq jami</div></td>
        <td><div class="text-xs"><strong><?= $t['att'] ?></strong> urinish</div><div class="c-dim text-xs"><?= $t['done'] ?> yakunlangan</div></td>
        <td><a href="?action=toggle&id=<?= $t['id'] ?>" style="text-decoration:none"><span style="font-size:12px;font-weight:600;color:<?= $t['is_active']?'var(--success)':'var(--text3)' ?>"><?= $t['is_active']?'✓ Aktiv':'○ Nofaol' ?></span></a></td>
        <td><div class="flex gap-1">
          <a href="?action=edit&id=<?= $t['id'] ?>" class="btn btn-g btn-xs">✏️</a>
          <a href="results.php?test=<?= $t['id'] ?>" class="btn btn-g btn-xs">📊</a>
          <a href="?action=delete&id=<?= $t['id'] ?>" class="btn btn-d btn-xs" onclick="return confirm('O\'chirasizmi?')">🗑</a>
        </div></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<script>
function upd(){
  const q=parseInt(document.querySelector('[name=question_count]')?.value)||0;
  const p=parseInt(document.querySelector('[name=points_per_q]')?.value)||0;
  const t=parseInt(document.querySelector('[name=time_per_q]')?.value)||0;
  document.getElementById('tq')&&(document.getElementById('tq').textContent=q);
  document.getElementById('tp')&&(document.getElementById('tp').textContent=q*p);
  document.getElementById('tt')&&(document.getElementById('tt').textContent=q*t);
}
document.querySelectorAll('.dr').forEach(r=>r.addEventListener('change',()=>{
  document.querySelectorAll('.dc').forEach(d=>{d.style.borderColor='var(--border)';d.style.background='var(--bg3)';});
  r.nextElementSibling.style.borderColor='var(--accent)';r.nextElementSibling.style.background='rgba(79,124,255,.1)';
}));
upd();
</script>
<?php include '../includes/foot.php'; ?>
