<?php

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/function.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "ไม่พบรหัสนักศึกษา";
    exit;
}
// $stmt =$pdo->query("SELECT * FROM leave_requests ORDER BY request_id DESC");
// $leave_requests = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT 
        lr.*, 
        s.std_prefix, 
        s.std_fname, 
        s.std_sname
    FROM leave_requests lr
    LEFT JOIN students s ON lr.student_id = s.student_id
    WHERE lr.student_id = ?
    ORDER BY lr.request_id DESC
");
$stmt->execute([$id]);
$student = $stmt->fetchAll(); 
$latest = $stmt->fetch(PDO::FETCH_ASSOC);
$latest = $student[0] ?? null; // เอาแถวแรก ถ้ามี
$status = $latest['status'] ?? ''; // กัน null

?>

<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">


    <div class="card-form">
        <div class="title">
            <?php if ($latest): ?>
            <span>🚦 <strong>สถานะการลาล่าสุด</strong></span>
            <?php if (isset($latest['request_id'])): ?>
            <span style="color: red;">#<?= htmlentities($latest['request_id'])?></span>
            <?php else: ?>
            <span style="color: red;">#ไม่พบรหัสคำร้อง</span>
            <?php endif; ?>
            <?php endif; ?>
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

    <div id="main-content">
    <div class="card-form">
    <div class="card-form-header">
    <h3>👥 ประวัติการลานักศึกษา</h3><br>
    <div class="card-form-actions">
         <button onclick="downloadPDF()" class="sdt-btn-print"><i class="bi bi-printer-fill"></i> Export to PDF</button>    </div>
    </div>
        <table class="his">
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
                <?php foreach ($student as $row): ?>
                <tr>
                <td style="color: red;">#<?= htmlentities($row['request_id'])?></td>
                <td><?= htmlspecialchars($row['std_prefix'] . $row['std_fname'] . ' ' . $row['std_sname']) ?></td>
                <td><?= htmlentities($row['leave_type'])?></td>
                <td><?= htmlentities($row['submitted_at'])?></td>
                <td><?= htmlentities($row['start_date'])?> - <?= htmlentities($row['end_date'])?></td>
                <td>
                    <button class="status-box <?= $row['status'] ?>"><?= htmlentities(getStatusThai($row['status'])) ?></button>
                </td>
                
                <td>
                    <?php if ($row['status'] === 'unready'): ?>
                        <!-- ถ้าไม่สมบูรณ์ ไปหน้าแก้ไข -->
                        <a href="leave-edit.php?id=<?= urlencode($row['student_id']) ?>&request_id=<?= urlencode($row['request_id']) ?>">
                            <button class="btn-yellow , col-detail"><i class="fas fa-pen"></i></button>
                        </a>
                    <?php else: ?>
                        <!-- สถานะอื่น ไปหน้าอ่านรายละเอียด -->
                        <a href="leave-detail-1001.php?id=<?= urlencode($row['student_id']) ?>&request_id=<?= urlencode($row['request_id']) ?>">
                            <button class="btn-blue , col-detail"><i class="fas fa-search"></i></button>
                        </a>
                    <?php endif; ?>
                </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<!-- สคริปต์สำหรับการดาวน์โหลด PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('main-content');

    // สร้าง <style> inline
    const style = document.createElement('style');
    style.innerHTML = `
        body {
            font-family: "TH Sarabun New", Arial, sans-serif;
            font-size: 12pt;
            margin: 0;
            padding: 0;
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
        .status-box.approved { background-color: #1EBF24; color: #ffffffff; }
        .status-box.pending { background-color: #E5832D; color: #ffffffff; }
        .status-box.rejected { background-color: #E52D2D; color: #ffffffff; }
    `;
    document.head.appendChild(style);

    const opt = {
        margin: 0.5,
        filename: 'leave-history.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: 'avoid-all' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        document.head.removeChild(style);
    });
}
</script>

<?php
    $content = ob_get_clean();
    $title = "History";
    include __DIR__ . '/layouts/layout.php';
?>