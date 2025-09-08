<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_leave";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// ดึง user_id (ตามใน login.php) จาก session 
$student_id = $_SESSION['user_id'] ?? null;


if (!$student_id) {
    die("ไม่พบรหัสนักศึกษา กรุณาเข้าสู่ระบบก่อน");
}

// --- ดึงข้อมูลการลาของนักศึกษาคนนั้น ---
$totalRequestsQuery   = "SELECT COUNT(*) AS total FROM leave_requests WHERE student_id = ?";
$pendingRequestsQuery = "SELECT COUNT(*) AS pending FROM leave_requests WHERE student_id = ? AND status = 'pending'";
$approvedRequestsQuery= "SELECT COUNT(*) AS approved FROM leave_requests WHERE student_id = ? AND status = 'approved'";
$rejectedRequestsQuery= "SELECT COUNT(*) AS rejected FROM leave_requests WHERE student_id = ? AND status = 'rejected'";

$stmt = $conn->prepare($totalRequestsQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$totalRequests = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare($pendingRequestsQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$pendingRequests = $stmt->get_result()->fetch_assoc()['pending'];

$stmt = $conn->prepare($approvedRequestsQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$approvedRequests = $stmt->get_result()->fetch_assoc()['approved'];

$stmt = $conn->prepare($rejectedRequestsQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$rejectedRequests = $stmt->get_result()->fetch_assoc()['rejected'];
?>



<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<div class="dashboard">

    <div class="header">ยินดีต้อนรับเข้าสู่ระบบ!</div>

    <div class="container">
        <div class="col-12 col-md-3">
            <div class="card">
                <img src="https://cdn-icons-png.flaticon.com/512/3652/3652191.png" alt="icon1">
                <div class="text-group">
                    <div class="title">จำนวนครั้งที่ลา</div>
                    <div class="number"><?php echo $totalRequests; ?></div>
                    <div class="footer">คำร้อง</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card">
                <img src="https://i.pinimg.com/564x/ab/83/78/ab8378ba1dc220e174f1fb0eac563fde.jpg" alt="icon2">
                <div class="text-group">
                    <div class="title">การลาที่รออนุมัติ</div>
                    <div class="number"><?php echo $pendingRequests; ?></div>
                    <div class="footer">คำร้อง</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card">
                <img src="https://cdn-icons-png.flaticon.com/512/845/845646.png" alt="icon3">
                <div class="text-group">
                    <div class="title">การลาที่ได้รับอนุมัติ</div>
                    <div class="number"><?php echo $approvedRequests; ?></div>
                    <div class="footer">คำร้อง</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card">
                <img src="https://cdn-icons-png.flaticon.com/512/753/753345.png" alt="icon4">
                <div class="text-group">
                    <div class="title">การลาที่ถูกปฏิเสธ</div>
                    <div class="number"><?php echo $rejectedRequests; ?></div>
                    <div class="footer">คำร้อง</div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
    $content = ob_get_clean();
    $title = "Dashboard";
    include __DIR__ . '/layouts/layout.php';
?>
