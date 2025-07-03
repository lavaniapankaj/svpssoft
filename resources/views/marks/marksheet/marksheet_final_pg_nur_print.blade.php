@extends ('marks.index')

@section('sub-content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{ 'Print Final Marksheet (PG & Nursury)' }}
                    <a href="{{ route('marks.marks-report.marksheet.pg.nursary') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>
                    <button type="button" id="print-marksheet" class="btn btn-primary btn-sm mx-2 print-marksheet" style="float: right;">Print Marksheet</button>
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
                                    <tr><td>
                                        <div class="text-center mb-4">
                                            <img src="${response.logo.school_logo}" alt="School Logo" class="mb-2">
                                            <h2 class="fst-italic">St. Vivekanand Play House</h2>
                                            <h6 class="fw-normal">(English Medium)</h6>
                                            <p class="mb-1 small">Vivekanand Chowk, Chirawa, 01596 - 220877</p>
                                            <p class="small">Session : ${response.session}</p>
                                        </div>
                                    </td></tr>
                                    <tr><td>
                                        <div class="row g-0 mb-3">
                                            <div class="col-3 pe-2">
                                                <p class="mb-1">Name of Student :</p>
                                                <p class="mb-1">Father's Name :</p>
                                                <p class="mb-1">Class :</p>
                                                <p class="mb-1">Section :</p>
                                            </div>
                                            <div class="col-3">
                                                <p class="mb-1">${studentInfo.name}</p>
                                                <p class="mb-1">${studentInfo.father_name}</p>
                                                <p class="mb-1">${studentInfo.class}</p>
                                                <p class="mb-1">${studentInfo.section}</p>
                                            </div>
                                            <div class="col-3 pe-2">
                                                <p class="mb-1">S.R.No. :</p>
                                                <p class="mb-1">Mother's Name :</p>
                                                <p class="mb-1">Date of Birth :</p>
                                                <p class="mb-1">Roll No. :</p>
                                            </div>
                                            <div class="col-3">
                                                <p class="mb-1">${studentInfo.sr_no}</p>
                                                <p class="mb-1">${studentInfo.mother_name}</p>
                                                <p class="mb-1">${studentInfo.dob}</p>
                                                <p class="mb-1">${studentInfo.roll_no}</p>
                                            </div>
                                        </div>
                                    </td></tr>
                                    <tr><td>
                                        <div class="row g-0 d-flex align-items-stretch">
                                            <div class="col-9 align-items-stretch">
                                                <table class="table table-bordered border-dark mb-0 h-100">
                                                    <thead>
                                                        <tr>
                                                            <th class="fw-normal" style="width: 25%">Subject</th>`;

                                                                // Add exam headers
                                                                uniqueExams.forEach((examName, examId) => {
                                                                    tableHtml += `<th class="fw-normal text-center">${examName}</th>`;
                                                                });
                                                                tableHtml += `<th class="fw-normal text-center">Grand Total</th></tr></thead><tbody>`;

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
                                                                                displayValue = "Abs";
                                                                            } else { // Marks-based subject
                                                                                displayValue = examInfo.grade || "Abs";
                                                                            }
                                                                          /*   if (examInfo.status == "Abst") {
                                                                                displayValue = "Abs";
                                                                            } else if (subject.by_m_g == 2) { // Grade-based subject
                                                                                displayValue = examInfo.grade || "Abs";
                                                                            } else { // Marks-based subject
                                                                                displayValue = examInfo.obtained_marks;
                                                                            } */
                                                                        } else {
                                                                            displayValue = "Abs";
                                                                        }

                                                                        tableHtml += `<td class="text-center">${displayValue}</td>`;
                                                                    });

                                                                    // Add total
                                                                    const totalDisplay = subject.by_m_g == 2 ? subject.total_obtained :
                                                                        (subject.total_obtained == "Abst" ? "Abs" : subject.total_obtained);
                                                                    tableHtml += `<td class="text-center">${totalDisplay}</td></tr>`;
                                                                });

                                                                        // Close the marks table and add attendance/grade section
                                                                        tableHtml += `
                                                                                                </tbody>
                                                                                            </table>
                                                                                        </div>
                                                                                        <div class="col-3 d-flex align-content-center align-items-stretch">
                                                                                            <div class="border-dark border-top-1 border border-bottom-1 col-12 text-center">
                                                                                                <p class="fw-bold mb-1 text-decoration-underline">Attendance</p>
                                                                                                <p class="mb-1">Attended</p>
                                                                                                <p class="mb-1 text-decoration-underline">${attendance.days_present}</p>
                                                                                                <p class="mb-1">${attendance.total_days}</p>
                                                                                                <p class="mb-1 text-decoration-underline">Result</p>
                                                                                                <p class="mb-1">${result.result}</p>
                                                                                                <p class="mb-1 border-bottom border-top border-dark py-1">${result.result_date_message}</p>
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
                                                                                <tr><td>
                                                                                    <div class="row pt-2 mt-5">
                                                                                        <div class="col-4 text-center align-content-end">
                                                                                            <p class="mb-2"></p>
                                                                                            <div class="border-top border-dark pt-2">Sign of Class Teacher</div>
                                                                                        </div>
                                                                                        <div class="col-4 text-center align-content-end">
                                                                                            <p class="mb-2"></p>
                                                                                            <div class="border-top border-dark pt-2">Sign of Checker</div>
                                                                                        </div>
                                                                                        <div class="col-4 text-center align-content-end">
                                                                                            <img src="${response.logo.principal_sign}" alt="Principal Signature" class="mb-2" style="height:35px;">
                                                                                            <div class="border-top border-dark pt-2">Sign of Principal</div>
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
                                    zoom: 0.75; // Adjust the zoom level as needed
                                    margin: 10 !important;
                                    padding: 10px !important;
                                }
                                .marksheet-container {
                                        margin-top: 20px;
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