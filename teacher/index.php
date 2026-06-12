<?php
require_once '../config.php';
define('ROOT','../');
requireRole('teacher');
$me=me();

$stats=[
    'subjects'=>db()->prepare("SELECT COUNT(*) FROM subjects WHERE teacher_id=?"),
    'questions'=>db()->prepare("SELECT COUNT(*) FROM questions WHERE teacher_id=?"),
    'tests'=>db()->prepare("SELECT COUNT(*) FROM tests WHERE teacher_id=?"),
    'students'=>db()->prepare("SELECT COUNT(DISTINCT student_id) FROM test_sessions ts JOIN tests t ON t.id=ts.test_id WHERE t.teacher_id=?"),
];
foreach($stats as $k=>$s){$s->execute([$me['id']]);$stats[$k]=$s->fetchColumn();}

$recent=db()->prepare("SELECT ts.*,t.title,s.name sn,s.icon si,u.name un,t.question_count,t.points_per_q FROM test_sessions ts JOIN tests t ON t.id=ts.test_id JOIN subjects s ON s.id=t.subject_id JOIN users u ON u.id=ts.student_id WHERE t.teacher_id=? ORDER BY ts.id DESC LIMIT 8");
$recent->execute([$me['id']]);$recent=$recent->fetchAll();

// O'qituvchi fanlarini config dan olish
$teacherSubjects=json_decode($me['teacher_subjects']??'[]',true);
$allSubjs=getAllSubjectsList();
$mySubjects=[];
foreach($allSubjs as $cat){
    foreach($cat['items'] as $item){
        if(in_array($item['id'],$teacherSubjects)) $mySubjects[]=$item;
    }
}
$pageTitle='Dashboard';
include '../includes/ui.php';
?>
<div class="page-wrap">
  <div class="topbar">
    <div>
      <div class="page-title">👋 Xush kelibsiz, <?= h(explode(' ',$me['name'])[0]) ?>!</div>
      <div class="page-sub">O'qituvchi paneli — fanlaringiz va testlaringizni boshqaring</div>
    </div>
    <div class="tb-right">
      <a href="tests.php?action=create" class="btn btn-p">➕ Yangi test</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="stat-grid mb-6">
    <?php foreach([
      ['c1','📚','Fanlar',$stats['subjects'],'Tanlangan fanlar'],
      ['c2','❓','Savollar',$stats['questions'],'Yozilgan savollar'],
      ['c3','📝','Testlar',$stats['tests'],'Yaratilgan testlar'],
      ['c4','🎓','O\'quvchilar',$stats['students'],'Aktiv o\'quvchilar'],
    ] as [$c,$i,$l,$v,$s]): ?>
    <div class="stat <?= $c ?>">
      <div class="stat-lbl"><?= $l ?></div>
      <div class="stat-val"><?= $v ?></div>
      <div class="stat-hint"><?= $s ?></div>
      <div class="stat-ico"><?= $i ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="g2 mb-4">
    <!-- Tezkor harakatlar -->
    <div class="card">
      <div class="card-h"><span class="card-t">⚡ Tezkor harakatlar</span></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <?php foreach([
            ['📚','Fan yaratish','subjects.php?action=create','var(--accent)'],
            ['❓','Savol qo\'shish','questions.php?action=create','var(--success)'],
            ['📝','Test yaratish','tests.php?action=create','var(--teal)'],
            ['📊','Natijalar','results.php','var(--purple)'],
          ] as [$i,$l,$u,$c]): ?>
          <a href="<?= $u ?>" style="text-decoration:none;display:flex;align-items:center;gap:10px;padding:12px;background:var(--bg3);border:1px solid var(--border);border-radius:10px;transition:all .18s" class="card-hover">
            <div style="width:36px;height:36px;border-radius:9px;background:<?= $c ?>22;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0"><?= $i ?></div>
            <div style="font-size:13px;font-weight:600;color:var(--text)"><?= $l ?></div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Tanlangan fanlar -->
    <div class="card">
      <div class="card-h">
        <span class="card-t">📚 Mening fanlarim</span>
        <a href="<?= ROOT ?>profile.php" class="btn btn-g btn-xs">Tahrirlash</a>
      </div>
      <div class="card-body">
        <?php if(empty($mySubjects)): ?>
        <div class="empty"><div class="empty-ico">📭</div><div class="empty-txt">Fanlar tanlanmagan</div></div>
        <?php else: ?>
        <div style="display:flex;flex-wrap:wrap;gap:7px">
          <?php foreach($mySubjects as $subj): ?>
          <div style="display:flex;align-items:center;gap:6px;padding:5px 11px;background:var(--bg3);border:1px solid var(--border);border-radius:20px;font-size:12px;font-weight:500">
            <span><?= $subj['icon'] ?></span>
            <span><?= h($subj['name']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- So'nggi natijalar -->
  <div class="card">
    <div class="card-h">
      <span class="card-t">📈 So'nggi natijalar</span>
      <a href="results.php" class="btn btn-g btn-xs">Barchasini ko'r →</a>
    </div>
    <?php if(empty($recent)): ?>
    <div class="empty"><div class="empty-ico">📭</div><div class="empty-txt">Hali natijalar yo'q</div></div>
    <?php else: ?>
    <div class="tbl-wrap" style="border:none;border-radius:0">
      <table class="tbl">
        <thead><tr><th>O'quvchi</th><th>Test</th><th>Ball</th><th>Status</th><th>Vaqt</th></tr></thead>
        <tbody>
        <?php foreach($recent as $r):
          $max=$r['question_count']*$r['points_per_q'];
          $pct=$max>0?round($r['score']/$max*100):0;
          $g=grade($pct);$gc=gradeColor($g);
        ?>
        <tr>
          <td style="font-weight:600;color:var(--text)"><?= h($r['un']) ?></td>
          <td><?= h($r['si'].' '.$r['sn']) ?> <span class="text-dim text-xs">— <?= h($r['title']) ?></span></td>
          <td>
            <?php if($r['status']!=='active'): ?>
            <span class="mono text-sm"><?= round($r['score'],1) ?>/<?= $max ?></span>
            <span style="font-family:'JetBrains Mono',monospace;font-weight:700;color:<?= $gc ?>;margin-left:6px"><?= $g ?></span>
            <?php else: ?><span class="c-dim">Davom etmoqda</span><?php endif; ?>
          </td>
          <td>
            <?php if($r['status']==='completed'): ?><span class="c-s text-xs fw-600">✓ Yakunlangan</span>
            <?php elseif($r['status']==='active'): ?><span class="c-a text-xs fw-600">▶ Aktiv</span>
            <?php else: ?><span class="c-d text-xs fw-600">⏰ Tugadi</span><?php endif; ?>
          </td>
          <td class="c-dim text-xs"><?= ago($r['started_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include '../includes/foot.php'; ?>
