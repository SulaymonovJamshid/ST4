<?php
require_once 'config.php';
define('ROOT','');
requireLogin();
if(!isStudent()) redirect('index.php');
$me=me();
$sessions=db()->prepare("SELECT ts.*,t.title,t.question_count,t.points_per_q,s.name sn,s.icon si,u.name tn FROM test_sessions ts JOIN tests t ON t.id=ts.test_id JOIN subjects s ON s.id=t.subject_id JOIN users u ON u.id=t.teacher_id WHERE ts.student_id=? ORDER BY ts.id DESC");
$sessions->execute([$me['id']]);$sessions=$sessions->fetchAll();
$pageTitle='Natijalarim';
include 'includes/ui.php';
?>
<div class="page-wrap">
  <div class="topbar">
    <div><div class="page-title">📊 Natijalarim</div><div class="page-sub">Barcha test natijalari tarixi</div></div>
    <a href="index.php" class="btn btn-g">← Bosh sahifa</a>
  </div>
  <?php if(empty($sessions)): ?>
  <div class="card"><div class="empty"><div class="empty-ico">📭</div><div class="empty-txt">Hali birorta test topshirilmagan. Test boshlang!</div><a href="index.php" class="btn btn-p mt-3" style="text-decoration:none">Testlarga o'tish</a></div></div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Test</th><th>Fan</th><th>Ball</th><th>Baho</th><th>Status</th><th>Vaqt</th><th></th></tr></thead>
      <tbody>
      <?php foreach($sessions as $r):
        $mx=$r['question_count']*$r['points_per_q'];
        $pct=$mx>0?round($r['score']/$mx*100,1):0;
        $g=grade($pct);$gc=gradeColor($g);
        $sc=['active'=>['var(--amber)','▶ Davom'],'completed'=>['var(--success)','✓ Yakunlangan'],'expired'=>['var(--danger)','⏰ Vaqt tugadi']];
        [$cl,$sl]=$sc[$r['status']]??['var(--text3)','?'];
      ?>
      <tr>
        <td style="font-weight:500;font-size:13px;color:var(--text)"><?= h($r['title']) ?></td>
        <td><?= h($r['si'].' '.$r['sn']) ?></td>
        <td><?php if($r['status']!=='active'): ?><span class="mono text-sm" style="color:var(--accent2)"><?= round($r['score'],1) ?>/<?= $mx ?></span><?php else: ?>—<?php endif; ?></td>
        <td><?php if($r['status']!=='active'): ?><span style="font-family:'JetBrains Mono',monospace;font-weight:700;color:<?= $gc ?>"><?= $g ?></span><?php else: ?>—<?php endif; ?></td>
        <td><span style="color:<?= $cl ?>;font-size:11px;font-weight:600"><?= $sl ?></span></td>
        <td class="c-dim text-xs"><?= ago($r['started_at']) ?></td>
        <td>
          <?php if($r['status']==='active'): ?>
          <a href="test.php?session=<?= $r['id'] ?>" class="btn btn-t btn-xs">Davom</a>
          <?php elseif($r['status']!=='active'): ?>
          <a href="result.php?session=<?= $r['id'] ?>" class="btn btn-g btn-xs">Ko'rish</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php include 'includes/foot.php'; ?>
