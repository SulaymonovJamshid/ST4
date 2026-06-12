<?php
require_once '../config.php';
define('ROOT','../');
requireRole('admin');
if(isset($_GET['toggle'])){db()->prepare("UPDATE tests SET is_active=1-is_active WHERE id=?")->execute([(int)$_GET['toggle']]);redirect('tests.php');}
if(isset($_GET['delete'])){db()->prepare("DELETE FROM tests WHERE id=?")->execute([(int)$_GET['delete']]);flash('success','Test o\'chirildi.');redirect('tests.php');}
$ft=(int)($_GET['teacher']??0);
$where='WHERE 1=1';$params=[];
if($ft){$where.=' AND t.teacher_id=?';$params[]=$ft;}
$tests=db()->prepare("SELECT t.*,s.name sn,s.icon si,u.name tn,(SELECT COUNT(*) FROM test_sessions ts WHERE ts.test_id=t.id) att,(SELECT COUNT(*) FROM test_sessions ts WHERE ts.test_id=t.id AND ts.status='completed') done FROM tests t JOIN subjects s ON s.id=t.subject_id JOIN users u ON u.id=t.teacher_id $where ORDER BY t.id DESC");
$tests->execute($params);$tests=$tests->fetchAll();
$teachers=db()->query("SELECT id,name FROM users WHERE role='teacher' ORDER BY name")->fetchAll();
$pageTitle='Testlar (Admin)';
include '../includes/ui.php';
?>
<div class="page-wrap">
  <div class="topbar"><div><div class="page-title">📝 Barcha Testlar</div></div><a href="index.php" class="btn btn-g">← Dashboard</a></div>
  <form method="GET" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap">
    <select name="teacher" class="fs" style="width:auto;min-width:160px" onchange="this.form.submit()">
      <option value="">Barcha o'qituvchilar</option>
      <?php foreach($teachers as $t): ?><option value="<?= $t['id'] ?>" <?= $ft==$t['id']?'selected':'' ?>><?= h($t['name']) ?></option><?php endforeach; ?>
    </select>
    <?php if($ft): ?><a href="tests.php" class="btn btn-g btn-xs">✕</a><?php endif; ?>
  </form>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Test</th><th>O'qituvchi</th><th>Qiyinlik</th><th>Savollar</th><th>Urinish</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach($tests as $t): ?>
      <tr>
        <td><div class="flex items-c gap-2"><span style="font-size:17px"><?= h($t['si']) ?></span><div><div style="font-weight:600;font-size:13px"><?= h($t['title']) ?></div><div class="c-dim text-xs"><?= h($t['sn']) ?></div></div></div></td>
        <td class="text-xs c-m"><?= h($t['tn']) ?></td>
        <td><?= $t['difficulty']==0?'<span class="badge badge-blue">Aralash</span>':diffBadge($t['difficulty']) ?></td>
        <td><strong><?= $t['question_count'] ?></strong>ta · <?= $t['points_per_q'] ?>ball · <?= $t['question_count']*$t['time_per_q'] ?>daq</td>
        <td><span class="text-xs"><strong><?= $t['att'] ?></strong> / <?= $t['done'] ?> yakunlangan</span></td>
        <td><a href="?toggle=<?= $t['id'] ?>" style="text-decoration:none"><span style="font-size:11px;font-weight:600;color:<?= $t['is_active']?'var(--success)':'var(--text3)' ?>"><?= $t['is_active']?'✓ Aktiv':'○ Nofaol' ?></span></a></td>
        <td><a href="sessions.php?test=<?= $t['id'] ?>" class="btn btn-g btn-xs">📊</a> <a href="?delete=<?= $t['id'] ?>" class="btn btn-d btn-xs" onclick="return confirm('O\'chirasizmi?')">🗑</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($tests)): ?><tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text3)">Topilmadi</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/foot.php'; ?>
