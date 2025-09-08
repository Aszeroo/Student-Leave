<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/config.php';

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    echo "ไม่พบรหัสนักเรียน";
    exit;
}

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

// ดึงข้อมูล education
$edu_stmt = $pdo->query("SELECT * FROM education");
$educations = $edu_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูล classroom
$class_stmt = $pdo->query("SELECT * FROM classroom");
$classrooms = $class_stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลนักเรียน
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    echo "ไม่พบนักเรียนในระบบ";
    exit;
}

// แก้ไขข้อมูล (เมื่อกด submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $std_prefix = trim($_POST['std_prefix'] ?? '');
    $std_fname = trim($_POST['std_fname'] ?? '');
    $std_sname = trim($_POST['std_sname'] ?? '');
    $student_id = intval($_POST['student_id'] ?? '');
    $education_id = trim($_POST['education_id'] ?? '');
    $sub_major_fullname = trim($_POST['sub_major_fullname'] ?? '');
    $classroom_id = trim($_POST['classroom_id'] ?? '');

    if (!$std_prefix || !$std_fname || !$std_sname || !$student_id || !$education_id || !$sub_major_fullname || !$classroom_id) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        // ทำการอัปเดตฐานข้อมูล
        $update_stmt = $pdo->prepare("UPDATE students SET std_prefix = ?, std_fname = ?, std_sname = ?, education_id = ?, sub_major_fullname = ?, classroom_id = ? WHERE student_id = ?");
        $update_stmt->execute([$std_prefix, $std_fname, $std_sname, $education_id, $sub_major_fullname, $classroom_id, $student_id]);

        echo "<script> alert('อัปเดตข้อมูลเรียบร้อยแล้ว'); location.href='stu-dt.php?id=$teacher_id&classroom_id=$classroom_id'; </script>";
        exit;
    }
}

// ลบข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $del_stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
    $del_stmt->execute([$student_id]);

    echo "<script>alert('ลบนักเรียนเรียบร้อยแล้ว'); location.href='stu-dt.php?id=$teacher_id&classroom_id=$classroom_id'; </script>";
    exit;
}

?>

