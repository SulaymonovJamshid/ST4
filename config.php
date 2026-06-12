<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'smarttest4');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('SITE_URL',   'http://localhost/st4');

define('GOOGLE_CLIENT_ID',     '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT',      SITE_URL.'/auth_google.php');

if(session_status()===PHP_SESSION_NONE) session_start();

function db():PDO {
    static $p=null;
    if(!$p){
        try{
            $p=new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",DB_USER,DB_PASS,[
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=>false
            ]);
        }catch(PDOException $e){
            die('<div style="font-family:monospace;padding:30px;color:#f87171;background:#0a0e1a;min-height:100vh">
                <h2>❌ DB ulanmadi</h2><p>'.h($e->getMessage()).'</p>
                <p style="color:#4d6494;margin-top:12px">config.php da DB_PASS ni tekshiring.</p></div>');
        }
    }
    return $p;
}

function me():?array{
    if(empty($_SESSION['uid'])) return null;
    static $u=null;
    if(!$u){$s=db()->prepare("SELECT * FROM users WHERE id=?");$s->execute([$_SESSION['uid']]);$u=$s->fetch()?:null;}
    return $u;
}
function isLoggedIn():bool{return!empty($_SESSION['uid']);}
function isTeacher():bool{$u=me();return$u&&$u['role']==='teacher';}
function isStudent():bool{$u=me();return$u&&$u['role']==='student';}
function isAdmin():bool{$u=me();return$u&&$u['role']==='admin';}
function requireLogin():void{if(!isLoggedIn()){flash('error','Kirish talab etiladi.');redirect((defined('ROOT')?ROOT:'').'login.php');}}
function requireRole(string $r):void{requireLogin();$u=me();if(!$u||$u['role']!==$r)redirect((defined('ROOT')?ROOT:'').'index.php');}

function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function redirect(string $u):void{header("Location:$u");exit;}
function flash(string $k,string $m):void{$_SESSION['flash'][$k]=$m;}
function getFlash(string $k):string{$m=$_SESSION['flash'][$k]??'';unset($_SESSION['flash'][$k]);return $m;}
function token():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(16));return$_SESSION['csrf'];}
function checkToken():bool{return isset($_POST['_token'])&&hash_equals($_SESSION['csrf']??'',$_POST['_token']);}
function theme():string{return$_COOKIE['theme']??'dark';}
function ago(string $dt):string{
    $s=time()-strtotime($dt);
    if($s<60)return'Hozirgina';if($s<3600)return floor($s/60).' daq oldin';
    if($s<86400)return floor($s/3600).' soat oldin';return date('d.m.Y',strtotime($dt));
}
function grade(float $p):string{
    if($p>=90)return'A+';if($p>=80)return'A';if($p>=70)return'B';
    if($p>=60)return'C';if($p>=50)return'D';return'F';
}
function gradeColor(string $g):string{
    return match($g){'A+','A'=>'#51cf66','B'=>'#4f7cff','C','D'=>'#ffb347',default=>'#ff6b6b'};
}
function diffLabel(int $d):string{return match($d){1=>'Oson',2=>"O'rta",3=>'Qiyin',default=>'Aralash'};}
function diffBadge(int $d):string{
    $cls=['','badge-easy','badge-medium','badge-hard'][$d]??'badge-blue';
    return '<span class="badge '.$cls.'">'.diffLabel($d).'</span>';
}

