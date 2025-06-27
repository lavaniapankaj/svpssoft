@extends('marks.index')
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">

            <div class="col-md-12">

                <div class="card">

                    <div class="card-header">

                        {{ 'Print Final Marksheet (6th to 8th)' }}

                        <a href="{{ route('marks.marks-report.marksheet.six.eighth') }}" class="btn btn-warning btn-sm"
                            style="float: right;">Back</a>
                        <button type="button" id="print-marksheet" class="btn btn-sm btn-primary mx-2 print-marksheet"
                            style="float: right;">Print Marksheet</button>

                    </div>

                    <div class="card-body">

                        <input type="hidden" id="class" name="class"
                            value="{{ old('class', isset($class) ? $class : '') }}">

                        <input type="hidden" id="section" name="section"
                            value="{{ old('section', isset($section) ? $section : '') }}">

                        <input type="hidden" id="students" name="students"
                            value="{{ old('section', isset($students) ? $students : '') }}">

                        <input type="hidden" id="exam" name="exam"
                            value="{{ old('exam', isset($exam) ? $exam : '') }}">

                        <input type="hidden" id="with" name="with"
                            value="{{ old('with', isset($with) ? $with : '') }}">

                        <input type="hidden" id="without" name="without"
                            value="{{ old('without', isset($without) ? $without : '') }}">

                        <input type="hidden" id="session-message" name="sessionMessage"
                            value="{{ old('sessionMessage', isset($sessionMessage) ? $sessionMessage : '') }}">

                        <input type="hidden" id="date-message" name="dateMessage"
                            value="{{ old('dateMessage', isset($dateMessage) ? $dateMessage : '') }}">


                        <div class="row">

                            <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader"
                                style="width:10%;">

                            <div class="marksheet-div">
                                <div class="marksheet"></div>
                                <div class="mt-3">
                                    <button type="button" id="print-marksheet"
                                        class="btn btn-primary print-marksheet">Print
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
            function marksheetData() {

                let classId = $('#class').val();
                let sectionId = $('#section').val();
                let sessionMessage = $('#session-message').val();
                let dateMessage = $('#date-message').val();
                let stdId = $('#students').val();
                let examId = $('#exam').val();
                let withId = $('#with').val().split(',');
                let withoutId = $('#without').val().split(',');
                let SubjectgrandTotalValue = 0;
                let grandTotalValue = 0;
                let overallGrandTotal = 0;
                let sbMarks = 0;
                let colValue = 8;
                let colValue2 = 2;
                let currentIndex = 0;
                if (classId && sectionId && stdId && examId && withId && withoutId) {

                    $.ajax({
                        url: siteUrl + '/marks/marksheet-final-six-eighth/report',
                        type: 'GET',
                        dataType: 'JSON',
                        data: {
                            class: classId,
                            section: sectionId,
                            students: stdId,
                            exam: examId,
                            sessionMessage: sessionMessage,
                            dateMessage: dateMessage,
                        },
                        success: function(response) {

                            let tableHtml = '';

                            let spanValue = 0;

                            let maxMarks = 0;

                            if (response.data) {

                                response.data.forEach(function(studentData, index) {
                                    const studentInfo = studentData.student_info;
                                    const sessionInfo = studentData.session;
                                    const examsData = studentData.exams;
                                    const attendanceData = studentData.attendance;
                                    let tdCount = 0;
                                    studentId = studentInfo.srno;
                                    tableHtml += `
                                    <table class="w-100 marksheet-container">
                                        <tr>

                                         <td><!-- Header Section -->

                                            <div class="row mb-4 text-center">

                                                <div class="col-2">

                                                    <img src="${response.logo.school_logo}" alt="School Logo" class="img-fluid rounded-circle">

                                                </div>

                                                <div class="col-8">

                                                    <h2 class="font-italic mb-0">St. Vivekanand ${studentInfo.school == 1 ? 'Play House' : 'Public Secondary School'}</h2>

                                                    <p class="text-muted mb-0">(English Medium)</p>

                                                    <p class="small mb-0">Vivekanand Chowk, Chirawa, 01596 - 220877</p>

                                                    <p class="small mb-0">Session : ${response.session.session}</p>

                                                </div>
                                                <div class="col-2"></div>

                                           </div>

                                        </td>

                                      </tr>

                                      <tr>

                                          <td><!-- Student Details -->
                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <div class="row mb-2">
                                                        <div class="col-5">Name of Student:</div>
                                                        <div class="col-7">${studentInfo.name}</div>

                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class="col-5">Father's Name:</div>
                                                        <div class="col-7">${studentInfo.f_name}</div>

                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class="col-5">Class:</div>
                                                        <div class="col-7">${studentInfo.class_name} ${studentInfo.section_name}</div>

                                                    </div>

                                                </div>

                                                <div class="col-md-6">

                                                    <div class="row mb-2">
                                                        <div class="col-5">S.R.No.:</div>
                                                        <div class="col-7">${studentInfo.srno}</div>

                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class="col-5">Roll No.:</div>
                                                        <div class="col-7">${studentInfo.rollno}</div>
                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class="col-5">Date of Birth:</div>
                                                        <div class="col-7">${studentInfo.dob !== null ? formatDOB(studentInfo.dob) : 'N/A'}</div>

                                                    </div>

                                                </div>

                                            </div>

                                        </td>

                                      </tr>

                                      <tr>

                                         <td>

                                            <!-- Academic Performance -->

                                            <div class="w-100 px-0 align-items-stretch">

                                                <div class="d-flex mb-1">`;
                                                    if (withoutId != '') {
                                                        colValue = 5;
                                                        console.log(colValue);
                                                    } else {
                                                        colValue = 1;

                                                    }

                                    tableHtml += `

                                        <div class="col-${colValue} px-0">

                                            <table class="table table-bordered h-100 w-100 ac mb-0" cellspacing="0">

                                                <thead>

                                                    <tr style="height:80px;">

                                                        <th rowspan="2" class="align-middle">Subject</th>`;

                                    // Step 1: Group exams by `exam_id`

                                    let examGroupedById = {};
                                    let maxmMarksTotalSide = 0;

                                    examsData.forEach(exam => {

                                        if (exam['exam-info'] && exam['exam-info']
                                            .length > 0) {

                                            exam['exam-info'].forEach(info => {

                                                if (!examGroupedById[info
                                                        .exam_id]) {

                                                    examGroupedById[info
                                                        .exam_id] = {

                                                        examID: info
                                                            .exam_id,

                                                        examName: info
                                                            .exam,

                                                        subjects: [],

                                                        totalMarks: 0,
                                                        maxMarks: 0,



                                                    };

                                                }

                                                examGroupedById[info
                                                        .exam_id].subjects
                                                    .push(exam);

                                            });

                                        }

                                    });
                                    rowSpan = 0;
                                    // Step 2: Create table headers dynamically based on `exam_id`
                                    Object.keys(examGroupedById).forEach(examId => {
                                        let exam = examGroupedById[examId];
                                        if (withoutId.includes(exam.examID.toString())) {
                                            tableHtml +=`<th class="text-center align-middle" rowspan="2" id="exam-name" data-id="${exam.examID}">${exam.examName}</th>`;
                                        }
                                        spanValue += withId.includes(exam.examID.toString()) ? 3 : 1;
                                    });
                                    if (withoutId != '') {
                                        tableHtml += `<th class="text-center align-middle" rowspan="2">Total</th>`;
                                    }
                                    tableHtml += `</tr>`;

                                    tableHtml += `</thead><tbody>`;

                                    tableHtml += `<tr><td>M.M.</td>`;
                                    let subjectMaxMarksTotal = 0;
                                    if (examsData[0].by_m_g == 1 && examsData[0].priority == 1 && examsData[0]['exam-info'] && Array.isArray(examsData[0]['exam-info']) && examsData[0]['exam-info'].length > 0) {
                                        // For each exam associated with the subject
                                        Object.keys(examGroupedById).forEach(examId => {
                                            let examGroup = examGroupedById[examId];
                                            // Check if the exam is in the 'withoutId' array
                                            if (withoutId.includes(examGroup.examID.toString())) {
                                                // Find the specific exam info for the current examId
                                                let examInfo = examsData[0]['exam-info'].find(info => info.exam_id === parseInt(examId));
                                                // If examInfo is found, display the max_marks
                                                if (examInfo) {
                                                    tableHtml += `<td class="text-center">${examInfo.max_marks}</td> `;
                                                    subjectMaxMarksTotal += examInfo.max_marks;
                                                } else {
                                                    // If no exam info is found, display "Abs"
                                                    tableHtml +=` <td class="text-center">Abs</td> `;

                                                }

                                            }

                                        });

                                    }
                                    // maxMarks += subjectMaxMarksTotal;
                                    maxmMarksTotalSide += subjectMaxMarksTotal;
                                    if (withoutId != '') {
                                        tableHtml +=`<td class="text-center">${subjectMaxMarksTotal}</td></tr>`;
                                    }

                                    // Step 3: Generate the table body
                                    let allSubjectsToMarks = 0;
                                    examsData.forEach(exam => {

                                        if (exam.by_m_g == 1 && exam.priority == 1 && exam['exam-info'] && exam['exam-info'].length > 0) {

                                            tableHtml += `<tr><td style="white-space:nowrap;">${exam.subject}</td>`;
                                            let subjectTotal = 0;
                                            // For each exam associated with the subject
                                            Object.keys(examGroupedById).forEach(
                                                examId => {
                                                    if (withoutId.includes(examGroupedById[examId].examID.toString())) {

                                                        let examInfo = exam['exam-info'].find(info => info.exam_id === parseInt(examId));
                                                        // If the subject has data for this exam_id, display the actual data

                                                        if (examInfo) {
                                                            examGroupedById[examId].totalMarks += examInfo.total_marks;
                                                            examGroupedById[examId].maxMarks += examInfo.max_marks;
                                                            subjectTotal += examInfo.total_marks;
                                                            allSubjectsToMarks += examInfo.max_marks;
                                                            tableHtml += `<td class="text-center">${examInfo.total_marks}</td>`;
                                                        } else {

                                                            tableHtml += `<td class="text-center">Abs</td> `;

                                                        }

                                                    }

                                                });
                                            if (withoutId != '') {
                                                tableHtml += `<td class="text-center">${subjectTotal}</td></tr>`;
                                            }
                                        }

                                    });
                                    tableHtml += `<tr> <td class="text-end fw-bold">Total</td>`;

                                    let sssp = 0;
                                    Object.keys(examGroupedById).forEach(examId => {
                                        let exam = examGroupedById[examId];
                                        if (withoutId.includes(exam.examID
                                                .toString())) {
                                            SubjectgrandTotalValue += exam.totalMarks;
                                            sssp += exam.totalMarks;
                                            tableHtml += `<td class="text-center">${exam.totalMarks}</td> `;
                                        }
                                    });
                                    sbMarks = sssp;
                                    if (withoutId != '') {
                                        tableHtml += `<td class="text-center">${sssp}</td></tr>`;
                                    }

                                    tableHtml += `</tbody>

                                            </table>

                                        </div>`;
                                    if (withoutId != '') {
                                        colValue2 = 5;
                                    } else {
                                        colValue2 = 1;
                                    }

                                    tableHtml += `<div class="col-${colValue2} px-0">

                                            <table class="table table-bordered h-100 w-100 mb-0">
                                                <thead>`;

                                    if (withId != '') {

                                        tableHtml += `<tr style="height:40px;">`;

                                    } else {

                                        tableHtml += `<tr style="height:80px;">`;

                                    }
                                    // Step 1: Group exams by `exam_id`
                                    let examGroupedById2 = {};
                                    examsData.forEach(exam => {

                                        if (exam['exam-info'] && exam['exam-info'].length > 0) {

                                            exam['exam-info'].forEach(info => {

                                                if (!examGroupedById2[info.exam_id]) {
                                                    examGroupedById2[info.exam_id] = {
                                                        examID: info.exam_id,
                                                        examName: info.exam,
                                                        subjects: [],
                                                        totalMarks: 0,
                                                        maxMarks: 0,
                                                    };

                                                }
                                                examGroupedById2[info.exam_id].subjects.push(exam);

                                            });

                                        }

                                    });
                                    rowSpan = 0;

                                    // Step 2: Create table headers dynamically based on `exam_id`
                                    let isWithOut = false;
                                    let isWith = false;

                                    Object.keys(examGroupedById2).forEach(examId => {

                                        let exam = examGroupedById2[examId];

                                        if (withId && (withId.includes(exam.examID.toString()))) {

                                            tableHtml += `<th class="text-center" colspan="3" id="exam-name" data-id="${exam.examID}">${exam.examName}</th>`;
                                            spanValue += 3;

                                        }

                                    });
                                    tableHtml +=`<th class="text-center align-middle" rowspan="2">Grand Total</th></tr>`;
                                    tableHtml += `<tr style="height:40px;">`;
                                    Object.keys(examGroupedById2).forEach(examId => {
                                        let exam = examGroupedById2[examId];
                                        if (withId && (withId.includes(exam.examID.toString()))) {
                                            // Add sub-headers only for exams in `withId`

                                            tableHtml += `<th class="text-center">Written</th>
                                                           <th class="text-center">Oral</th>
                                                            <th class="text-center">Total</th>`;
                                            spanValue += 3;
                                        }
                                    });

                                    tableHtml += `</tr>`;
                                    tableHtml += `</thead><tbody>`;
                                    tableHtml += `<tr>`;
                                    if (examsData[0].by_m_g == 1 && examsData[0].priority == 1 && examsData[0]['exam-info'] && Array.isArray(examsData[0]['exam-info']) && examsData[0]['exam-info'].length > 0) {

                                        // For each exam associated with the subject
                                        Object.keys(examGroupedById).forEach(examId => {
                                            let examGroup = examGroupedById[examId]; // Get the exam group by exam_id
                                            if (withId && (withId.includes(examGroup.examID.toString()))) {
                                                let examInfo = examsData[0]['exam-info'].find(info => info.exam_id === parseInt(examId));
                                                if (examInfo) {
                                                    examGroupedById[examId].totalMarks += examInfo.total_marks;
                                                    examGroupedById[examId].maxMarks += examInfo.max_marks;
                                                    maxMarks += examInfo.max_marks;
                                                    maxmMarksTotalSide += examInfo.max_marks;
                                                    tableHtml += `
                                                                        <td class="text-center">${examInfo.written_max_marks}</td>

                                                                        <td class="text-center">${examInfo.oral_max_marks}</td>

                                                                        <td class="text-center">${examInfo.max_marks}</td>


                                                                    `;

                                                } else {

                                                    // If no exam info is found, display "Abs"
                                                    tableHtml +=
                                                        `<td class="text-center">Abs</td>`;
                                                    tableHtml +=
                                                        `<td class="text-center">Abs</td>`;
                                                    tableHtml +=
                                                        `<td class="text-center">Abs</td>`;


                                                }

                                            }

                                        });

                                    }
                                    tableHtml += `<td class="text-center">${maxmMarksTotalSide}</td></tr>`;
                                    let grandTotal = 0;
                                    // Step 3: Generate the table body
                                    examsData.forEach(exam => {

                                        if (exam.by_m_g == 1 && exam.priority == 1 && exam['exam-info'] && Array.isArray(exam['exam-info']) && exam['exam-info'].length > 0) {
                                            if (withId != '') {
                                                tableHtml += `<tr>`;
                                            } else {
                                                tableHtml += `<tr style="height:50.75px;">`;

                                            }
                                            let subjectTotalForSuperTotal = 0;
                                            // For each exam associated with the subject
                                            Object.keys(examGroupedById2).forEach(
                                                examId => {
                                                    let examInfo = exam['exam-info'].find(info => info.exam_id === parseInt(examId));
                                                    let examGroup = examGroupedById2[examId];
                                                    if (examInfo) {

                                                        if (withId && (withId.includes(examGroup.examID.toString()))) {
                                                            // For exams in withId, show written, oral, and total marks
                                                            tableHtml += `

                                                                        <td class="text-center">${examInfo.written_marks}</td>

                                                                        <td class="text-center">${examInfo.oral_marks}</td>

                                                                        <td class="text-center">${examInfo.total_marks}</td>

                                                                    `;

                                                            examGroup.totalMarks += examInfo.total_marks;
                                                            // examGroup.maxMarks += examInfo.max_marks;
                                                            allSubjectsToMarks += examInfo.max_marks;
                                                            subjectMaxMarksTotal += examInfo.max_marks;
                                                            subjectTotalForSuperTotal += examInfo.total_marks;
                                                        }

                                                    } else {
                                                        // If the subject does not have this exam's data, insert appropriate placeholders
                                                        if (withId && (withId.includes(examGroup.examID.toString()))) {

                                                            tableHtml += `

                                                                        <td class="text-center">Abs</td>

                                                                        <td class="text-center">Abs</td>

                                                                        <td class="text-center">Abs</td>

                                                                    `;

                                                        }



                                                    }

                                                });



                                            // Add the super total column with subject total

                                            tableHtml +=
                                                `<td class="text-center">${exam.allExamsTotal}</td></tr>`;

                                        }

                                    });

                                    tableHtml += ` <tr>`;
                                    overallGrandTotal = sbMarks;
                                    Object.keys(examGroupedById2).forEach(examId => {
                                        let exam = examGroupedById2[examId];
                                        if (withId && (withId.includes(exam.examID.toString()))) {
                                            tableHtml +=
                                                `<td></td><td></td><td class="text-center">${exam.totalMarks}</td>`;
                                            overallGrandTotal += exam.totalMarks;
                                        }

                                    });
                                    tableHtml += `<td>${overallGrandTotal}</td></tr>`;
                                    grandTotal = overallGrandTotal;


                                    tableHtml += ` </tbody>

                                            </table>

                                        </div>
                                        <div class="col-md-2 align-items-stretch justify-content-center px-0">

                                            <table class="table table-bordered h-100 w-100">

                                                <tr>

                                                    <td class="py-5">

                                                    <p class="fw-bold mb-1 text-center">${response.logo.result_date_message}</p>

                                                   <p class="mb-1 border-top border-black text-center">${response.logo.session_start_message}</p></td>

                                                </tr>

                                            </table>
                                        </div>
                                    </div>



                                    <!-- Grades Section -->

                                    <div class="d-flex align-items-stretch">

                                        <div class="col-md-10 px-0">

                                             <table class="table table-bordered h-100 w-100">

                                                <thead>

                                                    <tr> `;
                                    // Step 1: Group exams by `exam_id`

                                    let examGroupedGradeById = {};

                                    examsData.forEach(exam => {

                                        if (exam.by_m_g == 2) {

                                            if (exam['exam-info'] && exam['exam-info'].length > 0) {

                                                exam['exam-info'].forEach(info => {

                                                    if (!examGroupedGradeById[info.exam_id]) {

                                                        examGroupedGradeById[info.exam_id] = {
                                                                examName: info.exam,
                                                                subjects: []
                                                        };

                                                    }
                                                    examGroupedGradeById[info.exam_id].subjects.push(exam);
                                                });
                                            }

                                        }

                                    });
                                    // Step 2: Check if all subjects have only one exam type (same `exam_id`)

                                    let allSubjectsSameExam = Object.keys(examGroupedGradeById).length === 1;
                                    // Generate headers

                                    if (allSubjectsSameExam) {
                                        Object.keys(examGroupedGradeById).forEach(
                                            examId => {
                                                let exam = examGroupedGradeById[examId];
                                                tableHtml += `<th>Subject</th><th class="text-center">${exam.examName}</th><th class="text-center">Grade</th>
                                                              <th>Subject</th><th class="text-center">${exam.examName}</th><th class="text-center">Grade</th>`;
                                            });

                                    } else {

                                        // If multiple exams exist, create headers for each exam type and grade
                                        tableHtml += `<th>Subject</th>`;
                                        Object.keys(examGroupedGradeById).forEach(examId => {
                                                let exam = examGroupedGradeById[examId];
                                                tableHtml += `<th class="text-center">${exam.examName}</th><th class="text-center">Grade</th>`;
                                        });

                                    }
                                    tableHtml += `</tr></thead><tbody>`;
                                     // Step 3: Generate the table body

                                    if (allSubjectsSameExam) {

                                        // For the "only one exam type" case, group subjects and display them side by side

                                        let rows = [];

                                        examsData.forEach(exam => {

                                            if (exam.by_m_g == 2 && exam['exam-info'] && exam['exam-info'].length > 0) {
                                                rows.push(exam);
                                            }

                                        });
                                        // Now, render subjects side by side
                                        let maxSubjects = rows.length;

                                        for (let i = 0; i < maxSubjects; i += 2) {

                                            tableHtml += `<tr>`;

                                            if (rows[i]) {
                                                tableHtml += `<td>${rows[i].subject}</td>`;
                                                // Check if the subject has data for the current exam
                                                let examInfo = rows[i]['exam-info'].find(info => info.exam_id === parseInt(Object.keys(examGroupedGradeById)[0]));
                                                if (examInfo) {
                                                    tableHtml += `<td class="text-center">${examInfo.grade}</td><td class="text-center">${examInfo.grade}</td>`;
                                                }
                                                // Add the next subject (if any)

                                                if (rows[i + 1]) {

                                                    tableHtml += `<td>${rows[i + 1].subject}</td>`;
                                                    // Check if the next subject has data for the current exam
                                                    let examInfo2 = rows[i + 1]['exam-info'].find(info => info.exam_id === parseInt(Object.keys(examGroupedGradeById)[0]));

                                                    if (examInfo2) {
                                                        tableHtml += `<td class="text-center">${examInfo2.grade}</td><td class="text-center">${examInfo2.grade}</td>`;

                                                    }
                                                } else {
                                                    tableHtml += `<td class="text-center"></td><td class="text-center"></td><td>`;

                                                }

                                            }
                                            tableHtml += `</tr>`;

                                        }
                                    } else {

                                        // If multiple exams (e.g., Unit Test and Half Yearly) exist, display the data in separate columns
                                        examsData.forEach(exam => {

                                            if (exam.by_m_g == 2 && exam['exam-info'] && exam['exam-info'].length > 0) {

                                                tableHtml += `<tr><td style="white-space:nowrap;">${exam.subject}</td>`;
                                                // For each exam (e.g., Unit Test, Half Yearly) associated with the subject
                                                Object.keys(examGroupedGradeById).forEach(examId => {

                                                        let examInfo = exam['exam-info'].find(info => info.exam_id === parseInt(examId));

                                                        if (examInfo) {

                                                            tableHtml += `<td class="text-center">${examInfo.grade}</td><td class="text-center">${examInfo.grade}</td>`;

                                                        } else {

                                                            // If the subject does not have this exam's data, insert empty td
                                                            tableHtml += `<td class="text-center">Abs</td><td class="text-center">Abs</td>`;
                                                        }

                                                    });
                                                tableHtml += `</tr>`;

                                            }

                                        });

                                    }

                                    tableHtml +=`
                                                </tbody>

                                            </table>

                                        </div>

                                        <div class="col-md-2 align-items-stretch px-0">

                                            <table class="table table-bordered h-100 w-100">

                                                <tr>

                                                    <td>

                                                    <p>Percentage: ${((overallGrandTotal / allSubjectsToMarks) * 100).toFixed(2)}%</p> <p>Result: Pass</p>`;

                                                                grandTotal = 0;
                                                                maxMarks = 0;
                                                                sbMarks = 0;
                                                                overallGrandTotal = 0;

                                    tableHtml += `</td>

                                                </tr>

                                            </table>
                                        </div>

                                    </div>

                                    </td>

                                      </tr>

                                          <tr><td class="py-0"><div class="border border-bottom-0 p-1 mt-1"><strong>Attendance Record</strong></div></td></tr>

                                      <tr><td class="py-0">



                                    <!-- Attendance Record -->

                                    <div class="align-items-stretch">

                                        <div class="col-12 px-0">

                                            <table class="table table-bordered text-center h-100 w-100">

                                                <thead>

                                                    <tr>

                                                        <th>Months</th>`;

                                                        attendanceData.monthly_attendance.map(month => {
                                                            return tableHtml += `<th>${month.month}</th>`;
                                                        });

                                    tableHtml += `<th>Total</th>

                                                    </tr>

                                                </thead>

                                                <tbody>

                                                    <tr>
                                                    <td>Total Meetings</td>`;

                                    attendanceData.monthly_attendance.map(month => {
                                        return tableHtml += `<td>${month.total_meetings}</td>`;

                                    });

                                    tableHtml += `<td>${attendanceData.summary.total_meetings}</td>
                                                    </tr>
                                                    <tr>
                                                    <td>Attended</td>`;

                                    attendanceData.monthly_attendance.map(month => {

                                        return tableHtml += `<td>${month.attended_meetings}</td>`;

                                    });

                                    tableHtml += `<td>${attendanceData.summary.total_attended}</td>
                                                    </tr>

                                                </tbody>

                                            </table>

                                        </div>

                                    </div>

                                    </td></tr><tr><td>
                                    <!-- Signatures -->

                                    <div class="row mt-0">

                                        <div class="col-4 text-center align-content-end">

                                            <p>Sign of Class Teacher</p>

                                        </div>

                                        <div class="col-4 text-center align-content-end">

                                            <p>Sign of Checker</p>

                                        </div>

                                        <div class="col-4 text-center align-content-end">

                                             <img src="${response.logo.principal_sign}" alt="Signature" class="mb-2" style="height:35px;">

                                            <p>Sign of Principal</p>

                                        </div>

                                    </div>

                                    </div>

                                    </td></tr>

                                    </table>

                                `;

                                });

                                $('.marksheet').html(tableHtml);
                            } else {
                                $('.marksheet').html(
                                    '<p>No data available for the selected criteria.</p>');
                            }
                        },
                        complete: function() {
                            $('#loader').hide();
                        },
                        error: function(xhr) {
                            console.log('Error:', xhr);
                        }

                    });
                }

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
                                            zoom: 0.80; /* Adjust the zoom level as needed */
                                            margin: auto !important;
                                            padding: auto !important;
                                        }
                                        table td {
                                            line-height: 1em;
                                            vertical-align: bottom;
                                            padding: 1px 5px !important;
                                        }
                                        table th {
                                            line-height: 1em;
                                            vertical-align: bottom;
                                            padding: 5px 10px !important;
                                        }
                                        .ac td {
                                            padding: 0 !important;
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
                                            font-size: 14px; /* Adjust the font size of the signatures */
                                        }
                                    }
                            </style>

                        `);
                iframeDoc.write('</head><body>');
                // Append each table separately
                $('.marksheet .marksheet-container').each(function() {
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
            marksheetData();

        });
    </script>
@endsection
