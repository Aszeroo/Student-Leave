<?php 
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/config.php';
session_start();



// รับค่า id จาก URL
$teacher_id = $_GET['id'] ?? null;

if ($teacher_id) {
    // ดึงข้อมูล teacher_id จากฐานข้อมูล โดย JOIN หรือตาม logic ของคุณ
    $stmt1 = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
    $stmt1->execute([$teacher_id]);
    $teacher = $stmt1->fetch();

    if ($teacher) {
        $teacher_id = $teacher['teacher_id'];
    } else {
        // กรณีไม่เจอข้อมูล
        $teacher_id = null;
    }
} else {
    $teacher_id = null;
}

// ดึงข้อมูลระดับชั้นจาก education
$stmt = $pdo->prepare("SELECT education_id, education_name FROM education ORDER BY education_name ASC");
$stmt->execute();
$educations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลสาขาจาก student (หรือ sub_major table ถ้ามีแยก)
$stmt = $pdo->prepare("
    SELECT DISTINCT sub_major_fullname, sub_major_short_name
    FROM students
    WHERE sub_major_fullname IS NOT NULL
      AND sub_major_short_name IS NOT NULL
    ORDER BY sub_major_fullname ASC, sub_major_short_name ASC
");
$stmt->execute();
$majors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลห้องจาก classroom
$stmt = $pdo->prepare("SELECT classroom_id, classname FROM classroom ORDER BY classname ASC");
$stmt->execute();
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลรอบจาก round
$stmt = $pdo->prepare("SELECT round_id, round_name FROM round");
$stmt->execute();
$round = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $std_prefix = trim($_POST['std_prefix'] ?? '');
    $std_fname = trim($_POST['std_fname'] ?? '');
    $std_sname = trim($_POST['std_sname'] ?? '');
    $student_id = intval($_POST['student_id'] ?? 0);
    $education_id = intval($_POST['education_id'] ?? 0);
    $sub_major_fullname = trim($_POST['sub_major_fullname'] ?? '');
    $sub_major_short_name = trim($_POST['sub_major_short_name'] ?? '');
    $classroom_id = intval($_POST['classroom_id'] ?? 0);
    $round_id = intval($_POST['round_id'] ?? 0);

    if (!$std_prefix || !$std_fname || !$std_sname || !$student_id || !$education_id || !$sub_major_fullname || !$classroom_id || !$sub_major_short_name) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }

    if (!$error) {
        try {
            $stmt = $pdo->prepare("INSERT INTO students (std_prefix, std_fname, std_sname, student_id, education_id, sub_major_fullname, classroom_id, round_id, sub_major_short_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$std_prefix, $std_fname, $std_sname, $student_id, $education_id, $sub_major_fullname, $classroom_id, $round_id, $sub_major_short_name]);

            $_SESSION['add_student'] = true;
            header('Location: stu-dt.php?id=' . urlencode($teacher['teacher_id']));
            exit;

        } catch (PDOException $e) {
            echo "Database Error: " . $e->getMessage();
            exit;
        }
    }
}
?>

<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<form method="post" action="">
<div class="stu-info">
  <div class="stu-card">
     <div class="stu-card-header">
      <h2 class="stu-title">➕ เพิ่มข้อมูลนักศึกษา</h2>
    </div>

    <div class="stu-group">
      <label class="required">คำนำหน้า</label>
        <select class="stu-prefix" name="std_prefix" required>
        <option value="นาย">นาย</option>
        <option value="นางสาว">นางสาว</option>
    </select>
    </div>

    <div class="stu-group-row">
      <div class="stu-group">
        <label for="std_fname" class="required">ชื่อ</label>
        <input type="text" id="std_fname" name="std_fname" required>
      </div>

      <div class="stu-group">
        <label for="std_sname" class="required">นามสกุล</label>
        <input type="text" id="std_sname" name="std_sname" required>
      </div>
    </div>

    <div class="stu-group">
      <label class="required">รหัสนักศึกษา</label>
      <input type="text" name="student_id" required>
    </div>

    <div class="stu-group">
      <label for="education_id">ระดับชั้น</label>
      <select name="education_id" id="education_id" required>
          <?php foreach ($educations as $edu): ?>
                    <option value="<?= $edu['education_id'] ?>"><?= htmlspecialchars($edu['education_name']) ?></option>
          <?php endforeach; ?>
      </select>
    </div>

    <div class="stu-group">
      <label class="required">สาขา</label>
      <select name="sub_major_fullname" id="sub_major_fullname" required>
           <?php foreach ($majors as $major): ?>
                <option value="<?= htmlspecialchars($major['sub_major_fullname']) ?>">
                    <?= htmlspecialchars($major['sub_major_fullname']) ?>
                </option>
            <?php endforeach; ?>
      </select>
    </div>

    <div class="stu-group">
      <label class="required">สาขา(ตัวย่อ)</label>
      <select name="sub_major_short_name" id="sub_major_short_name" required>
           <?php foreach ($majors as $major): ?>
                <option value="<?= htmlspecialchars($major['sub_major_short_name']) ?>">
                    <?= htmlspecialchars($major['sub_major_short_name']) ?>
                </option>
            <?php endforeach; ?>
      </select>
    </div>

    <div class="stu-group">
      <label for="classroom_id">ห้อง</label>
      <select name="classroom_id" id="classroom_id" required>
          <?php foreach ($classrooms as $class): ?>
                <option value="<?= $class['classroom_id'] ?>"><?= htmlspecialchars($class['classname']) ?></option>
          <?php endforeach; ?>
      </select>
    </div>

    <div class="stu-group">
      <label for="classroom_id">รอบ</label>
      <select name="round_id" id="round_id" required>
          <?php foreach ($round as $r): ?>
                <option value="<?= $r['round_id'] ?>"><?= htmlspecialchars($r['round_name']) ?></option>
          <?php endforeach; ?>
      </select>
    </div>
  </form>

    <div class="button-container">
        <button type="cancel" class="btn cancel">ยกเลิก</button>
        <button type="submit" class="btn confirm">บันทึก</button>
    </div>

  </div>
</div>

<?php
    $content = ob_get_clean();
    $title = "add_student";
    include __DIR__ . '/layouts/layout.php';
?>