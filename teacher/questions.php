<?php
require_once '../config.php';
define('ROOT','../');
requireRole('teacher');
$me=me();
$action=$_GET['action']??'list';
$filterSubject=(int)($_GET['subject']??0);
$filterDiff=(int)($_GET['diff']??0);
$page=max(1,(int)($_GET['page']??1));
$perPage=20;

if($action==='delete'&&isset($_GET['id'])){
    db()->prepare("DELETE FROM questions WHERE id=? AND teacher_id=?")->execute([(int)$_GET['id'],$me['id']]);
    flash('success','Savol o\'chirildi.');redirect('questions.php'.($filterSubject?"?subject=$filterSubject":''));
}

if($_SERVER['REQUEST_METHOD']==='POST'&&checkToken()){
    $sid=(int)($_POST['subject_id']??0);
    $qt=trim($_POST['question_text']??'');
    $oa=trim($_POST['option_a']??'');$ob=trim($_POST['option_b']??'');
    $oc=trim($_POST['option_c']??'');$od=trim($_POST['option_d']??'');
    $cor=$_POST['correct_option']??'';$diff=(int)($_POST['difficulty']??1);
    if(!$sid||!$qt||!$oa||!$ob||!$oc||!$od||!in_array($cor,['a','b','c','d'])){
        flash('error','Barcha maydonlarni to\'ldiring.');
    } else {
        if(!empty($_POST['id'])){
            db()->prepare("UPDATE questions SET subject_id=?,question_text=?,option_a=?,option_b=?,option_c=?,option_d=?,correct_option=?,difficulty=? WHERE id=? AND teacher_id=?")
                ->execute([$sid,$qt,$oa,$ob,$oc,$od,$cor,$diff,(int)$_POST['id'],$me['id']]);
            flash('success','Savol yangilandi.');
        } else {
            db()->prepare("INSERT INTO questions(teacher_id,subject_id,question_text,option_a,option_b,option_c,option_d,correct_option,difficulty) VALUES(?,?,?,?,?,?,?,?,?)")
                ->execute([$me['id'],$sid,$qt,$oa,$ob,$oc,$od,$cor,$diff]);
            flash('success','Savol qo\'shildi.');
        }
        redirect('questions.php'.($filterSubject?"?subject=$filterSubject":''));
    }
}

$subjects=db()->prepare("SELECT * FROM subjects WHERE teacher_id=? ORDER BY name");
$subjects->execute([$me['id']]);$subjects=$subjects->fetchAll();

$where="WHERE q.teacher_id={$me['id']}";$params=[];
if($filterSubject){$where.=' AND q.subject_id=?';$params[]=$filterSubject;}
if($filterDiff){$where.=' AND q.difficulty=?';$params[]=$filterDiff;}
$total=db()->prepare("SELECT COUNT(*) FROM questions q $where");
$total->execute($params);$total=(int)$total->fetchColumn();
$pages=max(1,ceil($total/$perPage));$offset=($page-1)*$perPage;
$qs=db()->prepare("SELECT q.*,s.name sn FROM questions q JOIN subjects s ON s.id=q.subject_id $where ORDER BY q.id DESC LIMIT $perPage OFFSET $offset");
$qs->execute($params);$qs=$qs->fetchAll();