<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<form method="post" action="">
<div class="stu-info">
  <div class="stu-card">
     <div class="stu-card-header">
      <h2 class="stu-title">👤 ข้อมูลส่วนตัวนักศึกษา</h2>
      <!-- <button class="stu-btn-edit" onclick="stuOpenEditPopup()">แก้ไข</button> -->
    </div>

    <div class="stu-group">
      <label class="required">คำนำหน้า</label>
        <select class="stu-prefix" name="std_prefix" required>
        <option value="นาย" <?= $student['std_prefix'] == 'นาย' ? 'selected' : '' ?>>นาย</option>
        <option value="นางสาว" <?= $student['std_prefix'] == 'นางสาว' ? 'selected' : '' ?>>นางสาว</option>
    </select>
    </div>

    <div class="stu-group-row">
      <div class="stu-group">
        <label for="std_fname" class="required">ชื่อ</label>
        <input type="text" id="std_fname" name="std_fname" value="<?= htmlspecialchars($student['std_fname']) ?>" required>
      </div>

      <div class="stu-group">
        <label for="std_sname" class="required">นามสกุล</label>
        <input type="text" id="std_sname" name="std_sname" value="<?= htmlspecialchars($student['std_sname']) ?>" required>
      </div>
    </div>

    <!-- <div class="stu-group">
      <label class="required">เลขบัตรประชาชน</label>
      <input type="text" value="1126543597845" readonly>
    </div> -->

    <div class="stu-group">
      <label class="required">รหัสนักศึกษา</label>
      <input type="text" name="student_id" value="0<?= $student['student_id'] ?>">
    </div>

    <div class="stu-group">
      <label for="education_id">ระดับชั้น</label>
      <select name="education_id" id="education_id" required>
          <?php foreach ($educations as $edu): ?>
              <option value="<?= $edu['education_id'] ?>" <?= $student['education_id'] == $edu['education_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($edu['education_name']) ?>
              </option>
          <?php endforeach; ?>
      </select>
    </div>

    <div class="stu-group">
      <label class="required">สาขา</label>
      <select  name="sub_major_fullname" id="sub_major_fullname" required>
          
              <option value="<?= htmlspecialchars($student['sub_major_fullname']) ?>">
                  <?= htmlspecialchars($student['sub_major_fullname']) ?>
              </option>
          
      </select>
    </div>

    <div class="stu-group">
      <label for="classroom_id">ห้อง</label>
      <select name="classroom_id" id="classroom_id" required>
          <?php foreach ($classrooms as $cls): ?>
              <option value="<?= $cls['classroom_id'] ?>" <?= $student['classroom_id'] == $cls['classroom_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cls['classname']) ?>
              </option>
          <?php endforeach; ?>
      </select>
    </div>
  </form>

    <!-- <div class="stu-group">
      <label class="required">สถานที่ฝึกงาน</label>
      <input type="text" value="Pupi company" readonly>
    </div>

    <div class="stu-group">
      <label class="required">เบอร์โทรศัพท์</label>
      <input type="text"readonly>
    </div>

    <div class="stu-group">
      <label class="required">ที่อยู่ที่ติดต่อได้</label>
      <textarea rows="3" readonly></textarea>
    </div> -->

    <div class="button-container">
      <button type="submit" name="delete" class="btn delete" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบนักเรียนคนนี้?')">🗑️ ลบข้อมูล</button>
      <button type="submit" class="btn confirm" name="update">💾 บันทึกการแก้ไข</button>
    </div>

  </div>
</div>

<!-- <div class="stu-popup-overlay" id="stuPopupEdit">
  <div class="stu-popup">
    <h3>แก้ไขข้อมูล</h3>

    <div class="stu-info-group">
      <label class="stu-required" >คำนำหน้า</label>
        <select class="stu-input" id="stuEditPrefix">
            <option>นาย</option>
            <option>นางสาว</option>
            <option>นาง</option>
        </select>
    </div>

    <div class="stu-info-group">
      <label class="stu-required">ชื่อ-นามสกุล</label><br>
      <input type="text" class="stu-input" id="stuEditName" >
    </div>

    <div class="stu-info-group">
      <label class="stu-required">เลขบัตรประชาชน</label>
      <input type="text" class="stu-input" id="stuEditId" >
    </div>

    <div class="stu-info-group">
      <label class="stu-required">รหัสนักศึกษา</label>
      <input type="text" class="stu-input" id="stuEditPass" >
    </div>

    <div class="stu-info-group">
      <label class="stu-required">ระดับชั้น</label>
      <input type="text" class="stu-input" id="stuEditGrade">
    </div>


    <div class="stu-info-group">
      <label class="stu-required">สาขา</label>
      <input type="text" class="stu-input" id="stuEditMajor" >
    </div>

    <div class="stu-info-group">
      <label class="stu-required">ห้อง</label>
      <input type="text" class="stu-input" id="stuEditRoom" >
    </div>

    <div class="stu-info-group">
      <label class="stu-required">สถานที่ฝึกงาน</label>
      <input type="text" class="stu-input" id="stuEditWorkplece" >
    </div>

    <div class="stu-info-group">
      <label class="stu-required">เบอร์โทรศัพท์</label>
      <input type="text" class="stu-input" id="stuEditTel">
    </div>

    <div class="stu-info-group">
      <label class="stu-required">ที่อยู่ที่ติดต่อได้</label>
      <textarea rows="3" type="text" class="stu-input" id="stuEditAddress" ></textarea>
    </div>

    <div class="stu-popup-actions">
      <button class="stu-btn-cancel" onclick="stuCloseEditPopup()">ยกเลิก</button>
      <button class="stu-btn-save" onclick="stuSaveEdit()">บันทึก</button>
    </div>
  </div>
</div> -->

<!-- <script>
  function stuOpenEditPopup() {
    // ดึงค่าจากฟอร์มหลัก
    const mainInputs = document.querySelectorAll('.stu-prefix, .stu-group input, .stu-group textarea');

    document.getElementById("stuEditPrefix").value = mainInputs[0].value;
    document.getElementById("stuEditName").value = mainInputs[1].value;
    document.getElementById("stuEditId").value = mainInputs[2].value;
    document.getElementById("stuEditPass").value = mainInputs[3].value;
    document.getElementById("stuEditGrade").value = mainInputs[4].value;
    document.getElementById("stuEditMajor").value = mainInputs[5].value;
    document.getElementById("stuEditRoom").value = mainInputs[6].value;
    document.getElementById("stuEditWorkplece").value = mainInputs[7].value;
    document.getElementById("stuEditTel").value = mainInputs[8].value;
    document.getElementById("stuEditAddress").value = mainInputs[9].value;

    // แสดง popup
    document.getElementById("stuPopupEdit").style.display = "flex";
  }

  function stuCloseEditPopup() {
    document.getElementById("stuPopupEdit").style.display = "none";
  }

  function stuDeleteStudent() {
    if (confirm("คุณแน่ใจว่าต้องการลบนักศึกษาคนนี้ใช่หรือไม่?")) {
      alert("ลบข้อมูลนักศึกษาแล้ว");

      // ✅ ลบข้อมูลออกจาก DOM
      document.querySelector('.stu-card').remove();

      // ✅ กลับไปหน้าก่อนหน้า
      window.history.back();
    }
  }

  function stuSaveEdit() {
    // รับค่าจาก popup
    const prefix = document.getElementById("stuEditPrefix").value;
    const name = document.getElementById("stuEditName").value;
    const id = document.getElementById("stuEditId").value;
    const pass = document.getElementById("stuEditPass").value;
    const grade = document.getElementById("stuEditGrade").value;
    const major = document.getElementById("stuEditMajor").value;
    const room = document.getElementById("stuEditRoom").value;
    const workplace = document.getElementById("stuEditWorkplece").value;
    const tel = document.getElementById("stuEditTel").value;
    const address = document.getElementById("stuEditAddress").value;

    // อัปเดตค่าบนหน้า
    const groups = document.querySelectorAll('.stu-prefix, .stu-group input, .stu-group textarea');
    groups[0].value = prefix;
    groups[1].value = name;
    groups[2].value = id;
    groups[3].value = pass;
    groups[4].value = grade;
    groups[5].value = major;
    groups[6].value = room;
    groups[7].value = workplace;
    groups[8].value = tel;
    groups[9].value = address;

    // ปิด popup
    stuCloseEditPopup();

    alert("บันทึกข้อมูลสำเร็จ");
  }
</script> -->


<?php
    $content = ob_get_clean();
    $title = "Student";
    include __DIR__ . '/layouts/layout.php';
?>