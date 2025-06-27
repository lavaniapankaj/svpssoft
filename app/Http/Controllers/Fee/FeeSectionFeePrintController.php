<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\Fee\FeeDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Rmunate\Utilities\SpellNumber;

class FeeSectionFeePrintController extends Controller
{
    //

    public function fillDetails(Request $request)
    {
        try {
            // Get query parameters
            $recp_no = $request->query('recpNo');
            $fee_id = (int) $request->query('feeId'); // Ensure fee_id is an integer
            $sess = $request->query('session');

            // Fetch fee details
            $feeDetails = FeeDetail::select(
                'stu_main_srno.gender',
                'stu_main_srno.srno',
                'stu_main_srno.school',
                'stu_detail.name',
                'parents_detail.f_name',
                'class_masters.class',
                'section_masters.section',
                'fee_details.srno',
                'fee_details.amount',
                'fee_details.fee_of',
                'fee_details.pay_date',
                'session_masters.session',
                'fee_details.session_id'
            )
                ->join('stu_main_srno', 'stu_main_srno.srno', '=', 'fee_details.srno')
                ->join('stu_detail', 'stu_detail.srno', '=', 'fee_details.srno')
                ->join('parents_detail', 'parents_detail.srno', '=', 'fee_details.srno')
                ->join('class_masters', 'class_masters.id', '=', 'stu_main_srno.class')
                ->join('section_masters', 'section_masters.id', '=', 'stu_main_srno.section')
                ->join('session_masters', 'session_masters.id', '=', 'fee_details.session_id')
                ->where('fee_details.recp_no', $recp_no)
                ->where('fee_details.academic_trans', $fee_id)
                ->where('fee_details.session_id', $sess)
                ->where('fee_details.paid_mercy', 1)
                ->where('fee_details.active', 1)
                ->where('stu_main_srno.session_id', $sess)
                ->whereIn('stu_main_srno.ssid', [1, 2, 3, 4, 5])
                ->where('stu_detail.active', 1)
                ->where('parents_detail.active', 1)
                ->get();

            if ($feeDetails->count() > 0) {
                // Sum total amount
                $total = $feeDetails->sum('amount');

                // Extract first record details for student info
                $firstRecord = $feeDetails->first();

                // Generate fee_of descriptions based on fee_id
                $feeOfDescriptions = $feeDetails->pluck('fee_of')->unique()->map(function ($feeOf) use ($fee_id) {
                    return match (true) {
                        ($fee_id == 1 && $feeOf == 1) => 'Admission Fee',
                        ($fee_id == 1 && $feeOf == 2) => 'Ist Installment Fee',
                        ($fee_id == 1 && $feeOf == 3) => 'IInd Installment Fee',
                        ($fee_id == 1 && $feeOf == 4) => 'Complete Fee',
                        ($fee_id == 2 && $feeOf == 1) => 'Ist Installment Fee',
                        ($fee_id == 2 && $feeOf == 2) => 'IInd Installment Fee',
                        ($fee_id == 2 && $feeOf == 3) => 'Complete Fee',
                        default => 'Mercy Fee'
                    };
                })->implode(', ');

                return view('fee.fee_entry.print_fee_slip')
                    ->with('school', $firstRecord->school == 1 ? 'PLAY HOUSE' : 'Public School')
                    ->with('recp_no', $recp_no)
                    ->with('date', Carbon::parse($firstRecord->pay_date)->format('d-M-Y'))
                    ->with('name', $firstRecord->name)
                    ->with('father_name', $firstRecord->f_name)
                    ->with('gender', $firstRecord->gender)
                    ->with('class', $firstRecord->class)
                    ->with('section', $firstRecord->section)
                    ->with('total', (int) $total)
                    ->with('wordstotal', $this->convertToWords((int) $total))
                    ->with('fee_of', $feeOfDescriptions)
                    ->with('session', $firstRecord->session);
            } else {
                return redirect()->back()->with('error', 'Details Not Found.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }



    // Helper function to convert number to words
    private function convertToWords($number)
    {

        $num  = (string) ((int) $number);

        if ((int) ($num) && ctype_digit($num)) {
            $words  = array();

            $num    = str_replace(array(',', ' '), '', trim($num));

            $list1  = array(
                '',
                'one',
                'two',
                'three',
                'four',
                'five',
                'six',
                'seven',
                'eight',
                'nine',
                'ten',
                'eleven',
                'twelve',
                'thirteen',
                'fourteen',
                'fifteen',
                'sixteen',
                'seventeen',
                'eighteen',
                'nineteen'
            );

            $list2  = array(
                '',
                'ten',
                'twenty',
                'thirty',
                'forty',
                'fifty',
                'sixty',
                'seventy',
                'eighty',
                'ninety',
                'hundred'
            );

            $list3  = array(
                '',
                'thousand',
                'million',
                'billion',
                'trillion',
                'quadrillion',
                'quintillion',
                'sextillion',
                'septillion',
                'octillion',
                'nonillion',
                'decillion',
                'undecillion',
                'duodecillion',
                'tredecillion',
                'quattuordecillion',
                'quindecillion',
                'sexdecillion',
                'septendecillion',
                'octodecillion',
                'novemdecillion',
                'vigintillion'
            );

            $num_length = strlen($num);
            $levels = (int) (($num_length + 2) / 3);
            $max_length = $levels * 3;
            $num    = substr('00' . $num, -$max_length);
            $num_levels = str_split($num, 3);

            foreach ($num_levels as $num_part) {
                $levels--;
                $hundreds   = (int) ($num_part / 100);
                $hundreds   = ($hundreds ? ' ' . $list1[$hundreds] . ' Hundred' . ($hundreds == 1 ? '' : 's') . ' ' : '');
                $tens       = (int) ($num_part % 100);
                $singles    = '';

                if ($tens < 20) {
                    $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '');
                } else {
                    $tens = (int) ($tens / 10);
                    $tens = ' ' . $list2[$tens] . ' ';
                    $singles = (int) ($num_part % 10);
                    $singles = ' ' . $list1[$singles] . ' ';
                }
                $words[] = $hundreds . $tens . $singles . (($levels && (int) ($num_part)) ? ' ' . $list3[$levels] . ' ' : '');
            }
            $commas = count($words);
            if ($commas > 1) {
                $commas = $commas - 1;
            }

            $words  = implode(', ', $words);

            $words  = trim(str_replace(' ,', ',', ucwords($words)), ', ');
            if ($commas) {
                $words  = str_replace(',', ' and', $words);
            }
        } else if (! ((int) $num)) {
            $words = 'Zero';
        } else {
            $words = '';
        }

        return $words;
    }
}
