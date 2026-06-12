<?php
require_once '../config.php';
define('ROOT','../');
requireRole('teacher');
$me=me();
$action=$_GET['action']??'list';
$allSubjs=getAllSubjectsList();

// O'chirish
if($action==='delete'&&isset($_GET['id'])){
    db()->prepare("DELETE FROM subjects WHERE id=? AND teacher_id=?")->execute([(int)$_GET['id'],$me['id']]);
    flash('success','Fan o\'chirildi.');redirect('subjects.php');
}

// Saqlash
if($_SERVER['REQUEST_METHOD']==='POST'&&checkToken()){
    $subjectKey=trim($_POST['subject_key']??'');
    $desc=trim($_POST['description']??'');
    // Config dan fan ma'lumotlarini olish
    $foundSubj=null;$foundLevel=null;
    foreach($allSubjs as $level=>$cat){
        foreach($cat['items'] as $item){
            if($item['id']===$subjectKey){$foundSubj=$item;$foundLevel=$level;break 2;}
        }
    }
    if(!$foundSubj){flash('error','Fan topilmadi.');redirect('subjects.php');}
    else {
        if(!empty($_POST['id'])){
            db()->prepare("UPDATE subjects SET subject_key=?,name=?,icon=?,level=?,description=? WHERE id=? AND teacher_id=?")
                ->execute([$subjectKey,$foundSubj['name'],$foundSubj['icon'],$foundLevel,$desc,(int)$_POST['id'],$me['id']]);
            flash('success','Fan yangilandi.');
        } else {
            // Avval mavjudmi tekshirish
            $chk=db()->prepare("SELECT id FROM subjects WHERE teacher_id=? AND subject_key=?");
            $chk->execute([$me['id'],$subjectKey]);
            if($chk->fetch()){flash('error','Bu fan allaqachon qo\'shilgan.');}
            else {
                db()->prepare("INSERT INTO subjects(teacher_id,subject_key,name,icon,level,description) VALUES(?,?,?,?,?,?)")
                    ->execute([$me['id'],$subjectKey,$foundSubj['name'],$foundSubj['icon'],$foundLevel,$desc]);
                flash('success','Fan qo\'shildi.');
            }
        }
        redirect('subjects.php');
    }
}

$subjects=db()->prepare("SELECT s.*,COUNT(q.id) qc FROM subjects s LEFT JOIN questions q ON q.subject_id=s.id WHERE s.teacher_id=? GROUP BY s.id ORDER BY s.level,s.name");
$subjects->execute([$me['id']]);$subjects=$subjects->fetchAll();

// Qo'shilgan fan keylarini olish
$addedKeys=array_column($subjects,'subject_key');

$editItem=null;
if($action==='edit'&&isset($_GET['id'])){
    $e=db()->prepare("SELECT * FROM subjects WHERE id=? AND teacher_id=?");
    $e->execute([(int)$_GET['id'],$me['id']]);$editItem=$e->fetch();
}

