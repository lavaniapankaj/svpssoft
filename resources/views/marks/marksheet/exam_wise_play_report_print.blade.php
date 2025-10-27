@extends('marks.index')
@section('sub-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 bg-white">
                    <div class="card-header flex-wrap bg-white d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 mt-0">{{ 'Print Report Exam Wise (Play House)' }}</h5>
                        <div class="align-items-center d-flex gap-2">
                            <a href="{{ route('marks.marks-report.play-exam-wise') }}" class="btn bg-light btn-sm" ><span class="mdi mdi-chevron-left me-2"></span>Back</a>
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
           marksheetPrint();
      });
    </script>
@endsection
