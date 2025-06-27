@extends('marks.index')

@section('sub-content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        {{ 'Print Final Marksheet (KG)' }}
                        <a href="{{ route('marks.marks-report.marksheet.kg') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>
                        <button type="button" id="print-marksheet" class="btn btn-primary print-marksheet btn-sm mx-2"
                            style="float: right;">Print Marksheet</button>
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
                                        <table class="marksheet-container w-100">
                                            <tr>
                                             <td>
                                                <div class="text-center mb-4">
                                                    <img src="${response.logo.school_logo}" alt="School Logo" class="rounded-circle mb-2 pt-3">
                                                    <h2 class="fs-2 fw-bold">St. Vivekanand Play House</h2>
                                                    <p class="fs-5">(English Medium)</p>
                                                    <p class="mb-1">Vivekanand Chowk, Chirawa, 01596 - 220877</p>
                                                    <p class="mb-3">Session: ${response.session.name}</p>
                                                </div>
                                             </td>
                                            </tr>
                                            <tr>
                                             <td>
                                                <!-- Student Details -->
                                                <div class="row mb-4">
                                                    <div class="col-md-6">
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold">Name of Student:</div>
                                                            <div class="col-7">${student.student_details.name}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold">Father's Name:</div>
                                                            <div class="col-7">${student.student_details.father_name}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold">Class:</div>
                                                            <div class="col-7">${student.student_details.class_name}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold">Section:</div>
                                                            <div class="col-7">${student.student_details.section_name}</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold">S.R.No.:</div>
                                                            <div class="col-7">${student.student_id}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold">Date of Birth:</div>
                                                            <div class="col-7">${student.student_details.dob ?? 'N/A'}</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-5 fw-bold">Roll No.:</div>
                                                            <div class="col-7">${student.student_details.roll_no}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                             </td>
                                            </tr>
                                            <tr>
                                             <td>
                                            <!-- Academic Performance Table -->
                                            <div class="row">
                                                <div class="col-10 px-0 align-items-stretch">
                                                    <table class="table table-bordered w-100 h-100">
                                                        <thead>
                                                            <tr class="table-light">
                                                                <th rowspan="2">Subject</th>
                                                                ${student.marks_data[0].exam_marks.map(exam => `
                                                                                <th colspan="2">${exam.exam_name}</th>
                                                                            `).join('')}
                                                                <th colspan="2">Grand Total</th>

                                                            </tr>
                                                            <tr class="table-light">
                                                                ${student.marks_data[0].exam_marks.map(exam => `
                                                                                <th>MM.</th>
                                                                                <th>M. Obtd.</th>
                                                                            `).join('')}
                                                                <th>MM.</th>
                                                                    <th>M. Obtd.</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            ${student.marks_data.map(subject => {
                                                                 if(subject.by_m_g == 1)
                                                                {
                                                                    return `
                                                                                <tr>
                                                                                    <td>${subject.subject_name}</td>
                                                                                    ${subject.exam_marks.map(exam => `
                                                                            <td>${exam.max_marks ?? '0'}</td>
                                                                            <td>${exam.obtained_marks ?? '0'}</td>
                                                                        `).join('')}
                                                                                    <td>${subject.total_max_marks}</td>
                                                                                    <td>${subject.total_obtained_marks}</td>
                                                                                </tr>`;
                                                                }
                                                            }).join('')}
                                                        </tbody>
                                                    </table>
                                                </div>
                                                 <div class="col-md-2 d-flex px-0 align-items-stretch">
                                                    <div class="border-dark border-top-1 border border-bottom-1 text-center align-content-center w-100">
                                                        <p class="fw-bold mb-1">Attendance</p>
                                                        <p class="mb-1">Attended</p>
                                                        <p class="mb-1 text-decoration-underline">${student.attendance.days_present}</p>
                                                        <p class="mb-1">${student.attendance.total_days}</p>
                                                        <p class="mb-1 border-top border-black text-center">${student.attendance.result_date_message}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            </td>
                                            </tr>
                                            <tr><td>
                                             <div class="row mb-2 mt-2"><h5 class="text-center fw-bold px-0 w-100">Grades</h5></div>
                                            </td></tr>
                                            <tr><td>
                                            <!-- Grades -->
                                            <div class="row align-items-stretch">
                                                <div class="col-10 align-items-stretch px-0">
                                                    <table class="table table-bordered w-100 h-100">
                                                       <tbody>
                                                            <!-- Check if there are any subjects with by_m_g == 2 -->
                                                            ${student.marks_data.some(subject => subject.by_m_g == 2) ? student.marks_data.map(subject => {
                                                                if (subject.by_m_g == 2) {
                                                                    return `
                                                                                    <tr>
                                                                                        <td style="width:118px;">${subject.subject_name}</td>
                                                                                        ${subject.exam_marks.map(exam => `
                                                                                <td colspan="6" class="text-center">${exam.grade ?? 'Abs'}</td>
                                                                            `).join('')}
                                                                                        <td colspan="6" class="text-center">${subject.overall_grade}</td>
                                                                                    </tr>`;
                                                                }
                                                            }).join('') : '<tr class="text-center"><td>No Grade Subjects are found</td></tr>'}
                                                        </tbody>


                                                    </table>
                                                </div>

                                                <!-- Result Summary -->
                                                <div class="col-md-2 px-0 align-content-center align-items-stretch">
                                                    <div class="border-dark border-top-1 border border-bottom-1 text-center h-100">
                                                        <p class="fw-bold mb-1">Percentage</p>
                                                        <p>${student.summary.overall_percentage ?? 'Nan'}%</p>

                                                        <p class="fw-bold mb-1">Result</p>
                                                        <p>${student.summary.overall_result}</p>
                                                        <p class="mb-1 border-top border-black text-center">${student.attendance.session_start_message}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            </td></tr>
                                            <tr><td>
                                            <!-- Signatures -->
                                            <div class="row mt-3 signature-container">
                                                <div class="col-md-4 align-content-end text-center">
                                                    <hr class="w-75 mx-auto">
                                                    <p>Sign of Class Teacher</p>
                                                </div>
                                                <div class="col-md-4 align-content-end text-center">
                                                    <hr class="w-75 mx-auto">
                                                    <p>Sign of Checker</p>
                                                </div>
                                                <div class="col-md-4 text-center">
                                                    <img src="${response.logo.principal_sign}" alt="School Logo" class="mb-2" style="height:35px;">
                                                    <hr class="w-75 mx-auto">
                                                    <p>Sign of Principal</p>
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
                                    zoom: 0.75; // Adjust the zoom level as needed
                                     margin: 10 !important;
                                     padding: 10px !important;
                                }
                                 table td {
                                        line-height: 1em;
                                        vertical-align: bottom;
                                        padding: 2px 10px !important;
                                }
                                table th {
                                        line-height: 1em;
                                        vertical-align: bottom;
                                        padding: 5px 10px !important;
                                }
                               .marksheet-container {
                                        margin-top: 20px;
                                        padding: 10px !important;
                                        page-break-inside: avoid; /* Prevent table from breaking across pages */
                                        page-break-after: always; /* Insert a page break after each marksheet */
                                }
                                 .marksheet .signature-container {
                                    page-break-before: avoid; // Prevent signatures from breaking across pages
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: flex-end;
                                    margin-top: 20px;
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
