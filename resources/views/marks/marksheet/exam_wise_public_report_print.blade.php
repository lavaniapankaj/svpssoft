@extends('marks.index')

@section('sub-content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        {{ 'Print Report Exam Wise' }}
                        <a href="{{ route('marks.marks-report.public-exam-wise') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>
                        <button type="button" id="print-marksheet" class="btn btn-sm btn-primary print-marksheet mx-2" style="float: right;">Print Marksheet</button>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="current_session" value='' id="current_session">
                        <input type="hidden" id="exam_id" value="{{$exam}}">
                        <input type="hidden" id="class_id" value="{{$class}}">
                        <input type="hidden" id="section_id" value="{{$section}}">
                        <input type="hidden" id="std_id" value="{{$students}}">
                        <div class="row">
                            <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"
                                    id="loader" style="width:10%;">
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
    </div>
@endsection
@section('marks-scripts')
    <script>
       $(document).ready(function() {
            marksheetPrint();
       });
    </script>
@endsection
