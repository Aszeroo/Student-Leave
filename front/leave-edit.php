<?php
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/config.php';

$error = '';

$request_id = $_GET['request_id'] ?? null;
if (!$request_id) {
    die("ไม่พบรหัสคำขอลา");
}

// ดึงข้อมูลเดิมมาแสดง
$stmt = $pdo->prepare("
    SELECT lr.*, s.std_prefix, s.std_fname, s.std_sname, 
           e.education_name, s.sub_major_fullname, c.classname
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.student_id
    LEFT JOIN education e ON s.education_id = e.education_id
    LEFT JOIN classroom c ON s.classroom_id = c.classroom_id
    WHERE lr.request_id = ?
");
$stmt->execute([$request_id]);
$leave = $stmt->fetch();

// **ดึงวันลาเดิม**
$stmt = $pdo->prepare("SELECT * FROM leave_days WHERE request_id=? ORDER BY leave_date ASC");
$stmt->execute([$request_id]);
$leave_days = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$leave) {
    die("ไม่พบข้อมูลคำขอลา");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $student_id = intval($_POST['student_id'] ?? '');
        $leave_type = trim($_POST['leave_type'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $leave_period = floatval($_POST['leave_period'] ?? 0);
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $submitted_at = trim($_POST['submitted_at'] ?? '');
        $status = trim($_POST['status'] ?? 'pending');

        // upload file ใหม่
        $evidence_file = $leave['evidence_file']; // ใช้ไฟล์เดิมก่อน
        if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === 0) {
            $targetDir = '../uploads/';
            $imageName = uniqid() . '_' . basename($_FILES['evidence_file']['name']);
            $targetPath = $targetDir . $imageName;
            if (move_uploaded_file($_FILES['evidence_file']['tmp_name'], $targetPath)) {
                $evidence_file = $imageName;
            } else {
                throw new Exception('ไม่สามารถอัปโหลดไฟล์ได้');
            }
        }

        if (!$student_id || !$leave_type || !$reason || !$start_date || !$end_date || !$submitted_at || !$evidence_file) {
            throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
        }

        $start_date = date('Y-m-d', strtotime($start_date));
        $end_date = date('Y-m-d', strtotime($end_date));
        $submitted_at = date('Y-m-d', strtotime($submitted_at));

        // --- UPDATE leave_requests ---
        $stmt = $pdo->prepare("
            UPDATE leave_requests
            SET student_id=?, leave_type=?, reason=?, leave_period=?, 
                start_date=?, end_date=?, submitted_at=?, evidence_file=?, status=?
            WHERE request_id=?
        ");
        $stmt->execute([
            $student_id, $leave_type, $reason, $leave_period, 
            $start_date, $end_date, $submitted_at, $evidence_file, $status, $request_id
        ]);

        // --- UPDATE leave_days ---
        $leave_dates = $_POST['leave_date'] ?? [];
        $day_names = $_POST['day_name'] ?? [];
        $leave_options = $_POST['leave_option'] ?? [];
        $leave_counts = $_POST['leave_count'] ?? [];

        // ลบข้อมูลเดิมก่อน
        $stmt = $pdo->prepare("DELETE FROM leave_days WHERE request_id = ?");
        $stmt->execute([$request_id]);

        $min = min(count($leave_dates), count($day_names), count($leave_options), count($leave_counts));

        for ($i = 0; $i < $min; $i++) {
            $leave_date = date("Y-m-d", strtotime($leave_dates[$i]));
            $day_name = trim($day_names[$i]);
            $leave_option = trim($leave_options[$i]);
            $leave_count = floatval($leave_counts[$i]);

            $stmt = $pdo->prepare("
                INSERT INTO leave_days (request_id, leave_date, day_name, leave_option, leave_count)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$request_id, $leave_date, $day_name, $leave_option, $leave_count]);
        }

        $pdo->commit();
        $_SESSION['form_updated'] = true;
        header('Location: history.php?id=' . urlencode($student_id));
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

?>

<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<div class="dashboard">
<div class="form">
  <?php if ($error): ?>
        <p style="color: red;"><?=htmlspecialchars($error)?></p>
  <?php endif;?>
    <div class="card-form">
        <h3>🧑🏻‍💼 ข้อมูลส่วนตัว </h3>
        <div class="personal-info">
            <div class="info-item"><strong>ชื่อ-นามสกุล :</strong> <?= htmlspecialchars($leave['std_prefix'] . $leave['std_fname'] . ' ' . $leave['std_sname']) ?></div>
            <div class="info-item"><strong>รหัสนักศึกษา :</strong> <?= htmlspecialchars($leave['student_id']) ?></div>
            <div class="info-item"><strong>ระดับชั้น :</strong> <?= htmlspecialchars($leave['education_name']) ?></div>
            <div class="info-item"><strong>สาขา :</strong> <?= htmlspecialchars($leave['sub_major_fullname']) ?></div>
            <div class="info-item"><strong>ห้อง :</strong> <?= htmlspecialchars($leave['classname']) ?></div>
        </div>
    </div>

  <!-- แบบฟอร์มลา -->
  <div class="card-form">
    <h3>📝 แบบฟอร์มสำหรับกรอกข้อมูล</h3>

    <form method="POST" enctype="multipart/form-data" action="">
      <div class="form-grid grid-container">
          <div class="grid-item">
              <label>รหัสนักศึกษา :</label>
          </div>

          <div class="grid-item">
              <input type="number" name="student_id" value="<?= htmlspecialchars($leave['student_id']) ?>">
          </div>

          <div class="grid-item">
              <label>เรื่อง :</label>
          </div>

          <div class="grid-item">
              <select name="leave_type">
                  <option value="ลากิจ" <?= $leave['leave_type']=='ลากิจ'?'selected':'' ?>>ลากิจ</option>
                  <option value="ลาป่วย" <?= $leave['leave_type']=='ลาป่วย'?'selected':'' ?>>ลาป่วย</option>
              </select>
          </div>

          <div class="grid-item">
              <label type="text" name="reason">เนื่องจาก/รายละเอียด :</label>
          </div>

          <div class="grid-item">
              <textarea name="reason" rows="3"><?= htmlspecialchars($leave['reason']) ?></textarea>
          </div>

          <div class="grid-item">
              <label>วันที่ยื่นลา :</label>
          </div>

          <div class="grid-item">
              <input type="date" name="submitted_at" id="myDate" value="<?= htmlspecialchars($leave['submitted_at']) ?>">
          </div>

          <div class="grid-item">
              <label>ขอลาตั้งแต่วันที่:</label>
          </div>

          <div class="grid-item">
              <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($leave['start_date']) ?>">
          </div>

          <div class="grid-item">
              <label>จนถึงวันที่:</label>
          </div>

          <div class="grid-item">
              <input type="date" id="end_date1" name="end_date" value="<?= htmlspecialchars($leave['end_date']) ?>" onchange="generateLeaveRows()">
          </div>

      </div>

      <!-- ตารางวันลา -->
      <table id="leaveTable">
        <thead class="table-header">
          <tr>
            <th>ลำดับ</th>
            <th>วัน/เดือน/ปี</th>
            <th>วัน</th>
            <th>ตัวเลือก</th>
            <th>จำนวนวันลา (วัน)</th>
          </tr>
        </thead>

        <tbody id="leaveTableBody">
          <!-- แถวจะถูกสร้างที่นี่ -->
        </tbody>

        <tfoot>
          <tr>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>รวม</strong></td>
            <td><input type="hidden" name="leave_period" id="leave_period_input" name="leave_count[]"><span id="totalLeave">0</span></td>
          </tr>
        </tfoot>
      </table>

      <script>
        const today = new Date().toISOString().split('T')[0]; // แปลงวันที่ให้อยู่ในรูปแบบ YYYY-MM-DD
        document.getElementById('myDate').value = today;
      </script>

    <script> 
        const leaveDaysData = <?= json_encode($leave_days) ?>; 
    </script>


      <script>
          const dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

          function generateLeaveRows() {
            const tbody = document.getElementById("leaveTableBody");
            tbody.innerHTML = ""; // เคลียร์ก่อนสร้างแถวใหม่
            let rowCount = 0;

            // --- เติมแถวจากข้อมูลเดิม ---
            leaveDaysData.forEach(day => {
                rowCount++;
                const yyyy_mm_dd = day.leave_date;
                const dayOfWeek = day.day_name;
                const leaveOption = day.leave_option;
                const leaveCount = day.leave_count;

                const row = document.createElement("tr");
                row.innerHTML = `
                    <td>${rowCount}</td>
                    <td>
                        <input type="date" value="${yyyy_mm_dd}" readonly>
                        <input type="hidden" name="leave_date[]" value="${yyyy_mm_dd}">
                    </td>
                    <td>
                        <select disabled><option>${dayOfWeek}</option></select>
                        <input type="hidden" name="day_name[]" value="${dayOfWeek}">
                    </td>
                    <td>
                        <select name="leave_option[]" onchange="updateLeaveDay(this)">
                            <option value="ทั้งวัน" data-count="1" ${leaveOption=='ทั้งวัน'?'selected':''}>ทั้งวัน</option>
                            <option value="ครึ่งวันเช้า" data-count="0.5" ${leaveOption=='ครึ่งวันเช้า'?'selected':''}>ครึ่งวันเช้า</option>
                            <option value="ครึ่งวันบ่าย" data-count="0.5" ${leaveOption=='ครึ่งวันบ่าย'?'selected':''}>ครึ่งวันบ่าย</option>
                        </select>
                    </td>
                    <td class="leave-days">${leaveCount}
                        <input type="hidden" class="leave-count-input" name="leave_count[]" value="${leaveCount}">
                    </td>
                `;
                tbody.appendChild(row);
            });

            updateTotalLeave();
        }

          function updateTotalLeave() {
              const cells = document.querySelectorAll(".leave-days");
              let total = 0;
              cells.forEach(cell => {
                  total += parseFloat(cell.textContent) || 0;
              });
              document.getElementById("totalLeave").textContent = total;
              document.getElementById("leave_period_input").value = total;
          }

      </script>

    <script>
        generateLeaveRows();
    </script>

      <label>เอกสารแนบ : </label>
        <input type="file" name="evidence_file" id="evidence_file" accept="image/*,application/pdf" class="form-control">

      <div class="button-container">
        <button type="cancel" class="btn cancel">ยกเลิก</button>
        <button type="submit" class="btn confirm">ยืนยัน</button>
      </div>
    </form>
  </div>

  <!-- Popup -->
  <div id="popup" class="popup"></div>

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
  </script>

</div>

<?php
    $content = ob_get_clean();
    $title = "Edit";
    include __DIR__ . '/layouts/layout.php';
?>
