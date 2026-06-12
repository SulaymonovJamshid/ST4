<?php
require_once '../config.php';
define('ROOT','../');
requireRole('admin');
$ft=(int)($_GET['test']??0);$fs=$_GET['status']??'';
$where='WHERE 1=1';$params=[];
if($ft){$where.=' AND ts.test_id=?';$params[]=$ft;}
if($fs&&in_array($fs,['active','completed','expired'])){$where.=' AND ts.status=?';$params[]=$fs;}
$sessions=db()->prepare("SELECT ts.*,t.title,t.question_count,t.points_per_q,s.icon si,u.name un,u.email ue,tu.name tn FROM test_sessions ts JOIN tests t ON t.id=ts.test_id JOIN subjects s ON s.id=t.subject_id JOIN users u ON u.id=ts.student_id JOIN users tu ON tu.id=t.teacher_id $where ORDER BY ts.id DESC LIMIT 200");
$sessions->execute($params);$sessions=$sessions->fetchAll();
$tests=db()->query("SELECT t.id,t.title,s.name sn FROM tests t JOIN subjects s ON s.id=t.subject_id ORDER BY t.id DESC")->fetchAll();
$pageTitle='Sessiyalar';
include '../includes/ui.php';
?>
<div class="page-wrap">
  <div class="topbar">
    <div><div class="page-title">🎯 Sessiyalar</div><div class="page-sub"><?= count($sessions) ?> ta sessiya</div></div>
    <a href="index.php" class="btn btn-g">← Dashboard</a>
  </div>
  <form method="GET" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap">
    <select name="test" class="fs" style="width:auto;min-width:180px" onchange="this.form.submit()">
      <option value="">Barcha testlar</option>
      <?php foreach($tests as $t): ?><option value="<?= $t['id'] ?>" <?= $ft==$t['id']?'selected':'' ?>><?= h($t['sn'].' — '.$t['title']) ?></option><?php endforeach; ?>
    </select>
    <select name="status" class="fs" style="width:auto" onchange="this.form.submit()">
      <option value="">Barcha statuslar</option>
      <option value="active" <?= $fs==='active'?'selected':'' ?>>▶ Aktiv</option>
      <option value="completed" <?= $fs==='completed'?'selected':'' ?>>✓ Yakunlangan</option>
      <option value="expired" <?= $fs==='expired'?'selected':'' ?>>⏰ Tugadi</option>
    </select>
    <?php if($ft||$fs): ?><a href="sessions.php" class="btn btn-g btn-xs">✕</a><?php endif; ?>
  </form>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>O'quvchi</th><th>Test</th><th>O'qituvchi</th><th>Ball</th><th>Baho</th><th>Status</th><th>Vaqt</th><th></th></tr></thead>
      <tbody>
      <?php foreach($sessions as $r):
        $mx=$r['question_count']*$r['points_per_q'];
        $pct=$mx>0?round($r['score']/$mx*100,1):0;
        $g=grade($pct);$gc=gradeColor($g);
        $sc=['active'=>['var(--amber)','▶'],'completed'=>['var(--success)','✓'],'expired'=>['var(--danger)','⏰']];
        [$cl,$sl]=$sc[$r['status']]??['var(--text3)','?'];
      ?>
      <tr>
        <td><div style="font-weight:600;font-size:13px"><?= h($r['un']) ?></div><div class="c-dim text-xs"><?= h($r['ue']) ?></div></td>
        <td><span style="font-size:15px"><?= h($r['si']) ?></span> <span class="text-xs c-m"><?= h($r['title']) ?></span></td>
        <td class="text-xs c-m"><?= h($r['tn']) ?></td>
        <td><?php if($r['status']!=='active'): ?><span class="mono text-xs"><?= round($r['score'],1) ?>/<?= $mx ?></span><?php else: ?>—<?php endif; ?></td>
        <td><?php if($r['status']!=='active'): ?><span style="font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:800;color:<?= $gc ?>"><?= $g ?></span><?php else: ?>—<?php endif; ?></td>
        <td><span style="color:<?= $cl ?>;font-size:11px;font-weight:600"><?= $sl ?> <?= $r['status'] ?></span></td>
        <td class="c-dim text-xs"><?= ago($r['started_at']) ?></td>
        <td><?php if($r['status']!=='active'): ?><a href="<?= ROOT ?>result.php?session=<?= $r['id'] ?>" target="_blank" class="btn btn-g btn-xs">👁</a><?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($sessions)): ?><tr><td colspan="8" style="text-align:center;padding:32px;color:var(--text3)">Topilmadi</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/foot.php'; ?>
