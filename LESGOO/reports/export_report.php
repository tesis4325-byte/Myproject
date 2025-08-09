<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="library_report_'.date('Y-m-d').'.xls"');
header('Cache-Control: max-age=0');

// Get report data
$popular_books = $conn->query("
    SELECT b.title, COUNT(*) as borrow_count 
    FROM borrowings br 
    JOIN books b ON br.book_id = b.id 
    GROUP BY b.id 
    ORDER BY borrow_count DESC
");

$monthly_stats = $conn->query("
    SELECT 
        DATE_FORMAT(borrow_date, '%Y-%m') as month,
        COUNT(*) as total_borrowings,
        SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as total_returns,
        SUM(fine) as total_fines
    FROM borrowings
    WHERE borrow_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month DESC
");

$overdue_books = $conn->query("
    SELECT b.title, m.full_name, br.due_date,
           DATEDIFF(CURRENT_DATE, br.due_date) as days_overdue,
           DATEDIFF(CURRENT_DATE, br.due_date) * 5 as fine
    FROM borrowings br 
    JOIN books b ON br.book_id = b.id 
    JOIN members m ON br.member_id = m.id 
    WHERE br.status = 'borrowed' AND br.due_date < CURRENT_DATE
    ORDER BY br.due_date ASC
");

// Create Excel content
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Library Report</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
</head>
<body>';

// Popular Books Section
echo '<h2>Most Popular Books</h2>
<table border="1">
    <tr>
        <th>Book Title</th>
        <th>Times Borrowed</th>
    </tr>';
while($book = $popular_books->fetch_assoc()) {
    echo '<tr>
        <td>'.htmlspecialchars($book['title']).'</td>
        <td>'.$book['borrow_count'].'</td>
    </tr>';
}
echo '</table><br><br>';

// Monthly Statistics Section
echo '<h2>Monthly Statistics</h2>
<table border="1">
    <tr>
        <th>Month</th>
        <th>Total Borrowings</th>
        <th>Total Returns</th>
        <th>Total Fines</th>
    </tr>';
while($stat = $monthly_stats->fetch_assoc()) {
    echo '<tr>
        <td>'.date('F Y', strtotime($stat['month'].'-01')).'</td>
        <td>'.$stat['total_borrowings'].'</td>
        <td>'.$stat['total_returns'].'</td>
        <td>₱'.number_format($stat['total_fines'], 2).'</td>
    </tr>';
}
echo '</table><br><br>';

// Overdue Books Section
echo '<h2>Overdue Books</h2>
<table border="1">
    <tr>
        <th>Book Title</th>
        <th>Borrower</th>
        <th>Days Overdue</th>
        <th>Fine Amount</th>
    </tr>';
while($book = $overdue_books->fetch_assoc()) {
    echo '<tr>
        <td>'.htmlspecialchars($book['title']).'</td>
        <td>'.htmlspecialchars($book['full_name']).'</td>
        <td>'.$book['days_overdue'].' days</td>
        <td>₱'.number_format($book['fine'], 2).'</td>
    </tr>';
}
echo '</table>';

echo '</body></html>';