<?php
session_start();
include 'config.php';

// Angalia kama ni admin
if (!isset($_SESSION['username']) || !isAdmin($_SESSION['username'], $conn)) {
    header("Location: login.php");
    exit();
}

// Chukua data kwa ajili ya kuhamisha
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$stmt = $conn->prepare("SELECT name, department, date, time_in, time_out 
                        FROM attendance 
                        WHERE date BETWEEN ? AND ?
                        ORDER BY date DESC");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

// Weka header za Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="attendance_report_'.date('Ymd').'.xls"');
header('Pragma: no-cache');
header('Expires: 0');


// Output Excel file
echo "Name\tDepartment\tDate\tTime In\tTime Out\tHours\n";
foreach ($data as $row) {
    $hours = '--';
    if ($row['time_out']) {
        $time_in = new DateTime($row['time_in']);
        $time_out = new DateTime($row['time_out']);
        $diff = $time_out->diff($time_in);
        $hours = $diff->h . 'h ' . $diff->i . 'm';
    }
    
    echo implode("\t", [
        $row['name'],
        $row['department'],
        $row['date'],
        $row['time_in'],
        $row['time_out'] ?: '--',
        $hours
    ]) . "\n";
}
exit();