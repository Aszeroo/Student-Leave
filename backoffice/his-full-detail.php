<?php 
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/config.php';
session_start();

// รับค่า id จาก URL
$request_id = $_GET['request_id'] ?? null;
$teacher_id = $_GET['id'] ?? null;

$student = null;
$leave = null;


if ($request_id) {
    // ดึงข้อมูลการลา
    $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE request_id = ?");
    $stmt->execute([$request_id]);
    $leave = $stmt->fetch(PDO::FETCH_ASSOC);

    // ถ้ามีการลา → เอา student_id จาก leave_requests ไปหาใน students
    if ($leave && !empty($leave['student_id'])) {
        $sql = "
            SELECT s.student_id,
                s.std_prefix,
                s.std_fname,
                s.std_sname,
                s.sub_major_fullname,
                s.sub_major_short_name,
                c.classname,
                c.education_id,
                e.education_name
            FROM students s
            LEFT JOIN classroom c ON s.classroom_id = c.classroom_id
            LEFT JOIN education e ON c.education_id = e.education_id
            WHERE s.student_id = :student_id
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['student_id' => $leave['student_id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        }

}

if ($leave) {
    $status = $leave['status']; // กำหนดค่า status ที่นี่
} else {
    echo "<p style='color:red;'>ไม่พบข้อมูลใบลาที่ระบุ</p>";
    exit;
}

$stmtDays = $pdo->prepare("
    SELECT leave_day_id, leave_date, day_name, leave_option, leave_count
    FROM leave_days
    WHERE request_id = ?
    ORDER BY leave_date ASC
");
$stmtDays->execute([$request_id]);
$results = $stmtDays->fetchAll();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];

    if (in_array($status, ['approved', 'rejected']) && $request_id) {
        $sql = "UPDATE leave_requests SET status = ? WHERE request_id = ?";
        $stmt2 = $pdo->prepare($sql);
        $success = $stmt2->execute([$status, $request_id]);

        if ($success) {
            header('Location: rq.php?id=' . urlencode($teacher_id));
            exit;
        } else {
            echo "เกิดข้อผิดพลาดในการอัปเดตสถานะ";
        }
    }
}

?>



<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<?php if ($leave): ?>
<div class="card-form">
    <div class="title">
        <span>🚦 <strong>สถานะการลา</strong></span>
        <span style="color: red;">#<?= htmlspecialchars($leave['request_id']) ?></span>
    </div>

    <div class="container-status">
        <div class="status-item <?= $status === 'unready' ? '' : 'disabled' ?>">
            <img src="https://cdn-icons-png.flaticon.com/512/2246/2246687.png" />
            <p>ข้อมูลไม่ครบ</p>
        </div>

        <div class="status-item">
            <img src="https://cdn-icons-png.flaticon.com/256/10485/10485083.png" />
            <p>ส่งคำร้องสำเร็จ</p>
        </div>

        <div class="status-item <?= $status === 'pending' ? '' : 'disabled' ?>">
            <img src="https://cdn-icons-png.flaticon.com/512/1289/1289317.png" />
            <p>รอการอนุมัติ</p>
        </div>

        <div class="status-item <?= $status === 'approved' ? '' : 'disabled' ?> ">
            <img src="https://cdn-icons-png.flaticon.com/256/5454/5454607.png" />
            <p>คำร้องขอผ่านการอนุมัติ</p>
        </div>

        <div class="status-item <?= $status === 'rejected' ? '' : 'disabled' ?>">
            <img src="https://cdn-icons-png.freepik.com/128/5454/5454426.png" />
            <p>คำร้องขอถูกปฏิเสธ</p>
        </div>
    </div>
</div>

<div class="card-form">
    <h3>🧑🏻‍💼 ข้อมูลนักศึกษา </h3>
    <div class="personal-info">
        <div class="info-item"><strong>ชื่อ-นามสกุล :</strong> <?= ($student['std_fname'] ?? '-') . ' ' . ($student['std_sname'] ?? '-') ?></div>
        <div class="info-item"><strong>รหัสนักศึกษา :</strong> 0<?= $student['student_id'] ?? '-' ?></div>
        <div class="info-item"><strong>ระดับชั้น :</strong><?= $student['education_name'] ?? '-' ?></div>
        <div class="info-item"><strong>สาขา :</strong><?= $student['sub_major_fullname'] ?? '-' ?></div>
        <div class="info-item"><strong>ห้อง :</strong><?= $student['classname'] ?? '-' ?></div>
    </div>
</div>

<div class="card-detail">
    <h3>📝 รายละเอียดข้อมูลการลา <span style="color:red">#<?= htmlspecialchars($leave['request_id']) ?></span></h3>

    <div class="detail-grid grid-detail-container">
        <div class="grid-item-detail"><label>รหัสนักศึกษา :</label></div>
        <div class="grid-item-detail">0<?= htmlspecialchars($leave['student_id'] ?? '-') ?></div>

        <div class="grid-item-detail"><label>เรื่อง :</label></div>
        <div class="grid-item-detail"><?= htmlspecialchars($leave['leave_type'] ?? '-') ?></div>

        <div class="grid-item-detail"><label>เนื่องจาก/รายละเอียด :</label></div>
        <div class="grid-item-detail"><?= htmlspecialchars($leave['reason'] ?? '-') ?></div>

        <div class="grid-item-detail"><label>วันที่ยื่นลา :</label></div>
        <div class="grid-item-detail"><?= htmlspecialchars($leave['submitted_at'] ?? '-') ?></div>

        <div class="grid-item-detail"><label>ขอลาตั้งแต่วันที่:</label></div>
        <div class="grid-item-detail"><?= htmlspecialchars($leave['start_date'] ?? '-') ?></div>

        <div class="grid-item-detail"><label>จนถึงวันที่:</label></div>
        <div class="grid-item-detail"><?= htmlspecialchars($leave['end_date'] ?? '-') ?></div>
    </div>

    <!-- ตารางวันลา -->
        <table>
        <thead class="table-header">
            <tr>
                <th>ลำดับ</th>
                <th>วัน/เดือน/ปี</th>
                <th>วัน</th>
                <th>ตัวเลือก</th>
                <th>จำนวนวันลา (วัน)</th>
            </tr>
        </thead>
        <?php if (empty($results)): ?>
            <p style="color:red;">ไม่พบข้อมูลวันที่ลาสำหรับคำร้องนี้</p>
        <?php else: ?>
        <tbody>
            <?php foreach ($results as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['leave_day_id'])?></td>
                <td><?= htmlspecialchars($row['leave_date'])?></td>
                <td><?= htmlspecialchars($row['day_name'])?></td>
                <td><?= htmlspecialchars($row['leave_option'])?></td>
                <td><?= htmlspecialchars($row['leave_count'])?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <?php endif; ?>
        <tfoot>
            <tr>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>รวม</strong></td>
            <td><?= htmlspecialchars($leave['leave_period'])?></td>
            </tr>
        </tfoot>
    </table>

        <label>เอกสารแนบ : </label>
        <?php if (!empty($leave['evidence_file'])): ?>
            <img src="../uploads/<?= htmlspecialchars($leave['evidence_file']) ?>" 
                alt="เอกสารแนบ"
                style="max-width:200px; cursor:pointer; border:1px solid #ccc; border-radius:8px;"
                onclick="openImagePopup(this.src)">
        <?php else: ?>
            -
        <?php endif; ?>

        <!-- Popup -->
        <div id="imgPopup" class="popup" onclick="closeImagePopup()">
            <span class="close">&times;</span>
            <img class="popup-content" id="popupImg">
        </div>

        <script>
        function openImagePopup(src) {
            document.getElementById("imgPopup").style.display = "block";
            document.getElementById("popupImg").src = src;
        }
        function closeImagePopup() {
            document.getElementById("imgPopup").style.display = "none";
        }
        </script>

        <?php else: ?>
            <p style="color:red;">❌ ไม่พบข้อมูลใบลาที่คุณเลือก</p>
        <?php endif; ?>

<?php
$content = ob_get_clean();
$title = $leave ? "Leave Detail #{$leave['request_id']}" : "ไม่พบข้อมูล";
include __DIR__ . '/layouts/layout.php';
?>