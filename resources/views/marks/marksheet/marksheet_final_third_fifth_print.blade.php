@extends('marks.index')
@section('sub-content')

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-md-12">

                <div class="card">

                    <div class="card-header">

                        {{ 'Print Final Marksheet (3rd to 5th)' }}

                        <a href="{{ route('marks.marks-report.marksheet.third.fifth') }}" class="btn btn-warning btn-sm" style="float: right;">Back</a>
                        <button type="button" id="print-marksheet" class="btn btn-sm btn-primary mx-2 print-marksheet" style="float: right;">Print Marksheet</button>
                    </div>

                    <div class="card-body">

                            <input type="hidden" id="class" value="{{$class}}">

                            <input type="hidden" id="section" value="{{$section}}">

                            <input type="hidden" id="students" value="{{$students}}">

                            <input type="hidden" id="exam" value="{{$exam}}">

                            <input type="hidden" id="with" value="{{$with}}">

                            <input type="hidden" id="without" value="{{$without}}">

                            <input type="hidden" id="session-message" value="{{$sessionMessage}}">

                            <input type="hidden" id="date-message" value="{{$dateMessage}}">

                        <div class="row">

                            <img src="{{ config('myconfig.myloader') }}" alt="Loading..." class="loader"

                                    id="loader" style="width:10%;">

                            <div class="marksheet-div">
                                <div class="marksheet"></div>
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
            marksheetData();
        });

    </script>

@endsection