$editItem=null;
if($action==='edit'&&isset($_GET['id'])){
    $e=db()->prepare("SELECT * FROM questions WHERE id=? AND teacher_id=?");
    $e->execute([(int)$_GET['id'],$me['id']]);$editItem=$e->fetch();
}
$pageTitle='Savollar';
include '../includes/ui.php';
?>
<div class="page-wrap">
  <div class="topbar">
    <div><div class="page-title">❓ Savollar</div><div class="page-sub">Savol banki — jami <?= $total ?> ta</div></div>
    <div class="tb-right">
      <a href="index.php" class="btn btn-g">← Dashboard</a>
      <a href="?action=create<?= $filterSubject?"&subject=$filterSubject":'' ?>" class="btn btn-p">➕ Savol qo'shish</a>
    </div>
  </div>

  <?php if($action==='create'||$action==='edit'): ?>
  <div class="card mb-4">
    <div class="card-h"><span class="card-t"><?= $action==='edit'?'Savolni tahrirlash':'Yangi savol qo\'shish' ?></span></div>
    <div class="card-body">
      <?php if(empty($subjects)): ?>
      <div class="alert a-warn">⚠️ Avval fan yarating. <a href="subjects.php?action=create" style="color:var(--amber)">Fan qo'shish →</a></div>
      <?php else: ?>
      <form method="POST">
        <input type="hidden" name="_token" value="<?= token() ?>">
        <?php if($editItem): ?><input type="hidden" name="id" value="<?= $editItem['id'] ?>"><?php endif; ?>
        <div class="g2">
          <div class="fg">
            <label class="fl">Fan *</label>
            <select name="subject_id" class="fs" required>
              <option value="">Fan tanlang...</option>
              <?php foreach($subjects as $s): ?>
              <option value="<?= $s['id'] ?>" <?= ($editItem['subject_id']??$filterSubject)==$s['id']?'selected':'' ?>><?= h($s['icon'].' '.$s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg">
            <label class="fl">Qiyinlik *</label>
            <select name="difficulty" class="fs">
              <option value="1" <?= ($editItem['difficulty']??1)==1?'selected':'' ?>>🟢 Oson</option>
              <option value="2" <?= ($editItem['difficulty']??0)==2?'selected':'' ?>>🟡 O'rta</option>
              <option value="3" <?= ($editItem['difficulty']??0)==3?'selected':'' ?>>🔴 Qiyin</option>
            </select>
          </div>
        </div>
        <div class="fg">
          <label class="fl">Savol matni *</label>
          <textarea name="question_text" class="ft" rows="3" placeholder="Savolingizni yozing..." required><?= h($editItem['question_text']??'') ?></textarea>
        </div>
        <div class="g2" style="margin-bottom:14px">
          <?php foreach(['a'=>'A','b'=>'B','c'=>'C','d'=>'D'] as $k=>$lbl): ?>
          <div class="fg" style="margin-bottom:0">
            <label class="fl">
              <span style="display:inline-flex;width:20px;height:20px;background:var(--accent);color:#fff;font-size:10px;font-weight:700;border-radius:5px;align-items:center;justify-content:center;margin-right:4px;vertical-align:middle"><?= $lbl ?></span>
              Variant <?= $lbl ?> *
            </label>
            <input type="text" name="option_<?= $k ?>" class="fi" placeholder="Variant <?= $lbl ?>" value="<?= h($editItem['option_'.$k]??'') ?>" required>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="fg">
          <label class="fl">To'g'ri javob *</label>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <?php foreach(['a'=>'A','b'=>'B','c'=>'C','d'=>'D'] as $k=>$lbl): ?>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 14px;border:2px solid var(--border);border-radius:9px;transition:all .15s" class="ans-lbl" id="albl-<?= $k ?>">
              <input type="radio" name="correct_option" value="<?= $k ?>" <?= ($editItem['correct_option']??'')===$k?'checked':'' ?> onchange="selAns('<?= $k ?>')" style="display:none">
              <span style="width:26px;height:26px;border-radius:6px;background:var(--bg3);border:2px solid var(--border2);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--text3)" id="akey-<?= $k ?>"><?= $lbl ?></span>
              <span class="text-sm c-m">Variant <?= $lbl ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="flex gap-2">
          <button type="submit" class="btn btn-p"><?= $action==='edit'?'Saqlash':'Qo\'shish' ?></button>
          <a href="questions.php" class="btn btn-g">Bekor</a>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Filter -->
  <form method="GET" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;align-items:center">
    <select name="subject" class="fs" style="width:auto;min-width:150px" onchange="this.form.submit()">
      <option value="">Barcha fanlar</option>
      <?php foreach($subjects as $s): ?>
      <option value="<?= $s['id'] ?>" <?= $filterSubject==$s['id']?'selected':'' ?>><?= h($s['icon'].' '.$s['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="diff" class="fs" style="width:auto" onchange="this.form.submit()">
      <option value="">Barcha darajalar</option>
      <option value="1" <?= $filterDiff==1?'selected':'' ?>>🟢 Oson</option>
      <option value="2" <?= $filterDiff==2?'selected':'' ?>>🟡 O'rta</option>
      <option value="3" <?= $filterDiff==3?'selected':'' ?>>🔴 Qiyin</option>
    </select>
    <?php if($filterSubject||$filterDiff): ?><a href="questions.php" class="btn btn-g btn-xs">✕ Tozalash</a><?php endif; ?>
    <span class="text-xs c-dim" style="margin-left:auto"><?= $total ?> ta savol</span>
  </form>

  <?php if(empty($qs)): ?>
  <div class="card"><div class="empty"><div class="empty-ico">📭</div><div class="empty-txt">Savollar yo'q</div></div></div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>#</th><th>Savol</th><th>Fan</th><th>Daraja</th><th>Javob</th><th></th></tr></thead>
      <tbody>
      <?php foreach($qs as $i=>$q): ?>
      <tr>
        <td class="c-dim text-xs"><?= $offset+$i+1 ?></td>
        <td style="max-width:320px"><p style="font-size:13px;font-weight:500;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical"><?= h($q['question_text']) ?></p></td>
        <td class="text-xs c-m"><?= h($q['sn']) ?></td>
        <td><?= diffBadge($q['difficulty']) ?></td>
        <td><span style="width:24px;height:24px;background:var(--success);color:#fff;font-size:10px;font-weight:700;border-radius:6px;display:inline-flex;align-items:center;justify-content:center"><?= strtoupper($q['correct_option']) ?></span></td>
        <td><div class="flex gap-1">
          <a href="?action=edit&id=<?= $q['id'] ?>" class="btn btn-g btn-xs">✏️</a>
          <a href="?action=delete&id=<?= $q['id'] ?>&subject=<?= $filterSubject ?>&diff=<?= $filterDiff ?>&page=<?= $page ?>" class="btn btn-d btn-xs" onclick="return confirm('O\'chirasizmi?')">🗑</a>
        </div></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if($pages>1): ?>
  <div style="display:flex;gap:5px;justify-content:center;flex-wrap:wrap;margin-top:12px">
    <?php for($i=1;$i<=$pages;$i++): ?>
    <a href="?page=<?= $i ?>&subject=<?= $filterSubject ?>&diff=<?= $filterDiff ?>" class="btn btn-xs <?= $i==$page?'btn-p':'btn-g' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
<script>
const ec='<?= $editItem['correct_option']??'' ?>';
if(ec) selAns(ec);
function selAns(k){
  document.querySelectorAll('.ans-lbl').forEach(l=>{l.style.borderColor='var(--border)';l.style.background='var(--bg2)';});
  document.querySelectorAll('[id^=akey-]').forEach(e=>{e.style.background='var(--bg3)';e.style.borderColor='var(--border2)';e.style.color='var(--text3)';});
  const l=document.getElementById('albl-'+k),key=document.getElementById('akey-'+k),r=l?.querySelector('input');
  if(l){l.style.borderColor='var(--success)';l.style.background='rgba(81,207,102,.07)';}
  if(key){key.style.background='var(--success)';key.style.borderColor='var(--success)';key.style.color='#fff';}
  if(r) r.checked=true;
}
</script>
<?php include '../includes/foot.php'; ?>
