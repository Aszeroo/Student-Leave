<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';

$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ต้องเป็น user == password เท่านั้น
    if ($username !== $password) {
        echo "<p style='color:red;'>ชื่อผู้ใช้และรหัสผ่านต้องตรงกัน</p>";
        exit;
    }

    // ตรวจในตารางนักศึกษา
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$username]);
    $student = $stmt->fetch();

    if ($student) {
        $_SESSION['user_id'] = $student['student_id'];
        $_SESSION['role'] = $student['role'] ?? 'student';
        header("Location: front/dashboard.php?id=" . urlencode($student['student_id']));
        exit;
    }

    // ถ้าไม่พบใน students → ตรวจในตาราง teachers
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
    $stmt->execute([$username]);
    $teacher = $stmt->fetch();

    if ($teacher) {
        $_SESSION['user_id'] = $teacher['teacher_id'];
        $_SESSION['role'] = $teacher['role'] ?? 'teacher';
        header("Location: backoffice/db.php?id=" . urlencode($teacher['teacher_id']));
        exit;
    }

    // ถ้าไม่พบในทั้งสองตาราง
    echo "<p style='color:red;'>ไม่พบผู้ใช้ในระบบ</p>";
}
?>



<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>เข้าสู่ระบบ</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai&display=swap" rel="stylesheet">
<link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>

<div class="grid-container-login">
  <div class="left"></div>

  <div class="right">
    <div class="login-wrapper">
    <img class="logo-login" src="products/img/logopsc.png" alt="logo">
    <h2>ระบบบันทึกการลานักศึกษา<br>วิทยาลัยเทคโนโลยีพงษ์สวัสดิ์</h2>

    <div class="form-box-login">
      <!-- <div class="input-icon-login">
        <i class="fas fa-user"></i>
        <input placeholder="กรอกชื่อผู้ใช้" type="text" name="username" required>
      </div>
      <div class="input-icon-login">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" required>
      </div>
      <button type="submit">
        <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
      </button> -->

      <form method="post" action="">
        <div class="form-box-login">
          <div class="input-icon-login">
            <i class="fas fa-user"></i>
            <input placeholder="Username" type="text" name="username" required>
          </div>
          <div class="input-icon-login">
            <i class="fas fa-lock"></i>
            <input placeholder="Password" type="password" name="password" required>
          </div>
          <button type="submit" name="login_btn">
            <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
          </button>
        </div>
      </form>
    </div>
    </div>
  </div>
</div>

</body>
</html>