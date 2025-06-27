@extends('admin.index')
@section('styles')
    <style type="text/css">
        @media print {

            /* Add a wrapper class to contain the certificate */
            .certificate-wrapper {
                page-break-inside: avoid;
                page-break-after: auto;
                break-inside: avoid;
                max-width: 100%;
                margin: 0;
                padding: 0;
            }

            /* Reset any conflicting margins/padding */
            #tables {
                margin: 0;
                padding: 0;
                width: 100%;
            }

            /* Ensure images and signatures stay with content */
            img,
            .signature {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* Optimize page size and margins */
            @page {
                size: A4;
                margin: 1cm;
            }
        }
    </style>
@endsection
@section('sub-content')
    <div class="container">

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">{{ __('Print Fee Slip') }}
                        <a class="btn btn-warning btn-sm" style="float: right;" onclick="history.back()">Back</a>
                    </div>

                    <div class="card-body">
                        <div class="row" id="tables">
                            <!-- Left Receipt -->
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <div class="text-center mb-3">
                                            <h4 class="mb-0">St. Vivekanand {{ $school }}</h4>
                                            <p class="small mb-0">(Managed by Helping Hands Society (Regd.))</p>
                                            <p class="small mb-0">(An English Medium Upper Primary School)</p>
                                            <p class="small mb-0">Near Kabir Teela, Chirawa, JJN (Raj.)</p>
                                            <p class="small mb-0">Ph. 01596 - 220877, 9829059133, 9414080877</p>
                                            <p class="small mb-0">Email: svpscrw@yahoo.com, www.svpschirawa.com</p>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <p class="mb-0"><strong>Receipt No.:</strong> {{ $recp_no }}</p>
                                            </div>
                                            <div class="col-6 text-end">
                                                <p class="mb-0"><strong>Date:</strong> {{ $date }}</p>
                                            </div>
                                        </div>

                                        <p class="mb-3">
                                            From <strong>{{ $name }}</strong> {{ $gender == 1 ? 'S/o' : 'D/o' }} <strong>SH. {{ $father_name }}</strong> Class <strong>{{ $class }}</strong>
                                            Class Section <strong>{{ $section }}</strong> Received with thanks of a sum of Rs. <strong>{{ $total }}/-</strong>
                                            (Rupees <strong>{{ $wordstotal }} only</strong>) on account of {{ $fee_of }} for session <strong>{{ $session }}</strong>
                                        </p>

                                        <div class="text-end mt-4">
                                            <p class="mb-0">(Authorised Signatory)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Receipt (Duplicate) -->
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <div class="text-center mb-3">
                                            <h4 class="mb-0">St. Vivekanand {{ $school }}</h4>
                                            <p class="small mb-0">(Managed by Helping Hands Society (Regd.))</p>
                                            <p class="small mb-0">(An English Medium Upper Primary School)</p>
                                            <p class="small mb-0">Near Kabir Teela, Chirawa, JJN (Raj.)</p>
                                            <p class="small mb-0">Ph. 01596 - 220877, 9829059133, 9414080877</p>
                                            <p class="small mb-0">Email: svpscrw@yahoo.com, www.svpschirawa.com</p>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <p class="mb-0"><strong>Receipt No.:</strong> {{ $recp_no }}</p>
                                            </div>
                                            <div class="col-6 text-end">
                                                <p class="mb-0"><strong>Date:</strong> {{ $date }}</p>
                                            </div>
                                        </div>

                                        <p class="mb-3">
                                            From <strong>{{ $name }}</strong> {{ $gender == 1 ? 'S/o' : 'D/o' }} <strong>SH. {{ $father_name }}</strong> Class <strong>{{ $class }}</strong>
                                            Class Section <strong>{{ $section }}</strong> Received with thanks of a sum of Rs. <strong>{{ $total }}/-</strong>
                                            (Rupees <strong>{{ $wordstotal }} only</strong>) on account of {{ $fee_of }} for session <strong>{{ $session }}</strong>
                                        </p>

                                        <div class="text-end mt-4">
                                            <p class="mb-0">(Authorised Signatory)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" id="print-receipt" class="btn btn-primary print-receipt">Print</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
@section('admin-scripts')
    <script>
        $(document).ready(function() {
            $('.print-receipt').click(function() {
                $('#tables').print();
            });
        });
    </script>
@endsection
