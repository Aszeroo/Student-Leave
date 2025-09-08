<?php
include "../Student-Leave/includes/db.php";
require("../Student-Leave/fpdf/fpdf.php");

class PDF extends FPDF {
    function Header() {
        $this->Image('../Student-Leave/uploads/logo.png', 90, 8, 20);
        $this->Ln(20);
        $this->AddFont('THSarabunNew','','THSarabunNew.php');
        $this->SetFont('THSarabunNew','',18);
        $this->Cell(0,10,iconv('UTF-8','cp874','รายงานการลาของนักศึกษา วิทยาลัยเทคโนโลยีพงสวัสดิ์'),0,1,'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('THSarabunNew','',12);
        $this->Cell(0,10,iconv('UTF-8','cp874','หน้า '.$this->PageNo()),0,0,'C');
    }
}

// ฟังก์ชันพิมพ์หัวตาราง
function printTableHeader($pdf, $marginLeft, $widths) {
    $pdf->SetX($marginLeft);
    $pdf->Cell($widths[0],10,iconv('UTF-8','cp874','ลำดับ'),1,0,'C');
    $pdf->Cell($widths[1],10,iconv('UTF-8','cp874','รหัสนักศึกษา'),1,0,'C');
    $pdf->Cell($widths[2],10,iconv('UTF-8','cp874','ชื่อ-นามสกุล'),1,0,'C');
    $pdf->Cell($widths[3],10,iconv('UTF-8','cp874','ห้อง'),1,0,'C');
    $pdf->Cell($widths[4],10,iconv('UTF-8','cp874','สาขา'),1,0,'C');
    $pdf->Cell($widths[5],10,iconv('UTF-8','cp874','จำนวนลา (วัน)'),1,0,'C');
    $pdf->Cell($widths[6],10,iconv('UTF-8','cp874','จำนวนลา (ชั่วโมง)'),1,1,'C');
}

// รับค่าฟิลเตอร์จาก POST
$education_id = $_POST['education_id'] ?? '';
$sub_major_fullname = $_POST['sub_major_fullname'] ?? '';
$classroom_id = $_POST['classroom_id'] ?? '';

// สร้าง SQL พื้นฐาน
$sql = "
    SELECT 
        s.student_id,
        s.std_prefix,
        s.std_fname,
        s.std_sname,
        c.classname,
        s.sub_major_short_name,
        IFNULL(SUM(r.leave_period), 0) AS total_leave_hours,
        IFNULL(SUM(r.leave_period) * 8, 0) AS total_leave_days
    FROM students s
    LEFT JOIN leave_requests r 
        ON s.student_id = r.student_id AND r.status = 'approved'
    LEFT JOIN classroom c ON s.classroom_id = c.classroom_id
    WHERE 1=1
";

$params = [];

if ($education_id !== '') {
    $sql .= " AND s.education_id = :education_id";
    $params[':education_id'] = $education_id;
}
if ($sub_major_fullname !== '') {
    $sql .= " AND s.sub_major_fullname = :sub_major_fullname";
    $params[':sub_major_fullname'] = $sub_major_fullname;
}
if ($classroom_id !== '') {
    $sql .= " AND s.classroom_id = :classroom_id";
    $params[':classroom_id'] = $classroom_id;
}

$sql .= " GROUP BY s.student_id, s.std_prefix, s.std_fname, s.std_sname, c.classname, s.sub_major_short_name
          ORDER BY s.student_id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdf = new PDF();
$pdf->AddPage('P');  // แนวตั้ง
$pdf->AddFont('THSarabunNew','','THSarabunNew.php');
$pdf->SetFont('THSarabunNew','',12); // ลดขนาดฟอนต์ลง

// กำหนดความกว้างของแต่ละคอลัมน์
$widths = [
    10,  // ลำดับ
    22,  // รหัสนักศึกษา
    35,  // ชื่อ-นามสกุล
    30,  // ห้อง
    20,  // สาขา
    30,  // จำนวนลา (ชั่วโมง)
    30   // จำนวนลา (วัน)
];

// รวมความกว้างทั้งหมดของตาราง
$totalTableWidth = array_sum($widths);

// กำหนดขนาดหน้ากระดาษ A4 แนวตั้งกว้าง 210 mm
$pageWidth = 210;

// คำนวณระยะขอบซ้ายเพื่อให้ตารางอยู่กึ่งกลาง
$marginLeft = ($pageWidth - $totalTableWidth) / 2;

// กำหนดขอบบนและล่างของหน้าเพื่อกันข้อมูลชนขอบ
$marginTop = 40;  // ขอบบน (header + margin)
$marginBottom = 20;  // ขอบล่าง (footer + margin)

// ความสูงของหน้ากระดาษ A4 ในหน่วย mm
$pageHeight = 297;

// ความสูงของแถว
$lineHeight = 10;

// คำนวณจำนวนบรรทัดที่สามารถพิมพ์ได้
$maxLinesPerPage = floor(($pageHeight - $marginTop - $marginBottom) / $lineHeight);

$lineCount = 0;
$i = 1;

printTableHeader($pdf, $marginLeft, $widths);
$lineCount++;  // นับแถวหัวตาราง

foreach ($students as $row) {
    if ($lineCount >= $maxLinesPerPage) {
        $pdf->AddPage();
        printTableHeader($pdf, $marginLeft, $widths);
        $lineCount = 1;  // เริ่มนับใหม่ เพราะเพิ่งพิมพ์หัวตารางหน้าใหม่
    }

    $fullname = $row['std_prefix'] . $row['std_fname'] . " " . $row['std_sname'];
    $std_id = 0 . $row['student_id'];

    $pdf->SetX($marginLeft);
    $pdf->Cell($widths[0], $lineHeight, $i, 1, 0, 'C');
    $pdf->Cell($widths[1], $lineHeight, $std_id, 1, 0, 'C');
    $pdf->Cell($widths[2], $lineHeight, iconv('UTF-8', 'cp874', $fullname), 1, 0, 'L');
    $pdf->Cell($widths[3], $lineHeight, iconv('UTF-8', 'cp874', $row['classname']), 1, 0, 'C');
    $pdf->Cell($widths[4], $lineHeight, iconv('UTF-8', 'cp874', $row['sub_major_short_name']), 1, 0, 'C');
    $pdf->Cell($widths[5], $lineHeight, $row['total_leave_hours'], 1, 0, 'C');
    $pdf->Cell($widths[6], $lineHeight, $row['total_leave_days'], 1, 1, 'C');

    $i++;
    $lineCount++;
}

$pdf->Output();
?>
