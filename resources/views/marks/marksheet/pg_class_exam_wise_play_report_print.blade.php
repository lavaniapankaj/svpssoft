@extends('marks.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Print Report Exam Wise (PG Class)' }}</h5>
                        <div class="align-items-center d-flex gap-2">
                            <a href="{{ route('marks.marks-report.pg-class-exam-wise') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
                            <button type="button" id="print-marksheet" class="btn btn-primary btn-sm print-marksheet mx-2" >Print Marksheet</button>
                        </div>
                    </div>
                    <div class="card-body">
                    <input type="hidden" name="current_session" value='' id="current_session">
                    <input type="hidden" id="exam_id" value="{{$exam}}">
                    <input type="hidden" id="class_id" value="{{$class}}">
                    <input type="hidden" id="section_id" value="{{$section}}">
                    <input type="hidden" id="std_id" value="{{$students}}">
                    <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader" id="loader" style="width:10%;">
                    <div class="marksheet-div">
                            <div class="marksheet">
                            </div>
                            <div class="mt-3">
                                <button type="button" id="print-marksheet" class="btn btn-primary print-marksheet">Print Marksheet</button>
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
        function pgMarksheetPrint() {
            let marksheetDiv = $('.marksheet-div');
            let classId = $('#class_id').val();
            let sectionId = $('#section_id').val();
            let sessionId = $('#current_session').val();
            let examId = $('#exam_id').val();
            let stdId = $('#std_id').val();
            if (classId && sessionId && examId && stdId) {
                $.ajax({
                    url: siteUrl + '/marks/pg-marksheet-report',
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
                                tableHtml += ` <tr class="">
                                                <th class="fw-bold" colspan="6">Subject</th>
                                                <th class="fw-bold text-center" colspan="2">Grade</th>
                                            </tr>`;
                                if (value.subjects) {
                                    $.each(value.subjects, function(subjectKey, subject) {
                                        if (subject.by_m_g == 2) {
                                            tableHtml += `
                                                    <tr class="">
                                                        <td colspan="6" class="fw-bold">${subject.name || '--'}</td>
                                                        <td colspan="2" class="text-center">${subject.total || '--'}</td>
                                                    </tr>`;
                                        }
                                    });
                                }
                                // ✅ Add Final Grade row
                                tableHtml += `
                                    <tr class="table-secondary">
                                        <td colspan="6" class="fw-bold text-end">Final Grade</td>
                                        <td colspan="2" class="text-center fw-bold">${value.overall_grade || '--'}</td>
                                    </tr>`;
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
        pgMarksheetPrint();
      });
    </script>
@endsection
