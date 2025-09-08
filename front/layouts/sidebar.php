<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/config.php';

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch(); 
?>

<div class="overlay"></div>

<div class="sidebar">
    <?php if ($student): ?>
        <img src="https://www.andresgutierrez.com/wp-content/uploads/2020/03/icono-persona.png" />
        <h4><?= htmlspecialchars($student['std_prefix'] . $student['std_fname'] . ' ' . $student['std_sname']) ?></h4>
        <small> นักศึกษา </small>
    <?php else: ?>
        <span style="color: red; text-align: center; padding-top: 10px;">ไม่พบนักศึกษา</span>
    <?php endif; ?>

    <ul class="sidebar-menu">
        <li><a href="<?= BASE_PATH ?>/front/dashboard.php?id=<?= urlencode($student['student_id']) ?>"><span class="icon">🏠</span> หน้าหลัก</a></li>
        <li><a href="<?= BASE_PATH ?>/front/info.php?id=<?= urlencode($student['student_id']) ?>"><span class="icon">👤</span> ข้อมูลส่วนตัว</a></li>
        <li><a href="<?= BASE_PATH ?>/front/form.php?id=<?= urlencode($student['student_id']) ?>"><span class="icon">📝</span> แบบฟอร์มการแจ้งลา</a></li>
        <li><a href="<?= BASE_PATH ?>/front/history.php?id=<?= urlencode($student['student_id']) ?>"><span class="icon">⏳</span> ประวัติการแจ้งลา</a></li>
        <li><a href="<?= BASE_PATH ?>/login.php"><span class="icon">🚪</span> ออกจากระบบ</a></li>
    </ul>
</div>
