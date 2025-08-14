<?php
require_once '../../config/database.php';
require_once '../../config/security.php';
require_once '../../vendor/autoload.php';

use TCPDF;

class ElectionReport extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 15, 'E-BOTO Election Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
    }
}

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../../index.php");
    exit();
}

$election_id = isset($_GET['election']) ? (int)$_GET['election'] : 0;

if (!$election_id) {
    die('Election ID required');
}

// Create new PDF document
$pdf = new ElectionReport(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('E-BOTO System');
$pdf->SetAuthor('E-BOTO Administrator');
$pdf->SetTitle('Election Report');

// Set default header data
$pdf->setHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'E-BOTO Election Report', '');

// Set margins
$pdf->SetMargins(15, 25, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Add a page
$pdf->AddPage();

// Get election details
$election = $conn->query("SELECT * FROM elections WHERE id = $election_id")->fetch_assoc();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Write(0, $election['title'], '', 0, 'L', true, 0, false, false, 0);
$pdf->Ln(5);

// Election Details
$pdf->SetFont('helvetica', '', 12);
$html = '
<table border="0" cellpadding="5">
    <tr>
        <td width="30%"><b>Status:</b></td>
        <td>'.ucfirst($election['status']).'</td>
    </tr>
    <tr>
        <td><b>Start Date:</b></td>
        <td>'.date('F j, Y h:i A', strtotime($election['start_date'])).'</td>
    </tr>
    <tr>
        <td><b>End Date:</b></td>
        <td>'.date('F j, Y h:i A', strtotime($election['end_date'])).'</td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(10);

// Voter Turnout
$turnout = $conn->query("
    SELECT 
        (SELECT COUNT(DISTINCT voter_id) FROM votes WHERE election_id = $election_id) as total_votes,
        (SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'active') as total_voters
")->fetch_assoc();

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Voter Turnout', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

$turnout_percentage = ($turnout['total_votes'] / $turnout['total_voters']) * 100;
$html = '
<table border="0" cellpadding="5">
    <tr>
        <td width="50%">Total Votes Cast:</td>
        <td>'.$turnout['total_votes'].'</td>
    </tr>
    <tr>
        <td>Total Eligible Voters:</td>
        <td>'.$turnout['total_voters'].'</td>
    </tr>
    <tr>
        <td>Turnout Percentage:</td>
        <td>'.number_format($turnout_percentage, 1).'%</td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(10);

// Position Results
$positions = $conn->query("SELECT * FROM positions WHERE election_id = $election_id ORDER BY position_name");

while ($position = $positions->fetch_assoc()) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $position['position_name'] . ' Results', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);
    
    $candidates = $conn->query("
        SELECT 
            c.*,
            COUNT(v.id) as vote_count,
            (SELECT COUNT(DISTINCT voter_id) FROM votes WHERE position_id = {$position['id']}) as total_votes
        FROM candidates c
        LEFT JOIN votes v ON c.id = v.candidate_id AND v.position_id = {$position['id']}
        WHERE c.position_id = {$position['id']}
        GROUP BY c.id
        ORDER BY vote_count DESC
    ");
    
    $html = '<table border="1" cellpadding="5">
    <tr style="background-color: #f5f5f5;">
        <th width="40%"><b>Candidate</b></th>
        <th width="30%"><b>Votes</b></th>
        <th width="30%"><b>Percentage</b></th>
    </tr>';
    
    $first = true;
    while ($candidate = $candidates->fetch_assoc()) {
        $percentage = $candidate['total_votes'] > 0 ? 
            ($candidate['vote_count'] / $candidate['total_votes'] * 100) : 0;
            
        $style = $first ? ' style="background-color: #e8f5e9;"' : '';
        $html .= '<tr'.$style.'>
            <td>'.$candidate['firstname'].' '.$candidate['lastname'].($first ? ' (Winner)' : '').'</td>
            <td>'.$candidate['vote_count'].'</td>
            <td>'.number_format($percentage, 1).'%</td>
        </tr>';
        $first = false;
    }
    
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(10);
}

// Security Statistics
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Security Statistics', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

$security_stats = $conn->query("
    SELECT 
        action_type,
        COUNT(*) as count
    FROM security_logs
    WHERE created_at BETWEEN '{$election['start_date']}' AND '{$election['end_date']}'
    GROUP BY action_type
    ORDER BY count DESC
");

$html = '<table border="1" cellpadding="5">
<tr style="background-color: #f5f5f5;">
    <th width="70%"><b>Action Type</b></th>
    <th width="30%"><b>Count</b></th>
</tr>';

while ($stat = $security_stats->fetch_assoc()) {
    $html .= '<tr>
        <td>'.ucwords(str_replace('_', ' ', $stat['action_type'])).'</td>
        <td>'.$stat['count'].'</td>
    </tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

// Output the PDF
$pdf->Output('election_report.pdf', 'D');
