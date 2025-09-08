<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/config.php';

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
$stmt->execute([$id]);
$teacher = $stmt->fetch(); 
?>

<?php ob_start(); ?>

<div class="overlay"></div>

<div class="sidebar">
    <?php if ($teacher): ?>
    <img src="https://www.andresgutierrez.com/wp-content/uploads/2020/03/icono-persona.png" />
        <h4><?= htmlspecialchars($teacher['prefix_name'] . $teacher['fname'] . ' ' . $teacher['sname']) ?></h4>
        <small> อาจารย์ </small>

    <?php else: ?>
        <span style="color: red; text-align: center; padding-top: 10px;">ไม่พบอาจารย์</span>
    <?php endif; ?>

    <ul class="sidebar-menu">

        <li><a href="<?= BASE_PATH ?>/backoffice/db.php?id=<?= urlencode($teacher['teacher_id']) ?>"><span class="icon">🏠</span> หน้าหลัก</a></li>
        <!-- <li><a href="if-t.php"><span class="icon">👤</span> ข้อมูลส่วนตัว</a></li> -->
        <li><a href="<?= BASE_PATH ?>/backoffice/rq.php?id=<?= urlencode($teacher['teacher_id']) ?>"><span class="icon">🙇🏻‍♀️</span> คำร้องขอการลา</a></li>
        <li><a href="<?= BASE_PATH ?>/backoffice/stu-dt.php?id=<?= urlencode($teacher['teacher_id']) ?>"><span class="icon">📄</span> ข้อมูลนักศึกษา</a></li>
        <li><a href="<?= BASE_PATH ?>/backoffice/his-t.php?id=<?= urlencode($teacher['teacher_id']) ?>"><span class="icon">⏳</span> การลาของนักศึกษา</a></li>
        <li><a href="<?= BASE_PATH ?>/login.php"><span class="icon">🚪</span> ออกจากระบบ</a></li>

    </ul>
</div>