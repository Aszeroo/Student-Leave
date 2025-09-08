<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/config.php';

// $id = $_GET['id'] ?? null;
// $stmt = $pdo->prepare("SELECT * FROM leave$leave WHERE request_id = ?");
// $stmt->execute([$id]);
// $leave = $stmt->fetch();

$student_id = $_GET['id'] ?? null;
$request_id = $_GET['request_id'] ?? null;

if (!$student_id || !$request_id) {
    echo "ไม่พบข้อมูลที่ต้องการ";
    exit;
}

$stmt = $pdo->prepare("
    SELECT lr.*, s.std_prefix, s.std_fname, s.std_sname, s.sub_major_fullname ,e.education_name, c.classname
    FROM leave_requests lr
    LEFT JOIN students s ON lr.student_id = s.student_id
    LEFT JOIN education e ON s.education_id = e.education_id
    LEFT JOIN classroom c ON s.classroom_id = c.classroom_id
    WHERE lr.student_id = ? AND lr.request_id = ?
");
$stmt->execute([$student_id, $request_id]);
$leave = $stmt->fetch();
$status = $leave['status'] ?? 'pending';

if (!$leave) {
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

?>

<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<div class="dashboard">
<div class="card-form">
    <div class="title">
        <span>🚦 <strong>สถานะการลา</strong></span>
        <span style="color: red;">#<?= htmlspecialchars($leave['request_id'])?></span>
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
    <h3>🧑🏻‍💼 ข้อมูลส่วนตัว </h3>
    <div class="personal-info">
        <div class="info-item"><strong>ชื่อ-นามสกุล :</strong> <?= htmlspecialchars($leave['std_prefix'] . $leave['std_fname'] . ' ' . $leave['std_sname']) ?></div>
        <div class="info-item"><strong>รหัสนักศึกษา :</strong> 0<?= htmlspecialchars($leave['student_id'])?></div>
        <div class="info-item"><strong>ระดับชั้น :</strong> </strong> <?= htmlspecialchars($leave['education_name'])?></div>
        <div class="info-item"><strong>สาขา :</strong> </strong> <?= htmlspecialchars($leave['sub_major_fullname'])?></div>
        <div class="info-item"><strong>ห้อง :</strong> <?= htmlspecialchars($leave['classname']) ?></div>
    </div>
</div>


<div class="card-detail">
    <h3>📝 รายละเอียดข้อมูลการลา <span style="color: red;">#<?= htmlspecialchars($leave['request_id'])?></span></h3>

    
      <div class="detail-grid grid-detail-container">
          <div class="grid-item-detail">
              <label>รหัสนักศึกษา :</label>
          </div>

          <div class="grid-item-detail">
            0<?= htmlspecialchars($leave['student_id'])?>
          </div>

          <div class="grid-item-detail">
              <label>เรื่อง :</label>
          </div>

          <div class="grid-item-detail">
              <?= htmlspecialchars($leave['leave_type'])?>
          </div>

          <div class="grid-item-detail">
              <label>รายละเอียด :</label>
          </div>

          <div class="grid-item-detail">
              <?= htmlspecialchars($leave['reason'])?>
          </div>

          <div class="grid-item-detail">
              <label>วันที่ยื่นลา :</label>
          </div>

          <div class="grid-item-detail">
              <?= htmlspecialchars($leave['submitted_at'])?>
          </div>

          <div class="grid-item-detail">
              <label>ขอลาตั้งแต่วันที่ :</label>
          </div>

          <div class="grid-item-detail">
              <?= htmlspecialchars($leave['start_date'])?>
          </div>

          <div class="grid-item-detail">
              <label>จนถึงวันที่ :</label>
          </div>

          <div class="grid-item-detail">
              <?= htmlspecialchars($leave['end_date'])?>
          </div>

      </div>

      

      <!-- ตารางวันลา -->
<table class="leave-day">
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
            <img src="../uploads/<?=htmlspecialchars($leave['evidence_file']) ?>" 
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

</div>

<?php
    $content = ob_get_clean();
    $title = "Leave Detail";
    include __DIR__ . '/layouts/layout.php';
?>