// Barcha fanlar ro'yxati (maktab, universitet, magistratura)
function getAllSubjectsList():array{
    return [
        'school'=>[
            'label'=>'🏫 Maktab (1–11 sinf)',
            'items'=>[
                ['id'=>'matematika','name'=>'Matematika','icon'=>'➕','color'=>'#4f7cff'],
                ['id'=>'algebra','name'=>'Algebra','icon'=>'📐','color'=>'#4f7cff'],
                ['id'=>'geometriya','name'=>'Geometriya','icon'=>'📏','color'=>'#4f7cff'],
                ['id'=>'fizika','name'=>'Fizika','icon'=>'⚛️','color'=>'#00d4aa'],
                ['id'=>'kimyo','name'=>'Kimyo','icon'=>'🧪','color'=>'#ffb347'],
                ['id'=>'biologiya','name'=>'Biologiya','icon'=>'🧬','color'=>'#da77f2'],
                ['id'=>'tarix','name'=>'Tarix','icon'=>'📜','color'=>'#ff9a9a'],
                ['id'=>'geografiya','name'=>'Geografiya','icon'=>'🌍','color'=>'#51cf66'],
                ['id'=>'uzbek_tili','name'=>"O'zbek tili",'icon'=>'🇺🇿','color'=>'#4f7cff'],
                ['id'=>'adabiyot','name'=>'Adabiyot','icon'=>'📖','color'=>'#da77f2'],
                ['id'=>'ingliz_tili','name'=>'Ingliz tili','icon'=>'🇬🇧','color'=>'#00d4aa'],
                ['id'=>'rus_tili','name'=>'Rus tili','icon'=>'🇷🇺','color'=>'#ff9a9a'],
                ['id'=>'informatika','name'=>'Informatika','icon'=>'💻','color'=>'#4f7cff'],
                ['id'=>'ona_tili','name'=>"Ona tili",'icon'=>'✍️','color'=>'#ffb347'],
                ['id'=>'jismoniy','name'=>"Jismoniy tarbiya",'icon'=>'🏃','color'=>'#51cf66'],
                ['id'=>'tasviriy','name'=>"Tasviriy san'at",'icon'=>'🎨','color'=>'#da77f2'],
                ['id'=>'musiqa','name'=>"Musiqa",'icon'=>'🎵','color'=>'#00d4aa'],
                ['id'=>'texnologiya','name'=>"Texnologiya",'icon'=>'🔨','color'=>'#ffb347'],
            ]
        ],
        'university'=>[
            'label'=>'🎓 Oliy ta\'lim (Bakalavriat)',
            'items'=>[
                ['id'=>'oliy_matematika','name'=>'Oliy matematika','icon'=>'∑','color'=>'#4f7cff'],
                ['id'=>'chiziqli_algebra','name'=>'Chiziqli algebra','icon'=>'⊗','color'=>'#4f7cff'],
                ['id'=>'matematik_analiz','name'=>'Matematik analiz','icon'=>'∫','color'=>'#4f7cff'],
                ['id'=>'diskret_matematika','name'=>'Diskret matematika','icon'=>'🔢','color'=>'#00d4aa'],
                ['id'=>'ehtimollar','name'=>"Ehtimollar nazariyasi",'icon'=>'🎲','color'=>'#da77f2'],
                ['id'=>'dasturlash','name'=>'Dasturlash asoslari','icon'=>'🖥️','color'=>'#4f7cff'],
                ['id'=>'malumotlar_tuzilmasi','name'=>"Ma'lumotlar tuzilmasi",'icon'=>'🗂️','color'=>'#00d4aa'],
                ['id'=>'algoritmlar','name'=>'Algoritmlar','icon'=>'⚙️','color'=>'#ffb347'],
                ['id'=>'malumotlar_bazasi','name'=>"Ma'lumotlar bazasi",'icon'=>'🗃️','color'=>'#00d4aa'],
                ['id'=>'tarmoqlar','name'=>'Kompyuter tarmoqlari','icon'=>'🌐','color'=>'#51cf66'],
                ['id'=>'iqtisodiyot','name'=>'Iqtisodiyot','icon'=>'📈','color'=>'#ffb347'],
                ['id'=>'menejment','name'=>'Menejment','icon'=>'📊','color'=>'#ffb347'],
                ['id'=>'marketing','name'=>'Marketing','icon'=>'🛒','color'=>'#ff9a9a'],
                ['id'=>'buxgalteriya','name'=>'Buxgalteriya','icon'=>'💰','color'=>'#51cf66'],
                ['id'=>'huquq','name'=>'Huquq asoslari','icon'=>'⚖️','color'=>'#ff9a9a'],
                ['id'=>'psixologiya','name'=>'Psixologiya','icon'=>'🧠','color'=>'#da77f2'],
                ['id'=>'pedagogika','name'=>'Pedagogika','icon'=>'📚','color'=>'#da77f2'],
                ['id'=>'muhandislik','name'=>'Muhandislik asoslari','icon'=>'🔧','color'=>'#00d4aa'],
                ['id'=>'umumiy_fizika','name'=>'Umumiy fizika','icon'=>'⚡','color'=>'#00d4aa'],
                ['id'=>'umumiy_kimyo','name'=>'Umumiy kimyo','icon'=>'⚗️','color'=>'#ffb347'],
                ['id'=>'tibbiyot','name'=>'Tibbiyot asoslari','icon'=>'🏥','color'=>'#ff9a9a'],
                ['id'=>'arxitektura','name'=>'Arxitektura','icon'=>'🏛️','color'=>'#da77f2'],
            ]
        ],
        'masters'=>[
            'label'=>'🎓 Magistratura va ilmiy soha',
            'items'=>[
                ['id'=>'machine_learning','name'=>'Machine Learning','icon'=>'🤖','color'=>'#4f7cff'],
                ['id'=>'deep_learning','name'=>'Deep Learning','icon'=>'🧠','color'=>'#da77f2'],
                ['id'=>'data_science','name'=>'Data Science','icon'=>'📊','color'=>'#00d4aa'],
                ['id'=>'sun_intellekt','name'=>"Sun'iy intellekt",'icon'=>'✨','color'=>'#4f7cff'],
                ['id'=>'kiberxavfsizlik','name'=>'Kiberxavfsizlik','icon'=>'🔐','color'=>'#ff6b6b'],
                ['id'=>'cloud_computing','name'=>'Cloud Computing','icon'=>'☁️','color'=>'#51cf66'],
                ['id'=>'blockchain','name'=>'Blockchain','icon'=>'⛓️','color'=>'#ffb347'],
                ['id'=>'tadqiqot_metodlari','name'=>'Ilmiy tadqiqot metodlari','icon'=>'🔬','color'=>'#da77f2'],
                ['id'=>'dissertatsiya','name'=>'Dissertatsiya yozish','icon'=>'📝','color'=>'#4f7cff'],
                ['id'=>'moliyaviy_menj','name'=>'Moliyaviy menejment','icon'=>'💹','color'=>'#ffb347'],
                ['id'=>'strategik_menj','name'=>'Strategik menejment','icon'=>'🗺️','color'=>'#ffb347'],
                ['id'=>'loyiha_menj','name'=>'Loyiha menejment','icon'=>'📋','color'=>'#51cf66'],
                ['id'=>'davlat_boshqaruv','name'=>'Davlat boshqaruvi','icon'=>'🏛️','color'=>'#ff9a9a'],
                ['id'=>'xalqaro_huquq','name'=>'Xalqaro huquq','icon'=>'🌐','color'=>'#ff9a9a'],
                ['id'=>'makroiqtisodiyot','name'=>'Makroiqtisodiyot','icon'=>'📉','color'=>'#ffb347'],
                ['id'=>'nanomateriallar','name'=>'Nanomateriallar','icon'=>'⚛️','color'=>'#00d4aa'],
                ['id'=>'biomuhandislik','name'=>'Biotibbiy muhandislik','icon'=>'🧬','color'=>'#da77f2'],
                ['id'=>'energetika','name'=>'Energetika va ekologiya','icon'=>'🌱','color'=>'#51cf66'],
            ]
        ]
    ];
}
