<?php
namespace App\Http\Controllers;

use App\Business\Dao\EmployeeTimesheetDao;
use App\Business\Dao\LeaveDao;
use App\Business\Dao\Core\PersonDao;

use App\Business\Model\AppResponse;
use App\Business\Model\PagingAppResponse;
use App\Business\Model\Requester;
use App\Exceptions\AppException;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeTimesheetController extends Controller 
{
	private $requester;
	private $employeeTimesheetDao;
	private $leaveDao;
	private $personDao;

	/**
	 * [__construct]
	 * @param Requester            $requester           
	 * @param EmployeeTimesheetDao $employeeTimesheetDao
	 */
	public function __construct(
				Requester $requester,
				EmployeeTimesheetDao $employeeTimesheetDao,
				LeaveDao $leaveDao,
				PersonDao $personDao) {
		parent::__construct();

		$this->requester 			= $requester;
		$this->employeeTimesheetDao = $employeeTimesheetDao;
		$this->leaveDao 			= $leaveDao;
		$this->personDao 			= $personDao;
	}


	public function advancedSearch(Request $request) {
		$this->validate($request, [
            'companyId' => 'required',
            'menuCode' 	=> 'required',
            'personCode'=> 'required',
            'booisflexy'=> 'required',
            'pageInfo' 	=> 'required',
        ]);

        $request->merge((array)$request->pageInfo);
        
        $this->validate($request, [
            'pageLimit' 	=> 'required|integer|min:0',
            'pageNo' 		=> 'required|integer|min:1',
            'order' 		=> 'nullable|present|string',
            'orderDirection'=> 'nullable|present|in:asc,desc',
        ]);

        $offset 		= PagingAppResponse::getOffset($request->pageInfo);
        $limit 			= PagingAppResponse::getPageLimit($request->pageInfo);
        $pageNo 		= PagingAppResponse::getPageNo($request->pageInfo);
        $order 			= PagingAppResponse::getOrder($request->pageInfo);
        $orderDirection = PagingAppResponse::getOrderDirection($request->pageInfo);

        $data 	= $this->employeeTimesheetDao->advancedSearch(
            $request->employeeId,
            $request->startDate,
            $request->endDate,
            $request->booisflexy,
            $offset,
            $limit,
            $order,
            $orderDirection
        );

        $timesheets = $data[0];
        $totalRows 	= $data[1];

        // \Log::info(json_encode(['timesheets'=>$timesheets, 'totalRows'=>$timesheets]));
        \Log::info(json_encode(['requeset'=>$request]));

        return $this->renderResponse(
            new PagingAppResponse(
                $timesheets,
                trans('messages.dataRetrieved'),
                $limit,
                $totalRows,
                $pageNo)
        );
	}

	/**
	 * [get_convert_time description]
	 * @param  [type] $date_time [description]
	 * @return [type]            [description]
	 */
	private function get_convert_time($date_time)
    {
        return date('H:i', strtotime($date_time));
    }

    /**
     * [get_deviation_time description]
     * @param  [type] $date_time_in  [description]
     * @param  [type] $date_time_out [description]
     * @return [type]                [description]
     */
    private function get_deviation_time($date_time_in, $date_time_out)
    {
        $dt_in  = new \Datetime($date_time_in);
        $dt_out  = new \Datetime($date_time_out);
        $time_diff = $dt_in->diff($dt_out);
        return $time_diff->format('%H:%i');
    }

    /**
     * [downloadAllReport description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
	public function downloadAllReport(Request $request)
    {
    	$this->validate($request, [
            'companyId' => 'required',
            'menuCode' 	=> 'required',
            'personCode'=> 'required',
            'booisflexy'=> 'required',
            'pageInfo' 	=> 'required',
        ]);

        //
        \Log::info('TimeSheetController:downloadAllReport');
        \Log::info($request);

        // ===========
        header('Cache-Control: no-cache');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="ExportScan.xlsx"');
        header('Cache-Control: max-age=0');

        // STYLE
        $styleHeaderReport = [
                                'font' => [
                                    'bold' => true,
                                    'size' => 12,
                                ],
                                'alignment' => [
                                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                                    'vertical' => Alignment::VERTICAL_CENTER,
                                ],
                            ];
        $styleHeaderTable = [
                                'font' => [
                                    'bold' => true,
                                    'size' => 10,
                                    'color' => ['argb' => 'FFFFFF'],
                                ],
                                'alignment' => [
                                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                                    'vertical' => Alignment::VERTICAL_CENTER,
                                ],
                                'fill' => [
                                    'fillType' => Fill::FILL_GRADIENT_LINEAR,
                                    // 'rotation' => 90,
                                    'type' => Fill::FILL_SOLID,
                                    'startColor' => ['argb' => '808080'],
                                    'endColor' => ['argb' => '808080'],
                                ],
                                'borders' => [
                                    'diagonaldirection' => Borders::DIAGONAL_BOTH,
                                    'allborders' => [
                                        'style' => Border::BORDER_THIN,
                                    ],
                                    'color' => ['argb' => '000000'],
                                ],
                            ];
        $styleInfo 		= [
                                'font' => [
                                    'bold' => true,
                                    'size' => 10,
                                    'color' => ['argb' => 'FFFFFF'],
                                ],
                                'fill' => [
                                    'fillType' => Fill::FILL_GRADIENT_LINEAR,
                                    // 'rotation' => 90,
                                    'type' => Fill::FILL_SOLID,
                                    'startColor' => ['argb' => '808080'],
                                    'endColor' => ['argb' => '808080'],
                                ],
                            ];
        $styleStandardReport = [
                        'font' => [
                            'bold' => false,
                            'size' => 10,
                        ],
                    ];

        $spreadsheet = new Spreadsheet();

        /* META DATA */
        $spreadsheet->getProperties()
            ->setCreator("LinovHR3")
            ->setLastModifiedBy("LinovHR3")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription(
                "Test document for Office 2007 XLSX, generated using PHP classes."
            )
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");

        $i      = 2;
        $sheet  = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A'.$i, 'From Date');
        $sheet->setCellValue('A'.($i+2), 'Flexy Hour');
        $sheet->setCellValue('A'.($i+1), 'Employee');
        $sheet->setCellValue('A'.($i+4), 'No');
        $sheet->setCellValue('B'.($i+4), 'Date');
        $sheet->setCellValue('C'.($i+4), 'Time In');
        $sheet->setCellValue('C'.($i+5), 'Schedule');
        $sheet->setCellValue('D'.($i+5), 'Actual');
        $sheet->setCellValue('E'.($i+5), 'Deviation');
        $sheet->setCellValue('F'.$i, strtoupper('Absence And Attendance List'));
        $sheet->setCellValue('F'.($i+4), 'Time Out');
        $sheet->setCellValue('F'.($i+5), 'Schedule');
        $sheet->setCellValue('G'.($i+5), 'Actual');
        $sheet->setCellValue('H'.($i+5), 'Deviation');
        $sheet->setCellValue('I'.($i+4), 'Duration');
        $sheet->setCellValue('I'.($i+5), 'Schedule');
        $sheet->setCellValue('J'.($i+5), 'Actual');
        $sheet->setCellValue('K'.($i+5), 'Deviation');
        $sheet->setCellValue('L'.($i+4), 'Attendance');
        $sheet->setCellValue('M'.($i+4), 'Leave');
        $sheet->setCellValue('M'.($i+5), 'Status');
        $sheet->setCellValue('N'.($i+5), 'Half / Full');
        $sheet->setCellValue('O'.($i+4), 'Overtime');

        // Merge header
        $spreadsheet->getActiveSheet()
        		->mergeCells('A'.($i+4).':A'.($i+5))
        		->mergeCells('B'.($i+4).':B'.($i+5))
        		->mergeCells('B'.($i).':C'.($i))
        		->mergeCells('B'.($i+1).':C'.($i+1))
        		->mergeCells('C'.($i+4).':E'.($i+4))
        		->mergeCells('F'.$i.':I'.$i)
        		->mergeCells('F'.($i+4).':H'.($i+4))
        		->mergeCells('I'.($i+4).':K'.($i+4))
        		->mergeCells('L'.($i+4).':L'.($i+5))
        		->mergeCells('M'.($i+4).':N'.($i+4))
        		->mergeCells('O'.($i+4).':O'.($i+5));

        // Styling
        $spreadsheet->getActiveSheet()->getStyle('F'.$i)->applyFromArray($styleHeaderReport);
        $spreadsheet->getActiveSheet()->getStyle('A'.($i+4).':O'.($i+5))->applyFromArray($styleHeaderTable);
        $spreadsheet->getActiveSheet()->getStyle('A'.$i.':A'.($i+2))->applyFromArray($styleInfo);
        $spreadsheet->getActiveSheet()->getStyle('A'.($i+4).':O'.($i+5))->getAlignment()->setHorizontal('center');
        // Coloring
        

        // PROCESSING DATA //
        $this->validate($request, [
            'companyId' => 'required',
            'menuCode' 	=> 'required',
            'personCode'=> 'required',
            'booisflexy'=> 'required',
            'pageInfo' 	=> 'required',
        ]);
        $request->merge((array)$request->pageInfo);
        $this->validate($request, [
            'pageLimit' => 'required|integer|min:0',
            'pageNo' => 'required|integer|min:1',
            'order' => 'nullable|present|string',
            'orderDirection' => 'nullable|present|in:asc,desc',
        ]);

        $offset = PagingAppResponse::getOffset($request->pageInfo);
        $limit 	= PagingAppResponse::getPageLimit($request->pageInfo);
        $pageNo = PagingAppResponse::getPageNo($request->pageInfo);
        $order 	= PagingAppResponse::getOrder($request->pageInfo);
        $orderDirection = PagingAppResponse::getOrderDirection($request->pageInfo);

        $startDate  	= $request->has('startDate') ? $request->startDate : 'null'; 
        $endDate    	= $request->has('endDate') ? $request->endDate : 'null'; 
        $employeeName   = $request->has('employeeName') ? $request->employeeName : null; 
        $isflexy   		= $request->has('booisflexy') ? (($request->booisflexy=='true') ? 'Yes' : 'No') : 'No'; 

        $data 	= $this->employeeTimesheetDao->advancedSearch(
            $request->employeeId,
            $request->startDate,
            $request->endDate,
            $request->booisflexy,
            $offset,
            $limit,
            $order,
            $orderDirection
        );
        $timesheets = $data[0];
        // PROCESSING DATA //


        if ($startDate != 'null') {
            $sheet->setCellValue('B'.$i, date('Y-m-d', strtotime($startDate)).' s/d '.date('Y-m-d', strtotime($endDate)));
        }

        if ($employeeName) {
            $sheet->setCellValue('B'.($i+1), $request->employeeId.' - '.$employeeName);
        }
        $sheet->setCellValue('B'.($i+2), $isflexy);

        if (!$timesheets)
            return 0;

        $i = 8;
        foreach ($timesheets as $value) 
        {
            $sheet->setCellValue('A'.$i, ($i-7));
            $sheet->setCellValue('B'.$i, $value->date);

            $sheet->setCellValue('C'.$i, ($value->scheduleTimeIn) ? $this->get_convert_time($value->scheduleTimeIn) : '');
            $sheet->setCellValue('D'.$i, ($value->timeIn) ? $this->get_convert_time($value->timeIn) : '');
            $sheet->setCellValue('E'.$i, ($value->deviationTimeOut) ? $value->deviationTimeOut : '');

            $sheet->setCellValue('F'.$i, ($value->scheduleTimeOut) ? $this->get_convert_time($value->scheduleTimeIn) : '');
            $sheet->setCellValue('G'.$i, ($value->timeOut) ? $this->get_convert_time($value->timeOut) : '');
            $sheet->setCellValue('H'.$i, ($value->deviationTimeOut) ? $value->deviationTimeOut : '');

           	$sheet->setCellValue('I'.$i, ($value->scheduleDuration) ? $this->get_convert_time($value->scheduleDuration) : '');
            $sheet->setCellValue('J'.$i, ($value->duration) ? $this->get_convert_time($value->duration) : '');
            $sheet->setCellValue('K'.$i, ($value->deviationDuration) ? $value->deviationDuration : '');

           	$sheet->setCellValue('L'.$i, ($value->attendanceCode) ? $value->attendanceCode : '');

           	// $leaveData = $this->leaveDao->getOne($value->leaveCode);
           	// $sheet->setCellValue('L'.$i, ($leaveData) ? $leaveData->name : '');
           	$sheet->setCellValue('M'.$i, ($value->leaveCode) ? $value->leaveCode : '');
           	$sheet->setCellValue('N'.$i, ($value->leaveWeight) ? $value->leaveWeight : '');
           	$sheet->setCellValue('O'.$i, ($value->overtime) ? $value->overtime : '');

            $i++;
        }
        
        $writer = new Xlsx($spreadsheet);
        // $writer = IOFactory::createWriter($spreadsheet, "Mpdf");
        // $writer = IOFactory::createWriter($spreadsheet, "xlsx");
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        return ($response);
    }
}