@extends('marks.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Print Final Marksheet (9th)' }}</h5>
                        <div class="align-items-center d-flex gap-2">
                            <a href="{{ route('marks.marks-report.marksheet.ninth') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                            <button type="button" id="print-marksheet" class="btn btn-sm btn-primary mx-2 print-marksheet"
                            style="float: right;">Print Marksheet</button>
                        </div>
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
                        url: siteUrl + '/marks/marksheet-final-ninth/report',
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
                                        <tr class="marksheet_row first">
                                         <td class="border-0"><!-- Header Section -->
                                            <div class="row mb-4">
                                                <div class="col-2">
                                                    <img src="${response.logo.school_logo}" alt="School Logo" class="img-fluid rounded-circle">
                                                </div>
                                                <div class="col-8 text-center">
                                                    <h2 class="mb-2 name_st_ms_sv">St. Vivekanand ${studentInfo.school == 1 ? 'Play House' : 'Public Secondary School'}</h2>
                                                    <p class="mb-2 med_text_sv">(English Medium)</p>
                                                    <p class="mb-2 add_text_sv">Vivekanand Chowk, Chirawa, 01596 - 220877</p>
                                                    <p class="mb-2 sec_text_sv">Session : ${response.session.session}</p>
                                                </div>
                                                <div class="col-2"></div>
                                           </div>
                                        </td>
                                      </tr>
                                      <tr class="marksheet_row second">
                                          <td class="border-0"><!-- Student Details -->
                                            <div class="row mb-2">
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
                                                    <div class="row ms_text_head mb-1">
                                                        <div class="col-5 ms_text_head fw-bold">Class:</div>
                                                        <div class="col-7 ms_text_head">${studentInfo.class_name} ${studentInfo.section_name}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="row mb-2">
                                                        <div class="col-5 ms_text_head fw-bold">S.R.No.:</div>
                                                        <div class="col-7 ms_text_head">${studentInfo.srno}</div>
                                                    </div>
                                                    <div class="row mb-2 ms_text_head">
                                                        <div class="col-5 ms_text_head fw-bold">Roll No.:</div>
                                                        <div class="col-7 ms_text_head">${studentInfo.rollno}</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-5 ms_text_head fw-bold">Date of Birth:</div>
                                                        <div class="col-7 ms_text_head">${studentInfo.dob !== null ? formatDOB(studentInfo.dob) : 'N/A'}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                      </tr>
                                      <tr class="marksheet_row third">
                                         <td class="p-0 border-0">
                                            <!-- Academic Performance -->
                                            <div class="w-100 px-0 align-items-stretch">
                                                <div class="d-flex">`;
                                                    if (withoutId != '') {
                                                        colValue = 5;
                                                    } else {
                                                        colValue = 1;
                                                    }
                                    tableHtml += `
                                        <div class="col-${colValue} px-0 test_mark_table_sv mb-1">
                                            <table class="table table-bordered h-100 w-100 ac mb-0" cellspacing="0">
                                                <thead class="t_head_sv">
                                                    <tr style="height:52px;">
                                                        <th rowspan="2" class="align-middle">Subject</th>`;
                                    // Step 1: Group exams by `exam_id`
                                    let examGroupedById = {};
                                    let maxmMarksTotalSide = 0;
                                    examsData.forEach(exam => {
                                        if (exam['exam-info'] && exam['exam-info'].length > 0) {
                                            exam['exam-info'].forEach(info => {
                                                if (!examGroupedById[info.exam_id]) {
                                                    examGroupedById[info.exam_id] = {
                                                        examID: info.exam_id,
                                                        examName: info.exam,
                                                        subjects: [],
                                                        totalMarks: 0,
                                                        maxMarks: 0,
                                                    };
                                                }
                                                examGroupedById[info.exam_id].subjects.push(exam);
                                            });
                                        }
                                    });
                                    rowSpan = 0;
                                    // Step 2: Create table headers dynamically based on `exam_id`
                                    // CHANGE 1: removed "Total" column header for withoutId exams — no rowspan="2" Total th here
                                    Object.keys(examGroupedById).forEach(examId => {
                                        let exam = examGroupedById[examId];
                                        if (withoutId.includes(exam.examID.toString())) {
                                            tableHtml +=`<th class="text-center align-middle" rowspan="2" id="exam-name" data-id="${exam.examID}">${exam.examName}</th>`;
                                        }
                                        spanValue += withId.includes(exam.examID.toString()) ? 3 : 1;
                                    });
                                    // REMOVED: Total column header for withoutId table
                                    tableHtml += `</tr>`;
                                    tableHtml += `</thead><tbody>`;
                                    tableHtml += `<tr><td class="fw-bold">M.M.</td>`;
                                    let subjectMaxMarksTotal = 0;
                                    if (examsData[0].by_m_g == 1 && examsData[0].priority == 1 && examsData[0]['exam-info'] && Array.isArray(examsData[0]['exam-info']) && examsData[0]['exam-info'].length > 0) {
                                        Object.keys(examGroupedById).forEach(examId => {
                                            let examGroup = examGroupedById[examId];
                                            if (withoutId.includes(examGroup.examID.toString())) {
                                                let examInfo = examsData[0]['exam-info'].find(info => info.exam_id === parseInt(examId));
                                                if (examInfo) {
                                                    tableHtml += `<td class="text-center fw-bold">${examInfo.max_marks}</td> `;
                                                    subjectMaxMarksTotal += examInfo.max_marks;
                                                } else {
                                                    tableHtml +=` <td class="text-center">Abs</td> `;
                                                }
                                            }
                                        });
                                    }
                                    maxmMarksTotalSide += subjectMaxMarksTotal;
                                    // REMOVED: Total cell in MM row for withoutId table
                                    tableHtml += `</tr>`;
                                    // Step 3: Generate the table body
                                    let allSubjectsToMarks = 0;
                                    examsData.forEach(exam => {
                                        if (exam.by_m_g == 1 && exam.priority == 1 && exam['exam-info'] && exam['exam-info'].length > 0) {
                                            tableHtml += `<tr><td class="fw-bold">${exam.subject}</td>`;
                                            let subjectTotal = 0;
                                            Object.keys(examGroupedById).forEach(examId => {
                                                if (withoutId.includes(examGroupedById[examId].examID.toString())) {
                                                    let examInfo = exam['exam-info'].find(info => info.exam_id === parseInt(examId));
                                                    if (examInfo) {
                                                        let numericMarks = (examInfo.marks === 'Abs') ? 0 : Number(examInfo.marks);
                                                        examGroupedById[examId].totalMarks += numericMarks;
                                                        examGroupedById[examId].maxMarks   += examInfo.max_marks;
                                                        subjectTotal                        += numericMarks;
                                                        allSubjectsToMarks                  += examInfo.max_marks;
                                                        tableHtml += `<td class="text-center">${examInfo.marks}</td>`;
                                                    } else {
                                                        tableHtml += `<td class="text-center">Abs</td> `;
                                                    }
                                                }
                                            });
                                            // REMOVED: subjectTotal cell per row for withoutId table
                                            tableHtml += `</tr>`;
                                        }
                                    });
                                    tableHtml += `<tr> <td class="text-center fw-bold">Total</td>`;
                                    let sssp = 0;
                                    Object.keys(examGroupedById).forEach(examId => {
                                        let exam = examGroupedById[examId];
                                        if (withoutId.includes(exam.examID.toString())) {
                                            SubjectgrandTotalValue += exam.totalMarks;
                                            sssp += exam.totalMarks;
                                            tableHtml += `<td class="text-center fw-bold">${exam.totalMarks}</td> `;
                                        }
                                    });
                                    sbMarks = sssp;
                                    // REMOVED: sssp total cell in Total row for withoutId table
                                    tableHtml += `</tr>`;
                                    tableHtml += `</tbody>
                                            </table>
                                        </div>`;
                                    if (withoutId != '') {
                                        colValue2 = 5;
                                    } else {
                                        colValue2 = 1;
                                    }
                                    tableHtml += `<div class="col-${colValue2} px-0 mb-1">
                                            <table class="table table-bordered h-100 w-100 mb-0">
                                                <thead class="t_head_sv">`;
                                    if (withId != '') {
                                        tableHtml += `<tr class="align-middle"style="height:26px;">`;
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
                                    // Step 2: Create table headers
                                    // CHANGE 2: withId exam headers — only "Marks" column, no "Total" column
                                    let isWithOut = false;
                                    let isWith = false;
                                    Object.keys(examGroupedById2).forEach(examId => {
                                        let exam = examGroupedById2[examId];
                                        if (withId && (withId.includes(exam.examID.toString()))) {
                                            // CHANGED colspan from 2 to 1 — removed Total sub-column
                                            tableHtml += `<th class="text-center" colspan="1" id="exam-name" data-id="${exam.examID}">${exam.examName}</th>`;
                                            spanValue += 1;
                                        }
                                    });
                                    tableHtml +=`<th class="text-center align-middle" rowspan="2">Grand Total</th></tr>`;
                                    tableHtml += `<tr class="align-middle"style="height:26px;">`;
                                    Object.keys(examGroupedById2).forEach(examId => {
                                        let exam = examGroupedById2[examId];
                                        if (withId && (withId.includes(exam.examID.toString()))) {
                                            // CHANGED: only Marks header, removed Total header
                                            tableHtml += `<th class="text-center">Marks</th>`;
                                        }
                                    });
                                    tableHtml += `</tr>`;
                                    tableHtml += `</thead><tbody>`;
                                    // M.M. row for withId table
                                    tableHtml += `<tr>`;
                                    if (examsData[0].by_m_g == 1 && examsData[0].priority == 1 && examsData[0]['exam-info'] && Array.isArray(examsData[0]['exam-info']) && examsData[0]['exam-info'].length > 0) {
                                        Object.keys(examGroupedById2).forEach(examId => {
                                            let examGroup = examGroupedById2[examId];
                                            if (withId && (withId.includes(examGroup.examID.toString()))) {
                                                let examInfo = examsData[0]['exam-info'].find(info => info.exam_id === parseInt(examId));
                                                if (examInfo) {
                                                    maxMarks += examInfo.max_marks;
                                                    maxmMarksTotalSide += examInfo.max_marks;
                                                    allSubjectsToMarks += examInfo.max_marks;
                                                    // CHANGED: only one td for Marks, removed Total td
                                                    tableHtml += `
                                                        <td class="text-center fw-bold">${examInfo.max_marks}</td>
                                                    `;
                                                } else {
                                                    tableHtml += `<td class="text-center">Abs</td>`;
                                                }
                                            }
                                        });
                                    }
                                    tableHtml += `<td class="text-center fw-bold">${maxmMarksTotalSide}</td></tr>`;
                                    let grandTotal = 0;
                                    // Step 3: Data rows for withId table
                                    examsData.forEach(exam => {
                                        if (exam.by_m_g == 1 && exam.priority == 1 && exam['exam-info'] && Array.isArray(exam['exam-info']) && exam['exam-info'].length > 0) {
                                            if (withId != '') {
                                                tableHtml += `<tr>`;
                                            } else {
                                                tableHtml += `<tr style="">`;
                                            }
                                            let subjectTotalForSuperTotal = 0;
                                            Object.keys(examGroupedById2).forEach(examId => {
                                                let examInfo = exam['exam-info'].find(info => info.exam_id === parseInt(examId));
                                                let examGroup = examGroupedById2[examId];
                                                if (examInfo) {
                                                    if (withId && (withId.includes(examGroup.examID.toString()))) {
                                                        let numericMarks = (examInfo.marks === 'Abs') ? 0 : Number(examInfo.marks);
                                                        // CHANGED: only Marks cell, removed Total cell
                                                        tableHtml += `
                                                            <td class="text-center">${examInfo.marks}</td>
                                                        `;
                                                        examGroup.totalMarks     += numericMarks;
                                                        examGroup.maxMarks       += examInfo.max_marks;
                                                        subjectTotalForSuperTotal += numericMarks;
                                                    }
                                                } else {
                                                    if (withId && (withId.includes(examGroup.examID.toString()))) {
                                                        // CHANGED: only one Abs cell
                                                        tableHtml += `
                                                            <td class="text-center">Abs</td>
                                                        `;
                                                    }
                                                }
                                            });
                                            tableHtml += `<td class="text-center fw-bold">${exam.allExamsTotal}</td></tr>`;
                                        }
                                    });
                                    // Total row
                                    tableHtml += ` <tr>`;
                                    overallGrandTotal = sbMarks;
                                    Object.keys(examGroupedById2).forEach(examId => {
                                        let exam = examGroupedById2[examId];
                                        if (withId && (withId.includes(exam.examID.toString()))) {
                                            // CHANGED: only one total td (removed empty td before)
                                            tableHtml +=
                                                `<td class="text-center fw-bold">${exam.totalMarks}</td>`;
                                            overallGrandTotal += exam.totalMarks;
                                        }
                                    });
                                    tableHtml += `<td class="text-center fw-bold">${overallGrandTotal}</td></tr>`;
                                    grandTotal = overallGrandTotal;
                                    // Calculate correct total max marks
                                    let subjectCount = examsData.filter(e => e.by_m_g == 1 && e.priority == 1).length;
                                    let totalMaxMarksForPercentage = 0;
                                    if (examsData[0] && examsData[0]['exam-info']) {
                                        examsData[0]['exam-info'].forEach(info => {
                                            totalMaxMarksForPercentage += info.max_marks * subjectCount;
                                        });
                                    }

                                    // Overall Grade comes from backend
                                    let overallGrade = studentData.overallGrade ?? '';

                                    tableHtml += ` </tbody>
                                            </table>
                                        </div>
                                        <div class="col-md-2 align-items-stretch justify-content-center px-0 mb-1">
                                            <table class="table table-bordered h-100 w-100 mb-0">
                                                <tr>
                                                    <td class="align-middle border-start-0 ms_final_box_result_message">
                                                    <p class="text-center">${response.logo.result_date_message}</p>
                                                    <hr>
                                                    <p class="text-center">${response.logo.session_start_message}</p></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- Grades Section -->
                                    <div class="d-flex align-items-stretch">
                                        <div class="col-md-10 px-0 mb-1">
                                             <table class="table table-bordered h-100 w-100 mb-0">
                                                <thead>
                                                    <tr class="align-middle"> `;
                                    // Step 1: Group exams by `exam_id`
                                    let examGroupedGradeById = {};
                                    let examIdOrder = [];
                                    examsData.forEach(exam => {
                                        if (exam.by_m_g == 2) {
                                            if (exam['exam-info'] && exam['exam-info'].length > 0) {
                                                exam['exam-info'].forEach(info => {
                                                    if (!examGroupedGradeById[info.exam_id]) {
                                                        examGroupedGradeById[info.exam_id] = {
                                                                examName: info.exam,
                                                                subjects: []
                                                        };
                                                        examIdOrder.push(info.exam_id);
                                                    }
                                                    examGroupedGradeById[info.exam_id].subjects.push(exam);
                                                });
                                            }
                                        }
                                    });
                                    // Step 2: Check if all subjects have only one exam type
                                    let allSubjectsSameExam = Object.keys(examGroupedGradeById).length === 1;
                                    // Generate headers — Subject + exam mark columns + Overall Grade
                                    if (allSubjectsSameExam) {
                                        examIdOrder.forEach(examId => {
                                            let exam = examGroupedGradeById[examId];
                                            tableHtml += `<th>Subject</th><th class="text-center">${exam.examName}</th><th class="text-center">Overall Grade</th>
                                                          <th>Subject</th><th class="text-center">${exam.examName}</th><th class="text-center">Overall Grade</th>`;
                                        });
                                    } else {
                                        tableHtml += `<th>Subject</th>`;
                                        examIdOrder.forEach(examId => {
                                            let exam = examGroupedGradeById[examId];
                                            tableHtml += `<th class="text-center">${exam.examName}</th>`;
                                        });
                                        tableHtml += `<th class="text-center">Overall Grade</th>`;
                                    }
                                    tableHtml += `</tr></thead><tbody>`;
                                    // Step 3: Generate the table body
                                    if (allSubjectsSameExam) {
                                        let rows = [];
                                        examsData.forEach(exam => {
                                            if (exam.by_m_g == 2 && exam['exam-info'] && exam['exam-info'].length > 0) {
                                                rows.push(exam);
                                            }
                                        });
                                        let maxSubjects = rows.length;
                                        for (let i = 0; i < maxSubjects; i += 2) {
                                            tableHtml += `<tr class="align-middle">`;
                                            if (rows[i]) {
                                                tableHtml += `<td>${rows[i].subject}</td>`;
                                                let examInfo = rows[i]['exam-info'].find(info => info.exam_id === parseInt(Object.keys(examGroupedGradeById)[0]));
                                                if (examInfo) {
                                                    tableHtml += `<td class="text-center">${examInfo.grade}</td>`;
                                                }
                                                tableHtml += `<td class="text-center fw-bold">${rows[i].overallGrade ?? ''}</td>`;
                                                if (rows[i + 1]) {
                                                    tableHtml += `<td>${rows[i + 1].subject}</td>`;
                                                    let examInfo2 = rows[i + 1]['exam-info'].find(info => info.exam_id === parseInt(Object.keys(examGroupedGradeById)[0]));
                                                    if (examInfo2) {
                                                        tableHtml += `<td class="text-center">${examInfo2.grade}</td>`;
                                                    }
                                                    tableHtml += `<td class="text-center fw-bold">${rows[i + 1].overallGrade ?? ''}</td>`;
                                                } else {
                                                    tableHtml += `<td class="text-center"></td><td class="text-center"></td><td class="text-center"></td>`;
                                                }
                                            }
                                            tableHtml += `</tr>`;
                                        }
                                    } else {
                                        examsData.forEach(exam => {
                                            if (exam.by_m_g == 2 && exam['exam-info'] && exam['exam-info'].length > 0) {
                                                tableHtml += `<tr class="align-middle"><td class="fw-bold">${exam.subject}</td>`;
                                                examIdOrder.forEach(examId => {
                                                    let examInfo = exam['exam-info'].find(info => info.exam_id === parseInt(examId));
                                                    if (examInfo) {
                                                        tableHtml += `<td class="text-center">${examInfo.grade}</td>`;
                                                    } else {
                                                        tableHtml += `<td class="text-center">--</td>`;
                                                    }
                                                });
                                                tableHtml += `<td class="text-center fw-bold">${exam.overallGrade ?? ''}</td>`;
                                                tableHtml += `</tr>`;
                                            }
                                        });
                                    }
                                    tableHtml +=`
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="col-md-2 align-items-stretch px-0 mb-1">
                                            <table class="table table-bordered h-100 w-100 mb-0">
                                                <tr class="align-middle">
                                                    <td class="align-middle text-center border-start-0 ms_final_box_result_message">
                                                        <p>Percentage:<br>${totalMaxMarksForPercentage > 0 ? ((overallGrandTotal / totalMaxMarksForPercentage) * 100).toFixed(2) : '0.00'}%</p>
                                                        <hr>
                                                        <p>Result:<br>Pass</p>`;
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
                                      <tr class="marksheet_row attendence_details"><td class="p-0 border-0">
                                    <!-- Attendance Record -->
                                   <!-- <div class="align-items-stretch">
                                        <div class="col-12 px-0">
                                            <table class="table m-0">
                                                <thead class="t_head_sv">
                                                    <tr class="marksheet_row attendence_row">
                                                        <td colspan="14"><strong>Attendance Record</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Months</th>`;
                                                        attendanceData.monthly_attendance.map(month => { return tableHtml += `<th class="text-center">${month.month}</th>`;});
                                        tableHtml += `<th class="text-center">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                    <td class="fw-bold">Total Meetings</td>`;
                                    attendanceData.monthly_attendance.map(month => {
                                        return tableHtml += `<td class="text-center">${month.total_meetings}</td>`;
                                    });
                                    tableHtml += `<td class="text-center">${attendanceData.summary.total_meetings}</td>
                                                    </tr>
                                                    <tr>
                                                    <td class="fw-bold">Attended</td>`;
                                    attendanceData.monthly_attendance.map(month => {
                                        return tableHtml += `<td class="text-center">${month.attended_meetings}</td>`;
                                    });
                                    tableHtml += `<td class="text-center">${attendanceData.summary.total_attended}</td></tr>
                                            </tbody>
                                            </table>
                                        </div>
                                    </div> -->
                                    </td></tr><tr><td class="border-0">
                                    <!-- Signatures -->
                                    <div class="row mt-2">
                                        <div class="col-4 text-center align-content-end my-2">
                                            <p class="mb-0">Sign of Class Teacher</p>
                                        </div>
                                        <div class="col-4 text-center align-content-end my-2">
                                            <p class="mb-0">Sign of Checker</p>
                                        </div>
                                        <div class="col-4 text-center align-content-end my-2">
                                             <img src="${response.logo.principal_sign}" alt="Signature" class="mb-1" style="height:35px;">
                                            <p class="mb-0">Sign of Principal</p>
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
                                        .ms_final_box_result_message p {
                                            margin-bottom: 0px;
                                        }
                                        .ms_final_box_result_message hr {
                                            margin-block: 5px;
                                        }
                                        .marksheet-container th, .marksheet-container td {
                                            border: 1px solid #000 !important;
                                            padding: 2px 3px !important;
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
                                            line-height:1em !important;
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