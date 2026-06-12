<?php
require_once 'config.php';
define('ROOT','');
requireLogin();
if(!isStudent()) redirect('index.php');
$me=me();
$testId=(int)($_GET['test']??0);
$s=db()->prepare("SELECT t.*,s.name sname,s.icon sico,u.name tname FROM tests t JOIN subjects s ON s.id=t.subject_id JOIN users u ON u.id=t.teacher_id WHERE t.id=? AND t.is_active=1");
$s->execute([$testId]);$test=$s->fetch();
if(!$test){flash('error','Test topilmadi.');redirect('index.php');}
$as=db()->prepare("SELECT * FROM test_sessions WHERE student_id=? AND test_id=? AND status='active'");
$as->execute([$me['id'],$testId]);$activeSession=$as->fetch();
$cs=db()->prepare("SELECT * FROM test_sessions WHERE student_id=? AND test_id=? AND status='completed' ORDER BY id DESC LIMIT 1");
$cs->execute([$me['id'],$testId]);$completedSession=$cs->fetch();
if($_SERVER['REQUEST_METHOD']==='POST'){
    if($activeSession&&isset($_POST['restart'])){db()->prepare("DELETE FROM test_sessions WHERE id=? AND student_id=?")->execute([$activeSession['id'],$me['id']]);$activeSession=null;}
    if($activeSession){redirect('test.php?session='.$activeSession['id']);}
    $diff=(int)$test['difficulty'];$cnt=$test['question_count'];
    if($diff===0){
        $per=ceil($cnt/3);$qIds=[];
        foreach([1,2,3] as $d){
            $q=db()->prepare("SELECT id FROM questions WHERE subject_id=? AND difficulty=? ORDER BY RAND() LIMIT ?");
            $q->execute([$test['subject_id'],$d,$per]);
            $qIds=array_merge($qIds,array_column($q->fetchAll(),'id'));
        }
        shuffle($qIds);$qIds=array_slice($qIds,0,$cnt);
        if(count($qIds)>0){
            $pl=implode(',',array_fill(0,count($qIds),'?'));
            $ord=db()->prepare("SELECT id FROM questions WHERE id IN($pl) ORDER BY difficulty,RAND()");
            $ord->execute($qIds);$qIds=array_column($ord->fetchAll(),'id');
        }
    }else{
        $q=db()->prepare("SELECT id FROM questions WHERE subject_id=? AND difficulty=? ORDER BY RAND() LIMIT ?");
        $q->execute([$test['subject_id'],$diff,$cnt]);$qIds=array_column($q->fetchAll(),'id');
    }
    if(count($qIds)<$cnt){flash('error','Yetarli savollar mavjud emas ('.count($qIds).'/'.$cnt.').');redirect('index.php');}
    $exp=date('Y-m-d H:i:s',time()+$cnt*$test['time_per_q']*60);
    $ins=db()->prepare("INSERT INTO test_sessions(student_id,test_id,question_ids,current_index,answers,status,expires_at)VALUES(?,?,?,0,'{}','active',?)");
    $ins->execute([$me['id'],$testId,json_encode($qIds),$exp]);
    redirect('test.php?session='.db()->lastInsertId());
}
$totalMin=$test['question_count']*$test['time_per_q'];
$maxScore=$test['question_count']*$test['points_per_q'];
?>
<!DOCTYPE html>
<html lang="uz" data-theme="<?= theme() ?>">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SmartTest — Test boshlash</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0e1a;--bg2:#0f1526;--bg3:#141c30;--card:#161d30;--border:rgba(99,140,255,.14);--border2:rgba(99,140,255,.3);--accent:#4f7cff;--accent2:#7b9fff;--teal:#00d4aa;--teal2:#00b894;--amber:#ffb347;--text:#e8edff;--text2:#8fa3cc;--text3:#4d6494;--danger:#ff6b6b;--success:#51cf66;--glow:0 0 24px rgba(79,124,255,.18)}
[data-theme="light"]{--bg:#f0f4ff;--bg2:#fff;--bg3:#f5f7ff;--card:#fff;--border:rgba(79,124,255,.14);--text:#0d1b3e;--text2:#3d5a99;--text3:#7a9acc}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Space Grotesk',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(79,124,255,.022) 1px,transparent 1px),linear-gradient(90deg,rgba(79,124,255,.022) 1px,transparent 1px);background-size:40px 40px;pointer-events:none}
.wrap{width:100%;max-width:520px;position:relative;z-index:1}
.back{display:inline-flex;align-items:center;gap:6px;color:var(--text2);text-decoration:none;font-size:13px;margin-bottom:16px;padding:6px 12px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;transition:all .15s}
.back:hover{color:var(--text);border-color:var(--border2)}
.card{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden}
.hero{background:linear-gradient(135deg,#0f1526,#1c2540);padding:28px;text-align:center;border-bottom:1px solid var(--border)}
.hero-ico{font-size:52px;margin-bottom:10px}
.hero-title{font-size:20px;font-weight:700;margin-bottom:4px}
.hero-sub{font-size:12px;color:var(--text3)}
.meta-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;padding:20px;border-bottom:1px solid var(--border)}
.meta-item{background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:12px;display:flex;align-items:center;gap:10px}
.meta-ico{font-size:20px}
.meta-val{font-size:14px;font-weight:700;color:var(--text)}
.meta-lbl{font-size:10px;color:var(--text3)}
.rules{padding:16px 20px;border-bottom:1px solid var(--border)}
.rule{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--text2);padding:4px 0}
.rule-dot{width:6px;height:6px;border-radius:50%;background:var(--accent);flex-shrink:0}
.actions{padding:16px 20px;display:flex;flex-direction:column;gap:10px}
.btn{display:flex;align-items:center;justify-content:center;gap:7px;padding:11px;border-radius:9px;font-family:'Space Grotesk',sans-serif;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:all .18s;text-decoration:none}
.btn-p{background:var(--accent);color:#fff}
.btn-p:hover{background:var(--accent2);transform:translateY(-1px)}
.btn-t{background:var(--teal);color:#051a15}
.btn-t:hover{background:var(--teal2)}
.btn-g{background:var(--bg3);color:var(--text2);border:1px solid var(--border)}
.btn-g:hover{background:var(--card);color:var(--text)}
.btn-d{background:rgba(255,107,107,.1);color:var(--danger);border:1px solid rgba(255,107,107,.2)}
.btn-d:hover{background:rgba(255,107,107,.18)}
.warn{background:rgba(255,179,71,.07);border:1px solid rgba(255,179,71,.2);border-radius:9px;padding:10px 13px;font-size:12px;color:var(--amber);margin-bottom:6px}
.done{background:rgba(0,212,170,.07);border:1px solid rgba(0,212,170,.2);border-radius:9px;padding:10px 13px;font-size:12px;color:var(--teal);margin-bottom:6px}
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:600}
.be{background:rgba(81,207,102,.1);color:#51cf66;border:1px solid rgba(81,207,102,.2)}
.bm{background:rgba(255,179,71,.1);color:#ffb347;border:1px solid rgba(255,179,71,.2)}
.bh{background:rgba(255,107,107,.1);color:#ff6b6b;border:1px solid rgba(255,107,107,.2)}
.bb{background:rgba(79,124,255,.1);color:var(--accent2);border:1px solid rgba(79,124,255,.2)}
</style>
</head>
<body>
<div class="wrap">
  <a href="index.php" class="back">← Testlarga qaytish</a>
  <div class="card">
    <div class="hero">
      <div class="hero-ico"><?= h($test['sico']) ?></div>
      <div class="hero-title"><?= h($test['title']) ?></div>
      <div class="hero-sub"><?= h($test['sname']) ?> · <?= h($test['tname']) ?></div>
      <div style="display:flex;justify-content:center;gap:6px;margin-top:10px">
        <?php if($test['difficulty']==0): ?><span class="badge bb">🔀 Aralash</span>
        <?php elseif($test['difficulty']==1): ?><span class="badge be">🟢 Oson</span>
        <?php elseif($test['difficulty']==2): ?><span class="badge bm">🟡 O'rta</span>
        <?php else: ?><span class="badge bh">🔴 Qiyin</span><?php endif; ?>
      </div>
    </div>
    <div class="meta-grid">
      <?php foreach([['❓','Savollar',$test['question_count'].' ta'],['⏱','Jami vaqt',$totalMin.' daqiqa'],['⭐','Maksimal ball',$maxScore.' ball'],['📊','Qiyinlik',$test['difficulty']==0?'Aralash':diffLabel($test['difficulty'])]] as [$i,$l,$v]): ?>
      <div class="meta-item"><div class="meta-ico"><?= $i ?></div><div><div class="meta-val"><?= $v ?></div><div class="meta-lbl"><?= $l ?></div></div></div>
      <?php endforeach; ?>
    </div>
    <div class="rules">
      <?php foreach(['Har savol uchun '.$test['time_per_q'].' daqiqa vaqt beriladi','Javobni tasdiqlashdan oldin istalgan savolga o\'tishingiz mumkin','To\'g\'ri javoblar faqat test yakunida ko\'rsatiladi','Test yakunida batafsil tahlil ko\'rsatiladi','Brauzer yopilsa, keyingi kirishda davom ettirishingiz mumkin'] as $r): ?>
      <div class="rule"><div class="rule-dot"></div><span><?= $r ?></span></div>
      <?php endforeach; ?>
    </div>
    <div class="actions">
      <?php if($activeSession): ?>
      <div class="warn">⚠️ Bu testda tugallanmagan sessiya mavjud.</div>
      <a href="test.php?session=<?= $activeSession['id'] ?>" class="btn btn-t">▶ Davom ettirish</a>
      <form method="POST" onsubmit="return confirm('Eski sessiya o\'chiriladi. Davom etasizmi?')">
        <button type="submit" name="restart" class="btn btn-d" style="width:100%">🔄 Yangidan boshlash</button>
      </form>
      <?php else: ?>
      <?php if($completedSession): ?><div class="done">✓ Siz bu testni allaqachon topshirgansiz. Yana urinib ko'rishingiz mumkin.</div><?php endif; ?>
      <form method="POST"><button type="submit" class="btn btn-p">🚀 Testni boshlash</button></form>
      <?php endif; ?>
      <?php if($completedSession): ?>
      <a href="result.php?session=<?= $completedSession['id'] ?>" class="btn btn-g">📊 Oxirgi natijani ko'rish</a>
      <?php endif; ?>
    </div>
  </div>
</div>
</body></html>
