<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/config.php';

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt = $pdo->prepare("
    SELECT s.*, e.education_name , c.classname
    FROM students s
    LEFT JOIN education e ON s.education_id = e.education_id
    LEFT JOIN classroom c ON s.classroom_id = c.classroom_id
    WHERE s.student_id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch(); 

?>

<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<div class="dashboard">
   <div class="info">

        <div class="card-f">
            <h2>ข้อมูลส่วนตัว</h2>

                <div class="info-group">
                    <label class="required">คำนำหน้า</label>
                    <select disabled>
                    <option selected><?= htmlspecialchars($student['std_prefix']) ?></option>
                    </select>
                </div>

                <div class="info-group">
                    <label class="required">ชื่อ-นามสกุล</label>
                    <input type="text" value="<?= htmlspecialchars($student['std_fname'] . ' ' . $student['std_sname']) ?>" readonly>
                </div>

                <!-- <div class="info-group">
                    <label class="required">เลขบัตรประชาชน</label>
                    <input type="text" value="1126543597845" readonly>
                </div> -->

                <div class="info-group">
                    <label class="required">รหัสนักศึกษา</label>
                    <input type="text" value="0<?= htmlspecialchars($student['student_id']) ?>" readonly>
                </div>

                <div class="info-group">
                    <label class="required">ระดับชั้น</label>
                    <input type="text" value="<?= htmlspecialchars($student['education_name']) ?>" readonly>
                </div>

                <div class="info-group">
                    <label class="required">สาขา</label>
                    <input type="text" value="<?= htmlspecialchars($student['sub_major_fullname']) ?>" readonly>
                </div>

                <div class="info-group">
                    <label class="required">ห้อง</label>
                    <input type="text" value="<?= htmlspecialchars($student['classname']) ?>" readonly>
                </div>

                <!-- <div class="info-group">
                    <label class="required">สถานที่ฝึกงาน</label>
                    <input type="text" value="Boba company" readonly>
                </div>

                <div class="info-group">
                    <label class="required">เบอร์โทรศัพท์</label>
                    <input type="text"  >
                </div>

                <div class="info-group">
                    <label class="required">ที่อยู่ที่ติดต่อได้</label>
                    <textarea rows="3" ></textarea>
                </div> -->

                
                <!-- <div class="button-container">
                    <button class="btn cancel" onclick="showPopup('error', 'บันทึกข้อมูลไม่สำเร็จ')">ยกเลิก</button>
                    <button class="btn confirm" onclick="showPopup('success', 'บันทึกข้อมูลสำเร็จ')">ยืนยัน</button>
                </div> -->
                

                <!-- Popup -->
                <!-- <div id="popup" class="popup"></div>

                <script>
                    function showPopup(type, message) {
                    const popup = document.getElementById('popup');
                    popup.className = 'popup ' + type;
                    popup.textContent = message;
                    popup.style.display = 'block';

                    setTimeout(() => {
                        popup.style.display = 'none';
                    }, 3000);
                    }
                </script> -->
                
        </div>
        
    </div>

<?php
    $content = ob_get_clean();
    $title = "Info";
    include __DIR__ . '/layouts/layout.php';
?>