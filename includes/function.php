<?php

function getStatusThai($status) {
    $status_labels = [
        'pending'  => 'รออนุมัติ',
        'approved' => 'อนุมัติแล้ว',
        'rejected' => 'ไม่อนุมัติ',
        'unready' => 'ไม่สมบูรณ์'
    ];
    return $status_labels[$status] ?? 'ไม่ทราบสถานะ';
}

?>