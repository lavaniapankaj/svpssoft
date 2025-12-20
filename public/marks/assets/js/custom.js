//set current session(marks)
var currentSession = $('#marks_current_session').val();
$('#current_session').val(currentSession);
// let loader = $('#loader');
function getStd() {
    let stdSelect = $('#std_id');
    $('#section_id').change(function() {
        let classId = $('#class_id').val();
        let sectionId = $('#section_id').val();
        let sessionId = $('#current_session').val();
        if (classId && sectionId && sessionId) {
            loader.show();
            $.ajax({
                url: siteUrl + '/std-name-father',
                type: 'GET',
                dataType: 'JSON',
                data: {
                    class_id: classId,
                    section_id: sectionId,
                    session_id: sessionId,
                },
                success: function(students) {
                    stdSelect.empty();
                    let options = '<option value="" selected>All Students</option>';
                    const allStudentSrnos = [];
                    if (students.length > 0) {
                        $.each(students, function(index, student) {
                            allStudentSrnos.push(student.srno);
                            options += '<option value="' + student.srno + '">' +
                                student.rollno + '. ' + student.student_name +
                                '/' +
                                student.f_name + '</option>';
                        });
                    } else {
                        stdSelect.find('option[value=""]').text('No students found');
                    }
                    stdSelect.html(options);
                    stdSelect.find('option[value=""]').val(allStudentSrnos);
                },
                complete: function() {
                    loader.hide();
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        }
    });
}

function marksheetPrint() {
    let marksheetDiv = $('.marksheet-div');
    let classId = $('#class_id').val();
    let sectionId = $('#section_id').val();
    let sessionId = $('#current_session').val();
    let examId = $('#exam_id').val();
    let stdId = $('#std_id').val();
    if (classId && sessionId && examId && stdId) {
        $.ajax({
            url: siteUrl + '/marks/marksheet-report',
            type: 'GET',
            dataType: 'JSON',
            data: {
                class: classId,
                section: sectionId,
                session: sessionId,
                exam: examId,
                std_id: stdId,
            },
            success: function(response) {
                if (response.data && response.data.student) {
                    let tableHtml = '';
                    $.each(response.data.student, function(key, value) {
                        let upperHeader = false;
                        if (value.subjects) {
                            $.each(value.subjects, function(subjectKey,
                                subject) {
                                if (subject.by_m_g == 1) {
                                    upperHeader = true;
                                }
                            });
                        }
                        tableHtml += `
                        <table class="table exam_wise_marksheet_sv">
                            <thead>
                                <tr class="">
                                    <th width="17%"  colspan="1" class="border-0 pb_5 align-top">
                                        <img src="${value.logo}" alt="">
                                    </th>
                                    <th colspan="6" width="66%"  class="text-center border-0 pb_5">
                                        <h2 class="mb-2 name_st_ms_sv">${value.school} </h2>
                                        <p class="mb-1 med_text_sv">(English Medium) </p>
                                        <p  class="mb-1 add_text_sv">Vivekanand Chowk, Chirawa</p>
                                        <p class="mb-2 sec_text_sv">Session: ${value.session || ''}</p>
                                    </th>
                                    <th width="16%"  colspan="1" class="border-0 "></th>
                                </tr>
                                <tr class="">
                                    <th width="25%" colspan="2"  class="border-0 fw-bold ms_text_head">Name: </th>
                                    <th  width="25%" colspan="2"  class="border-0 ms_text_head fw-normal">${value.name || ''}</th>
                                    <th  width="25%" colspan="2"  class="border-0 fw-bold ms_text_head">SRNO: </th>
                                    <th  width="25%" colspan="2"  class="border-0 ms_text_head fw-normal">${value.srno || ''}</th>
                                </tr>
                                <tr class="">
                                    <th  width="25%" colspan="2" class="border-0 fw-bold ms_text_head">Father's Name: </th>
                                    <th  width="25%" colspan="2"  class="border-0 ms_text_head fw-normal">${value.father_name || ''}</th>
                                    <th  width="25%" colspan="2" class="border-0 fw-bold ms_text_head">Mother's Name: </th>
                                    <th  width="25%" colspan="2"  class="border-0 ms_text_head fw-normal">${value.mother_name || ''}</th>
                                </tr>
                                <tr class="">
                                    <th  width="25%" colspan="2" class="border-0 fw-bold ms_text_head">Class: </th>
                                    <th  width="25%" colspan="2" class="border-0 ms_text_head fw-normal">${value.class_name || ''}</th>
                                    <th  width="25%" colspan="2" class="border-0 fw-bold ms_text_head">DOB: </th>
                                    <th  width="25%" colspan="2" class="border-0 ms_text_head fw-normal">${value.dob || ''}</th>
                                </tr>
                                <tr class="">
                                    <th  width="25%" colspan="2"  class="border-0 fw-bold ms_text_head">Roll No.: </th>
                                    <th  width="25%"  colspan="2" class="border-0 ms_text_head fw-normal">${value.rollno || ''}</th>
                                    <th  width="25%" colspan="2" class="border-0 fw-bold ms_text_head">Exam: </th>
                                    <th  width="25%" colspan="2" class="border-0 ms_text_head fw-normal">${value.exam_name || ''}</th>
                                </tr>
                                <tr class="">
                                    <th  width="25%" colspan="2" class="border-0 fw-bold ms_text_head">Section: </th>
                                    <th  width="25%" colspan="2"  class="border-0 ms_text_head fw-normal">${value.section_name || ''}</th>
                                    <th  width="25%" colspan="2" class="border-0 fw-bold ms_text_head"></th>
                                    <th  width="25%" colspan="2" class="border-0 ms_text_head fw-normal"></th>
                                </tr>
                                <tr>
                                    <th colspan="8" class="med_text_sv text-center border-0 pb_3 text text-decoration-underline"">Mark Sheet</th>
                                </tr>
                            </thead>
                        <tbody>`;
                        if (upperHeader == true) {
                            tableHtml +=
                                `<tr class="">
                                    <th  width="22%"  colspan="2" class="fw-bold">Subject</th>
                                    <th width="22%"  colspan="1" class="fw-bold text-center">Written</th>
                                    <th width="22%"  colspan="2" class="fw-bold text-center">Oral</th>
                                    <th width="22%"  colspan="2" class="fw-bold text-center">Practical</th>
                                    <th  width="12%"  colspan="1" class="fw-bold text-center">Total</th>
                                </tr>`;
                        }
                        if (value.subjects) {
                            if (response.data.max_marks && upperHeader == true) {
                                let data_max_marks = response.data.max_marks;
                                tableHtml +=
                                    `<tr class="">
                                        <td width="22%" colspan="2"  class="fw-bold">MM.</td>
                                        <td width="22%" colspan="1" class="text-center fw-bold"> ${data_max_marks['0'].written || '--'} </td>
                                        <td width="22%" colspan="2" class="text-center fw-bold"> ${data_max_marks['0'].oral || '--'} </td>
                                        <td width="22%" colspan="2" class="text-center fw-bold"> ${data_max_marks['0'].practicle || '--'} </td>
                                        <td width="12%" colspan="1" class="text-center fw-bold"> ${data_max_marks['0'].total_maximum_marks || '--'} </td>
                                    </tr>`;
                            }
                            $.each(value.subjects, function(subjectKey, subject) {
                                if (subject.by_m_g == 1) {
                                    // upperHeader = true;
                                    tableHtml += `
                                                <tr class="">
                                                    <td width="22%" colspan="2"  class="fw-bold">${subject.name || '--'}</td>
                                                    <td width="22%" colspan="1" class="text-center">${subject.written || '--'}</td>
                                                    <td width="22%" colspan="2" class="text-center">${subject.oral || '--'}</td>
                                                    <td width="22%" colspan="2" class="text-center">${subject.practical || '--'}</td>
                                                    <td width="12%" colspan="1" class="text-center">${subject.total || '--'}</td>
                                                </tr>`;
                                }
                            });
                        }
                        if (upperHeader == true) {
                            tableHtml += `
                                        <tr class="">
                                            <th colspan="7" class="border-0 pb_4">Grand Total</th>
                                            <td class="result_number border-0 text-center pb_4 fw-bold">${value.grand_total_marks || ''}</td>
                                        </tr>`;
                        }
                        tableHtml += ` <tr class="">
                                        <th class="fw-bold" colspan="2">Subject</th>
                                        <th class="fw-bold text-center" colspan="1">Grade</th>
                                        <th class="fw-bold text-center" colspan="2">Grade</th>
                                        <th class="fw-bold text-center" colspan="2">Grade</th>
                                        <th class="fw-bold text-center" colspan="1">Grade</th>
                                    </tr>`;
                        if (value.subjects) {
                            // if (value.subjects.by_m_g == 2) {
                            /* tableHtml += `
                                        <tr class="">
                                            <td width="22%" colspan="2"  class="fw-bold">MM.</td>
                                            <td width="22%" colspan="1" class="text-center">A</td>
                                            <td width="22%" colspan="2" class="text-center">A</td>
                                            <td width="22%" colspan="2" class="text-center">A</td>
                                            <td width="12%" colspan="1" class="text-center">A</td>
                                        </tr>`; */
                            // }
                            $.each(value.subjects, function(subjectKey, subject) {
                                if (subject.by_m_g == 2) {
                                    tableHtml += `
                                            <tr class="">
                                                <td colspan="2" class="fw-bold">${subject.name || '--'}</td>
                                                <td colspan="1" class="text-center">--</td>
                                                <td colspan="2" class="text-center">--</td>
                                                <td colspan="2" class="text-center">--</td>
                                                <td colspan="1" class="text-center">${subject.total || '--'}</td>
                                            </tr>`;
                                }
                            });
                        }
                        tableHtml += `<tr class="">
                                        <th class="border-0" colspan="1"></th>
                                        <th class="border-0" colspan="5"></th>
                                        <th class="border-0 text-center" colspan="2"><img src="${value.principle_sign}" alt="" style="height:35px;"></th>
                                    </tr>
                                    <tr class="">
                                        <th class="border-0 ms_text_head p-0 fw-normal text-center pb_3" colspan="2">Class Teacher</th>
                                        <th class="border-0 ms_text_head p-0 fw-normal text-center pb_3" colspan="4">Checked By</th>
                                        <th class="border-0 ms_text_head p-0 fw-normal text-center pb_3" colspan="2">Principal</th>
                                    </tr>
                                </tbody>
                            </table>`;
                    });
                    $('.marksheet').html(tableHtml);
                } else {
                    $('.marksheet').html(
                        '<p>No data available for the selected criteria.</p>'
                    );
                }
            },
            complete: function() {
                $('#loader').hide();
            },
            error: function(xhr) {
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
                        /* Adjust the zoom level as needed */
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
                    .marksheet-container table {
                        border-collapse: separate !important;
                        border-spacing: 2px !important;
                    }
                    html body .border-0 {
                        border: 0 !important;
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
        $('.marksheet .table').each(function(index) {
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
}
//date
function formatDOB(inputDate) {
    // Create a date object from the input
    const date = new Date(inputDate);
    // Array of month names for conversion
    const months = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    // Pad single digit dates with a leading zero
    const day = date.getDate().toString().padStart(2, '0');
    // Get month name in three-letter format
    const month = months[date.getMonth()];
    // Get full year
    const year = date.getFullYear();
    // Return formatted date
    return `${day}-${month}-${year}`;
}
// Marksheet For class 3 to 5 and 6 to 8
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
    let xxx = 0;
    let colValue = 8;
    let colValue2 = 2;
    if (classId && sectionId && stdId && examId && withId && withoutId) {
        $.ajax({
            url: siteUrl + '/marks/marksheet-final-third-fifth/report',
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
                        const examsData = studentData.exams;
                        const attendanceData = studentData.attendance;
                        studentId = studentInfo.srno;
                        tableHtml += `
                        <table class="w-100 marksheet-container sv_marksheet_table">
                            <tr class="marksheet_row first">
                                <td class="border-0"><!-- Header Section -->
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
                          </tr>
                          <tr class="marksheet_row second">
                              <td class="border-0"><!-- Student Details -->
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
                            </td>
                          </tr>
                          <tr class="marksheet_row third">
                             <td class="p-0 border-0">
                                <!-- Academic Performance -->
                                <div class="w-100 px-0 align-items-stretch">
                                    <div class="d-flex mb-1">`;
                        if (withoutId != '') {
                            colValue = 5;
                        } else {
                            colValue = 1;
                        }
                        tableHtml += `
                            <div class="col-${colValue} px-0">
                                <table class="table table-bordered h-100 w-100 ac mb-0" cellspacing="0">
                                    <thead>
                                        <tr style="height:52px;">
                                            <th rowspan="2" class="align-middle">Subject</th>`;
                        // Step 1: Group exams by `exam_id`
                        let examGroupedById = {};
                        let maxmMarksTotalSide = 0;
                        examsData.forEach(exam => {
                            if (exam['exam-info'] && exam['exam-info'].length > 0) {
                                exam['exam-info'].forEach(info => {
                                    if (!examGroupedById[info.exam_id]) {
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
                                tableHtml += `<th class="text-center align-middle" rowspan="2" id="exam-name" data-id="${exam.examID}">${exam.examName}</th>`;
                            }
                            spanValue += withId.includes(exam.examID.toString()) ? 3 : 1;
                        });
                        if (withoutId != '') {
                            tableHtml += `<th class="text-center align-middle border-right-0" rowspan="2">Total</th>`;
                        }
                        tableHtml += `</tr>`;
                        tableHtml += `</thead><tbody>`;
                        tableHtml += `<tr><td class="fw-bold">M.M.</td>`;
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
                                        tableHtml += `<td class="text-center fw-bold">${examInfo.max_marks}</td> `;
                                        subjectMaxMarksTotal += examInfo.max_marks;
                                    } else {
                                        // If no exam info is found, display "Abs"
                                        tableHtml += ` <td class="text-center">Abs</td> `;
                                    }
                                }
                            });
                        }
                        // maxMarks += subjectMaxMarksTotal;
                        maxmMarksTotalSide += subjectMaxMarksTotal;
                        if (withoutId != '') {
                            tableHtml += `<td class="text-center fw-bold border-right-0">${subjectMaxMarksTotal}</td></tr>`;
                        }
                        // Step 3: Generate the table body
                        let allSubjectsToMarks = 0;
                        examsData.forEach(exam => {
                            if (exam.by_m_g == 1 && exam.priority == 1 && exam['exam-info'] && exam['exam-info'].length > 0) {
                                tableHtml += `<tr><td class="fw-bold">${exam.subject}</td>`;
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
                                                tableHtml += `<td class="text-center ">${examInfo.total_marks}</td>`;
                                            } else {
                                                tableHtml += `<td class="text-center">Abs</td> `;
                                            }
                                        }
                                    });
                                if (withoutId != '') {
                                    tableHtml += `<td class="text-center border-right-0">${subjectTotal}</td></tr>`;
                                }
                            }
                        });
                        tableHtml += `<tr> <td class="text-center fw-bold">Total</td>`;
                        let sssp = 0;
                        Object.keys(examGroupedById).forEach(examId => {
                            let exam = examGroupedById[examId];
                            if (withoutId.includes(exam.examID
                                    .toString())) {
                                SubjectgrandTotalValue += exam.totalMarks;
                                sssp += exam.totalMarks;
                                tableHtml += `<td class="text-center fw-bold">${exam.totalMarks}</td> `;
                            }
                        });
                        sbMarks = sssp;
                        if (withoutId != '') {
                            tableHtml += `<td class="text-center fw-bold border-right-0">${sssp}</td></tr>`;
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
                            tableHtml += `<tr class="align-middle" style="height:26px;">`;
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
                        tableHtml += `<th class="text-center align-middle border-right-0" rowspan="2">Grand Total</th></tr>`;
                        tableHtml += `<tr class="align-middle" style="height:26px;">`;
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
                                                            <td class="text-center fw-bold">${examInfo.written_max_marks}</td>
                                                            <td class="text-center fw-bold">${examInfo.oral_max_marks}</td>
                                                            <td class="text-center fw-bold">${examInfo.max_marks}</td>
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
                        tableHtml += `<td class="text-center fw-bold border-right-0">${maxmMarksTotalSide}</td></tr>`;
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
                                    `<td class="text-center fw-bold border-right-0">${exam.allExamsTotal}</td></tr>`;
                            }
                        });
                        tableHtml += ` <tr>`;
                        overallGrandTotal = sbMarks;
                        Object.keys(examGroupedById2).forEach(examId => {
                            let exam = examGroupedById2[examId];
                            if (withId && (withId.includes(exam.examID.toString()))) {
                                tableHtml +=
                                    `<td></td><td></td><td class="text-center fw-bold">${exam.totalMarks}</td>`;
                                overallGrandTotal += exam.totalMarks;
                            }
                        });
                        tableHtml += `<td class="text-center fw-bold border-right-0">${overallGrandTotal}</td></tr>`;
                        grandTotal = overallGrandTotal;
                        tableHtml += ` </tbody>
                                </table>
                            </div>
                            <div class="col-md-2 align-items-stretch justify-content-center px-0">
                                <table class="table table-bordered h-100 w-100 mb-0">
                                    <tr>
                                        <td class="align-middle">
                                        <p class="text-center">${response.logo.result_date_message}</p>
                                        <hr>
                                       <p class="text-center">${response.logo.session_start_message}</p></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <!-- Grades Section -->
                        <div class="d-flex align-items-stretch mb-1">
                            <div class="col-md-10 px-0">
                                 <table class="table table-bordered h-100 w-100 mb-0">
                                    <thead>
                                        <tr> `;
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
                                            // Track the order of exam IDs as they first appear
                                            examIdOrder.push(info.exam_id);
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
                            examIdOrder.forEach(
                                examId => {
                                    let exam = examGroupedGradeById[examId];
                                    tableHtml += `<th>Subject</th><th class="text-center">${exam.examName}</th><th class="text-center">Grade</th>
                                                  <th>Subject</th><th class="text-center">${exam.examName}</th><th class="text-center">Grade</th>`;
                                });
                        } else {
                            // If multiple exams exist, create headers for each exam type and grade
                            tableHtml += `<th>Subject</th>`;
                            examIdOrder.forEach(examId => {
                                let exam = examGroupedGradeById[examId];
                                tableHtml += `<th class="text-center">${exam.examName}</th><th class="text-center border-right-0">Grade</th>`;
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
                                    tableHtml += `<tr><td class="fw-bold">${exam.subject}</td>`;
                                    // For each exam (e.g., Unit Test, Half Yearly) associated with the subject
                                    examIdOrder.forEach(examId => {
                                        let examInfo = exam['exam-info'].find(info => info.exam_id === parseInt(examId));
                                        if (examInfo) {
                                            tableHtml += `<td class="text-center">${examInfo.grade}</td><td class="text-center border-right-0">${examInfo.grade}</td>`;
                                        } else {
                                            // If the subject does not have this exam's data, insert empty td
                                            tableHtml += `<td class="text-center">Abs</td><td class="text-center">Abs</td>`;
                                        }
                                    });
                                    tableHtml += `</tr>`;
                                }
                            });
                        }
                        tableHtml += `
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-2 align-items-stretch px-0">
                                <table class="table table-bordered h-100 w-100 mb-0">
                                    <tr>
                                        <td class="align-middle text-center">
                                        <p>Percentage: <br>${((overallGrandTotal / allSubjectsToMarks) * 100).toFixed(2)}%</p>
                                        <hr>
                                        <p>Result: <br>Pass</p>`;
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
                        <div class="align-items-stretch">
                            <div class="col-12 px-0">
                                <table class="table table-bordered h-100 w-100 mb-0">
                                    <thead>
                                         <tr class="marksheet_row attendence_row">
                                            <td class="" colspan="14"><strong>Attendance Record</strong></td>
                                        </tr>
                                        <tr>
                                            <th>Months</th>`;
                        attendanceData.monthly_attendance.map(month => {
                            return tableHtml += `<th class="text-center">${month.month}</th>`;
                        });
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
                        tableHtml += `<td class="text-center">${attendanceData.summary.total_attended}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        </td></tr><tr><td class ="border-0">
                        <!-- Signatures -->
                        <div class="row mt-0">
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
}