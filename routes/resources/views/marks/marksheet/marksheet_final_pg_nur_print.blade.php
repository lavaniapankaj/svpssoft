@extends ('marks.index')
@section('sub-content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 bg-white">
           <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">{{ 'Print Final Marksheet (PG & Nursury)' }}</h5>
                    <div class="align-items-center d-flex gap-2">
                        <a href="{{ route('marks.marks-report.marksheet.pg.nursary') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                        <button type="button" id="print-marksheet" class="btn btn-primary btn-sm mx-2 print-marksheet" >Print Marksheet</button>
                    </div>
                </div>
                <div class="card-body">
                        <input type="hidden" id="class" value="{{$class}}">
                        <input type="hidden" id="section" value="{{$section}}">
                        <input type="hidden" id="students" value="{{$students}}">
                        <input type="hidden" id="session-message" value="{{$sessionMessage}}">
                        <input type="hidden" id="date-message" value="{{$dateMessage}}">
                    <div class="row">
                        <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                    id="loader" style="width:10%;">
                        <div class="marksheet-div">
                            <div class="marksheet">
                            </div>
                            <div class="mt-3">
                                <button type="button" id="print-marksheet" class="btn btn-primary print-marksheet">Print
                                    Marksheet</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('marks-scripts')
<script>
    $(document).ready(function() {
        let classId = $('#class').val();
        let sectionId = $('#section').val();
        let sessionMessage = $('#session-message').val();
        let dateMessage = $('#date-message').val();
        let stdId = $('#students').val();
        if (classId && sectionId && stdId) {
            $.ajax({
                url: siteUrl + '/marks/marksheet-final-pg-nursary/report',
                type: 'GET',
                dataType: 'JSON',
                data: {
                    class: classId,
                    section: sectionId,
                    std_id: stdId,
                    sessionMessage: sessionMessage,
                    dateMessage: dateMessage,
                },
                success: function(response) {
                    if (response) {
                        let tableHtml = '';
                        response.students.forEach(student => {
                            // Extract all necessary data
                            const studentInfo = student.student_details;
                            const subjectMarks = student.subject_marks;
                            const attendance = student.attendance;
                            const result = student.result_data;
                            // Get unique valid exams
                            const uniqueExams = new Map();
                            subjectMarks.forEach(subject => {
                                if (subject.exams && Array.isArray(subject.exams)) {
                                    subject.exams.forEach(exam => {
                                        if (exam.exam_id) {
                                            uniqueExams.set(exam.exam_id, exam.exam_name);
                                        }
                                    });
                                }
                            });
                            // Start building the marksheet
                            tableHtml += `
                                <table class="marksheet-container w-100">
                                    <tr class="marksheet_row first"><td class="border-0">
                                        <div class="text-center mb-4">
                                            <img src="${response.logo.school_logo}" alt="School Logo" class="mb-2">
                                            <h2 class="mb-2 name_st_ms_sv">St. Vivekanand Play House</h2>
                                            <p class="mb-1 med_text_sv">(English Medium)</p>
                                            <p class="mb-1 add_text_sv">Vivekanand Chowk, Chirawa, 01596 - 220877</p>
                                            <p class="mb-2 sec_text_sv">Session : ${response.session}</p>
                                        </div>
                                    </td></tr>
                                    <tr class="marksheet_row second">
                                        <td class="border-0">
                                        <!-- Student Details -->
                                            <div class="row">
                                                <div class="col-md-6">		
                                                    <div class="row mb-2">
                                                        <div class="col-5 ms_text_head fw-bold">Name of Student :</div>
                                                        <div class="col-7 ms_text_head">${studentInfo.name}</div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-5 ms_text_head fw-bold">Father's Name :</div>
                                                        <div class="col-7 ms_text_head">${studentInfo.father_name}</div>
                                                    </div>			
                                                    <div class="row mb-2">
                                                        <div class="col-5 ms_text_head fw-bold">Mother's Name :</div>
                                                        <div class="col-7 ms_text_head">${studentInfo.mother_name}</div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-5 ms_text_head fw-bold">Class :</div>
                                                        <div class="col-7 ms_text_head">${studentInfo.class}</div>
                                                    </div>			
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="row mb-2">
                                                        <div class="col-5 ms_text_head fw-bold">Section :</div>
                                                        <div class="col-7 ms_text_head">${studentInfo.section}</div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-5 ms_text_head fw-bold">S.R.No. :</div>
                                                        <div class="col-7 ms_text_head">${studentInfo.sr_no}</div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-5 ms_text_head fw-bold">Roll No. :</div>
                                                        <div class="col-7 ms_text_head">${studentInfo.roll_no}</div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-5 ms_text_head fw-bold">Date of Birth :</div>
                                                        <div class="col-7 ms_text_head">${studentInfo.dob}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="marksheet_row third"><td class="border-0">
                                        <div class="row g-0 d-flex align-items-stretch">
                                            <div class="col-9 align-items-stretch">
                                                <table class="table table-bordered border-dark mb-0 h-100">
                                                    <thead>
                                                        <tr>
                                                            <th class="fw-bold" style="width: 25%">Subject</th>`;
                                                                // Add exam headers
                                                                uniqueExams.forEach((examName, examId) => {
                                                                    tableHtml += `<th class="fw-bold text-center">${examName}</th>`;
                                                                });
                                                                tableHtml += `<th class="fw-bold text-center">Grand Total</th></tr></thead><tbody>`;
                                                                // Process subjects and their marks
                                                                const processedSubjects = new Set(); // To track unique subjects
                                                                subjectMarks.forEach(subject => {
                                                                    // Skip if subject has already been processed
                                                                    if (processedSubjects.has(subject.subject_name)) {
                                                                        return;
                                                                    }
                                                                    processedSubjects.add(subject.subject_name);
                                                                    tableHtml += `<tr><td class="fw-bold">${subject.subject_name}</td>`;
                                                                    // Add marks for each exam
                                                                    uniqueExams.forEach((examName, examId) => {
                                                                        const examInfo = subject.exams?.find(e => e.exam_id == examId);
                                                                        let displayValue;
                                                                        if (examInfo) {
                                                                            if (examInfo.status == "Abst") {
                                                                                displayValue = "Abst";
                                                                            } else { // Marks-based subject
                                                                                displayValue = examInfo.grade || "Abst";
                                                                            }
                                                                          /*   if (examInfo.status == "Abst") {
                                                                                displayValue = "Abs";
                                                                            } else if (subject.by_m_g == 2) { // Grade-based subject
                                                                                displayValue = examInfo.grade || "Abs";
                                                                            } else { // Marks-based subject
                                                                                displayValue = examInfo.obtained_marks;
                                                                            } */
                                                                        } else {
                                                                            displayValue = "Abst";
                                                                        }
                                                                        tableHtml += `<td class="text-center">${displayValue}</td>`;
                                                                    });
                                                                    // Add total
                                                                    const totalDisplay = subject.total_obtained;
                                                                    // const totalDisplay = subject.by_m_g == 2 ? subject.total_obtained : (subject.total_obtained == "Abst" ? "Abst" : subject.total_obtained);
                                                                    tableHtml += `<td class="text-center">${totalDisplay}</td></tr>`;
                                                                });
                                                                        // Close the marks table and add attendance/grade section
                                                                        tableHtml += `
                                                                                                </tbody>
                                                                                            </table>
                                                                                        </div>
                                                                                        <div class="col-3 d-flex align-content-center align-items-stretch">
                                                                                            <div class="border-dark border-top-1 border border-bottom-1 col-12 text-center border-start-0 align-content-center">
                                                                                                <p class="fw-bold mb-1 text-decoration-underline">Attendance</p>
                                                                                                <p class="mb-1">Attended</p>
                                                                                                <p class="mb-1">${attendance.days_present} / ${attendance.total_days}</p>
                                                                                                <p class="mb-1">Result : ${result.result}</p>
                                                                                                <p class="mb-1">${result.result_date_message}</p>
                                                                                                <hr class="mx-2">
                                                                                                <p class="mb-1">${result.session_start_message}</p>
                                                                                            </div>`;
                                                                                            /*  <table class="table table-bordered border-dark mb-0">
                                                                                                <tr><td colspan="2" style="height: 12%"></td></tr>`; */
                                                                                                // Add grade table entries
                                                                                                processedSubjects.clear(); // Reset processed subjects for grade table
                                                                                            /*  subjectMarks.forEach(subject => {
                                                                                                    if (!processedSubjects.has(subject.subject_name)) {
                                                                                                        processedSubjects.add(subject.subject_name);
                                                                                                        const gradeDisplay = subject.by_m_g == 1 ? subject.total_obtained : (subject.by_m_g == 2 ? subject.total_obtained :
                                                                                                            (subject.total_obtained == "Abst" ? "Abs" : ""));
                                                                                                        tableHtml += `
                                                                                                            <tr>
                                                                                                                <td class="p-1 text-center">${gradeDisplay}</td>
                                                                                                                <td class="p-1 text-center">${gradeDisplay}</td>
                                                                                                            </tr>`;
                                                                                                    }
                                                                                                });
                                                                                                </table> */
                                                                        // Add signature section
                                                                        tableHtml += `
                                                                                        </div>
                                                                                    </div>
                                                                                </td></tr>
                                                                                <tr><td class="border-0">
                                                                                    <div class="row">
                                                                                        <div class="col-4 text-center align-content-end my-2">                                                                                            
                                                                                            <p class="mb-0">Sign of Class Teacher</p>
                                                                                        </div>
                                                                                        <div class="col-4 text-center align-content-end  my-2">                                                                                           
                                                                                            <p class="mb-0">Sign of Checker</p>
                                                                                        </div>
                                                                                        <div class="col-4 text-center align-content-end  my-2">
                                                                                            <img src="${response.logo.principal_sign}" alt="Principal Signature" class="mb-2" style="height:35px;">
                                                                                            <p class="mb-0">Sign of Principal</p>
                                                                                        </div>
                                                                                    </div>
                                                                                </td></tr>
                                                                            </table>`;
                        });
                        // Render the marksheet
                        $('.marksheet').html(tableHtml);
                    } else {
                        $('.marksheet').html('<p>No data available for the selected criteria.</p>');
                    }
                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(xhr) {
                    console.log('Error:', xhr);
                    console.error(xhr.responseText);
                }
            });
        }
        $('.print-marksheet').click(function() {
            // Create an iframe for printing
            const iframe = $('<iframe></iframe>').css({
                display: 'none'
            });
            $('body').append(iframe);
            const iframeDoc = iframe[0].contentWindow.document;
            iframeDoc.open();
            iframeDoc.write('<html><head><title>Print Marksheet</title>');
            // Include existing CSS styles
            $('link[rel="stylesheet"]').each(function() {
                iframeDoc.write(
                    `<link rel="stylesheet" type="text/css" href="${$(this).attr('href')}">`
                );
            });
            // Add additional styles for printing
            iframeDoc.write(`
                        <style>
                            @media print {
                                body {
                                    zoom: 1; /* Adjust the zoom level as needed */
                                    margin: auto !important;
                                    padding: auto !important;
                                }
                                .marksheet-container {
                                    page-break-after: always; /* Ensure page break after each marksheet */
                                }
                                .marksheet-container:last-child {
                                    page-break-after: auto; /* No page break after the last marksheet */
                                }
                                .signature-container {
                                    page-break-before: avoid; /* Prevent signatures from breaking across pages */
                                }
                                .signature-container .signature {
                                    font-size: 12px !important; /* Adjust the font size of the signatures */
                                }
                                .marksheet_row {
                                    font-family: 'Verdana';
                                }
                                html body .border-0 {
                                    border: 0 !important;
                                }
                                .marksheet-container th, .marksheet-container td {
                                    border: 1px solid #000 !important;
                                    padding: 4px 4px !important;
                                    font-size: 11px !important;
                                    line-height:1em !important;
                                }
                                html body .p-0 {
                                    padding: 0 !important;
                                }
                                .marksheet-container tr,
                                .marksheet-container table {
                                    border: none;
                                }
                                .marksheet-div p {
                                    font-family: 'Verdana';
                                    color: #000;
                                    font-size: 11px !important;
                                }
                                .marksheet_row.attendence_row td,
                                .marksheet_row.attendence_row th {
                                    font-family: 'Verdana';
                                    color: #000000;
                                    font-size: 11px !important;
                                    font-weight: 700;
                                    line-height:1.2em !important;
                                }
                                .ms_text_head {
                                    font-family: 'Verdana';
                                    font-size: 12px;
                                    line-height: 1.2em !important;
                                }
                                .name_st_ms_sv {
                                    font-size: 24px !important;
                                }
                                .med_text_sv {
                                    font-size: 15px;
                                }
                                .add_text_sv {
                                    font-size: 13px;
                                }
                                .sec_text_sv {
                                    font-size: 13px !important;
                                }
                            }
                        </style>
                    `);
            iframeDoc.write('</head><body>');
            // Append each table separately
            $('.marksheet .marksheet-container').each(function(index) {
                // Add a page break before each table except the first
                if (index > 0) {
                    iframeDoc.write('<div class="page-break"></div>');
                }
                iframeDoc.write($(this)[0].outerHTML);
            });
            iframeDoc.write('</body></html>');
            iframeDoc.close();
            // Print the iframe content
            iframe[0].contentWindow.focus();
            iframe[0].contentWindow.print();
            // Remove the iframe after printing
            setTimeout(() => {
                iframe.remove();
            }, 1000);
        });
    });
</script>
@endsection