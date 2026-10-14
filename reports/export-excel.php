<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

// Include PhpSpreadsheet Autoloader
$autoloader = __DIR__ . "/../vendor/autoload.php";

if (!file_exists($autoloader)) {
    die("PhpSpreadsheet is not installed. Please run 'composer require phpoffice/phpspreadsheet' in the project root.");
}

require_once $autoloader;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;


// Validate Month Parameter
$selected_month = $_GET["month"] ?? date("Y-m");

if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $selected_month)) {
    $selected_month = date("Y-m");
}


// Create Date Range
$start_date = $selected_month . "-01";
$end_date = date("Y-m-d", strtotime($start_date . " +1 month"));


// Format Month
$formatted_month_display = date("F Y", strtotime($start_date));


// Query Report Data
$report_query = "
    SELECT 
        s.course,
        COALESCE(SUM(p.amount), 0) AS total_payment
    FROM payments p
    INNER JOIN students s 
        ON p.student_id = s.id
    WHERE 
        p.payment_date >= ?
        AND p.payment_date < ?
    GROUP BY s.course
    ORDER BY total_payment DESC
";

$stmt = $conn->prepare($report_query);

$courses_report = [];
$highest_course = null;

if ($stmt) {

    $stmt->bind_param(
        "ss",
        $start_date,
        $end_date
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $courses_report[] = $row;
    }

    $stmt->close();

    if (!empty($courses_report)) {
        $highest_course = $courses_report[0];
    }
}

$conn->close();


// Create Spreadsheet
$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Monthly Report");


// ================================
// HEADER BRANDING
// ================================

$sheet->setCellValue("A1", "ALFA COMPUTER");

$sheet->mergeCells("A1:B1");

$sheet->getStyle("A1")
    ->getFont()
    ->setBold(true)
    ->setSize(16)
    ->setColor(new Color("4F46E5"));


$sheet->setCellValue(
    "A2",
    "FEES MANAGEMENT SYSTEM"
);

$sheet->mergeCells("A2:B2");

$sheet->getStyle("A2")
    ->getFont()
    ->setBold(true)
    ->setSize(11)
    ->setColor(new Color("6B7280"));


// Report Title
$sheet->setCellValue(
    "A3",
    "Monthly Course Payment Report - " .
    $formatted_month_display
);

$sheet->mergeCells("A3:B3");

$sheet->getStyle("A3")
    ->getFont()
    ->setBold(true)
    ->setSize(12);



// ================================
// TABLE HEADERS
// ================================

$sheet->setCellValue(
    "A5",
    "Course"
);

$sheet->setCellValue(
    "B5",
    "Total Payment Received"
);


$headerStyle = [

    "font" => [
        "bold" => true,
        "color" => [
            "rgb" => "FFFFFF"
        ]
    ],

    "fill" => [
        "fillType" => Fill::FILL_SOLID,

        "startColor" => [
            "rgb" => "4F46E5"
        ]
    ],

    "alignment" => [
        "vertical" => Alignment::VERTICAL_CENTER
    ]

];


$sheet->getStyle("A5:B5")
    ->applyFromArray($headerStyle);


$sheet->getStyle("B5")
    ->getAlignment()
    ->setHorizontal(
        Alignment::HORIZONTAL_RIGHT
    );



// ================================
// TABLE DATA
// ================================

$rowNum = 6;


if (!empty($courses_report)) {

    foreach ($courses_report as $data) {

        $sheet->setCellValue(
            "A" . $rowNum,
            $data["course"]
        );


        $sheet->setCellValue(
            "B" . $rowNum,
            (float)$data["total_payment"]
        );


        $sheet->getStyle(
            "B" . $rowNum
        )
        ->getNumberFormat()
        ->setFormatCode(
            '"₹"#,##0.00'
        );


        $rowNum++;
    }

} else {

    $sheet->setCellValue(
        "A" . $rowNum,
        "No payment records found for this month."
    );


    $sheet->mergeCells(
        "A" . $rowNum .
        ":B" . $rowNum
    );


    $sheet->getStyle(
        "A" . $rowNum
    )
    ->getAlignment()
    ->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    );


    $rowNum++;
}



// ================================
// TABLE BORDERS
// ================================

$borderStyle = [

    "borders" => [

        "allBorders" => [

            "borderStyle" => Border::BORDER_THIN,

            "color" => [
                "rgb" => "E5E7EB"
            ]

        ]

    ]

];


$sheet->getStyle(
    "A5:B" . ($rowNum - 1)
)
->applyFromArray($borderStyle);



// ================================
// SUMMARY
// ================================

$rowNum += 2;


$sheet->setCellValue(
    "A" . $rowNum,
    "Highest Payment Course:"
);


$sheet->setCellValue(
    "B" . $rowNum,
    $highest_course
        ? $highest_course["course"]
        : "No data available."
);


$sheet->getStyle(
    "A" . $rowNum
)
->getFont()
->setBold(true);


$rowNum++;


$sheet->setCellValue(
    "A" . $rowNum,
    "Highest Payment Amount:"
);


if ($highest_course) {

    $sheet->setCellValue(
        "B" . $rowNum,
        (float)$highest_course["total_payment"]
    );


    $sheet->getStyle(
        "B" . $rowNum
    )
    ->getNumberFormat()
    ->setFormatCode(
        '"₹"#,##0.00'
    );

} else {

    $sheet->setCellValue(
        "B" . $rowNum,
        "N/A"
    );
}


$sheet->getStyle(
    "A" . $rowNum
)
->getFont()
->setBold(true);



// ================================
// COLUMN WIDTH
// ================================

$sheet->getColumnDimension("A")
    ->setWidth(30);

$sheet->getColumnDimension("B")
    ->setWidth(30);



// ================================
// FILE NAME
// ================================

$clean_month_name = str_replace(
    " ",
    "_",
    $formatted_month_display
);


$filename =
    "ALFA_COMPUTER_Report_" .
    $clean_month_name .
    ".xlsx";



// ================================
// DOWNLOAD HEADERS
// ================================

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    'Content-Disposition: attachment; filename="' .
    $filename .
    '"'
);

header("Cache-Control: max-age=0");

header(
    "Expires: Mon, 26 Jul 1997 05:00:00 GMT"
);

header(
    "Last-Modified: " .
    gmdate("D, d M Y H:i:s") .
    " GMT"
);

header(
    "Cache-Control: cache, must-revalidate"
);

header("Pragma: public");


// Create Excel File
$writer = new Xlsx($spreadsheet);

$writer->save("php://output");

exit;

?>
