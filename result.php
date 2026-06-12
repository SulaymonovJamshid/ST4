<?php
require_once 'config.php';
define('ROOT','');
requireLogin();
$me=me();
$sessionId=(int)($_GET['session']??0);
$s=db()->prepare("SELECT ts.*,t.question_count,t.points_per_q,t.time_per_q,t.title,s.name sname,s.icon sico,u.name tname FROM test_sessions ts JOIN tests t ON t.id=ts.test_id JOIN subjects s ON s.id=t.subject_id JOIN users u ON u.id=t.teacher_id WHERE ts.id=? AND ts.student_id=?");
$s->execute([$sessionId,$me['id']]);$sess=$s->fetch();
if(!$sess){flash('error','Natija topilmadi.');redirect('index.php');}
if($sess['status']==='active'){redirect('test.php?session='.$sessionId);}
$qIds=json_decode($sess['question_ids'],true);
$answers=json_decode($sess['answers']??'{}',true);
$maxScore=$sess['question_count']*$sess['points_per_q'];
$pl=implode(',',array_fill(0,count($qIds),'?'));
$qs=db()->prepare("SELECT * FROM questions WHERE id IN($pl)");$qs->execute($qIds);
$qMap=[];foreach($qs->fetchAll() as $q) $qMap[$q['id']]=$q;
$correct=0;$wrong=0;$unanswered=0;
$byDiff=[1=>['c'=>0,'t'=>0],2=>['c'=>0,'t'=>0],3=>['c'=>0,'t'=>0]];
foreach($qIds as $qid){
    $q=$qMap[$qid]??null;if(!$q) continue;
    $d=(int)$q['difficulty'];$byDiff[$d]['t']++;
    $ch=$answers[$qid]??null;
    if(!$ch){$unanswered++;continue;}
    if($ch===$q['correct_option']){$correct++;$byDiff[$d]['c']++;}else $wrong++;
}
$score=(float)$sess['score'];
$pct=$maxScore>0?round($score/$maxScore*100,1):0;
$g=grade($pct);$gc=gradeColor($g);
$timeTaken=$sess['finished_at']?strtotime($sess['finished_at'])-strtotime($sess['started_at']):0;
$isExp=$sess['status']==='expired';
$circ=2*M_PI*52;$off=$circ*(1-$pct/100);
?>
<!DOCTYPE html>
<html lang="uz" data-theme="<?= theme() ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Natija — SmartTest</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0e1a;--bg2:#0f1526;--bg3:#141c30;--card:#161d30;--card2:#1c2540;--border:rgba(99,140,255,.13);--border2:rgba(99,140,255,.28);--accent:#4f7cff;--accent2:#7b9fff;--teal:#00d4aa;--teal2:#00b894;--amber:#ffb347;--text:#e8edff;--text2:#8fa3cc;--text3:#4d6494;--danger:#ff6b6b;--success:#51cf66;--purple:#da77f2;--glow:0 0 24px rgba(79,124,255,.18)}
[data-theme="light"]{--bg:#f0f4ff;--bg2:#fff;--bg3:#f5f7ff;--card:#fff;--card2:#eef1ff;--border:rgba(79,124,255,.13);--text:#0d1b3e;--text2:#3d5a99;--text3:#7a9acc}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Space Grotesk',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;padding:24px 16px}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(79,124,255,.022) 1px,transparent 1px),linear-gradient(90deg,rgba(79,124,255,.022) 1px,transparent 1px);background-size:40px 40px;pointer-events:none}
.wrap{max-width:660px;margin:0 auto;position:relative;z-index:1}
.back{display:inline-flex;align-items:center;gap:6px;color:var(--text2);text-decoration:none;font-size:12px;margin-bottom:16px;padding:6px 11px;background:var(--bg3);border:1px solid var(--border);border-radius:7px;transition:all .15s}
.back:hover{color:var(--text)}
.card{background:var(--card);border:1px solid var(--border);border-radius:13px;overflow:hidden;margin-bottom:14px}
/* HERO */
.hero{background:linear-gradient(135deg,#080c18,#141c30);padding:30px;text-align:center;position:relative;overflow:hidden;border-bottom:1px solid var(--border)}
.hero::before{content:'';position:absolute;top:-40%;left:50%;transform:translateX(-50%);width:280px;height:280px;background:radial-gradient(circle,rgba(79,124,255,.12),transparent 70%)}
.hero-sub{font-size:12px;color:var(--text3);margin-bottom:16px}
.donut{position:relative;width:120px;height:120px;margin:0 auto 18px}
.donut svg{width:120px;height:120px;transform:rotate(-90deg)}
.donut-txt{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
.grade{font-family:'JetBrains Mono',monospace;font-size:26px;font-weight:800;line-height:1}
.score-big{font-family:'JetBrains Mono',monospace;font-size:46px;font-weight:800;letter-spacing:-2px;line-height:1;color:#fff}
.score-max{font-size:20px;font-weight:400;color:rgba(255,255,255,.4)}
.pct-txt{font-size:14px;color:rgba(255,255,255,.5);margin-top:5px}
.time-txt{font-size:12px;color:rgba(255,255,255,.3);margin-top:4px}
/* STATS ROW */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);border-bottom:1px solid var(--border)}
.sr-item{padding:14px;text-align:center;border-right:1px solid var(--border)}
.sr-item:last-child{border-right:none}
.sr-ico{font-size:18px;margin-bottom:5px}
.sr-val{font-family:'JetBrains Mono',monospace;font-size:20px;font-weight:700;line-height:1}
.sr-lbl{font-size:10px;color:var(--text3);margin-top:3px}
/* DIFF BREAKDOWN */
.breakdown{padding:16px}
.bk-t{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text3);margin-bottom:12px}
.bk-row{display:flex;align-items:center;gap:11px;margin-bottom:11px}
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:600}
.be{background:rgba(81,207,102,.1);color:#51cf66;border:1px solid rgba(81,207,102,.2)}
.bm{background:rgba(255,179,71,.1);color:#ffb347;border:1px solid rgba(255,179,71,.2)}
.bh{background:rgba(255,107,107,.1);color:#ff6b6b;border:1px solid rgba(255,107,107,.2)}
.pb{flex:1;height:5px;background:var(--bg3);border-radius:3px;overflow:hidden}
.pf{height:100%;border-radius:3px;transition:width .6s}
/* ANSWERS */
.ans-header{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.ans-t{font-size:13px;font-weight:600}
.ans-item{padding:13px 16px;border-bottom:1px solid rgba(99,140,255,.05)}
.ans-item:last-child{border-bottom:none}
.ans-meta{display:flex;align-items:center;gap:8px;margin-bottom:8px}
.ans-q{font-size:13px;font-weight:500;color:var(--text);line-height:1.5;margin-bottom:10px}
.opts-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.opt-show{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;border:1px solid var(--border);font-size:12px;background:var(--bg3)}
.opt-show.right{background:rgba(81,207,102,.07);border-color:rgba(81,207,102,.3);color:#51cf66}
.opt-show.wrong{background:rgba(255,107,107,.07);border-color:rgba(255,107,107,.3);color:#ff6b6b}
.opt-k{width:22px;height:22px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;border:1.5px solid var(--border2);color:var(--text3)}
.opt-show.right .opt-k{background:#51cf66;border-color:#51cf66;color:#fff}
.opt-show.wrong .opt-k{background:#ff6b6b;border-color:#ff6b6b;color:#fff}
/* ACTIONS */
.actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px}
.btn{display:flex;align-items:center;justify-content:center;gap:7px;padding:11px;border-radius:9px;font-family:'Space Grotesk',sans-serif;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .17s;text-decoration:none}
.btn-p{background:var(--accent);color:#fff}
.btn-p:hover{background:var(--accent2);transform:translateY(-1px)}
.btn-g{background:var(--bg3);color:var(--text2);border:1px solid var(--border)}
.btn-g:hover{background:var(--card2);color:var(--text)}
.warn-banner{background:rgba(255,179,71,.07);border:1px solid rgba(255,179,71,.2);border-radius:9px;padding:10px 13px;font-size:12px;color:var(--amber);margin-bottom:12px}
</style>
</head>
<body>
<div class="wrap">
  <a href="index.php" class="back">← Testlarga qaytish</a>

  <!-- Score hero -->
  <div class="card">
    <div class="hero">
      <div class="hero-sub"><?= h($sess['sico'].' '.$sess['sname']) ?> · <?= h($sess['title']) ?></div>
      <?php if($isExp): ?><div style="background:rgba(255,179,71,.1);border:1px solid rgba(255,179,71,.25);border-radius:8px;padding:5px 12px;display:inline-block;font-size:11px;color:var(--amber);margin-bottom:12px">⏰ Vaqt tugaganidan yakunlandi</div><?php endif; ?>
      <div class="donut">
        <svg viewBox="0 0 110 110">
          <circle cx="55" cy="55" r="52" fill="none" stroke="rgba(255,255,255,.07)" stroke-width="7"/>
          <circle cx="55" cy="55" r="52" fill="none" stroke="<?= $gc ?>" stroke-width="7"
            stroke-dasharray="<?= $circ ?>" stroke-dashoffset="<?= $off ?>" stroke-linecap="round"/>
        </svg>
        <div class="donut-txt">
          <div class="grade" style="color:<?= $gc ?>"><?= $g ?></div>
        </div>
      </div>
      <div class="score-big"><?= round($score,1) ?><span class="score-max">/<?= $maxScore ?></span></div>
      <div class="pct-txt"><?= $pct ?>% to'g'ri</div>
      <?php if($timeTaken>0): ?><div class="time-txt">⏱ <?= floor($timeTaken/60) ?>m <?= $timeTaken%60 ?>s</div><?php endif; ?>
    </div>

    <div class="stats-row">
      <div class="sr-item"><div class="sr-ico">✅</div><div class="sr-val" style="color:var(--success)"><?= $correct ?></div><div class="sr-lbl">To'g'ri</div></div>
      <div class="sr-item"><div class="sr-ico">❌</div><div class="sr-val" style="color:var(--danger)"><?= $wrong ?></div><div class="sr-lbl">Noto'g'ri</div></div>
      <div class="sr-item"><div class="sr-ico">⬜</div><div class="sr-val" style="color:var(--text3)"><?= $unanswered ?></div><div class="sr-lbl">Javobsiz</div></div>
    </div>

    <div class="breakdown">
      <div class="bk-t">Qiyinlik bo'yicha natija</div>
      <?php foreach([1=>['Oson','be','#51cf66'],2=>["O'rta",'bm','#ffb347'],3=>['Qiyin','bh','#ff6b6b']] as $d=>[$dl,$bc,$clr]):
        if($byDiff[$d]['t']===0) continue;
        $dp=round($byDiff[$d]['c']/$byDiff[$d]['t']*100);
      ?>
      <div class="bk-row">
        <span class="badge <?= $bc ?>"><?= $dl ?></span>
        <div style="flex:1">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px">
            <span style="font-size:11px;color:var(--text2)"><?= $byDiff[$d]['c'] ?>/<?= $byDiff[$d]['t'] ?> to'g'ri</span>
            <span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:<?= $clr ?>"><?= $dp ?>%</span>
          </div>
          <div class="pb"><div class="pf" style="width:<?= $dp ?>%;background:<?= $clr ?>"></div></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Batafsil javoblar -->
  <div class="card">
    <div class="ans-header">
      <div class="ans-t">📋 Batafsil javoblar</div>
      <span style="font-size:11px;color:var(--text3)"><?= count($qIds) ?> ta savol</span>
    </div>
    <?php foreach($qIds as $idx=>$qid):
      $q=$qMap[$qid]??null; if(!$q) continue;
      $ch=$answers[$qid]??null;
      $isRight=$ch===$q['correct_option'];
      $isUna=!$ch;
      $ico=$isUna?'⬜':($isRight?'✅':'❌');
    ?>
    <div class="ans-item">
      <div class="ans-meta">
        <span style="font-size:16px"><?= $ico ?></span>
        <span style="font-size:11px;color:var(--text3)">Savol <?= $idx+1 ?></span>
        <span class="badge <?= ['','be','bm','bh'][$q['difficulty']] ?>"><?= diffLabel($q['difficulty']) ?></span>
        <?php if(!$isUna): ?>
        <span style="font-size:11px;font-weight:600;margin-left:auto;color:<?= $isRight?'var(--success)':'var(--danger)' ?>"><?= $isRight?'+'.$sess['points_per_q'].' ball':'0 ball' ?></span>
        <?php endif; ?>
      </div>
      <div class="ans-q"><?= h($q['question_text']) ?></div>
      <div class="opts-grid">
        <?php foreach(['a'=>'A','b'=>'B','c'=>'C','d'=>'D'] as $k=>$lbl):
          $isAns=$k===$ch; $isCorr=$k===$q['correct_option'];
          $cls=''; if($isCorr) $cls='right'; if($isAns&&!$isCorr) $cls='wrong';
        ?>
        <div class="opt-show <?= $cls ?>">
          <div class="opt-k"><?= $lbl ?></div>
          <span><?= h($q['option_'.$k]) ?></span>
          <?php if($isCorr): ?><span style="margin-left:auto">✓</span><?php endif; ?>
          <?php if($isAns&&!$isCorr): ?><span style="margin-left:auto">✗</span><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="actions">
    <a href="test_start.php?test=<?= $sess['test_id'] ?>" class="btn btn-g">🔄 Qayta urinish</a>
    <a href="index.php" class="btn btn-p">⊞ Bosh sahifa</a>
  </div>
</div>
</body></html>
