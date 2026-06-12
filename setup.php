<?php
if(file_exists(__DIR__.'/installed.lock')){
    header('Location: index.php'); exit;
}
define('DB_HOST','localhost'); define('DB_NAME','smarttest4');
define('DB_USER','root');      define('DB_PASS','');

try {
    $pdo=new PDO("mysql:host=".DB_HOST.";charset=utf8mb4",DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `".DB_NAME."`");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    foreach(['test_sessions','questions','tests','subjects','users'] as $t) $pdo->exec("DROP TABLE IF EXISTS `$t`");

    $pdo->exec("CREATE TABLE users(
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        email VARCHAR(200) NOT NULL UNIQUE,
        password VARCHAR(255),
        role ENUM('admin','teacher','student') NOT NULL DEFAULT 'student',
        google_id VARCHAR(100),
        avatar VARCHAR(255),
        teacher_subjects TEXT COMMENT 'JSON: tanlangan fanlar id lari',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE subjects(
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT UNSIGNED NOT NULL,
        subject_key VARCHAR(80) NOT NULL COMMENT 'config dan fan id',
        name VARCHAR(150) NOT NULL,
        icon VARCHAR(20) DEFAULT '📚',
        level ENUM('school','university','masters') NOT NULL DEFAULT 'school',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(teacher_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE tests(
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT UNSIGNED NOT NULL,
        subject_id INT UNSIGNED NOT NULL,
        title VARCHAR(200) NOT NULL,
        difficulty TINYINT UNSIGNED NOT NULL DEFAULT 1,
        question_count TINYINT UNSIGNED NOT NULL DEFAULT 10,
        points_per_q TINYINT UNSIGNED NOT NULL DEFAULT 1,
        time_per_q TINYINT UNSIGNED NOT NULL DEFAULT 1,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE questions(
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        subject_id INT UNSIGNED NOT NULL,
        teacher_id INT UNSIGNED NOT NULL,
        question_text TEXT NOT NULL,
        option_a TEXT NOT NULL,
        option_b TEXT NOT NULL,
        option_c TEXT NOT NULL,
        option_d TEXT NOT NULL,
        correct_option CHAR(1) NOT NULL,
        difficulty TINYINT UNSIGNED NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY(teacher_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE test_sessions(
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        student_id INT UNSIGNED NOT NULL,
        test_id INT UNSIGNED NOT NULL,
        question_ids TEXT NOT NULL,
        current_index TINYINT UNSIGNED NOT NULL DEFAULT 0,
        answers TEXT,
        score FLOAT DEFAULT 0,
        status ENUM('active','completed','expired') DEFAULT 'active',
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        finished_at TIMESTAMP NULL DEFAULT NULL,
        expires_at TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(test_id) REFERENCES tests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    // Admin
    $ah=password_hash('admin123',PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users(name,email,password,role)VALUES(?,?,?,?)")->execute(['Administrator','admin@smarttest.uz',$ah,'admin']);

    // Demo o'qituvchi (parol: teacher123) — Matematika va Fizika fanlari
    $th=password_hash('teacher123',PASSWORD_DEFAULT);
    $teacherSubjects=json_encode(['matematika','fizika','informatika']);
    $pdo->prepare("INSERT INTO users(name,email,password,role,teacher_subjects)VALUES(?,?,?,?,?)")
        ->execute(["Demo O'qituvchi",'teacher@smarttest.uz',$th,'teacher',$teacherSubjects]);
    $tid=$pdo->lastInsertId();

    // Demo o'quvchi (parol: student123)
    $sh=password_hash('student123',PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users(name,email,password,role)VALUES(?,?,?,?)")->execute(["Demo O'quvchi",'student@smarttest.uz',$sh,'student']);

    // Fanlar
    $pdo->prepare("INSERT INTO subjects(teacher_id,subject_key,name,icon,level,description)VALUES(?,?,?,?,?,?)")
        ->execute([$tid,'matematika','Matematika','➕','school','Algebra, geometriya va statistika']);
    $mid=$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO subjects(teacher_id,subject_key,name,icon,level,description)VALUES(?,?,?,?,?,?)")
        ->execute([$tid,'fizika','Fizika','⚛️','school','Mexanika va termodinamika']);
    $fid=$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO subjects(teacher_id,subject_key,name,icon,level,description)VALUES(?,?,?,?,?,?)")
        ->execute([$tid,'informatika','Informatika','💻','university','Algoritmlar va dasturlash']);
    $csid=$pdo->lastInsertId();

    // Testlar
    $pdo->prepare("INSERT INTO tests(teacher_id,subject_id,title,difficulty,question_count,points_per_q,time_per_q)VALUES(?,?,?,?,?,?,?)")
        ->execute([$tid,$mid,"Matematika — Boshlang'ich",1,10,2,1]);
    $pdo->prepare("INSERT INTO tests(teacher_id,subject_id,title,difficulty,question_count,points_per_q,time_per_q)VALUES(?,?,?,?,?,?,?)")
        ->execute([$tid,$mid,'Matematika — Aralash',0,12,3,1]);
    $pdo->prepare("INSERT INTO tests(teacher_id,subject_id,title,difficulty,question_count,points_per_q,time_per_q)VALUES(?,?,?,?,?,?,?)")
        ->execute([$tid,$csid,'Informatika asoslari',1,10,2,1]);

    // Demo savollar
    $q=$pdo->prepare("INSERT INTO questions(subject_id,teacher_id,question_text,option_a,option_b,option_c,option_d,correct_option,difficulty)VALUES(?,?,?,?,?,?,?,?,?)");
    $qs=[
        // Matematika oson
        [$mid,$tid,'2 + 2 = ?','3','4','5','6','b',1],
        [$mid,$tid,'5 × 6 = ?','28','30','32','36','b',1],
        [$mid,$tid,'100 - 37 = ?','63','67','73','57','a',1],
        [$mid,$tid,'81 ning kvadrat ildizi?','7','8','9','10','c',1],
        [$mid,$tid,"To'g'ri burchak necha gradus?",'45','60','90','180','c',1],
        [$mid,$tid,'1 km = ? metr','10','100','1000','10000','c',1],
        [$mid,$tid,'2³ = ?','6','8','9','12','b',1],
        [$mid,$tid,'Doira yuzasi formulasi?','πr','2πr','πr²','2πr²','c',1],
        [$mid,$tid,"Agar x+5=12 bo'lsa, x=?",'6','7','8','17','b',1],
        [$mid,$tid,'0.5 kasrga tengmi?','1/4','1/3','1/2','2/3','c',1],
        // Matematika o'rta
        [$mid,$tid,'x²-5x+6=0 ildizlari?','x=1,6','x=2,3','x=-2,-3','x=1,5','b',2],
        [$mid,$tid,'sin(30°) = ?','√3/2','1/2','√2/2','1','b',2],
        [$mid,$tid,'log₂(8) = ?','2','3','4','8','b',2],
        [$mid,$tid,'f(x)=2x+1 da f(3)=?','5','6','7','8','c',2],
        [$mid,$tid,'cos(60°) = ?','1','√3/2','1/2','0','c',2],
        [$mid,$tid,'5! = ?','60','100','120','150','c',2],
        [$mid,$tid,'C(5,2) = ?','5','8','10','20','c',2],
        [$mid,$tid,'∫x dx = ?','x+C','x²/2+C','2x+C','x²+C','b',2],
        [$mid,$tid,'2x+3y=12, x=3 da y=?','1','2','3','4','b',2],
        [$mid,$tid,'Parabola tepasi x=-b/2a — bu qaysi formula?','Vertex','Discriminant','Newton','Vieta','a',2],
        // Matematika qiyin
        [$mid,$tid,'e^(iπ)+1 = ?','2','-1','0','i','c',3],
        [$mid,$tid,'e^x Taylor qatorida?','Σxⁿ/n!','Σ(-1)ⁿxⁿ/n!','Σxⁿ','Σnxⁿ','a',3],
        [$mid,$tid,'|z| = ? (z=a+bi)','a+b','a²+b²','√(a²+b²)','√(a+b)','c',3],
        [$mid,$tid,'d(uv)/dx = ?','du·dv/dx','u·dv/dx+v·du/dx','u/v·dv','du+dv','b',3],
        [$mid,$tid,'Stokes teoremasi nimani bog\'laydi?','Chiziqli va sirt integral','Ikki va uch karra integral','Differensial va integral','Matritsa va vektor','a',3],
        // Fizika
        [$fid,$tid,'Yorug\'lik tezligi?','3×10⁶','3×10⁸ m/s','3×10¹⁰','3×10⁴','b',1],
        [$fid,$tid,'F=mg — bu nima?','Kinetik E','Og\'irlik kuchi','Impuls','Quvvat','b',1],
        [$fid,$tid,'1 Joule nima?','kg·m/s','kg·m²/s²','kg/m²','kg·m²/s','b',1],
        [$fid,$tid,'Om qonuni: U=?','IR','I/R','R/I','I+R','a',1],
        [$fid,$tid,'Arximed qonuni nima haqida?','Elektr','Suyuqlikda suzish','Yorug\'lik','Magnit','b',1],
        [$fid,$tid,'Guk qonuni: F=?','ma','kx','mg','mv','b',2],
        [$fid,$tid,'½mv² — bu nima?','Potensial E','Kinetik E','Impuls','Ish','b',2],
        [$fid,$tid,'Fotoeffekt: E=?','mc²','hf','kT','qV','b',2],
        [$fid,$tid,"Termodinamika 1-qonuni?",'Entropiya ortadi','ΔU=Q-W','E yo\'qoladi','T=0','b',2],
        [$fid,$tid,'Shrödinger tenglamasi?','Klassik mexanika','Kvant holat evolyutsiyasi','Nisbiylik','Termodinamika','b',3],
        // Informatika
        [$csid,$tid,'CPU nima?','Central Processing Unit','Computer Power Unit','Central Power Unit','Core Processing Unit','a',1],
        [$csid,$tid,'Binary 1010 = ?','8','10','12','16','b',1],
        [$csid,$tid,'RAM nima?','Random Access Memory','Read Access Memory','Rapid Access Memory','Remote Access Memory','a',1],
        [$csid,$tid,'Stack tamoyili?','FIFO','LIFO','Random','Priority','b',1],
        [$csid,$tid,'HTTP 404 nima?','Server xatosi','Sahifa topilmadi','Ruxsat yo\'q','OK','b',1],
        [$csid,$tid,'Bubble sort worst case?','O(n)','O(n log n)','O(n²)','O(log n)','c',2],
        [$csid,$tid,'OOP encapsulation nima?','Meros','Ma\'lumot yashirish','Polimorfizm','Abstraksiya','b',2],
        [$csid,$tid,'TCP vs UDP?','TCP ishonchli UDP tezroq','UDP ishonchli','Bir xil','TCP tezroq','a',2],
        [$csid,$tid,'CAP teoremasi?','Uchtalasi mumkin emas','Uchtalasi ta\'minlanadi','Ikki kerak','Tarqatilgan emas','a',3],
        [$csid,$tid,'P vs NP da P?','Polynomial vaqtda hal','Probabilistik','Parallel','Primitive','a',3],
    ];
    foreach($qs as $row) $q->execute($row);

    file_put_contents(__DIR__.'/installed.lock',date('Y-m-d H:i:s'));

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:system-ui;background:#0a0e1a;color:#e8edff;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
    .box{background:#0f1526;border:1px solid rgba(99,140,255,.2);border-radius:18px;padding:36px;max-width:520px;width:100%}
    h1{font-size:22px;font-weight:700;color:#51cf66;margin-bottom:6px}
    p{font-size:13px;color:#8fa3cc;margin-top:6px;line-height:1.6}
    .row{display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid rgba(99,140,255,.1);font-size:13px}
    .ok{color:#51cf66;font-weight:600}
    .cred{background:#141c30;border:1px solid rgba(99,140,255,.15);border-radius:10px;padding:14px;margin-top:18px;font-size:12px}
    .cred div{padding:3px 0;color:#8fa3cc}.cred strong{color:#7b9fff}
    .warn{background:rgba(255,179,71,.08);border:1px solid rgba(255,179,71,.2);border-radius:9px;padding:10px 14px;margin-top:12px;color:#ffb347;font-size:12px}
    .btns{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:20px}
    a.b{display:block;padding:11px;border-radius:9px;text-decoration:none;font-weight:600;font-size:13px;text-align:center}
    .bp{background:#4f7cff;color:#fff}.bs{background:#141c30;color:#8fa3cc;border:1px solid rgba(99,140,255,.2)}
    </style></head><body><div class="box">
    <h1>✅ O\'rnatish muvaffaqiyatli!</h1>
    <div style="margin-top:18px">
        <div class="row"><span class="ok">✓</span> <b>smarttest4</b> database yaratildi</div>
        <div class="row"><span class="ok">✓</span> 5 ta jadval yaratildi</div>
        <div class="row"><span class="ok">✓</span> 3 ta demo foydalanuvchi</div>
        <div class="row"><span class="ok">✓</span> 3 ta fan, 3 ta test, '.count($qs).' ta savol</div>
    </div>
    <div class="cred">
        <div>⚙️ <strong>Admin:</strong> admin@smarttest.uz / <strong style="color:#51cf66">admin123</strong></div>
        <div>👨‍🏫 <strong>O\'qituvchi:</strong> teacher@smarttest.uz / <strong style="color:#51cf66">teacher123</strong></div>
        <div>🎓 <strong>O\'quvchi:</strong> student@smarttest.uz / <strong style="color:#51cf66">student123</strong></div>
    </div>
    <div class="warn">⚠️ Xavfsizlik uchun <b>setup.php</b> ni o\'chirib tashlang!</div>
    <div class="btns">
        <a href="index.php" class="b bp">→ Saytga o\'tish</a>
        <a href="login.php" class="b bs">🔐 Kirish</a>
    </div></div></body></html>';
}catch(PDOException $e){
    echo '<div style="font-family:monospace;padding:40px;color:#ff6b6b;background:#0a0e1a;min-height:100vh">
        <h2>❌ Xatolik</h2><p>'.htmlspecialchars($e->getMessage()).'</p>
        <p style="color:#4d6494;margin-top:12px">config.php da DB_PASS ni tekshiring.</p></div>';
}