$levelLabels=['school'=>'🏫 Maktab','university'=>'🎓 Universitet','masters'=>'🔬 Magistratura'];
$pageTitle='Fanlar';
include '../includes/ui.php';
?>
<div class="page-wrap">
  <div class="topbar">
    <div>
      <div class="page-title">📚 Fanlar</div>
      <div class="page-sub">Fanlarni boshqarish va savollar bazasini yaratish</div>
    </div>
    <div class="tb-right">
      <a href="index.php" class="btn btn-g">← Dashboard</a>
      <a href="?action=create" class="btn btn-p">➕ Fan qo'shish</a>
    </div>
  </div>

  <?php if($action==='create'||$action==='edit'): ?>
  <div class="card mb-4" style="max-width:600px">
    <div class="card-h"><span class="card-t"><?= $action==='edit'?'Fanni tahrirlash':'Yangi fan qo\'shish' ?></span></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="_token" value="<?= token() ?>">
        <?php if($editItem): ?><input type="hidden" name="id" value="<?= $editItem['id'] ?>"><?php endif; ?>

        <div class="fg">
          <label class="fl">Fan tanlash *</label>
          <p class="fh" style="margin-bottom:10px">Qaysi fandan savollar va testlar yaratmoqchisiz?</p>

          <?php foreach($allSubjs as $level=>$cat): ?>
          <div style="margin-bottom:14px">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text3);margin-bottom:8px"><?= h($cat['label']) ?></div>
            <div style="display:flex;flex-wrap:wrap;gap:7px">
              <?php foreach($cat['items'] as $item):
                $isAdded=in_array($item['id'],$addedKeys)&&(!$editItem||$editItem['subject_key']!==$item['id']);
                $isSelected=$editItem&&$editItem['subject_key']===$item['id'];
              ?>
              <label style="cursor:<?= $isAdded?'not-allowed':'pointer' ?>">
                <input type="radio" name="subject_key" value="<?= $item['id'] ?>"
                  <?= $isSelected?'checked':'' ?>
                  <?= $isAdded?'disabled':'' ?>
                  style="display:none" class="subj-r"
                  onclick="this.parentElement.parentElement.querySelectorAll('.subj-chip').forEach(c=>c.classList.remove('sel'));this.nextElementSibling.classList.add('sel')">
                <div class="subj-chip <?= $isSelected?'sel':'' ?>" style="
                  display:inline-flex;align-items:center;gap:5px;padding:5px 11px;
                  border:1.5px solid <?= $isAdded?'rgba(99,140,255,.06)':($isSelected?'var(--accent)':'var(--border)') ?>;
                  border-radius:20px;font-size:12px;font-weight:500;transition:all .15s;
                  background:<?= $isAdded?'var(--bg3)':($isSelected?'rgba(79,124,255,.1)':'var(--bg3)') ?>;
                  opacity:<?= $isAdded?.45:1 ?>;
                  color:<?= $isAdded?'var(--text3)':'var(--text)' ?>;
                ">
                  <span><?= $item['icon'] ?></span>
                  <span><?= h($item['name']) ?></span>
                  <?php if($isAdded): ?><span style="font-size:10px">✓</span><?php endif; ?>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="fg">
          <label class="fl">Tavsif (ixtiyoriy)</label>
          <textarea name="description" class="ft" placeholder="Fan haqida qisqacha..."><?= h($editItem['description']??'') ?></textarea>
        </div>

        <div class="flex gap-2">
          <button type="submit" class="btn btn-p"><?= $action==='edit'?'Saqlash':'Fan qo\'shish' ?></button>
          <a href="subjects.php" class="btn btn-g">Bekor</a>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php if(empty($subjects)): ?>
  <div class="card"><div class="empty"><div class="empty-ico">📭</div><div class="empty-txt">Fanlar yo'q. Birinchi fanni qo'shing!</div></div></div>
  <?php else: ?>
  <div class="ga">
    <?php foreach($subjects as $s): ?>
    <div class="card card-hover card-glow">
      <div style="padding:18px 18px 14px">
        <div class="flex items-c justify-b mb-3">
          <span style="font-size:30px"><?= h($s['icon']) ?></span>
          <span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:var(--bg3);border:1px solid var(--border);color:var(--text3)"><?= $levelLabels[$s['level']]??$s['level'] ?></span>
        </div>
        <div style="font-size:15px;font-weight:700;margin-bottom:3px"><?= h($s['name']) ?></div>
        <div class="c-dim text-xs mb-3"><?= h($s['description']?:'Tavsif yo\'q') ?></div>
        <div class="flex gap-2">
          <a href="questions.php?subject=<?= $s['id'] ?>" class="btn btn-g btn-xs">❓ <?= $s['qc'] ?> savol</a>
        </div>
      </div>
      <div style="padding:10px 18px;border-top:1px solid var(--border);background:var(--bg3);display:grid;grid-template-columns:1fr 1fr;gap:7px">
        <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-g btn-xs">✏️ Tahrir</a>
        <a href="?action=delete&id=<?= $s['id'] ?>" class="btn btn-d btn-xs" onclick="return confirm('<?= h($s['name']) ?> o\'chirilsinmi?')">🗑 O'chir</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<style>
.subj-chip:hover{border-color:var(--border2)!important;background:var(--card2)!important}
.subj-chip.sel{border-color:var(--accent)!important;background:rgba(79,124,255,.1)!important;color:var(--accent2)!important}
</style>
<script>
document.querySelectorAll('.subj-r:not([disabled])').forEach(r=>{
  r.addEventListener('change',()=>{
    document.querySelectorAll('.subj-chip').forEach(c=>c.classList.remove('sel'));
    r.nextElementSibling.classList.add('sel');
  });
});
</script>
<?php include '../includes/foot.php'; ?>
