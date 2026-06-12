<?php
require_once 'config.php';
define('ROOT','');
requireLogin();
if(isTeacher()) redirect('teacher/');
if(isAdmin())   redirect('admin/');

$me=me();

// Statistika
$st=db()->prepare("SELECT COUNT(*) t,SUM(status='completed') d,
    AVG(CASE WHEN status='completed' THEN score END) av,
    MAX(CASE WHEN status='completed' THEN score END) mx
    FROM test_sessions WHERE student_id=?");
$st->execute([$me['id']]); $st=$st->fetch();

// Testlar
$tests=db()->query("SELECT t.*,s.name sname,s.icon sico,s.level slevel,
    u.name tname,
    (SELECT COUNT(*) FROM questions q WHERE q.subject_id=t.subject_id
     AND(t.difficulty=0 OR q.difficulty=t.difficulty)) qavail,
    (SELECT id FROM test_sessions ts WHERE ts.student_id={$me['id']} AND ts.test_id=t.id AND ts.status='active' LIMIT 1) asid,
    (SELECT ROUND((ts2.current_index/t.question_count)*100) FROM test_sessions ts2
     WHERE ts2.student_id={$me['id']} AND ts2.test_id=t.id AND ts2.status='active' LIMIT 1) apct,
    (SELECT score FROM test_sessions ts3 WHERE ts3.student_id={$me['id']} AND ts3.test_id=t.id AND ts3.status='completed' ORDER BY ts3.id DESC LIMIT 1) lscore
    FROM tests t JOIN subjects s ON s.id=t.subject_id JOIN users u ON u.id=t.teacher_id
    WHERE t.is_active=1 ORDER BY t.created_at DESC")->fetchAll();

// So'nggi natijalar (grafik uchun)
$history=db()->prepare("SELECT ts.score,ts.started_at,t.question_count,t.points_per_q,s.name sname
    FROM test_sessions ts JOIN tests t ON t.id=ts.test_id JOIN subjects s ON s.id=t.subject_id
    WHERE ts.student_id=? AND ts.status='completed' ORDER BY ts.id DESC LIMIT 8");
$history->execute([$me['id']]); $history=array_reverse($history->fetchAll());

// Fan statistikasi
$fanStats=db()->prepare("SELECT s.name,s.icon,COUNT(*) cnt,
    AVG(ts.score/(t.question_count*t.points_per_q)*100) pct
    FROM test_sessions ts JOIN tests t ON t.id=ts.test_id JOIN subjects s ON s.id=t.subject_id
    WHERE ts.student_id=? AND ts.status='completed' GROUP BY s.id ORDER BY pct DESC");
$fanStats->execute([$me['id']]); $fanStats=$fanStats->fetchAll();

$levelIco=['school'=>'🏫','university'=>'🎓','masters'=>'🔬'];
?>
<!DOCTYPE html>
<html lang="uz" data-theme="<?= theme() ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SmartTest — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<style>
:root{
  --bg:#0a0e1a;--bg2:#0f1526;--bg3:#141c30;
  --card:#161d30;--card2:#1c2540;--card3:#20294a;
  --border:rgba(99,140,255,.13);--border2:rgba(99,140,255,.28);
  --accent:#4f7cff;--accent2:#7b9fff;--accent3:#a8c0ff;
  --teal:#00d4aa;--teal2:#00b894;--amber:#ffb347;
  --danger:#ff6b6b;--success:#51cf66;--purple:#da77f2;
  --text:#e8edff;--text2:#8fa3cc;--text3:#4d6494;
  --glow:0 0 28px rgba(79,124,255,.18);
  --shadow:0 8px 32px rgba(0,0,0,.35);
}
[data-theme="light"]{
  --bg:#f0f4ff;--bg2:#ffffff;--bg3:#f5f7ff;
  --card:#ffffff;--card2:#eef1ff;--card3:#e4e9ff;
  --border:rgba(79,124,255,.13);--border2:rgba(79,124,255,.28);
  --text:#0d1b3e;--text2:#3d5a99;--text3:#7a9acc;
  --shadow:0 4px 16px rgba(0,0,0,.08);
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Space Grotesk',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(79,124,255,.022) 1px,transparent 1px),linear-gradient(90deg,rgba(79,124,255,.022) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;z-index:0}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:var(--bg3);border-radius:3px}

/* ─── LAYOUT ─── */
.app{position:relative;z-index:1;display:flex;min-height:100vh}
.sidebar{width:230px;background:var(--bg2);border-right:1px solid var(--border);position:fixed;top:0;left:0;bottom:0;z-index:200;display:flex;flex-direction:column}
.logo{padding:16px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:9px}
.logo-ico{width:32px;height:32px;background:linear-gradient(135deg,var(--accent),var(--teal));border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:var(--glow)}
.logo-txt{font-weight:700;font-size:15px;letter-spacing:-.3px}
.logo-txt em{color:var(--accent);font-style:normal}
.nav{flex:1;padding:10px 8px;overflow-y:auto;display:flex;flex-direction:column;gap:2px}
.nl{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--text3);padding:10px 9px 3px}
.ni{display:flex;align-items:center;gap:8px;padding:7px 9px;border-radius:8px;cursor:pointer;color:var(--text2);font-size:13px;font-weight:500;transition:all .15s;border:1px solid transparent;text-decoration:none}
.ni:hover{background:var(--bg3);color:var(--text)}
.ni.on{background:rgba(79,124,255,.11);color:var(--accent2);border-color:rgba(79,124,255,.18)}
.ni.danger:hover{background:rgba(255,107,107,.07);color:var(--danger)}
.sf{padding:9px 8px;border-top:1px solid var(--border)}
.uc{display:flex;align-items:center;gap:8px;padding:8px 9px;background:var(--bg3);border-radius:8px;border:1px solid var(--border)}
.av{width:28px;height:28px;background:linear-gradient(135deg,var(--accent),var(--teal));border-radius:7px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0}
.tp{font-family:'JetBrains Mono',monospace;font-size:9px;background:rgba(79,124,255,.14);color:var(--accent2);padding:2px 5px;border-radius:4px;border:1px solid var(--border);cursor:pointer;flex-shrink:0}

/* ─── MAIN ─── */
.main{margin-left:230px;flex:1;padding:24px 26px;min-height:100vh}

/* ─── TOPBAR ─── */
.tb{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;gap:12px;flex-wrap:wrap}
.tb-left h1{font-size:20px;font-weight:700;letter-spacing:-.4px}
.tb-left p{font-size:12px;color:var(--text2);margin-top:3px}
.tb-right{display:flex;gap:7px;align-items:center}

/* ─── BUTTONS ─── */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:7px 14px;border-radius:8px;font-family:'Space Grotesk',sans-serif;font-size:12px;font-weight:500;cursor:pointer;border:none;transition:all .17s;text-decoration:none;white-space:nowrap}
.btn-p{background:var(--accent);color:#fff}
.btn-p:hover{background:var(--accent2);transform:translateY(-1px)}
.btn-t{background:var(--teal);color:#051a15}
.btn-t:hover{background:var(--teal2)}
.btn-g{background:var(--bg3);color:var(--text2);border:1px solid var(--border)}
.btn-g:hover{background:var(--card2);color:var(--text)}
.btn-fw{width:100%}

/* ─── STAT CARDS ─── */
.sg{display:grid;grid-template-columns:repeat(4,1fr);gap:11px;margin-bottom:18px}
.sc{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:15px 16px;position:relative;overflow:hidden;transition:all .2s}
.sc:hover{border-color:var(--border2);transform:translateY(-1px)}
.sc::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px}
.sc.c1::after{background:linear-gradient(90deg,var(--accent),transparent)}
.sc.c2::after{background:linear-gradient(90deg,var(--teal),transparent)}
.sc.c3::after{background:linear-gradient(90deg,var(--amber),transparent)}
.sc.c4::after{background:linear-gradient(90deg,var(--purple),transparent)}
.sc-l{font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text3);margin-bottom:7px}
.sc-v{font-size:22px;font-weight:700;letter-spacing:-1px;line-height:1}
.sc-v.mo{font-family:'JetBrains Mono',monospace;font-size:18px}
.sc-s{font-size:11px;color:var(--text2);margin-top:3px}
.sc-i{position:absolute;top:13px;right:13px;font-size:20px;opacity:.1}

/* ─── GRID ─── */
.g2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.ga{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px}

/* ─── CARD ─── */
.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;transition:all .18s}
.ch{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:8px}
.ct{font-size:13px;font-weight:600}
.cs{font-size:11px;color:var(--text2);margin-top:2px}

/* ─── TABLE ─── */
.tbl{width:100%;border-collapse:collapse;font-size:12px}
.tbl th{text-align:left;padding:8px 11px;font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text3);border-bottom:1px solid var(--border);background:var(--bg3)}
.tbl td{padding:9px 11px;border-bottom:1px solid rgba(99,140,255,.05);color:var(--text2);vertical-align:middle}
.tbl tr:last-child td{border-bottom:none}
.tbl tbody tr:hover td{background:var(--bg3);color:var(--text)}

/* ─── BADGE ─── */
.badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:600}
.be{background:rgba(81,207,102,.1);color:#51cf66;border:1px solid rgba(81,207,102,.2)}
.bm{background:rgba(255,179,71,.1);color:#ffb347;border:1px solid rgba(255,179,71,.2)}
.bh{background:rgba(255,107,107,.1);color:#ff6b6b;border:1px solid rgba(255,107,107,.2)}
.bb{background:rgba(79,124,255,.1);color:var(--accent2);border:1px solid rgba(79,124,255,.2)}
.bt{background:rgba(0,212,170,.1);color:var(--teal);border:1px solid rgba(0,212,170,.2)}

/* ─── PROGRESS ─── */
.pb{height:3px;background:var(--bg3);border-radius:2px;overflow:hidden}
.pf{height:100%;border-radius:2px;background:linear-gradient(90deg,var(--accent),var(--teal));transition:width .5s}

/* ─── TEST CARDS ─── */
.tc{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;transition:all .18s;display:flex;flex-direction:column}
.tc:hover{border-color:var(--border2);transform:translateY(-2px);box-shadow:0 6px 24px rgba(0,0,0,.25)}
.tc-top{padding:16px 16px 12px;flex:1}
.tc-foot{padding:10px 16px;border-top:1px solid var(--border);background:var(--bg3)}
.tc-ico{font-size:26px;margin-bottom:8px}
.tc-t{font-size:14px;font-weight:600;margin-bottom:2px;line-height:1.3}
.tc-m{font-size:11px;color:var(--text3);margin-bottom:8px}
.tc-tags{display:flex;flex-wrap:wrap;gap:5px;margin-top:8px}

/* ─── CHART ─── */
.chart-wrap{position:relative;height:200px}

/* ─── PAGES ─── */
.page{display:none;animation:fi .28s ease}
.page.on{display:block}
@keyframes fi{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

/* ─── EMPTY ─── */
.empty{text-align:center;padding:40px 16px}
.ei{font-size:40px;margin-bottom:10px;opacity:.35}
.et{font-size:12px;color:var(--text3)}

/* ─── TOAST ─── */
.toast-w{position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:7px}
.toast{padding:10px 14px;border-radius:10px;font-size:12px;font-weight:500;background:var(--card2);border:1px solid var(--border2);box-shadow:var(--shadow);display:flex;align-items:center;gap:7px;animation:fi .3s ease}
.tok{border-color:rgba(81,207,102,.3);color:#51cf66}
.ter{border-color:rgba(255,107,107,.3);color:#ff6b6b}

@media(max-width:880px){.sg{grid-template-columns:repeat(2,1fr)}.g2{grid-template-columns:1fr}.sidebar{display:none}.main{margin-left:0}}
</style>
</head>
<body>
<div class="app">

<!-- SIDEBAR -->
<nav class="sidebar">
  <div class="logo">
    <div class="logo-ico">⚡</div>
    <div class="logo-txt">Smart<em>IQ</em></div>
  </div>
  <div class="nav">
    <div class="nl">O'quvchi</div>
    <div class="ni on" onclick="gp('dash')" id="n-dash"><span>🏠</span> Dashboard</div>
    <div class="ni" onclick="gp('tests')" id="n-tests"><span>🎯</span> Testlar
      <?php if(count($tests)): ?>
      <span style="margin-left:auto;background:var(--accent);color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:6px"><?= count($tests) ?></span>
      <?php endif; ?>
    </div>
    <div class="ni" onclick="gp('results')" id="n-results"><span>📊</span> Natijalarim</div>
    <div class="ni" onclick="gp('progress')" id="n-progress"><span>📈</span> Rivojlanish</div>
    <div class="nl">Profil</div>
    <a href="profile.php" class="ni"><span>👤</span> Profil</a>
    <a href="logout.php" class="ni danger"><span>🚪</span> Chiqish</a>
  </div>
  <div class="sf">
    <div class="uc">
      <div class="av"><?= strtoupper(mb_substr($me['name'],0,2)) ?></div>
      <div style="flex:1;min-width:0">
        <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h(explode(' ',$me['name'])[0]) ?></div>
        <div style="font-size:10px;color:var(--text3)">O'quvchi</div>
      </div>
      <div class="tp" onclick="toggleTheme()"><?= theme()==='dark'?'☀':'🌙' ?></div>
    </div>
  </div>
</nav>

<main class="main">

<!-- TOASTS -->
<?php $fs=getFlash('success');$fe=getFlash('error'); ?>
<div class="toast-w">
  <?php if($fs): ?><div class="toast tok">✓ <?= h($fs) ?></div><?php endif; ?>
  <?php if($fe): ?><div class="toast ter">✕ <?= h($fe) ?></div><?php endif; ?>
</div>

<!-- ══ DASHBOARD ══ -->
<div class="page on" id="p-dash">
  <div class="tb">
    <div class="tb-left">
      <h1><?= date('H')<12?'🌅 Xayrli tong':(date('H')<17?'☀️ Xayrli kun':'🌙 Xayrli kech') ?>, <?= h(explode(' ',$me['name'])[0]) ?>!</h1>
      <p>Bugungi test natijalaringiz va rivojlanishingiz</p>
    </div>
    <div class="tb-right">
      <button class="btn btn-g" onclick="toggleTheme()">
        <span id="tIco"><?= theme()==='dark'?'☀️':'🌙' ?></span>
      </button>
      <button class="btn btn-p" onclick="gp('tests')">🎯 Test boshlash</button>
    </div>
  </div>

  <!-- Stats -->
  <div class="sg">
    <div class="sc c1"><div class="sc-l">Jami urinish</div><div class="sc-v"><?= (int)$st['t'] ?></div><div class="sc-s"><?= (int)$st['d'] ?> yakunlangan</div><div class="sc-i">📝</div></div>
    <div class="sc c2"><div class="sc-l">O'rtacha ball</div><div class="sc-v mo"><?= $st['av']?round($st['av'],1):'—' ?></div><div class="sc-s">Barcha testlar bo'yicha</div><div class="sc-i">⭐</div></div>
    <div class="sc c3"><div class="sc-l">Eng yuqori ball</div><div class="sc-v mo"><?= $st['mx']?round($st['mx'],1):'—' ?></div><div class="sc-s">Rekord natija</div><div class="sc-i">🏆</div></div>
    <div class="sc c4"><div class="sc-l">Mavjud testlar</div><div class="sc-v"><?= count($tests) ?></div><div class="sc-s">O'qituvchilardan</div><div class="sc-i">🎯</div></div>
  </div>

  <div class="g2" style="margin-bottom:16px">
    <!-- Grafik -->
    <div class="card">
      <div class="ch"><div><div class="ct">Natijalar tarixi</div><div class="cs">Ball dinamikasi</div></div></div>
      <div class="chart-wrap"><canvas id="histChart"></canvas></div>
    </div>
    <!-- So'nggi natijalar -->
    <div class="card">
      <div class="ch">
        <div class="ct">So'nggi natijalar</div>
        <button class="btn btn-g" style="font-size:11px;padding:5px 10px" onclick="gp('results')">Barchasini ko'r →</button>
      </div>
      <?php
      $latestRes=db()->prepare("SELECT ts.*,t.title,t.question_count,t.points_per_q,s.name sn,s.icon si FROM test_sessions ts JOIN tests t ON t.id=ts.test_id JOIN subjects s ON s.id=t.subject_id WHERE ts.student_id=? AND ts.status='completed' ORDER BY ts.id DESC LIMIT 6");
      $latestRes->execute([$me['id']]); $latestRes=$latestRes->fetchAll();
      ?>
      <?php if(empty($latestRes)): ?>
      <div class="empty"><div class="ei">📭</div><div class="et">Hali natijalar yo'q. Birinchi testni boshlang!</div></div>
      <?php else: ?>
      <table class="tbl">
        <thead><tr><th>Fan</th><th>Ball</th><th>Baho</th><th>Vaqt</th></tr></thead>
        <tbody>
        <?php foreach($latestRes as $r):
          $max=$r['question_count']*$r['points_per_q'];
          $pct=$max>0?round($r['score']/$max*100):0;
          $g=grade($pct);$gc=gradeColor($g);
        ?>
        <tr>
          <td><?= h($r['si'].' '.$r['sn']) ?></td>
          <td style="font-family:'JetBrains Mono',monospace;color:var(--accent2)"><?= round($r['score'],1) ?>/<?= $max ?></td>
          <td><span style="font-family:'JetBrains Mono',monospace;font-weight:700;color:<?= $gc ?>"><?= $g ?></span></td>
          <td style="color:var(--text3)"><?= ago($r['started_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Testlar preview -->
  <div class="card">
    <div class="ch">
      <div><div class="ct">Mavjud testlar</div><div class="cs">O'qituvchilar tomonidan yaratilgan</div></div>
      <button class="btn btn-g" style="font-size:11px;padding:5px 10px" onclick="gp('tests')">Barchasini ko'r →</button>
    </div>
    <?php if(empty($tests)): ?>
    <div class="empty"><div class="ei">📭</div><div class="et">Hozircha testlar yo'q</div></div>
    <?php else: ?>
    <div class="ga">
      <?php foreach(array_slice($tests,0,3) as $t):
        $max=$t['question_count']*$t['points_per_q'];
        $hasA=!empty($t['asid']); $totalMin=$t['question_count']*$t['time_per_q'];
      ?>
      <div class="tc">
        <div class="tc-top">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
            <div class="tc-ico"><?= h($t['sico']) ?></div>
            <?php if($hasA): ?><span class="badge bm">▶ Davom</span>
            <?php elseif($t['lscore']!==null): ?><span class="badge bt">✓ Tugallangan</span><?php endif; ?>
          </div>
          <div class="tc-t"><?= h($t['title']) ?></div>
          <div class="tc-m"><?= h($t['sname']) ?> · <?= h($t['tname']) ?></div>
          <div class="tc-tags">
            <?php if($t['difficulty']==0): ?><span class="badge bb">🔀 Aralash</span>
            <?php else: ?><span class="badge <?= ['','be','bm','bh'][$t['difficulty']] ?>"><?= diffLabel($t['difficulty']) ?></span>
            <?php endif; ?>
            <span class="badge bb">❓ <?= $t['question_count'] ?> savol</span>
            <span class="badge bt">⏱ <?= $totalMin ?>d</span>
          </div>
          <?php if($hasA&&$t['apct']!==null): ?>
          <div style="margin-top:10px">
            <div style="display:flex;justify-content:space-between;margin-bottom:3px;font-size:10px;color:var(--amber)">
              <span>Tugallanmagan</span><span style="font-family:'JetBrains Mono',monospace"><?= $t['apct'] ?>%</span>
            </div>
            <div class="pb"><div class="pf" style="width:<?= $t['apct'] ?>%;background:var(--amber)"></div></div>
          </div>
          <?php endif; ?>
          <?php if($t['lscore']!==null): ?>
          <div style="margin-top:8px;padding:6px 10px;background:rgba(0,212,170,.06);border:1px solid rgba(0,212,170,.18);border-radius:7px;font-size:11px;color:var(--teal)">
            ✓ Oxirgi: <strong><?= round($t['lscore'],1) ?>/<?= $max ?> ball</strong>
          </div>
          <?php endif; ?>
        </div>
        <div class="tc-foot">
          <?php if($hasA): ?>
          <a href="test.php?session=<?= $t['asid'] ?>" class="btn btn-t btn-fw">▶ Davom ettirish</a>
          <?php elseif($t['qavail']>=$t['question_count']): ?>
          <a href="test_start.php?test=<?= $t['id'] ?>" class="btn btn-p btn-fw">🚀 Boshlash</a>
          <?php else: ?>
          <div class="btn btn-g btn-fw" style="opacity:.45;cursor:not-allowed">🔒 Yetarli savol yo'q</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ══ TESTLAR ══ -->
<div class="page" id="p-tests">
  <div class="tb">
    <div class="tb-left"><h1>🎯 Barcha Testlar</h1><p>O'qituvchilar yaratgan testlar</p></div>
  </div>
  <?php if(empty($tests)): ?>
  <div class="card"><div class="empty"><div class="ei">📭</div><div class="et">Hozircha testlar yo'q</div></div></div>
  <?php else: ?>
  <div class="ga">
    <?php foreach($tests as $t):
      $max=$t['question_count']*$t['points_per_q'];
      $hasA=!empty($t['asid']); $totalMin=$t['question_count']*$t['time_per_q'];
      $levelIcos=['school'=>'🏫','university'=>'🎓','masters'=>'🔬'];
    ?>
    <div class="tc">
      <div class="tc-top">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font-size:28px"><?= h($t['sico']) ?></span>
            <span style="font-size:11px;color:var(--text3)"><?= $levelIcos[$t['slevel']]??'📚' ?> <?= ucfirst($t['slevel']) ?></span>
          </div>
          <?php if($hasA): ?><span class="badge bm">▶ Davom</span>
          <?php elseif($t['lscore']!==null): ?><span class="badge bt">✓ Tugallangan</span><?php endif; ?>
        </div>
        <div class="tc-t"><?= h($t['title']) ?></div>
        <div class="tc-m"><?= h($t['sname']) ?> · <?= h($t['tname']) ?></div>
        <div class="tc-tags">
          <?php if($t['difficulty']==0): ?><span class="badge bb">🔀 Aralash</span>
          <?php else: ?><span class="badge <?= ['','be','bm','bh'][$t['difficulty']] ?>"><?= diffLabel($t['difficulty']) ?></span>
          <?php endif; ?>
          <span class="badge bb">❓ <?= $t['question_count'] ?></span>
          <span class="badge bt">⏱ <?= $totalMin ?>d</span>
          <span class="badge" style="background:rgba(218,119,242,.1);color:var(--purple);border:1px solid rgba(218,119,242,.2)">⭐ <?= $max ?></span>
        </div>
        <?php if($hasA&&$t['apct']!==null): ?>
        <div style="margin-top:10px">
          <div style="display:flex;justify-content:space-between;margin-bottom:3px;font-size:10px;color:var(--amber)">
            <span>Tugallanmagan</span><span style="font-family:'JetBrains Mono',monospace"><?= $t['apct'] ?>%</span>
          </div>
          <div class="pb"><div class="pf" style="width:<?= $t['apct'] ?>%;background:var(--amber)"></div></div>
        </div>
        <?php endif; ?>
        <?php if($t['lscore']!==null): ?>
        <div style="margin-top:8px;padding:6px 10px;background:rgba(0,212,170,.06);border:1px solid rgba(0,212,170,.18);border-radius:7px;font-size:11px;color:var(--teal)">
          ✓ Oxirgi: <strong><?= round($t['lscore'],1) ?>/<?= $max ?> ball</strong>
        </div>
        <?php endif; ?>
      </div>
      <div class="tc-foot">
        <?php if($hasA): ?>
        <a href="test.php?session=<?= $t['asid'] ?>" class="btn btn-t btn-fw">▶ Davom ettirish</a>
        <?php elseif($t['qavail']>=$t['question_count']): ?>
        <a href="test_start.php?test=<?= $t['id'] ?>" class="btn btn-p btn-fw">🚀 Testni boshlash</a>
        <?php else: ?>
        <div class="btn btn-g btn-fw" style="opacity:.45;cursor:not-allowed">🔒 Yetarli savol yo'q</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ══ NATIJALAR ══ -->
<div class="page" id="p-results">
  <div class="tb"><div class="tb-left"><h1>📊 Natijalarim</h1><p>Barcha test natijalari</p></div></div>
  <?php
  $allR=db()->prepare("SELECT ts.*,t.title,t.question_count,t.points_per_q,s.name sn,s.icon si,u.name tn FROM test_sessions ts JOIN tests t ON t.id=ts.test_id JOIN subjects s ON s.id=t.subject_id JOIN users u ON u.id=t.teacher_id WHERE ts.student_id=? ORDER BY ts.id DESC");
  $allR->execute([$me['id']]); $allR=$allR->fetchAll();
  ?>
  <?php if(empty($allR)): ?>
  <div class="card"><div class="empty"><div class="ei">📭</div><div class="et">Hali birorta test topshirilmagan</div></div></div>
  <?php else: ?>
  <div class="card" style="overflow:hidden;padding:0">
    <table class="tbl">
      <thead><tr><th>Test</th><th>Fan</th><th>Ball</th><th>Baho</th><th>Status</th><th>Vaqt</th><th></th></tr></thead>
      <tbody>
      <?php foreach($allR as $r):
        $max=$r['question_count']*$r['points_per_q'];
        $pct=$max>0?round($r['score']/$max*100):0;
        $g=grade($pct);$gc=gradeColor($g);
        $scfg=['active'=>['var(--amber)','▶ Davom'],'completed'=>['var(--success)','✓ Yakunlangan'],'expired'=>['var(--danger)','⏰ Vaqt tugadi']];
        [$sc,$sl]=$scfg[$r['status']]??['var(--text3)','?'];
      ?>
      <tr>
        <td style="font-weight:500;color:var(--text);font-size:12px"><?= h($r['title']) ?></td>
        <td><?= h($r['si'].' '.$r['sn']) ?></td>
        <td><?php if($r['status']!=='active'): ?><span style="font-family:'JetBrains Mono',monospace;color:var(--accent2)"><?= round($r['score'],1) ?>/<?= $max ?></span><?php else: ?>—<?php endif; ?></td>
        <td><?php if($r['status']!=='active'): ?><span style="font-family:'JetBrains Mono',monospace;font-weight:700;color:<?= $gc ?>"><?= $g ?></span><?php else: ?>—<?php endif; ?></td>
        <td><span style="color:<?= $sc ?>;font-size:11px;font-weight:600"><?= $sl ?></span></td>
        <td style="color:var(--text3);font-size:11px"><?= ago($r['started_at']) ?></td>
        <td>
          <?php if($r['status']==='active'): ?><a href="test.php?session=<?= $r['id'] ?>" class="btn btn-t" style="padding:5px 10px;font-size:11px">Davom</a>
          <?php elseif($r['status']!=='active'): ?><a href="result.php?session=<?= $r['id'] ?>" class="btn btn-g" style="padding:5px 10px;font-size:11px">Ko'rish</a><?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ══ RIVOJLANISH ══ -->
<div class="page" id="p-progress">
  <div class="tb"><div class="tb-left"><h1>📈 Rivojlanish</h1><p>Bilim darajangiz dinamikasi</p></div></div>
  <div class="g2" style="margin-bottom:16px">
    <div class="card">
      <div class="ch"><div><div class="ct">Ball dinamikasi</div><div class="cs">Har bir test sessiyasi</div></div></div>
      <div class="chart-wrap" style="height:240px"><canvas id="histChart2"></canvas></div>
    </div>
    <div class="card">
      <div class="ch"><div class="ct">Fanlar bo'yicha</div></div>
      <div class="chart-wrap" style="height:240px"><canvas id="radChart"></canvas></div>
    </div>
  </div>
  <?php if(!empty($fanStats)): ?>
  <div class="card">
    <div class="ch"><div class="ct">Fanlar bo'yicha tahlil</div></div>
    <div style="display:flex;flex-direction:column;gap:11px">
      <?php foreach($fanStats as $fs):
        $fp=round($fs['pct'],0);
        $bc=$fp>=70?'var(--success)':($fp>=50?'var(--amber)':'var(--danger)');
      ?>
      <div style="display:flex;align-items:center;gap:12px">
        <span style="font-size:18px;width:24px"><?= h($fs['icon']) ?></span>
        <div style="flex:1">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px">
            <span style="font-size:12px;font-weight:500"><?= h($fs['name']) ?></span>
            <span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:<?= $bc ?>"><?= $fp ?>%</span>
          </div>
          <div class="pb" style="height:4px"><div class="pf" style="width:<?= $fp ?>%;background:<?= $bc ?>"></div></div>
        </div>
        <span style="font-size:10px;color:var(--text3);min-width:36px"><?= $fs['cnt'] ?> test</span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

</main>
</div>

<script>
// NAV
function gp(id){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('on'));
  document.querySelectorAll('.ni[id^="n-"]').forEach(n=>n.classList.remove('on'));
  const pg=document.getElementById('p-'+id);
  if(pg) pg.classList.add('on');
  const ni=document.getElementById('n-'+id);
  if(ni) ni.classList.add('on');
  if(id==='dash'||id==='progress') setTimeout(drawCharts,80);
}

// TEMA
function toggleTheme(){
  const h=document.documentElement,n=h.dataset.theme==='dark'?'light':'dark';
  h.dataset.theme=n;document.cookie=`theme=${n};path=/;max-age=31536000`;
  document.querySelectorAll('#tIco,.tp').forEach(el=>el.textContent=n==='dark'?'☀️':'🌙');
  if(n==='light') document.querySelectorAll('.tp').forEach(el=>el.textContent='🌙');
  else document.querySelectorAll('.tp').forEach(el=>el.textContent='☀');
}

// CHARTS
<?php
$hLabels=[]; $hData=[];
foreach($history as $r){
  $max=$r['question_count']*$r['points_per_q'];
  $hLabels[]=$r['sname'];
  $hData[]=$max>0?round($r['score']/$max*100,1):0;
}
$fLabels=array_column($fanStats,'name');
$fData=array_map(fn($f)=>round($f['pct'],0),$fanStats);
?>
const hL=<?= json_encode($hLabels) ?>;
const hD=<?= json_encode($hData) ?>;
const fL=<?= json_encode($fLabels) ?>;
const fD=<?= json_encode($fData) ?>;

let drawn=false;
function drawCharts(){
  if(drawn) return; drawn=true;
  const gc='rgba(99,140,255,.07)';
  const tc='#4d6494';
  const fn={family:"'JetBrains Mono',monospace",size:10};

  ['histChart','histChart2'].forEach(id=>{
    const c=document.getElementById(id);
    if(!c||c._ch) return;
    c._ch=new Chart(c,{type:'line',data:{
      labels:hL.length?hL:['—'],
      datasets:[{label:'%',data:hD.length?hD:[0],borderColor:'#4f7cff',backgroundColor:'rgba(79,124,255,.07)',borderWidth:2,fill:true,pointBackgroundColor:'#4f7cff',pointRadius:3,tension:0.4}]
    },options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
      scales:{y:{min:0,max:100,grid:{color:gc},ticks:{color:tc,font:fn,callback:v=>v+'%'}},
      x:{grid:{display:false},ticks:{color:tc,font:fn}}}}});
  });

  const c2=document.getElementById('radChart');
  if(c2&&!c2._ch&&fL.length){
    c2._ch=new Chart(c2,{type:'radar',data:{
      labels:fL,datasets:[{label:'%',data:fD,borderColor:'#4f7cff',backgroundColor:'rgba(79,124,255,.1)',pointBackgroundColor:'#4f7cff',borderWidth:2}]
    },options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
      scales:{r:{min:0,max:100,grid:{color:gc},ticks:{color:tc,font:fn{size:9},backdropColor:'transparent'},
      pointLabels:{color:'#8fa3cc',font:{size:fn10}}}}}});
  }
}

setTimeout(drawCharts,200);
setTimeout(()=>document.querySelectorAll('.toast').forEach(t=>{t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(()=>t.remove(),400);}),3500);
</script>
</body></html>
