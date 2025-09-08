<?php

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/function.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "ไม่พบรหัสอาจารย์";
    exit;
}

$stmt2 = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
$stmt2->execute([$id]);
$teacher = $stmt2->fetch();

// กัน null
$status = $leaveRequests[0]['status'] ?? '';

$status = $latest['status'] ?? ''; // กัน null

// ดึงข้อมูลระดับชั้นจาก education
$stmt = $pdo->prepare("SELECT education_id, education_name FROM education ORDER BY education_name ASC");
$stmt->execute();
$educations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลสาขาจาก student (หรือ sub_major table ถ้ามีแยก)
$stmt = $pdo->prepare("SELECT DISTINCT sub_major_fullname 
                       FROM students 
                       WHERE sub_major_fullname IS NOT NULL 
                       ORDER BY sub_major_fullname ASC");
$stmt->execute();
$majors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลห้องจาก classroom
$stmt = $pdo->prepare("SELECT classroom_id, classname FROM classroom ORDER BY classname ASC");
$stmt->execute();
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// รับค่าจากฟอร์ม
$education_id = $_POST['education_id'] ?? '';
$sub_major = $_POST['sub_major_fullname'] ?? '';
$classroom_id = $_POST['classroom_id'] ?? '';

/* ---------- 1) Pending Requests ---------- */
$sql1 = "
    SELECT 
        lr.request_id,
        lr.leave_type,
        lr.submitted_at,
        lr.start_date,
        lr.end_date,
        lr.status,
        s.std_prefix,
        s.std_fname,
        s.std_sname,
        c.classname,
        e.education_name
    FROM leave_requests lr
    LEFT JOIN students s ON lr.student_id = s.student_id
    LEFT JOIN classroom c ON s.classroom_id = c.classroom_id
    LEFT JOIN education e ON c.education_id = e.education_id
    WHERE lr.status = 'pending'
";

$params1 = [];

if (!empty($education_id)) {
    $sql1 .= " AND e.education_id = ? ";
    $params1[] = $education_id;
}

if (!empty($sub_major)) {
    $sql1 .= " AND s.sub_major_fullname = ? ";
    $params1[] = $sub_major;
}

if (!empty($classroom_id)) {
    $sql1 .= " AND c.classroom_id = ? ";
    $params1[] = $classroom_id;
}

$sql1 .= " ORDER BY lr.start_date DESC";

$stmt1 = $pdo->prepare($sql1);
$stmt1->execute($params1);
$leaveRequestsPending = $stmt1->fetchAll(PDO::FETCH_ASSOC);


/* ---------- 2) Approved Requests ---------- */
$sql2 = "
    SELECT 
        lr.request_id,
        lr.leave_type,
        lr.submitted_at,
        lr.start_date,
        lr.end_date,
        lr.status,
        s.std_prefix,
        s.std_fname,
        s.std_sname,
        c.classname,
        e.education_name
    FROM leave_requests lr
    LEFT JOIN students s ON lr.student_id = s.student_id
    LEFT JOIN classroom c ON s.classroom_id = c.classroom_id
    LEFT JOIN education e ON c.education_id = e.education_id
    WHERE lr.status = 'approved'
";

$params2 = [];

if (!empty($education_id)) {
    $sql2 .= " AND e.education_id = ? ";
    $params2[] = $education_id;
}

if (!empty($sub_major)) {
    $sql2 .= " AND s.sub_major_fullname = ? ";
    $params2[] = $sub_major;
}

if (!empty($classroom_id)) {
    $sql2 .= " AND c.classroom_id = ? ";
    $params2[] = $classroom_id;
}

$sql2 .= " ORDER BY lr.start_date DESC";

$stmt2 = $pdo->prepare($sql2);
$stmt2->execute($params2);
$leaveRequestsApprove = $stmt2->fetchAll(PDO::FETCH_ASSOC);

/* ---------- 3) Unready ---------- */
$sql3 = "
    SELECT 
        lr.request_id,
        lr.leave_type,
        lr.submitted_at,
        lr.start_date,
        lr.end_date,
        lr.status,
        s.std_prefix,
        s.std_fname,
        s.std_sname,
        c.classname,
        e.education_name
    FROM leave_requests lr
    LEFT JOIN students s ON lr.student_id = s.student_id
    LEFT JOIN classroom c ON s.classroom_id = c.classroom_id
    LEFT JOIN education e ON c.education_id = e.education_id
    WHERE lr.status = 'unready'
";

$params3 = [];

if (!empty($education_id)) {
    $sql3 .= " AND e.education_id = ? ";
    $params3[] = $education_id;
}

if (!empty($sub_major)) {
    $sql3 .= " AND s.sub_major_fullname = ? ";
    $params3[] = $sub_major;
}

if (!empty($classroom_id)) {
    $sql3 .= " AND c.classroom_id = ? ";
    $params3[] = $classroom_id;
}

$sql3 .= " ORDER BY lr.start_date DESC";

$stmt3 = $pdo->prepare($sql3);
$stmt3->execute($params3);
$leaveRequestsUnready = $stmt3->fetchAll(PDO::FETCH_ASSOC);

/* ---------- 4) Rejected Requests ---------- */
$sql4 = "
    SELECT 
        lr.request_id,
        lr.leave_type,
        lr.submitted_at,
        lr.start_date,
        lr.end_date,
        lr.status,
        s.std_prefix,
        s.std_fname,
        s.std_sname,
        c.classname,
        e.education_name
    FROM leave_requests lr
    LEFT JOIN students s ON lr.student_id = s.student_id
    LEFT JOIN classroom c ON s.classroom_id = c.classroom_id
    LEFT JOIN education e ON c.education_id = e.education_id
    WHERE lr.status = 'rejected'
";

$params4 = [];

if (!empty($education_id)) {
    $sql4 .= " AND e.education_id = ? ";
    $params4[] = $education_id;
}

if (!empty($sub_major)) {
    $sql4 .= " AND s.sub_major_fullname = ? ";
    $params4[] = $sub_major;
}

if (!empty($classroom_id)) {
    $sql4 .= " AND c.classroom_id = ? ";
    $params4[] = $classroom_id;
}

$sql4 .= " ORDER BY lr.start_date DESC";

$stmt4 = $pdo->prepare($sql4);
$stmt4->execute($params4);
$leaveRequestsReject = $stmt4->fetchAll(PDO::FETCH_ASSOC);

?>

<?php ob_start(); ?>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- <div class="card-form">
        <h3>🧑🏻‍💼 ข้อมูลส่วนตัว </h3>
        <div class="personal-info">
            <div class="info-item"><strong>ชื่อ-นามสกุล :</strong> <?= htmlspecialchars($teacher['prefix_name'] . $teacher['fname'] . ' ' . $teacher['sname']) ?></div>
            <div class="info-item"><strong>รหัสอาจารย์ :</strong> <?= htmlspecialchars($teacher['teacher_id']) ?></div>
        </div>
    </div> -->

    <div class="filter">
        <form method="POST">
            <div class="if-stu-filter">
                <div class="if-stu-filter-f">
                    
                    <label for="education">ระดับชั้น :</label>
                    <select name="education_id" id="education">
                        <option value="">-- เลือก --</option>
                        <?php foreach ($educations as $edu): ?>
                            <option value="<?= $edu['education_id'] ?>"><?= htmlspecialchars($edu['education_name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="major">สาขา :</label>
                    <select name="sub_major_fullname" id="major">
                        <option value="">-- เลือก --</option>
                            <?php foreach ($majors as $major): ?>
                                <option value="<?= htmlspecialchars($major['sub_major_fullname']) ?>">
                                    <?= htmlspecialchars($major['sub_major_fullname']) ?>
                                </option>
                            <?php endforeach; ?>
                    </select>

                    <label for="classroom">ห้อง :</label>
                    <select name="classroom_id" id="classroom">
                        <option value="">-- เลือก --</option>
                        <?php foreach ($classrooms as $class): ?>
                            <option value="<?= $class['classroom_id'] ?>"><?= htmlspecialchars($class['classname']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-blue">
                        <i class="fas fa-search"></i>
                    </button>
                    
                </div>
            </div>
        </form>

        <div>
            <button class="btn-blue-f" onclick="filterCards('all')">
                <i class="bi bi-list-ul"></i> ทั้งหมด
            </button>
            <button class="btn-yellow-f" onclick="filterCards('unready')">
                <i class="bi bi-exclamation-circle"></i> ไม่สมบูรณ์
            </button>
            <button class="btn-orange-f" onclick="filterCards('pending')">
                <i class="bi bi-hourglass-split"></i> รออนุมัติ
            </button>
            <button class="btn-red-f" onclick="filterCards('reject')">
                <i class="bi bi-x-circle"></i> ไม่อนุมัติ
            </button>
            <button class="btn-green-f" onclick="filterCards('approve')">
                <i class="bi bi-check-circle"></i> อนุมัติ
            </button>
        </div>
    </div>

    <div class="status" data-status="pending">
        <h3>⏳ การแจ้งลาที่รออนุมัติ </h3>
        <table class="table-his">

            <thead class="table-header">
                <tr>
                <th>รหัสใบลา</th>
                <th>ผู้ขอลา</th>
                <th>ประเภท</th>
                <th>วันที่ยื่น</th>
                <th>วันที่ลา</th>
                <th>สถานะ</th>
                <th>รายละเอียด</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($leaveRequestsPending as $row): ?>
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
                    <!-- <a href="leave-detail-1001.php?id=<?= urlencode($row['request_id']) ?>"> -->
                    <a href="leave-detail.php?id=<?= urlencode($teacher['teacher_id']) ?>&request_id=<?= urlencode($row['request_id']) ?>">
                    <button class="btn-blue"> <i class="fas fa-search"></i>
                    </a>
                </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="status" data-status="unready">
        <h3>⚠️ การแจ้งลาที่ข้อมูลยังไม่ครบ</h3>
        <table class="table-his">
            <thead class="table-header">
                <tr>
                <th>รหัสใบลา</th>
                <th>ผู้ขอลา</th>
                <th>ประเภท</th>
                <th>วันที่ยื่น</th>
                <th>วันที่ลา</th>
                <th>สถานะ</th>
                <th>รายละเอียด</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($leaveRequestsUnready as $ur): ?>
                <tr>
                <td style="color: red;">#<?= htmlentities($ur['request_id'])?></td>
                <td><?= htmlspecialchars($ur['std_prefix'] . $ur['std_fname'] . ' ' . $ur['std_sname']) ?></td>
                <td><?= htmlentities($ur['leave_type'])?></td>
                <td><?= htmlentities($ur['submitted_at'])?></td>
                <td><?= htmlentities($ur['start_date'])?> - <?= htmlentities($ur['end_date'])?></td>
                <td>
                    <button class="status-box <?= $ur['status'] ?>"><?= htmlentities(getStatusThai($ur['status'])) ?></button>
                </td>
                
                <td>
                    <!-- <a href="leave-detail-1001.php?id=<?= urlencode($ur['request_id']) ?>"> -->
                    <a href="his-full-detail.php?id=<?= urlencode($teacher['teacher_id']) ?>&request_id=<?= urlencode($ur['request_id']) ?>">
                    <button class="btn-blue"> <i class="fas fa-search"></i>
                    </a>
                </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="status" data-status="approve">
        <h3>✅ การแจ้งลาที่อนุมัติแล้ว</h3>
        <table class="table-his">
            <thead class="table-header">
                <tr>
                <th>รหัสใบลา</th>
                <th>ผู้ขอลา</th>
                <th>ประเภท</th>
                <th>วันที่ยื่น</th>
                <th>วันที่ลา</th>
                <th>สถานะ</th>
                <th>รายละเอียด</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($leaveRequestsApprove as $rw): ?>
                <tr>
                <td style="color: red;">#<?= htmlentities($rw['request_id'])?></td>
                <td><?= htmlspecialchars($rw['std_prefix'] . $rw['std_fname'] . ' ' . $rw['std_sname']) ?></td>
                <td><?= htmlentities($rw['leave_type'])?></td>
                <td><?= htmlentities($rw['submitted_at'])?></td>
                <td><?= htmlentities($rw['start_date'])?> - <?= htmlentities($rw['end_date'])?></td>
                <td>
                    <button class="status-box <?= $rw['status'] ?>"><?= htmlentities(getStatusThai($rw['status'])) ?></button>
                </td>
                
                <td>
                    <!-- <a href="leave-detail-1001.php?id=<?= urlencode($rw['request_id']) ?>"> -->
                    <a href="his-full-detail.php?id=<?= urlencode($teacher['teacher_id']) ?>&request_id=<?= urlencode($rw['request_id']) ?>">
                    <button class="btn-blue"> <i class="fas fa-search"></i>
                    </a>
                </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    

    <div class="status" data-status="reject">
        <h3>❌ การแจ้งลาที่ไม่อนุมัติ</h3>
        <table class="table-his">
            <thead class="table-header">
                <tr>
                <th>รหัสใบลา</th>
                <th>ผู้ขอลา</th>
                <th>ประเภท</th>
                <th>วันที่ยื่น</th>
                <th>วันที่ลา</th>
                <th>สถานะ</th>
                <th>รายละเอียด</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($leaveRequestsReject as $r): ?>
                <tr>
                <td style="color: red;">#<?= htmlentities($r['request_id'])?></td>
                <td><?= htmlspecialchars($r['std_prefix'] . $r['std_fname'] . ' ' . $r['std_sname']) ?></td>
                <td><?= htmlentities($r['leave_type'])?></td>
                <td><?= htmlentities($r['submitted_at'])?></td>
                <td><?= htmlentities($r['start_date'])?> - <?= htmlentities($r['end_date'])?></td>
                <td>
                    <button class="status-box <?= $r['status'] ?>"><?= htmlentities(getStatusThai($r['status'])) ?></button>
                </td>
                
                <td>
                    <!-- <a href="leave-detail-1001.php?id=<?= urlencode($r['request_id']) ?>"> -->
                    <a href="his-full-detail.php?id=<?= urlencode($teacher['teacher_id']) ?>&request_id=<?= urlencode($r['request_id']) ?>">
                    <button class="btn-blue"> <i class="fas fa-search"></i>
                    </a>
                </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<script>
    function filterCards(status) {
    let cards = document.querySelectorAll(".status");
    cards.forEach(card => {
        if (status === "all" || card.dataset.status === status) {
        card.style.display = "block"; // แสดงการ์ด
        } else {
        card.style.display = "none";  // ซ่อนการ์ด
        }
    });
    }
</script>

<?php
    $content = ob_get_clean();
    $title = "Leave Request";
    include __DIR__ . '/layouts/layout.php';
?>