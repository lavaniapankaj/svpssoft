@extends('marks.index')
@section('sub-content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        {{ 'Marksheet' }}

                    </div>
                    <div class="card-body">
                        <a href="{{ route('marks.marks-report.public-exam-wise') }}" class="btn btn-primary btn-sm mt-2">Exam Wise Report (Public School)</a>
                        <a href="{{ route('marks.marks-report.play-exam-wise') }}" class="btn btn-primary btn-sm mt-2">Exam Wise Report (Play School)</a>
                        <a href="{{ route('marks.marks-report.marksheet.pg.nursary') }}" class="btn btn-primary btn-sm mt-2">Final Marksheet (Only for PG and Nursary)</a>
                        <a href="{{ route('marks.marks-report.marksheet.kg') }}" class="btn btn-primary btn-sm mt-2">Final Marksheet (Only for KG)</a>
                        <a href="{{ route('marks.marks-report.marksheet.first.second') }}" class="btn btn-primary btn-sm mt-2">Final Marksheet (Only for First And Second)</a>
                        <a href="{{ route('marks.marks-report.marksheet.third.fifth') }}" class="btn btn-primary btn-sm mt-2">Final Marksheet (Only for Third to Fifth)</a>
                        <a href="{{ route('marks.marks-report.marksheet.six.eighth') }}" class="btn btn-primary btn-sm mt-2">Final Marksheet (Only for Sixth to Eighth)</a>


                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

