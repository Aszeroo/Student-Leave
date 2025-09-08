<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/config.php';

session_start();

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

// รับค่าฟิลเตอร์จากฟอร์ม
$education_id = $_POST['education_id'] ?? '';
$sub_major_fullname = $_POST['sub_major_fullname'] ?? '';
$classroom_id = $_POST['classroom_id'] ?? '';

$sql = "
SELECT *, c.classname, e.education_name
FROM students s
LEFT JOIN classroom c ON s.classroom_id = c.classroom_id
LEFT JOIN education e ON s.education_id = e.education_id
WHERE 1=1
";
$params = [];

if ($education_id !== '') {
    $sql .= " AND s.education_id = :education_id";
    $params[':education_id'] = $education_id;
}
if ($sub_major_fullname !== '') {
    $sql .= " AND s.sub_major_fullname = :sub_major_fullname";
    $params[':sub_major_fullname'] = $sub_major_fullname;
}
if ($classroom_id !== '') {
    $sql .= " AND s.classroom_id = :classroom_id";
    $params[':classroom_id'] = $classroom_id;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลระดับชั้นจาก education
$stmt = $pdo->prepare("SELECT education_id, education_name FROM education ORDER BY education_name ASC");
$stmt->execute();
$educations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลสาขาจาก student (หรือ sub_major table ถ้ามีแยก)
$stmt = $pdo->prepare("SELECT DISTINCT sub_major_fullname 
                       FROM students 
                       WHERE sub_major_fullname IS NOT NULL 
                       ORDER BY sub_major_fullname ASC");
$stmt->execute();
$majors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลห้องจาก classroom
$stmt = $pdo->prepare("SELECT classroom_id, classname FROM classroom ORDER BY classname ASC");
$stmt->execute();
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<div class="card-form">
  <div class="card-form-header">
    <h3>👥 ข้อมูลนักศึกษา</h3><br>
    <div class="card-form-actions">
      <button class="sdt-btn-add" onclick="location.href='add-stu.php?id=<?= urlencode($teacher['teacher_id']) ?>'">
          <i class="bi bi-person-fill-add"></i>
      </button>
    </div>
  </div>

<form method="POST">
            <div class="if-stu-filter">
                <div class="if-stu-filter-f">
                    
                    <label for="education">ระดับชั้น :</label>
                    <select name="education_id" id="education">
                        <option value="">-- เลือก --</option>
                        <?php foreach ($educations as $edu): ?>
                            <option value="<?= $edu['education_id'] ?>"><?= htmlspecialchars($edu['education_name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="major">สาขา :</label>
                    <select name="sub_major_fullname" id="major">
                        <option value="">-- เลือก --</option>
                            <?php foreach ($majors as $major): ?>
                                <option value="<?= htmlspecialchars($major['sub_major_fullname']) ?>">
                                    <?= htmlspecialchars($major['sub_major_fullname']) ?>
                                </option>
                            <?php endforeach; ?>
                    </select>

                    <label for="classroom">ห้อง :</label>
                    <select name="classroom_id" id="classroom">
                        <option value="">-- เลือก --</option>
                        <?php foreach ($classrooms as $class): ?>
                            <option value="<?= $class['classroom_id'] ?>"><?= htmlspecialchars($class['classname']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-blue">
                        <i class="fas fa-search"></i>
                    </button>
                    
                </div>
            </div>
        </form>

  
<div id="studentList">
<table class="if-stu-table"  id="sdt-table">
  <thead>
    <tr>
      <th>ลำดับ</th>
      <th>รหัสนักศึกษา</th>
      <th>ชื่อ-นามสกุล</th>
      <th>ห้อง</th>
      <th>สาขา</th>
      <th>รายละเอียด</th>
    </tr>
  </thead>
<tbody id="sdt-tbody">
    <?php
    if (!empty($students)) {
        $index = 1;
        foreach ($students as $std) {
            $stmt2 = $pdo->prepare("SELECT SUM(leave_period) AS total_leave FROM leave_requests WHERE student_id = ?");
            $stmt2->execute([$std['student_id']]);
            $lr = $stmt2->fetch();
            $total_leave = $lr['total_leave'] ?? 0;
    ?>
            <tr>
                <td><?= $index++ ?></td>
                <td>0<?= htmlentities($std['student_id']) ?></td>
                <td><?= htmlspecialchars($std['std_prefix'] . $std['std_fname'] . ' ' . $std['std_sname']) ?></td>
                <td><?= htmlspecialchars($std['classname']) ?></td>
                <td><?= htmlspecialchars($std['sub_major_short_name']) ?></td>
                <td>
                    <a href="stu.php?id=<?= urlencode($teacher['teacher_id']) ?>&student_id=<?= urlencode($std['student_id']) ?>">
                      <button class="btn-blue"> <i class="fas fa-search"></i>
                    </a>
                </td>
            </tr>
    <?php
    }
    } else {
        echo '<tr><td colspan="5">ไม่พบนักศึกษา</td></tr>';
    }
    ?>
  </tbody>
</table>
</div>
    
</div>


<?php
    $content = ob_get_clean();
    $title = "Information Student Detail";
    include __DIR__ . '/layouts/layout.php';
?>