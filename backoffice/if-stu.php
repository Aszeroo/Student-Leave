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

$classroom_id = $_GET['classroom_id'] ?? null;

if ($classroom_id) {
    $stmt = $pdo->prepare("SELECT * FROM classroom WHERE classroom_id = ?");
    $stmt->execute([$classroom_id]);
    $classroom_id = $stmt->fetch();
}



?>

<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<div class="card-form">
  <div class="card-form-header">
    <h3>👥 รายชื่อสาขาวิชา </h3>
    <button class="ar-btn-add" onclick="openPopup()">+</button>
  </div>
    <div class="if-stu-filter">
      <div class="if-stu-filter-left">
        <label for="level">ระดับชั้น :</label>
        <select id="level">
          <option>ปวช.</option>
          <option>ปวส.</option>
        </select>

        <label for="major">สาขา :</label>
        <select id="major">
          <option>ทุกสาขา</option>
          <option>การบัญชี</option>
          <option>การตลาด</option>
          <option>เทคโนโลยีสารสนเทศ</option>
          <option>ดิจิทัลกราฟฟิก</option>
          <option>การท่องเที่ยว</option>
          <option>อาหารและโภชนาการ</option>
          <option>คอมพิวเตอร์ธุรกิจ</option>
        </select>
      </div>

    </div>


<!-- กล่อง Popup -->
<div class="ar-popup-overlay" id="popupForm">
  <div class="ar-popup">
    <h3>เพิ่มข้อมูลห้อง</h3>

    <div class="ar-form-row">
      <label>สาขา :</label>
        <select id="ar-major" placeholder="ระบุสาขา">
          <option>การบัญชี</option>
          <option>การตลาด</option>
          <option>เทคโนโลยีสารสนเทศ</option>
          <option>ดิจิทัลกราฟฟิก</option>
          <option>การท่องเที่ยว</option>
          <option>อาหารและโภชนาการ</option>
          <option>คอมพิวเตอร์ธุรกิจ</option>
        </select>
    </div>
    <div class="ar-form-row">
      <label>ระดับชั้น :</label>
        <select id="ar-level" placeholder="ระบุระดับชั้น">
          <option>ปวช.</option>
          <option>ปวส.</option>
        </select>
    </div>
    <div class="ar-form-row">
      <label>ห้อง :</label>
      <input type="text" id="ar-room" placeholder="ระบุห้อง">
    </div>
    <div class="ar-form-row">
      <label>ที่ปรึกษา :</label>
      <input type="text" id="ar-advisor" placeholder="ระบุที่ปรึกษา">
    </div>
    <div class="ar-form-row">
      <label>จำนวนนักศึกษา :</label>
      <input type="number" id="ar-total" placeholder="เช่น 30">
    </div>

    <div class="ar-form-actions">
      <button class="ar-btn-cancel" onclick="closePopup()">ยกเลิก</button>
      <button class="ar-btn-save" onclick="saveRoom()">บันทึก</button>
    </div>
  </div>
</div>

  
<script>
  function openPopup() {
    document.getElementById("popupForm").style.display = "flex";
  }

  function closePopup() {
    document.getElementById("popupForm").style.display = "none";
    clearForm();
  }
</script>

<table class="if-stu-table"  id="ar-table">
  <thead>
    <tr>
      <th>ระดับชั้น</th>
      <th>ห้อง</th>
      <th>สาขา</th>
      <th>จำนวนนักศึกษา</th>
      <th>รายละเอียด</th>
    </tr>
  </thead>
  <tbody id="ar-tbody">
        <tr>
          <td>ปวช.</td>
          <td>2</td>
          <td>การตลาด</td>
          <td>32</td>
          <td>
          <button class="if-stu-search-btn">
          <a href="stu-dt.php?id=<?= urlencode($teacher['teacher_id']) ?>&classroom_id=<?= 1778 ?>"><i class="fas fa-search"></i></a>
          </button>
          </td>
        </tr>
    <!-- แถวจะเพิ่มตรงนี้ -->
  </tbody>
</table>

<script>

  function clearForm() {
    document.getElementById("ar-major");
    document.getElementById("ar-level");
    document.getElementById("ar-room").value = "";
    document.getElementById("ar-advisor").value = "";
    document.getElementById("ar-total").value = "";
  }

  function saveRoom() {
    const major = document.getElementById("ar-major").value;
    const level = document.getElementById("ar-level").value;
    const room = document.getElementById("ar-room").value;
    const advisor = document.getElementById("ar-advisor").value;
    const total = document.getElementById("ar-total").value;

    if (!major || !level || !room || !advisor || !total) {
      alert("กรุณากรอกข้อมูลให้ครบถ้วน");
      return;
    }

    const tbody = document.getElementById("ar-tbody");
    const row = document.createElement("tr");

    row.innerHTML = `
      <td>${level}</td>
      <td>${room}</td>
      <td>${major}</td>
      <td>${total}</td>
      <td>${advisor}</td>
      <td>
      <button class="if-stu-search-btn">
      <a href="lrq-1001.php"><i class="fas fa-search"></i></a>
      </button>
      </td>
    `;

    tbody.appendChild(row);
    closePopup();
  }
</script>




<?php
    $content = ob_get_clean();
    $title = "Information Student";
    include __DIR__ . '/layouts/layout.php';
?>