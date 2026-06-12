<?php
require_once 'config.php';
define('ROOT','');
requireLogin();
if(!isStudent()) redirect('index.php');
$me=me();
$sessionId=(int)($_GET['session']??0);
$s=db()->prepare("SELECT ts.*,t.question_count,t.points_per_q,t.time_per_q,t.title,s.name sname,s.icon sico FROM test_sessions ts JOIN tests t ON t.id=ts.test_id JOIN subjects s ON s.id=t.subject_id WHERE ts.id=? AND ts.student_id=?");
$s->execute([$sessionId,$me['id']]);$sess=$s->fetch();
if(!$sess){flash('error','Sessiya topilmadi.');redirect('index.php');}
if($sess['status']==='completed') redirect('result.php?session='.$sessionId);
$now=time();$exp=strtotime($sess['expires_at']);
if($now>=$exp&&$sess['status']==='active'){
    // Vaqt tugadi — natijalarga qarab saqlash
    $qIds=json_decode($sess['question_ids'],true);
    $answers=json_decode($sess['answers']??'{}',true);
    $pl=implode(',',array_fill(0,count($qIds),'?'));
    $qs=db()->prepare("SELECT id,correct_option,difficulty FROM questions WHERE id IN($pl)");
    $qs->execute($qIds);
    $score=0;$easy=0;$medium=0;$hard=0;
    foreach($qs->fetchAll() as $q){
        if(($answers[$q['id']]??'')!==$q['correct_option']) continue;
        $score+=$sess['points_per_q'];
        if($q['difficulty']==1)$easy++;elseif($q['difficulty']==2)$medium++;else$hard++;
    }
    db()->prepare("UPDATE test_sessions SET status='expired',score=?,finished_at=NOW() WHERE id=?")->execute([$score,$sessionId]);
    flash('info','Vaqt tugadi! Natijalaringiz saqlandi.');
    redirect('result.php?session='.$sessionId);
}
$qIds=json_decode($sess['question_ids'],true);
$answers=json_decode($sess['answers']??'{}',true);
$total=count($qIds);
$curIdx=(int)$sess['current_index'];
// Finish
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['finish'])&&checkToken()){
    $qid=(int)($_POST['question_id']??0);$ans=$_POST['answer']??null;
    if($qid&&$ans&&in_array($ans,['a','b','c','d'])) $answers[$qid]=$ans;
    $pl=implode(',',array_fill(0,count($qIds),'?'));
    $qs=db()->prepare("SELECT id,correct_option FROM questions WHERE id IN($pl)");$qs->execute($qIds);
    $score=0;
    foreach($qs->fetchAll() as $q){ if(($answers[$q['id']]??'')===$q['correct_option']) $score+=$sess['points_per_q']; }
    db()->prepare("UPDATE test_sessions SET status='completed',answers=?,score=?,finished_at=NOW(),current_index=? WHERE id=?")->execute([json_encode($answers),$score,$total,$sessionId]);
    flash('success','Test yakunlandi!');redirect('result.php?session='.$sessionId);
}
// Navigate
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['navigate'])&&checkToken()){
    $qid=(int)($_POST['question_id']??0);$ans=$_POST['answer']??null;
    if($qid&&$ans&&in_array($ans,['a','b','c','d'])) $answers[$qid]=$ans;
    $curIdx=max(0,min($total-1,(int)($_POST['nav_idx']??$curIdx)));
    db()->prepare("UPDATE test_sessions SET answers=?,current_index=? WHERE id=?")->execute([json_encode($answers),$curIdx,$sessionId]);
    redirect('test.php?session='.$sessionId);
}
// Joriy savol
$curQId=$qIds[$curIdx];
$q=db()->prepare("SELECT * FROM questions WHERE id=?");$q->execute([$curQId]);$q=$q->fetch();
$answered=$answers[$curQId]??null;
$secsLeft=max(0,$exp-$now);
$totalSec=$total*$sess['time_per_q']*60;
$answeredCount=count($answers);
$circ=2*M_PI*46;
?>
<!DOCTYPE html>
<html lang="uz" data-theme="<?= theme() ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Savol <?= $curIdx+1 ?>/<?= $total ?> — SmartTest</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0e1a;--bg2:#0f1526;--bg3:#141c30;--card:#161d30;--card2:#1c2540;--border:rgba(99,140,255,.13);--border2:rgba(99,140,255,.28);--accent:#4f7cff;--accent2:#7b9fff;--teal:#00d4aa;--teal2:#00b894;--amber:#ffb347;--text:#e8edff;--text2:#8fa3cc;--text3:#4d6494;--danger:#ff6b6b;--success:#51cf66;--glow:0 0 24px rgba(79,124,255,.18)}
[data-theme="light"]{--bg:#f0f4ff;--bg2:#fff;--bg3:#f5f7ff;--card:#fff;--card2:#eef1ff;--border:rgba(79,124,255,.13);--text:#0d1b3e;--text2:#3d5a99;--text3:#7a9acc}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Space Grotesk',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(79,124,255,.022) 1px,transparent 1px),linear-gradient(90deg,rgba(79,124,255,.022) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;z-index:0}
/* TOP PROGRESS */
.top-bar{position:fixed;top:0;left:0;right:0;z-index:100;height:3px;background:rgba(99,140,255,.15)}
.top-fill{height:100%;background:linear-gradient(90deg,var(--accent),var(--teal));transition:width .4s;width:<?= round($curIdx/$total*100) ?>%}
/* HEADER */
.hdr{position:sticky;top:3px;z-index:99;background:var(--bg2);border-bottom:1px solid var(--border);padding:10px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px}
.hdr-left{display:flex;align-items:center;gap:12px}
.back{display:flex;align-items:center;gap:5px;color:var(--text2);text-decoration:none;font-size:12px;padding:5px 10px;background:var(--bg3);border:1px solid var(--border);border-radius:7px;transition:all .15s}
.back:hover{color:var(--text)}
.hdr-info{font-size:13px;font-weight:500}
.hdr-sub{font-size:11px;color:var(--text3)}
/* TIMER */
.timer{position:relative;width:64px;height:64px;flex-shrink:0}
.timer svg{transform:rotate(-90deg);width:64px;height:64px}
.timer-txt{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
.timer-val{font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700;line-height:1}
.timer-lbl{font-size:9px;color:var(--text3)}
/* MAIN */
.main{max-width:680px;margin:0 auto;padding:20px 20px 80px}
/* STEP DOTS */
.dots{display:flex;gap:3px;margin-bottom:16px;flex-wrap:wrap}
.dot{flex:1;min-width:10px;height:5px;border-radius:3px;cursor:pointer;transition:all .15s}
/* QUESTION */
.qcard{background:var(--card);border:1px solid var(--border);border-radius:13px;overflow:hidden;margin-bottom:14px}
.qmeta{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border);background:var(--bg3)}
.qnum{font-size:12px;font-weight:600;color:var(--text2)}
.qbadges{display:flex;gap:6px}
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:600}
.be{background:rgba(81,207,102,.1);color:#51cf66;border:1px solid rgba(81,207,102,.2)}
.bm{background:rgba(255,179,71,.1);color:#ffb347;border:1px solid rgba(255,179,71,.2)}
.bh{background:rgba(255,107,107,.1);color:#ff6b6b;border:1px solid rgba(255,107,107,.2)}
.bb{background:rgba(79,124,255,.1);color:var(--accent2);border:1px solid rgba(79,124,255,.2)}
.ba{background:rgba(218,119,242,.1);color:#da77f2;border:1px solid rgba(218,119,242,.2)}
.qtext{padding:18px 18px 14px;font-size:16px;font-weight:600;line-height:1.6;color:var(--text)}
/* OPTIONS */
.opts{padding:4px 16px 16px;display:flex;flex-direction:column;gap:9px}
.opt{display:flex;align-items:flex-start;gap:12px;padding:13px 14px;border:2px solid var(--border);border-radius:10px;cursor:pointer;transition:all .15s;background:var(--bg2)}
.opt:hover{border-color:var(--border2);background:var(--card2)}
.opt.sel{border-color:var(--accent);background:rgba(79,124,255,.07)}
.opt input{display:none}
.opt-key{width:30px;height:30px;border-radius:7px;border:2px solid var(--border2);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--text3);flex-shrink:0;transition:all .15s}
.opt.sel .opt-key{background:var(--accent);border-color:var(--accent);color:#fff}
.opt-txt{font-size:14px;color:var(--text2);line-height:1.5;padding-top:3px;transition:color .15s}
.opt.sel .opt-txt{color:var(--text)}
/* NAVIGATION */
.nav-bar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border-top:1px solid var(--border)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 15px;border-radius:8px;font-family:'Space Grotesk',sans-serif;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .17s;white-space:nowrap}
.btn:disabled{opacity:.35;cursor:not-allowed}
.btn-p{background:var(--accent);color:#fff}
.btn-p:hover:not(:disabled){background:var(--accent2);transform:translateY(-1px)}
.btn-t{background:var(--teal);color:#051a15}
.btn-t:hover:not(:disabled){background:var(--teal2)}
.btn-g{background:var(--bg3);color:var(--text2);border:1px solid var(--border)}
.btn-g:hover:not(:disabled){background:var(--card2);color:var(--text)}
.btn-s{background:var(--success);color:#fff}
.btn-s:hover:not(:disabled){filter:brightness(1.1)}
/* MINI NAV */
.mini-nav{background:var(--card);border:1px solid var(--border);border-radius:13px;padding:14px 16px}
.mini-nav-t{font-size:12px;font-weight:600;color:var(--text2);margin-bottom:10px}
.mini-btns{display:flex;flex-wrap:wrap;gap:5px}
.mb{width:32px;height:32px;border-radius:7px;border:none;cursor:pointer;font-size:11px;font-weight:700;transition:all .12s}
/* MODAL */
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:500;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.modal.open{display:flex}
.modal-box{background:var(--card);border:1px solid var(--border2);border-radius:16px;padding:28px;max-width:380px;width:90%;text-align:center;animation:pop .3s ease}
@keyframes pop{from{opacity:0;transform:scale(.94)}to{opacity:1;transform:scale(1)}}
.modal-ico{font-size:44px;margin-bottom:12px}
.modal-t{font-size:18px;font-weight:700;margin-bottom:8px}
.modal-s{font-size:13px;color:var(--text2);margin-bottom:20px;line-height:1.5}
.modal-btns{display:grid;grid-template-columns:1fr 1fr;gap:10px}
</style>
</head>
<body>
<div class="top-bar"><div class="top-fill" id="topFill"></div></div>

<div class="hdr">
  <div class="hdr-left">
    <a href="index.php" class="back" onclick="return confirm('Testdan chiqasizmi? Javoblaringiz saqlanadi.')">← Chiqish</a>
    <div>
      <div class="hdr-info"><?= h($sess['sico'].' '.$sess['sname']) ?> · <?= h($sess['title']) ?></div>
      <div class="hdr-sub">Savol <?= $curIdx+1 ?>/<?= $total ?> · <?= $answeredCount ?> ta javoblangan</div>
    </div>
  </div>
  <!-- TIMER -->
  <div class="timer" title="Qolgan vaqt">
    <?php $off=$circ*(1-$secsLeft/$totalSec); ?>
    <svg viewBox="0 0 100 100">
      <circle cx="50" cy="50" r="46" fill="none" stroke="rgba(99,140,255,.15)" stroke-width="7"/>
      <circle cx="50" cy="50" r="46" fill="none" stroke="var(--accent)" stroke-width="7"
        stroke-dasharray="<?= $circ ?>" stroke-dashoffset="<?= $off ?>"
        stroke-linecap="round" id="timerArc"/>
    </svg>
    <div class="timer-txt">
      <div class="timer-val" id="timerVal">--:--</div>
      <div class="timer-lbl">qoldi</div>
    </div>
  </div>
</div>

<div class="main">
  <!-- Progress dots -->
  <div class="dots">
    <?php for($i=0;$i<$total;$i++):
      $qi=$qIds[$i]; $isAns=isset($answers[$qi]); $isCur=($i===$curIdx);
      $bg=$isCur?'var(--accent)':($isAns?'rgba(79,124,255,.4)':'var(--border)');
      $scale=$isCur?'scaleY(1.7)':'scaleY(1)';
    ?>
    <div class="dot" onclick="goTo(<?= $i ?>)" style="background:<?= $bg ?>;transform:<?= $scale ?>" title="Savol <?= $i+1 ?>"></div>
    <?php endfor; ?>
  </div>

  <!-- Savol -->
  <form method="POST" id="testForm">
    <input type="hidden" name="_token" value="<?= token() ?>">
    <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
    <input type="hidden" name="nav_idx" id="navIdx" value="<?= $curIdx ?>">

    <div class="qcard">
      <div class="qmeta">
        <span class="qnum">Savol <?= $curIdx+1 ?> / <?= $total ?></span>
        <div class="qbadges">
          <span class="badge <?= ['','be','bm','bh'][$q['difficulty']] ?>"><?= diffLabel($q['difficulty']) ?></span>
          <span class="badge ba">⭐ <?= $sess['points_per_q'] ?> ball</span>
        </div>
      </div>
      <div class="qtext"><?= nl2br(h($q['question_text'])) ?></div>
      <div class="opts">
        <?php foreach(['a'=>'A','b'=>'B','c'=>'C','d'=>'D'] as $k=>$lbl): ?>
        <label class="opt <?= $answered===$k?'sel':'' ?>" id="opt-<?= $k ?>" onclick="selectOpt('<?= $k ?>')">
          <input type="radio" name="answer" value="<?= $k ?>" <?= $answered===$k?'checked':'' ?>>
          <div class="opt-key" id="key-<?= $k ?>"><?= $lbl ?></div>
          <div class="opt-txt"><?= h($q['option_'.$k]) ?></div>
        </label>
        <?php endforeach; ?>
      </div>

      <div class="nav-bar">
        <div style="display:flex;gap:7px">
          <?php if($curIdx>0): ?>
          <button type="submit" name="navigate" class="btn btn-g" onclick="document.getElementById('navIdx').value=<?= $curIdx-1 ?>">← Oldingi</button>
          <?php endif; ?>
          <?php if($curIdx<$total-1): ?>
          <button type="submit" name="navigate" class="btn btn-g" onclick="document.getElementById('navIdx').value=<?= $curIdx+1 ?>">Keyingi →</button>
          <?php endif; ?>
        </div>
        <?php if($curIdx===$total-1||$answeredCount>=$total): ?>
        <button type="button" onclick="openModal()" class="btn btn-s">
          ✓ Yakunlash <?php if($answeredCount<$total): ?>(<?= $answeredCount ?>/<?= $total ?>)<?php endif; ?>
        </button>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <!-- Mini navigator -->
  <div class="mini-nav">
    <div class="mini-nav-t">Barcha savollarga o'tish — <?= $answeredCount ?>/<?= $total ?> javoblangan</div>
    <div class="mini-btns">
      <?php for($i=0;$i<$total;$i++):
        $qi=$qIds[$i];$isAns=isset($answers[$qi]);$isCur=($i===$curIdx);
        $bg=$isCur?'var(--accent)':($isAns?'rgba(79,124,255,.18)':'var(--bg3)');
        $cl=$isCur?'#fff':($isAns?'var(--accent2)':'var(--text3)');
        $brd=$isCur?'1px solid var(--accent)':($isAns?'1px solid rgba(79,124,255,.3)':'1px solid var(--border)');
      ?>
      <button type="button" onclick="goTo(<?= $i ?>)" class="mb"
        style="background:<?= $bg ?>;color:<?= $cl ?>;border:<?= $brd ?>"><?= $i+1 ?></button>
      <?php endfor; ?>
    </div>
  </div>
</div>

<!-- Finish modal -->
<div class="modal" id="finModal">
  <div class="modal-box">
    <div class="modal-ico">🏁</div>
    <div class="modal-t">Testni yakunlash</div>
    <div class="modal-s">
      Javoblangan: <strong style="color:var(--accent)"><?= $answeredCount ?>/<?= $total ?></strong>
      <?php if($answeredCount<$total): ?><br><span style="color:var(--amber)">⚠️ <?= $total-$answeredCount ?> ta savol javobsiz qoldi</span><?php endif; ?>
    </div>
    <div class="modal-btns">
      <button class="btn btn-g" onclick="document.getElementById('finModal').classList.remove('open')">Bekor qilish</button>
      <button class="btn btn-s" onclick="submitFinish()">✓ Tasdiqlash</button>
    </div>
  </div>
</div>

<script>
let secsLeft=<?= $secsLeft ?>;
const totalSec=<?= $totalSec ?>;
const circ=<?= $circ ?>;

function fmt(s){const m=Math.floor(s/60),sc=s%60;return m+':'+(sc<10?'0':'')+sc}
function tick(){
  if(secsLeft<=0){
    document.getElementById('testForm').insertAdjacentHTML('beforeend','<input type="hidden" name="finish" value="1">');
    document.getElementById('testForm').submit();return;
  }
  secsLeft--;
  document.getElementById('timerVal').textContent=fmt(secsLeft);
  const arc=document.getElementById('timerArc');
  const pct=secsLeft/totalSec;
  arc.setAttribute('stroke-dashoffset',circ*(1-pct));
  const urgent=secsLeft<120;
  arc.setAttribute('stroke',urgent?'#ff6b6b':'var(--accent)');
  document.getElementById('timerVal').style.color=urgent?'#ff6b6b':'var(--text)';
}
tick();const tInt=setInterval(tick,1000);

function selectOpt(k){
  document.querySelectorAll('.opt').forEach(o=>o.classList.remove('sel'));
  document.getElementById('opt-'+k).classList.add('sel');
  document.querySelector(`input[value="${k}"]`).checked=true;
}

function goTo(idx){
  document.getElementById('navIdx').value=idx;
  const i=document.createElement('input');i.type='hidden';i.name='navigate';i.value='1';
  document.getElementById('testForm').appendChild(i);
  document.getElementById('testForm').submit();
}

function openModal(){document.getElementById('finModal').classList.add('open')}
function submitFinish(){
  clearInterval(tInt);
  const i=document.createElement('input');i.type='hidden';i.name='finish';i.value='1';
  document.getElementById('testForm').appendChild(i);
  document.querySelectorAll('[name=navigate]').forEach(e=>e.remove());
  document.getElementById('testForm').submit();
}

// Keyboard shortcuts
document.addEventListener('keydown',e=>{
  if(['1','a'].includes(e.key)) selectOpt('a');
  if(['2','b'].includes(e.key)) selectOpt('b');
  if(['3','c'].includes(e.key)) selectOpt('c');
  if(['4','d'].includes(e.key)) selectOpt('d');
  if(e.key==='ArrowRight'&&<?= $curIdx ?><<?= $total-1 ?>) goTo(<?= $curIdx+1 ?>);
  if(e.key==='ArrowLeft'&&<?= $curIdx ?>>0) goTo(<?= $curIdx-1 ?>);
  if(e.key==='Enter'&&e.ctrlKey) openModal();
});
</script>
</body></html>
