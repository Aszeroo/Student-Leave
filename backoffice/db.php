<?php
    $servername = "localhost";  // หรือ IP ที่ใช้
    $username = "root";  // ชื่อผู้ใช้ฐานข้อมูล
    $password = "";  // รหัสผ่าน
    $dbname = "student_leave";  // ชื่อฐานข้อมูล

    // สร้างการเชื่อมต่อ
    $conn = new mysqli($servername, $username, $password, $dbname);

    // เช็คการเชื่อมต่อ
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    session_start();

    // คำสั่ง SQL เพื่อดึงข้อมูลคำร้องทั้งหมด
    $totalRequestsQuery = "SELECT COUNT(*) AS total FROM leave_requests";
    $pendingRequestsQuery = "SELECT COUNT(*) AS pending FROM leave_requests WHERE status = 'pending'";
    $approvedRequestsQuery = "SELECT COUNT(*) AS approved FROM leave_requests WHERE status = 'approved'";
    $rejectedRequestsQuery = "SELECT COUNT(*) AS rejected FROM leave_requests WHERE status = 'rejected'";

    // ดึงผลลัพธ์จากฐานข้อมูล
    $totalRequestsResult = $conn->query($totalRequestsQuery);
    $pendingRequestsResult = $conn->query($pendingRequestsQuery);
    $approvedRequestsResult = $conn->query($approvedRequestsQuery);
    $rejectedRequestsResult = $conn->query($rejectedRequestsQuery);

    // ตรวจสอบว่า query สำเร็จหรือไม่
    if (!$totalRequestsResult || !$pendingRequestsResult || !$approvedRequestsResult || !$rejectedRequestsResult) {
        die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $conn->error);
    }

    // ดึงข้อมูลจากผลลัพธ์
    $totalRequests = $totalRequestsResult->fetch_assoc()['total'];
    $pendingRequests = $pendingRequestsResult->fetch_assoc()['pending'];
    $approvedRequests = $approvedRequestsResult->fetch_assoc()['approved'];
    $rejectedRequests = $rejectedRequestsResult->fetch_assoc()['rejected'];
?>

<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<div class="dashboard">

    <div class="header">ยินดีต้อนรับเข้าสู่ระบบ!</div>

    <div class="container">
        <div class="card">
            <img src="https://cdn-icons-png.flaticon.com/512/3652/3652191.png" alt="icon1">
            <div class="text-group">
                <div class="title">คำร้องขอลาทั้งหมด</div>
                <div class="number"><?php echo $totalRequests; ?></div>
                <div class="footer">คำร้อง</div>
            </div>
        </div>

        <div class="card">
            <img src="https://i.pinimg.com/564x/ab/83/78/ab8378ba1dc220e174f1fb0eac563fde.jpg" alt="icon2">
            <div class="text-group">
                <div class="title">คำร้องที่รออนุมัติ</div>
                <div class="number"><?php echo $pendingRequests; ?></div>
                <div class="footer">คำร้อง</div>
            </div>
        </div>

        <div class="card">
            <img src="https://cdn-icons-png.flaticon.com/512/845/845646.png" alt="icon3">
            <div class="text-group">
                <div class="title">การลาที่อนุมัติแล้ว</div>
                <div class="number"><?php echo $approvedRequests; ?></div>
                <div class="footer">คำร้อง</div>
            </div>
        </div>

        <div class="card">
            <img src="https://cdn-icons-png.flaticon.com/512/753/753345.png" alt="icon4">
            <div class="text-group">
                <div class="title">การลาปฏิเสธแล้ว</div>
                <div class="number"><?php echo $rejectedRequests; ?></div>
                <div class="footer">คำร้อง</div>
            </div>
        </div>
    </div>

</div>

<?php
    $content = ob_get_clean();
    $title = "Dashboard";
    include __DIR__ . '/layouts/layout.php';
?>
