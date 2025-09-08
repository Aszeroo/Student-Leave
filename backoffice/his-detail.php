<?php 
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/function.php';
session_start();

// รับค่า id จาก URL
$teacher_id = $_GET['id'] ?? null;
$student_id = $_GET['student_id'] ?? null;

$student = null;
$leave = null;

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

if (!$student_id) {
    die("กรุณาระบุรหัสนักศึกษา");
}

// ดึงข้อมูลนักศึกษา (ถ้าต้องการ)
$stmt = $pdo->prepare("SELECT s.student_id,
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
            WHERE s.student_id = :student_id");

$stmt->execute(['student_id' => $student_id]); // <-- ตรงนี้ต้องเป็น key => value
$student = $stmt->fetch(PDO::FETCH_ASSOC); // แนะนำใช้ FETCH_ASSOC จะดึงเป็น array แบบชื่อ column

if (!$student) {
    die("ไม่พบนักศึกษาคนนี้");
}

// ดึงข้อมูลใบลาทั้งหมดของนักเรียนคนนี้
$stmt1 = $pdo->prepare("
    SELECT 
        lr.*, 
        s.std_prefix, 
        s.std_fname, 
        s.std_sname
    FROM leave_requests lr
    LEFT JOIN students s ON lr.student_id = s.student_id
    WHERE lr.student_id = ?
    ORDER BY start_date DESC
");
$stmt1->execute([$student_id]);
$leave_requests = $stmt1->fetchAll(PDO::FETCH_ASSOC);

?>

<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<div id="main-content">
<!-- เพิ่มโค้ดสำหรับการแปลงเป็น PDF -->
<div class="head-his">
    📄 ประวัติการลาของ <?= htmlspecialchars($student['std_prefix'] . $student['std_fname'] . ' ' . $student['std_sname']) ?>
</div>

<div class="card-form">
        <h3>🧑🏻‍💼 ข้อมูลส่วนตัว </h3>
        <div class="personal-info">
            <div class="info-item"><strong>ชื่อ-นามสกุล :</strong> <?= htmlspecialchars($student['std_prefix'] . $student['std_fname'] . ' ' . $student['std_sname']) ?></div>
            <div class="info-item"><strong>รหัสนักศึกษา :</strong> 0<?= htmlspecialchars($student['student_id']) ?></div>
            <div class="info-item"><strong>ระดับชั้น :</strong> <?= htmlspecialchars($student['education_name']) ?></div>
            <div class="info-item"><strong>สาขา :</strong> <?= htmlspecialchars($student['sub_major_fullname']) ?></div>
            <div class="info-item"><strong>ห้อง :</strong> <?= htmlspecialchars($student['classname']) ?></div>
        </div>
    </div>

<div class="card-form">
    <div class="his-head">
        <h3>🕒 ข้อมูลการแจ้งลาล่าสุด </h3>
        <!-- ปุ่ม Export PDF -->
            <button onclick="downloadPDF()" class="sdt-btn-print"><i class="bi bi-printer-fill"></i> Export to PDF</button>
    </div>
    
    <table class="his" width="100%" cellspacing="0" cellpadding="5">
        <thead class="table-header">
            <tr>
                <th>รหัสใบลา</th>
                <th>ผู้ขอลา</th>
                <th>ประเภท</th>
                <th>วันที่ยื่น</th>
                <th>วันที่ลา</th>
                <th>สถานะ</th>
                <th class="col-detail">รายละเอียด</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leave_requests as $row): ?>
            <tr>
                <td style="color: red;">#<?= htmlentities($row['request_id'])?></td>
                <td><?= htmlspecialchars($row['std_prefix'] . $row['std_fname'] . ' ' . $row['std_sname']) ?></td>
                <td><?= htmlentities($row['leave_type'])?></td>
                <td><?= htmlentities($row['submitted_at'])?></td>
                <td><?= htmlentities($row['start_date'])?> - <?= htmlentities($row['end_date'])?></td>
                <td>
                    <span class="status-box <?= $row['status'] ?>"><?= htmlentities(getStatusThai($row['status'])) ?></span>
                </td>
                <td>
                    <a href="his-full-detail.php?id=<?= urlencode($teacher['teacher_id'] ?? 0) ?>&request_id=<?= urlencode($row['request_id']) ?>">
                        <button class="btn-blue , col-detail"><i class="fas fa-search"></i></button>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>

<!-- สคริปต์สำหรับการดาวน์โหลด PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('main-content').cloneNode(true);

    // inject style ใน clone element โดยใช้ <style> ด้านบนสุด
    const style = document.createElement('style');
    style.innerHTML = `
        body {
            font-family: "TH Sarabun New", Arial, sans-serif;
            font-size: 12pt;
        }
        .sidebar, .header, .footer, .sdt-btn-print, .col-detail {
            display: none !important;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9pt;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
            font-size: 10pt;
        }
        h1, h2, h3 {
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }
        .status-box {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 8pt;
            text-align: center;
        }
        .status-box.approved { background-color: #1EBF24; color: #fff; }
        .status-box.pending { background-color: #E5832D; color: #fff; }
        .status-box.rejected { background-color: #E52D2D; color: #fff; }
    `;
    element.prepend(style);

    const opt = {
        margin: 0.5,
        filename: 'leave-history.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: 'avoid-all' }
    };

    html2pdf().set(opt).from(element).save();
}
</script>


<?php
$content = ob_get_clean();
$title = "History Leave Student";
include __DIR__ . '/layouts/layout.php';
?>
