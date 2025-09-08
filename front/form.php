<?php

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/config.php';

$error = '';

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt = $pdo->prepare("
    SELECT s.*, e.education_name, c.classname
    FROM students s
    LEFT JOIN education e ON s.education_id = e.education_id
    LEFT JOIN classroom c ON s.classroom_id = c.classroom_id
    WHERE s.student_id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch(); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction(); // ✅ ครอบทั้ง 2 ตาราง

        $student_id = intval($_POST['student_id'] ?? '');
        $leave_type = trim($_POST['leave_type'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $leave_period = floatval($_POST['leave_period'] ?? 0);
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $submitted_at = trim($_POST['submitted_at'] ?? '');
        $status = trim($_POST['status'] ?? 'pending');

        // อัปโหลดไฟล์
        $evidence_file = null;
        if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === 0) {
            $targetDir = '../uploads/';
            $imageName = uniqid() . '_' . basename($_FILES['evidence_file']['name']);
            $targetPath = $targetDir . $imageName;
            if (move_uploaded_file($_FILES['evidence_file']['tmp_name'], $targetPath)) {
                $evidence_file = $imageName;
            } else {
                throw new Exception('ไม่สามารถอัปโหลดรูปภาพได้');
            }
        }

        // ถ้าไม่กรอกไฟล์ → status = unready
        if (!$evidence_file) {
            $status = 'unready';
        }

        if (!$student_id || !$leave_type || !$reason || !$start_date || !$end_date || !$submitted_at ) {
            throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
        }

        $start_date = date('Y-m-d', strtotime($start_date));
        $end_date = date('Y-m-d', strtotime($end_date));
        $submitted_at = date('Y-m-d', strtotime($submitted_at));

        // --- INSERT INTO leave_requests ---
        $stmt = $pdo->prepare("
            INSERT INTO leave_requests (student_id, leave_type, reason, leave_period, start_date, end_date, submitted_at, evidence_file, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $leave_type, $reason, $leave_period, $start_date, $end_date, $submitted_at, $evidence_file, $status]);

        $request_id = $pdo->lastInsertId(); // ดึง request_id มาใช้กับ leave_days

        // --- INSERT INTO leave_days ---
        $leave_dates = $_POST['leave_date'] ?? '';
        $day_names = $_POST['day_name'] ?? '';
        $leave_options = $_POST['leave_option'] ?? 'ทั้งวัน';
        $leave_counts = $_POST['leave_count'] ?? '';

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

        $pdo->commit(); // ✅ สำเร็จ → commit
        $_SESSION['form_created'] = true;
        header('Location: history.php?id=' . urlencode($student['student_id']));
        exit;

    } catch (Exception $e) {
        $pdo->rollBack(); // ❌ ล้มเหลว → rollback
        echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

<div class="form">
  <?php if ($error): ?>
        <p style="color: red;"><?=htmlspecialchars($error)?></p>
  <?php endif;?>
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

  <!-- แบบฟอร์มลา -->
  <div class="card-form">
    <h3>📝 แบบฟอร์มสำหรับกรอกข้อมูล</h3>

    <form method="POST" enctype="multipart/form-data" action="">
      <div class="form-grid grid-container">
          <div class="grid-item">
              <label>รหัสนักศึกษา :</label>
          </div>

          <div class="grid-item">
              <input type="number" name="student_id" value="0<?= htmlspecialchars($student['student_id']) ?>">
          </div>

          <div class="grid-item">
              <label>เรื่อง :</label>
          </div>

          <div class="grid-item">
              <select name="leave_type">
                  <option value="ลากิจ">ลากิจ</option>
                  <option value="ลาป่วย">ลาป่วย</option>
              </select>
          </div>

          <div class="grid-item">
              <label type="text" name="reason">เนื่องจาก/รายละเอียด :</label>
          </div>

          <div class="grid-item">
              <textarea name="reason" rows="3"></textarea>
          </div>

          <div class="grid-item">
              <label>วันที่ยื่นลา :</label>
          </div>

          <div class="grid-item">
              <input type="date" name="submitted_at" id="myDate">
          </div>

          <div class="grid-item">
              <label>ขอลาตั้งแต่วันที่:</label>
          </div>

          <div class="grid-item">
              <input type="date" name="start_date" id="start_date">
          </div>

          <div class="grid-item">
              <label>จนถึงวันที่:</label>
          </div>

          <div class="grid-item">
              <input type="date" id="end_date1" name="end_date" onchange="generateLeaveRows()">
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
          const dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

          function generateLeaveRows() {
              const start = document.getElementById("start_date").value;
              const end = document.getElementById("end_date1").value;

              if (!start || !end) return;

              const tbody = document.getElementById("leaveTableBody");
              tbody.innerHTML = ""; // เคลียร์ตารางเก่า

              const start_date = new Date(start);
              const end_date = new Date(end);
              let rowCount = 0;

              for (let d = new Date(start_date); d <= end_date; d.setDate(d.getDate() + 1)) {
              rowCount++;

              const yyyy_mm_dd = d.toISOString().split("T")[0];
              const dayOfWeek = dayNames[d.getDay()];

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
                      <option value="ทั้งวัน" data-count="1">ทั้งวัน</option>
                      <option value="ครึ่งวันเช้า" data-count="0.5">ครึ่งวันเช้า</option>
                      <option value="ครึ่งวันบ่าย" data-count="0.5">ครึ่งวันบ่าย</option>
                    </select>
                  </td>
                  <td class="leave-days">1
                    <input type="hidden" class="leave-count-input" name="leave_count[]" value="1">
                  </td>
                  `;
              tbody.appendChild(row);
              }

              updateTotalLeave();
          }

          function updateLeaveDay(select) {
              const selectedOption = select.options[select.selectedIndex];
              const count = parseFloat(selectedOption.dataset.count);  

              const tr = select.closest("tr");

              const td = tr.querySelector(".leave-days");
              td.childNodes[0].nodeValue = count; 
              
              const input = tr.querySelector(".leave-count-input");
              input.value = count;

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



<?php
    $content = ob_get_clean();
    $title = "Form";
    include __DIR__ . '/layouts/layout.php';
?>