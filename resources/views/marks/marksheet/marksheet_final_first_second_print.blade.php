@extends('marks.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Print Final Marksheet (1st & 2nd)' }}</h5>
                        <div class="align-items-center d-flex gap-2">
                            <a href="{{ route('marks.marks-report.marksheet.first.second') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                            <button type="button" id="print-marksheet" class="btn btn-sm btn-primary mx-2 print-marksheet"
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
            if (classId && sectionId && stdId) {
                $.ajax({
                    url: siteUrl + '/marks/marksheet-final-first-second/report',
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
                        let spanValue = 0;
                        let maxMarks = 0;
                        if (Array.isArray(response.data) && response.data.length > 0) {
                            // Process each student
                            response.data.forEach(function(studentData, index) {
                                const studentInfo = studentData.student_info;
                                const sessionInfo = studentData.session;
                                const examsData = studentData.exams;
                                const attendanceData = studentData.attendance;
                                let tdCount = 0;
                                tableHtml += `
                                          <table class="marksheet-container w-100">
                                            <tr class="marksheet_row first">
                                                <td class="border-0">`;
                                tableHtml += `
                                        <!-- Header Section -->
                                        <div class="row mb-4">
                                            <div class="col-2">
                                                <img src="${response.logo.school_logo}" alt="School Logo" class="img-fluid rounded-circle">
                                            </div>
                                            <div class="col-8 text-center">
                                                <h2 class="mb-0 name_st_ms_sv">St. Vivekanand ${studentInfo.school == 1 ? 'Play House' : 'Public Secondary School'}</h2>
                                                <p class="mb-0 med_text_sv">(English Medium)</p>
                                                <p class="mb-0 add_text_sv">Vivekanand Chowk, Chirawa, 01596 - 220877</p>
                                                <p class="mb-0 sec_text_sv">Session : ${response.session.session}</p>
                                            </div>
                                            <div class="col-2"></div>
                                        </div>
                                        </td>
                                            </tr>`;
                                tableHtml += `
                                           <tr class="marksheet_row second"><td class="border-0">
                                        <!-- Student Details -->
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="row mb-2">
                                                    <div class="col-5 ms_text_head fw-bold">Name of Student:</div>
                                                    <div class="col-7 ms_text_head">${studentInfo.name}</div>
                                                </div>
                                                <div class="row mb-2">
                                                    <div class="col-5 ms_text_head fw-bold">Father's Name:</div>
                                                    <div class="col-7 ms_text_head">${studentInfo.f_name}</div>
                                                </div>
                                                <div class="row mb-2">
                                                    <div class="col-5 ms_text_head fw-bold">Mother's Name:</div>
                                                    <div class="col-7 ms_text_head">${studentInfo.m_name}</div>
                                                </div>
                                                <div class="row mb-2">
                                                    <div class="col-5 ms_text_head fw-bold">Class:</div>
                                                    <div class="col-7 ms_text_head">${studentInfo.class_name} ${studentInfo.section_name}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="row mb-2">
                                                    <div class="col-5 ms_text_head fw-bold">S.R.No.:</div>
                                                    <div class="col-7 ms_text_head">${studentInfo.srno}</div>
                                                </div>
                                                <div class="row mb-2">
                                                    <div class="col-5 ms_text_head fw-bold">Roll No.:</div>
                                                    <div class="col-7 ms_text_head">${studentInfo.rollno}</div>
                                                </div>
                                                <div class="row mb-2">
                                                    <div class="col-5 ms_text_head fw-bold">Date of Birth:</div>
                                                    <div class="col-7 ms_text_head">${studentInfo.dob !== null ? formatDOB(studentInfo.dob) : 'N/A'}</div>
                                                </div>
                                            </div>
                                        </div>
                                         </td></tr>`;
                                tableHtml += `
                                         <tr class="marksheet_row third">
                                             <td class="p-0 border-0">
                                        <!-- Academic Performance -->
                                        <div class="d-flex mb-1">
                                            <div class="col-md-10 px-0">
                                                <table class="table table-bordered h-100 w-100 mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th class="align-middle"rowspan="2">Subject</th>`;
                                // Step 1: Group exams by `exam_id`
                                let examGroupedById = {};
                                examsData.forEach(exam => {
                                    if (exam['exam-info'] && exam['exam-info'].length >
                                        0) {
                                        exam['exam-info'].forEach(info => {
                                            if (!examGroupedById[info
                                                    .exam_id]) {
                                                examGroupedById[info
                                                    .exam_id] = {
                                                    examName: info.exam,
                                                    subjects: [],
                                                    totalMarks: 0,
                                                    maXMarks: 0,
                                                };
                                            }
                                            examGroupedById[info.exam_id]
                                                .subjects.push(exam);
                                        });
                                    }
                                });
                                // Step 2: Create table headers dynamically based on `exam_id`
                                Object.keys(examGroupedById).forEach(examId => {
                                    let exam = examGroupedById[examId];
                                    tableHtml +=
                                        `<th class="text-center" colspan="3">${exam.examName}</th>`;
                                    spanValue += 3;
                                });
                                tableHtml += `
                                                        <th class="text-center border-right-0 align-middle" rowspan="2">Grand Total</th>
                                                        </tr>`;
                                tableHtml += `<tr >`;
                                // Create sub-headers for "Written", "Oral", "Total"
                                Object.keys(examGroupedById).forEach(examId => {
                                    tableHtml += `
                                                    <th class="text-center">Written</th>
                                                    <th class="text-center">Oral</th>
                                                    <th class="text-center">Total</th>
                                    `;
                                });
                                tableHtml += `</tr>`;
                                tableHtml += `
                                                    </thead>
                                                    <tbody>`;
                                tableHtml += `<tr><td class="fw-bold">M.M.</td>`;
                                let subjectMaxMarksTotal = 0;
                                if (examsData[0].by_m_g == 1 && examsData[0].priority ==
                                    1 && examsData[0]['exam-info'] && Array.isArray(
                                        examsData[0]['exam-info']) && examsData[0][
                                        'exam-info'
                                    ].length > 0) {
                                    // For each exam associated with the subject
                                    Object.keys(examGroupedById).forEach(examId => {
                                        let examGroup = examGroupedById[
                                            examId
                                        ];
                                        // Find the specific exam info for the current examId
                                        let examInfo = examsData[0]['exam-info'].find(info => info.exam_id === parseInt(examId));
                                        // If examInfo is found, display the max_marks
                                        if (examInfo) {
                                            tableHtml +=
                                                `<td class="text-center">${examInfo.written_max_marks}</td>
                                                <td class="text-center">${examInfo.oral_max_marks}</td>
                                                <td class="text-center fw-bold">${examInfo.max_marks}</td> `;
                                            subjectMaxMarksTotal += examInfo
                                                .max_marks;
                                        } else {
                                            // If no exam info is found, display "Abs"
                                            tableHtml +=
                                                ` <td class="text-center">Abs</td>
                                                <td class="text-center">Abs</td>
                                                <td class="text-center">Abs</td> `;
                                        }
                                    });
                                    tableHtml +=
                                            `<td class="text-center fw-bold border-right-0">${subjectMaxMarksTotal}</td></tr>`;
                                            subjectMaxMarksTotal = 0;
                                }
                                let grandTotal = 0;
                                let sideGrandTotal = 0;
                                // Step 3: Generate the table body
                                examsData.forEach(exam => {
                                    if (exam.by_m_g == 1 && exam.priority == 1 && exam[
                                            'exam-info'] && exam['exam-info'].length >
                                        0) {
                                        tableHtml += `<tr><td class="fw-bold">${exam.subject}</td>`;
                                        // For each exam associated with the subject
                                        Object.keys(examGroupedById).forEach(examId => {
                                            let examInfo = exam['exam-info']
                                                .find(info => info.exam_id ===
                                                    parseInt(examId));
                                            // If the subject has data for this exam_id, display the actual data
                                            if (examInfo) {
                                                examGroupedById[examId]
                                                    .totalMarks += examInfo
                                                    .total_marks;
                                                maxMarks += examInfo.max_marks;
                                                grandTotal += examInfo
                                                    .total_marks;
                                                sideGrandTotal += examInfo
                                                    .total_marks;
                                                tableHtml += `
                                                                        <td class="text-center">${examInfo.written_marks}</td>
                                                                        <td class="text-center">${examInfo.oral_marks}</td>
                                                                        <td class="text-center fw-bold">${examInfo.total_marks}</td>
                                                                    `;
                                            } else {
                                                // If the subject does not have this exam's data, insert empty td
                                                tableHtml += `
                                                                        <td class="text-center">Abs</td>
                                                                        <td class="text-center">Abs</td>
                                                                        <td class="text-center">Abs</td>
                                                                    `;
                                            }
                                        });
                                        tableHtml +=
                                            `<td class="text-center fw-bold border-right-0">${sideGrandTotal}</td></tr>`;
                                            sideGrandTotal = 0;
                                    }
                                });
                                tableHtml += `<tr>
                                              <td class="text-center fw-bold">Total</td>`;
                                let grandTotalValue = 0;
                                Object.keys(examGroupedById).forEach(examId => {
                                    let exam = examGroupedById[examId];
                                    tableHtml += `<td class="text-center"></td>
                                                   <td class="text-center"></td>
                                                    <td class="text-center fw-bold">${exam.totalMarks}</td>`;
                                    grandTotalValue += exam.totalMarks;
                                });
                                // <td class="text-center" colspan="${spanValue + 1}">${grandTotal}</td>
                                tableHtml += `<td class="text-center fw-bold border-right-0">${grandTotalValue}</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="col-md-2 align-items-stretch justify-content-center px-0">
                                                <table class="table table-bordered h-100 w-100 mb-0">
                                                    <tr>
                                                        <td class="align-middle text-center ms_final_box_result_message">
                                                        <p class="">${response.logo.result_date_message}</p>
                                                        <hr>
                                                       <p class="">${response.logo.session_start_message}</p></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        </td>
                                         </tr>`;
                                tableHtml += `
                                         <tr class="marksheet_row">
                                         <td class="p-0 border-0">
                                        <!-- Grades Section -->
                                        <div class="d-flex align-items-stretch mb-1">
                                            <div class="col-md-10 px-0">
                                                 <table class="table table-bordered h-100 w-100 mb-0">
                                                    <thead>
                                                        <tr class="marksheet_row">
                                                            `;
                                // Step 1: Group exams by `exam_id`
                                let examGroupedGradeById = {};
                                examsData.forEach(exam => {
                                    if (exam.by_m_g == 2) {
                                        if (exam['exam-info'] && exam['exam-info']
                                            .length > 0) {
                                            exam['exam-info'].forEach(info => {
                                                if (!examGroupedGradeById[info
                                                        .exam_id]) {
                                                    examGroupedGradeById[info
                                                        .exam_id] = {
                                                        examName: info.exam,
                                                        subjects: []
                                                    };
                                                }
                                                examGroupedGradeById[info
                                                    .exam_id].subjects.push(
                                                    exam);
                                                // maxMarks += info.max_marks;
                                                // grandTotal += info.total_marks;
                                                // grandTotalValue += info.totalMarks;
                                            });
                                        }
                                    }
                                });
                                // Step 2: Check if all subjects have only one exam type (same `exam_id`)
                                let allSubjectsSameExam = Object.keys(examGroupedGradeById)
                                    .length === 1;
                                // Generate headers
                                if (allSubjectsSameExam) {
                                    Object.keys(examGroupedGradeById).forEach(examId => {
                                        let exam = examGroupedGradeById[examId];
                                        // exam.subjects.forEach(sData => {
                                        //     if (sData.by_m_g == 2) {
                                        tableHtml +=
                                            `<th>Subject</th><th class="text-center">${exam.examName}</th><th class="text-center ">Grade</th>
                                                                            <th>Subject</th><th class="text-center">${exam.examName}</th><th class="text-center border-right-0">Grade</th>`;
                                        //     }
                                        // });
                                    });
                                } else {
                                    // If multiple exams exist, create headers for each exam type and grade
                                    tableHtml += `<th>Subject</th>`;
                                    Object.keys(examGroupedGradeById).forEach(examId => {
                                        let exam = examGroupedGradeById[examId];
                                        // exam.subjects.forEach(sData => {
                                        //     if (sData.by_m_g == 2) {
                                        tableHtml +=
                                            `<th class="text-center">${exam.examName}</th><th class="text-center border-right-0">Grade</th>`;
                                        //     }
                                        // });
                                    });
                                }
                                tableHtml += `</tr></thead><tbody>`;
                                // Step 3: Generate the table body
                                if (allSubjectsSameExam) {
                                    // For the "only one exam type" case, group subjects and display them side by side
                                    let rows = [];
                                    examsData.forEach(exam => {
                                        if (exam.by_m_g == 2 && exam['exam-info'] &&
                                            exam['exam-info'].length > 0) {
                                            rows.push(exam);
                                        }
                                    });
                                    // Now, render subjects side by side
                                    let maxSubjects = rows.length;
                                    for (let i = 0; i < maxSubjects; i += 2) {
                                        tableHtml += `<tr>`;
                                        if (rows[i]) {
                                            tableHtml += `<td class="fw-bold">${rows[i].subject}</td>`;
                                            // Check if the subject has data for the current exam
                                            let examInfo = rows[i]['exam-info'].find(info =>
                                                info.exam_id === parseInt(Object.keys(
                                                    examGroupedGradeById)[0]));
                                            if (examInfo) {
                                                tableHtml +=
                                                    `<td class="text-center ">${examInfo.grade}</td><td class="text-center">${examInfo.grade}</td>`;
                                            }
                                            // Add the next subject (if any)
                                            if (rows[i + 1]) {
                                                tableHtml += `<td class="border-right-0">${rows[i + 1].subject}</td>`;
                                                // Check if the next subject has data for the current exam
                                                let examInfo2 = rows[i + 1]['exam-info'].find(
                                                    info => info.exam_id === parseInt(Object
                                                        .keys(examGroupedGradeById)[0]));
                                                if (examInfo2) {
                                                    tableHtml +=
                                                        `<td class="text-center">${examInfo2.grade}</td><td class="text-center border-right-0">${examInfo2.grade}</td>`;
                                                }
                                            } else {
                                                tableHtml +=
                                                    `<td class="text-center"></td><td class="text-center border-right-0"></td><td>`;
                                            }
                                        }
                                        tableHtml += `</tr>`;
                                    }
                                } else {
                                    // If multiple exams (e.g., Unit Test and Half Yearly) exist, display the data in separate columns
                                    examsData.forEach(exam => {
                                        if (exam.by_m_g == 2 && exam['exam-info'] &&
                                            exam['exam-info'].length > 0) {
                                            tableHtml += `<tr><td class="fw-bold">${exam.subject}</td>`;
                                            // For each exam (e.g., Unit Test, Half Yearly) associated with the subject
                                            Object.keys(examGroupedGradeById).forEach(
                                                examId => {
                                                    let examInfo = exam['exam-info']
                                                        .find(info => info
                                                            .exam_id === parseInt(
                                                                examId));
                                                    if (examInfo) {
                                                        tableHtml +=
                                                            `<td class="text-center">${examInfo.grade}</td><td class="text-center border-right-0">${examInfo.grade}</td>`;
                                                    } else {
                                                        // If the subject does not have this exam's data, insert empty td
                                                        tableHtml +=
                                                            `<td class="text-center border-right-0">Abs</td><td class="text-center">Abs</td>`;
                                                    }
                                                });
                                            tableHtml += `</tr>`;
                                        }
                                    });
                                }
                                tableHtml +=
                                    `
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="col-md-2 align-items-stretch px-0">
                                                <table class="table table-bordered h-100 w-100 mb-0">
                                                    <tr>
                                                        <td class="align-middle text-center ms_final_box_result_message">
                                                        <p>Percentage: <br>${((grandTotal / maxMarks) * 100).toFixed(2)}%</p>
                                                        <hr>
                                                        <p>Result: <br>Pass</p>`;
                                grandTotal = 0;
                                maxMarks = 0;
                                tableHtml += `
                                                        </td>
                                                    </tr>
                                                </table>
                                                {{-- <p>Percentage: NaN</p>
                                                <p>Result: Pass</p> --}}
                                            </div>
                                        </div>
                                        </td></tr>`;
                                tableHtml += `
                                          <tr class="marksheet_row">
                                          <td class="p-0 border-0">
                                        <!-- Attendance Record -->
                                        <div class="row">
                                            <div class="col-12">
                                                <table class="table table-bordered text-center mb-0">
                                                    <thead>
                                                        <tr class="marksheet_row attendence_row">
                                                            <td class="text-start" colspan="14"><strong>Attendance Record</strong></td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-start">Months</th>`;
                                attendanceData.monthly_attendance.map(month => {
                                    return tableHtml += `<th>${month.month}</th>`;
                                });
                                tableHtml += `
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="fw-bold text-start">Total Meetings</td>`;
                                attendanceData.monthly_attendance.map(month => {
                                    return tableHtml +=
                                        `<td>${month.total_meetings}</td>`;
                                });
                                tableHtml += `
                                                            <td>${attendanceData.summary.total_meetings}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-bold text-start">Attended</td>`;
                                attendanceData.monthly_attendance.map(month => {
                                    return tableHtml +=
                                        `<td>${month.attended_meetings}</td>`;
                                });
                                tableHtml += `
                                                            <td>${attendanceData.summary.total_attended}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        </td>
                                          </tr>`;
                                tableHtml += `
                                          <tr><td class="border-0">
                                        <!-- Signatures -->
                                        <div class="row mt-3">
                                            <div class="col-4 text-center align-content-end my-2">
                                                <p class="mb-0">Sign of Class Teacher</p>
                                            </div>
                                            <div class="col-4 text-center align-content-end my-2">
                                                <p class="mb-0">Sign of Checker</p>
                                            </div>
                                            <div class="col-4 text-center align-content-end my-2">
                                                 <img src="${response.logo.principal_sign}" alt="School Logo" class="mb-2" style="height:35px;">
                                                <p class="mb-0">Sign of Principal</p>
                                            </div>
                                        </div>
                                        </td></tr>
                                          </table>`;
                            });
                        }
                        $('.marksheet').html(tableHtml);
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
                                    zoom: 0.95; /* Adjust the zoom level as needed */
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
                                .ms_final_box_result_message p {
                                    margin-bottom: 0px;
                                }
                                .ms_final_box_result_message hr {
                                    margin-block: 5px;
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
