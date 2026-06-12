<?php
require_once '../config.php';
define('ROOT','../');
requireRole('admin');
$me=me();
if($_SERVER['REQUEST_METHOD']==='POST'&&checkToken()){
    $uid=(int)($_POST['user_id']??0);$act=$_POST['action']??'';
    if($act==='role'&&$uid!==$me['id']){
        $r=in_array($_POST['role']??'',['student','teacher','admin'])?$_POST['role']:'student';
        db()->prepare("UPDATE users SET role=? WHERE id=?")->execute([$r,$uid]);
        flash('success','Rol o\'zgartirildi.');
    }
    if($act==='delete'&&$uid!==$me['id']){
        db()->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
        flash('success','Foydalanuvchi o\'chirildi.');
    }
    redirect('users.php');
}
$filter=$_GET['role']??'';$search=trim($_GET['q']??'');
$where="WHERE id!=".$me['id'];$params=[];
if($filter&&in_array($filter,['student','teacher','admin'])){$where.=' AND role=?';$params[]=$filter;}
if($search){$where.=' AND (name LIKE ? OR email LIKE ?)';$params[]="%$search%";$params[]="%$search%";}
$users=db()->prepare("SELECT u.*,(SELECT COUNT(*) FROM test_sessions ts WHERE ts.student_id=u.id) tc,(SELECT COUNT(*) FROM tests t WHERE t.teacher_id=u.id) tt FROM users u $where ORDER BY u.id DESC");
$users->execute($params);$users=$users->fetchAll();
$pageTitle='Foydalanuvchilar';
include '../includes/ui.php';
?>
<div class="page-wrap">
  <div class="topbar">
    <div><div class="page-title">👥 Foydalanuvchilar</div><div class="page-sub">Jami <?= count($users) ?> ta</div></div>
    <a href="index.php" class="btn btn-g">← Dashboard</a>
  </div>
  <form method="GET" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <div style="position:relative;flex:1;min-width:180px;max-width:280px">
      <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text3)">🔍</span>
      <input type="text" name="q" class="fi" style="padding-left:32px" placeholder="Ism yoki email..." value="<?= h($search) ?>">
    </div>
    <select name="role" class="fs" style="width:auto" onchange="this.form.submit()">
      <option value="">Barcha</option>
      <option value="student" <?= $filter==='student'?'selected':'' ?>>🎓 O'quvchi</option>
      <option value="teacher" <?= $filter==='teacher'?'selected':'' ?>>👨‍🏫 O'qituvchi</option>
      <option value="admin" <?= $filter==='admin'?'selected':'' ?>>⚙️ Admin</option>
    </select>
    <button type="submit" class="btn btn-p btn-xs">Qidirish</button>
    <?php if($filter||$search): ?><a href="users.php" class="btn btn-g btn-xs">✕</a><?php endif; ?>
  </form>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Foydalanuvchi</th><th>Rol</th><th>Statistika</th><th>Kirish</th><th>Sana</th><th></th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
      <tr>
        <td>
          <div class="flex items-c gap-2">
            <?php if($u['avatar']): ?><img src="<?= h($u['avatar']) ?>" style="width:32px;height:32px;border-radius:8px;object-fit:cover">
            <?php else: ?><div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,var(--accent),var(--teal));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0"><?= strtoupper(mb_substr($u['name'],0,2)) ?></div><?php endif; ?>
            <div><div style="font-weight:600;font-size:13px"><?= h($u['name']) ?></div><div class="c-dim text-xs"><?= h($u['email']) ?></div></div>
          </div>
        </td>
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="_token" value="<?= token() ?>">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <input type="hidden" name="action" value="role">
            <select name="role" onchange="this.form.submit()" class="fs" style="padding:4px 8px;font-size:11px;font-weight:700;width:auto">
              <option value="student" <?= $u['role']==='student'?'selected':'' ?>>🎓 O'quvchi</option>
              <option value="teacher" <?= $u['role']==='teacher'?'selected':'' ?>>👨‍🏫 O'qituvchi</option>
              <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>⚙️ Admin</option>
            </select>
          </form>
        </td>
        <td>
          <?php if($u['role']==='student'): ?><span class="text-xs"><?= $u['tc'] ?> test</span>
          <?php elseif($u['role']==='teacher'): ?><span class="text-xs"><?= $u['tt'] ?> test yaratgan</span>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td><?php if($u['google_id']): ?><span class="badge badge-blue">Google</span><?php else: ?><span class="badge" style="background:var(--bg3);color:var(--text3);border:1px solid var(--border)">Email</span><?php endif; ?></td>
        <td class="c-dim text-xs"><?= date('d.m.Y',strtotime($u['created_at'])) ?></td>
        <td>
          <form method="POST" onsubmit="return confirm('O\'chirasizmi?')">
            <input type="hidden" name="_token" value="<?= token() ?>">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-d btn-xs">🗑</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($users)): ?><tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text3)">Topilmadi</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/foot.php'; ?>
