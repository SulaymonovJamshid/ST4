<?php
require_once '../config.php';
define('ROOT','../');
requireRole('teacher');
$me=me();
$filterTest=(int)($_GET['test']??0);

$tests=db()->prepare("SELECT t.id,t.title,s.name sn FROM tests t JOIN subjects s ON s.id=t.subject_id WHERE t.teacher_id=? ORDER BY t.id DESC");
$tests->execute([$me['id']]);$tests=$tests->fetchAll();

$where="WHERE t.teacher_id={$me['id']}";$params=[];
if($filterTest){$where.=' AND ts.test_id=?';$params[]=$filterTest;}
$results=db()->prepare("SELECT ts.*,t.title,t.question_count,t.points_per_q,s.name sn,s.icon si,u.name un,u.email ue FROM test_sessions ts JOIN tests t ON t.id=ts.test_id JOIN subjects s ON s.id=t.subject_id JOIN users u ON u.id=ts.student_id $where ORDER BY ts.id DESC");
$results->execute($params);$results=$results->fetchAll();
$pageTitle='Natijalar';
include '../includes/ui.php';
?>
<div class="page-wrap">
  <div class="topbar">
    <div><div class="page-title">📈 O'quvchilar natijalari</div><div class="page-sub">Jami <?= count($results) ?> ta sessiya</div></div>
    <a href="index.php" class="btn btn-g">← Dashboard</a>
  </div>
  <form method="GET" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <select name="test" class="fs" style="width:auto;min-width:200px" onchange="this.form.submit()">
      <option value="">Barcha testlar</option>
      <?php foreach($tests as $t): ?>
      <option value="<?= $t['id'] ?>" <?= $filterTest==$t['id']?'selected':'' ?>><?= h($t['sn'].' — '.$t['title']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if($filterTest): ?><a href="results.php" class="btn btn-g btn-xs">✕</a><?php endif; ?>
  </form>
  <?php if($filterTest&&!empty($results)):
    $done=array_filter($results,fn($r)=>$r['status']==='completed');
    $scores=array_column(array_filter($done,fn($r)=>$r['score']!==null),'score');
    $max=($results[0]['question_count'])*($results[0]['points_per_q']);
  ?>
  <div class="g4 mb-4">
    <?php foreach([['👤','Urinish',count($results),'c1'],['✅','Yakunlangan',count($done),'c2'],['📈',"O'rtacha",count($scores)?round(array_sum($scores)/count($scores),1).'/'.$max:'—','c3'],['🏆','Maksimal',count($scores)?max($scores).'/'.$max:'—','c4']] as [$i,$l,$v,$c]): ?>
    <div class="stat <?= $c ?>"><div class="stat-lbl"><?= $l ?></div><div class="stat-val mono" style="font-size:18px"><?= $v ?></div><div class="stat-ico"><?= $i ?></div></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if(empty($results)): ?>
  <div class="card"><div class="empty"><div class="empty-ico">📭</div><div class="empty-txt">Natijalar yo'q</div></div></div>
  <?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>O'quvchi</th><th>Test</th><th>Ball</th><th>Baho</th><th>Status</th><th>Vaqt</th><th></th></tr></thead>
      <tbody>
      <?php foreach($results as $r):
        $mx=$r['question_count']*$r['points_per_q'];
        $pct=$mx>0?round($r['score']/$mx*100,1):0;
        $g=grade($pct);$gc=gradeColor($g);
        $sc=['active'=>['var(--amber)','▶ Davom'],'completed'=>['var(--success)','✓ Yakunlangan'],'expired'=>['var(--danger)','⏰ Tugadi']];
        [$cl,$sl]=$sc[$r['status']]??['var(--text3)','?'];
      ?>
      <tr>
        <td><div style="font-weight:600;font-size:13px"><?= h($r['un']) ?></div><div class="c-dim text-xs"><?= h($r['ue']) ?></div></td>
        <td><span style="font-size:15px"><?= h($r['si']) ?></span> <span class="text-xs c-m"><?= h($r['title']) ?></span></td>
        <td><?php if($r['status']!=='active'): ?><span class="mono text-sm"><?= round($r['score'],1) ?>/<?= $mx ?></span><?php else: ?>—<?php endif; ?></td>
        <td><?php if($r['status']!=='active'): ?><span style="font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:800;color:<?= $gc ?>"><?= $g ?></span><?php else: ?>—<?php endif; ?></td>
        <td><span style="color:<?= $cl ?>;font-size:11px;font-weight:600"><?= $sl ?></span></td>
        <td class="c-dim text-xs"><?= ago($r['started_at']) ?></td>
        <td><?php if($r['status']!=='active'): ?><a href="<?= ROOT ?>result.php?session=<?= $r['id'] ?>" target="_blank" class="btn btn-g btn-xs">👁</a><?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php include '../includes/foot.php'; ?>
