<?php
require_once '../config.php';
define('ROOT','../');
requireRole('admin');
$stats=[
    'users'=>db()->query("SELECT COUNT(*) FROM users WHERE role!='admin'")->fetchColumn(),
    'teachers'=>db()->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn(),
    'students'=>db()->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
    'subjects'=>db()->query("SELECT COUNT(*) FROM subjects")->fetchColumn(),
    'tests'=>db()->query("SELECT COUNT(*) FROM tests")->fetchColumn(),
    'questions'=>db()->query("SELECT COUNT(*) FROM questions")->fetchColumn(),
    'sessions'=>db()->query("SELECT COUNT(*) FROM test_sessions")->fetchColumn(),
    'completed'=>db()->query("SELECT COUNT(*) FROM test_sessions WHERE status='completed'")->fetchColumn(),
];
$recentSessions=db()->query("SELECT ts.*,t.title,s.icon,t.question_count,t.points_per_q,u.name un FROM test_sessions ts JOIN tests t ON t.id=ts.test_id JOIN subjects s ON s.id=t.subject_id JOIN users u ON u.id=ts.student_id ORDER BY ts.id DESC LIMIT 10")->fetchAll();
$recentUsers=db()->query("SELECT * FROM users WHERE role!='admin' ORDER BY id DESC LIMIT 6")->fetchAll();
$pageTitle='Admin Dashboard';
include '../includes/ui.php';
?>
<div class="page-wrap">
  <div class="topbar">
    <div><div class="page-title">⚙️ Admin Panel</div><div class="page-sub">Platforma nazorati</div></div>
  </div>
  <div class="g4 mb-4">
    <?php foreach([['c1','👥','Foydalanuvchi',$stats['users'],'users.php'],['c2','📝','Test',$stats['tests'],'tests.php'],['c3','❓','Savol',$stats['questions'],''],['c4','🎯','Urinish',$stats['sessions'],'sessions.php']] as [$c,$i,$l,$v,$u]): ?>
    <<?= $u?'a href="'.$u.'"':'div' ?> class="stat <?= $c ?>" style="<?= $u?'text-decoration:none;':'' ?>">
      <div class="stat-lbl"><?= $l ?></div><div class="stat-val"><?= number_format($v) ?></div><div class="stat-ico"><?= $i ?></div>
    </<?= $u?'a':'div' ?>>
    <?php endforeach; ?>
  </div>
  <div class="g4 mb-6">
    <?php foreach([['👨‍🏫',"O'qituvchi",$stats['teachers'],'c1'],['🎓',"O'quvchi",$stats['students'],'c2'],['📚','Fan',$stats['subjects'],'c3'],['✅','Yakunlangan',$stats['completed'],'c4']] as [$i,$l,$v,$c]): ?>
    <div class="stat <?= $c ?>"><div class="stat-lbl"><?= $l ?></div><div class="stat-val" style="font-size:20px"><?= number_format($v) ?></div><div class="stat-ico"><?= $i ?></div></div>
    <?php endforeach; ?>
  </div>
  <div class="g2">
    <div class="card">
      <div class="card-h"><span class="card-t">👤 So'nggi foydalanuvchilar</span><a href="users.php" class="btn btn-g btn-xs">Barchasini ko'r →</a></div>
      <?php foreach($recentUsers as $u):
        $rc=['teacher'=>'var(--info,#38bdf8)','student'=>'var(--success)','admin'=>'var(--amber)'][$u['role']]??'var(--text3)';
        $rl=['teacher'=>"O'qituvchi",'student'=>"O'quvchi",'admin'=>'Admin'][$u['role']]??'';
      ?>
      <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div class="flex items-c gap-2">
          <?php if($u['avatar']): ?><img src="<?= h($u['avatar']) ?>" style="width:28px;height:28px;border-radius:7px;object-fit:cover">
          <?php else: ?><div style="width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,var(--accent),var(--teal));display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0"><?= strtoupper(mb_substr($u['name'],0,2)) ?></div><?php endif; ?>
          <div><div style="font-size:13px;font-weight:600"><?= h($u['name']) ?></div><div class="c-dim text-xs"><?= h($u['email']) ?></div></div>
        </div>
        <span style="font-size:10px;font-weight:700;color:<?= $rc ?>;background:<?= str_replace(')','-l)',str_replace('var(--','var(--',$rc)) ?>;padding:2px 7px;border-radius:5px;background:rgba(0,0,0,.1)"><?= $rl ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="card">
      <div class="card-h"><span class="card-t">🎯 So'nggi sessiyalar</span><a href="sessions.php" class="btn btn-g btn-xs">Barchasini ko'r →</a></div>
      <?php foreach($recentSessions as $r):
        $mx=$r['question_count']*$r['points_per_q'];
        $pct=$mx>0?round($r['score']/$mx*100):0;
        $g=grade($pct);$gc=gradeColor($g);
      ?>
      <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div><div style="font-size:12px;font-weight:600"><?= h($r['un']) ?></div><div class="c-dim text-xs"><?= h($r['icon'].' '.$r['title']) ?></div></div>
        <?php if($r['status']==='completed'): ?>
        <span style="font-family:'JetBrains Mono',monospace;font-weight:700;color:<?= $gc ?>"><?= $g ?></span>
        <?php elseif($r['status']==='active'): ?><span style="color:var(--amber);font-size:11px">▶ Aktiv</span>
        <?php else: ?><span style="color:var(--danger);font-size:11px">⏰</span><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php include '../includes/foot.php'; ?>
