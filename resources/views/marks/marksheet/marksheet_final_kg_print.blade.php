@extends('marks.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row ">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
           <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between"><h5 class="mb-0 mt-0">
{{ 'Print Final Marksheet (KG)' }}</h5>
                        <div class="align-items-center d-flex gap-2">
                        <a href="{{ route('marks.marks-report.marksheet.kg') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                        <button type="button" id="print-marksheet" class="btn btn-primary print-marksheet btn-sm mx-2"
                            >Print Marksheet</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="class" value="{{ $class }}">
                        <input type="hidden" id="section" value="{{ $section }}">
                        <input type="hidden" id="students" value="{{ $students }}">
                        <input type="hidden" id="session-message" value="{{ $sessionMessage }}">
                        <input type="hidden" id="date-message" value="{{ $dateMessage }}">
                        <div class="row">
                            <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader"
                                style="width:10%;">
                            <div class="marksheet-div">
                                <div class="marksheet">
                                </div>
                                <div class="mt-3">
                                    <button type="button" id="print-marksheet"
                                        class="btn btn-primary print-marksheet">Print Marksheet</button>
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
            let colvalue = 2;
            if (classId && sectionId && stdId) {
                $.ajax({
                    url: siteUrl + '/marks/marksheet-final-kg/report',
                    type: 'GET',
                    dataType: 'JSON',
                    data: {
                        class: classId,
                        section: sectionId,
                        students: stdId,
                        sessionMessage: sessionMessage,
                        dateMessage: dateMessage,
                    },
                    success: function(response) {
                        let tableHtml = '';
                        response.report_cards.forEach(function(student) {
                            // Header section
                            tableHtml += `
                                        <table class="w-100 marksheet-container sv_marksheet_table">
                                            <tr class="marksheet_row first">
                                             <td class="border-0">
                                                <div class="text-center mb-4">
                                                    <img src="${response.logo.school_logo}" alt="School Logo" class="rounded-circle mb-2 pt-3">
                                                    <h2 class="mb-2 name_st_ms_sv">St. Vivekanand Public Secondary School</h2>
                                                    <p class="mb-2 med_text_sv">(English Medium)</p>
                                                    <p class="mb-2 add_text_sv">Vivekanand Chowk, Chirawa, 01596 - 220877</p>
                                                    <p class="mb-2 sec_text_sv">Session: ${response.session.name}</p>
                                                </div>
                                             </td>
                                            </tr>
                                            <tr class="marksheet_row second">
                                             <td class="border-0">
                                                <!-- Student Details -->
                                                <div class="row mb-2">
                                                    <div class="col-md-6">
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold ms_text_head">Name of Student:</div>
                                                            <div class="col-7 ms_text_head">${student.student_details.name}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold ms_text_head">Father's Name:</div>
                                                            <div class="col-7 ms_text_head">${student.student_details.father_name}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold ms_text_head">Mother's Name:</div>
                                                            <div class="col-7 ms_text_head">${student.student_details.mother_name}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold ms_text_head">Class:</div>
                                                            <div class="col-7 ms_text_head">${student.student_details.class}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold ms_text_head">Section:</div>
                                                            <div class="col-7 ms_text_head">${student.student_details.section}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold ms_text_head">S.R.No.:</div>
                                                            <div class="col-7 ms_text_head">${student.student_id}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold ms_text_head">Date of Birth:</div>
                                                            <div class="col-7 ms_text_head">${student.student_details.dob ? formatDOB(student.student_details.dob) : 'N/A'}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold ms_text_head">Roll No.:</div>
                                                            <div class="col-7 ms_text_head">${student.student_details.roll_no}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                             </td>
                                            </tr>
                                            <tr class="marksheet_row third lkg_ms">
                                             <td class="border-0 p-0">
                                            <!-- Academic Performance Table -->
                                            <div class="row mx-0 mb-1">
                                                <div class="col-10 px-0 align-items-stretch">
                                                    <table class="table table-bordered w-100 h-100 mb-0">
                                                        <thead>
                                                            <tr class="table-light text-center main_ms_th align-middle">
                                                                <th rowspan="2" class="align-middle text-start">Subject</th>
                                                                ${student.marks_data[0].exam_marks.map(exam => `
                                                                                <th colspan="2">${exam.exam_name}</th>
                                                                            `).join('')}
                                                                <th colspan="2">Grand Total</th>
                                                            </tr>
                                                            <tr class="table-light text-center mm_mx_col">
                                                                ${student.marks_data[0].exam_marks.map(exam => `
                                                                                <th class="">MM.</th>
                                                                                <th class="">M. Obtd.</th>
                                                                            `).join('')}
                                                                <th class="">MM.</th>
                                                                    <th class="">M. Obtd.</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            ${student.marks_data.map(subject => {
                                                                 if(subject.by_m_g == 1)
                                                                {
                                                                    return `
                                                                                <tr class="text-center">
                                                                                    <td class="fw-bold text-start">${subject.subject_name}</td>
                                                                                    ${subject.exam_marks.map(exam => `
                                                                            <td class="">${exam.max_marks ?? '0'}</td>
                                                                            <td class="">${exam.obtained_marks ?? '0'}</td>
                                                                        `).join('')}
                                                                                    <td class="">${subject.total_max_marks}</td>
                                                                                    <td class="">${subject.total_obtained_marks}</td>
                                                                                </tr>`;
                                                                }
                                                            }).join('')}
                                                        </tbody>
                                                    </table>
                                                </div>
                                                 <div class="col-md-2 d-flex px-0 align-items-stretch">
                                                    <div class="border-dark border-top-1 border border-bottom-1 text-center align-content-center w-100 border-start-0">
                                                       <!-- <p class="fw-bold mb-1">Attendance</p>
                                                        <p class="mb-1">Attended</p>
                                                        <p class="mb-1">${student.attendance.days_present} / ${student.attendance.total_days}</p>
                                                        <hr class="mx-2"> -->
                                                        <p class="text-center mb-0">${student.attendance.result_date_message}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            </td>
                                            </tr>

                                            <tr class="marksheet_row"><td class="border-0 p-0">
                                            <!-- Grades -->
                                            <div class="row align-items-stretch mx-0">
                                                <div class="col-10 align-items-stretch px-0">
                                                    <table class="table table-bordered w-100 h-100">
                                                    <thead>

                                                    <tr class="table-light text-center main_ms_th align-middle">
                                                        <th class="align-middle text-start">Subject</th>
                                                        ${student.marks_data[0].exam_marks.map(exam => `
                                                                        <th>${exam.exam_name}</th>
                                                                    `).join('')}
                                                        <th >Grand Total</th>

                                                    </tr>
                                                    </thead>
                                                       <tbody>
                                                            <!-- Check if there are any subjects with by_m_g == 2 -->
                                                            ${student.marks_data.some(subject => subject.by_m_g == 2) ? student.marks_data.map(subject => {
                                                                if (subject.by_m_g == 2) {
                                                                    return `
                                                                                    <tr class="grade_t_row_sv">
                                                                                        <td class="fw-bold">${subject.subject_name}</td>
                                                                                        ${subject.exam_marks.map(exam => `
                                                                                <td class="text-center align-middle">${exam.grade ?? 'Abs'}</td>
                                                                            `).join('')}
                                                                                        <td class="text-center align-middle">${subject.overall_grade}</td>
                                                                                    </tr>`;
                                                                }
                                                            }).join('') : '<tr class="text-center"><td>No Grade Subjects are found</td></tr>'}
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <!-- Result Summary -->
                                                <div class="col-md-2 px-0 align-content-center align-items-stretch">
                                                    <div class="border-dark border-top-1 border border-bottom-1 text-center h-100 border-start-0 align-content-center">
                                                        <p class="fw-bold mb-1">Percentage</p>
                                                        <p class="mb-1">${student.summary.overall_percentage ?? 'Nan'}%</p>
                                                        <p class="fw-bold mb-1">Result</p>
                                                        <p class="mb-0">${student.summary.overall_result}</p>
                                                        <hr class="mx-2 my-2">
                                                        <p class="text-center mb-0">${student.attendance.session_start_message}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            </td></tr>
                                            <tr><td class="border-0">
                                            <!-- Signatures -->
                                            <div class="row mx-0 mt-2 signature-container">
                                                <div class="col-md-4 align-content-end text-center my-2">
                                                    <p class="mb-0">Sign of Class Teacher</p>
                                                </div>
                                                <div class="col-md-4 align-content-end text-center my-2">
                                                    <p class="mb-0">Sign of Checker</p>
                                                </div>
                                                <div class="col-md-4 text-center my-2">
                                                    <img src="${response.logo.principal_sign}" alt="School Logo" class="mb-1" style="height:35px;">
                                                    <p class="mb-0">Sign of Principal</p>
                                                </div>
                                            </div>
                                            </td></tr>
                                        </table>
                                    `;
                        });
                        $('.marksheet').html(
                            tableHtml); // Append all content after processing all students
                    },
                    complete: function() {
                        $('#loader').hide();
                    },
                    error: function(xhr) {
                        console.log('Error:', xhr);
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
                                    /* Adjust the zoom level as needed */
                                    margin: 8mm; !important;
                                    padding: auto !important;
                                    size: A4 landscape;
                                    box-sizing: border-box;
                                    zoom:0.98;
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
                                    letter-spacing: 3px !important;
                                }
                                .med_text_sv {
                                    font-size: 15px;
                                    letter-spacing: 3px !important;
                                }
                                .add_text_sv {
                                    font-size: 13px;
                                    letter-spacing: 3px !important;
                                }
                                .sec_text_sv {
                                    font-size: 13px !important;
                                    letter-spacing: 3px !important;
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
