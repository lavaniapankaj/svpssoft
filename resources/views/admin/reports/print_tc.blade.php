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
                    <div class="card-header">{{ __('Print Student TC ') }}
                        <a class="btn btn-warning btn-sm" style="float: right;" onclick="history.back()">Back</a>
                    </div>

                    <div class="card-body">
                        <div class="border border-secondary p-3" id="tables">
                            <!-- Recognition Number -->
                            <div class="text-end recognition-number">
                                <div>Recog. No.: DEEO/Primary/JJN/Recognition/06/702 dt.5-7-2006</div>
                                <div>Reg. No.: 37/JJN/2004</div>
                                <div>Ref. Slip No.:{{ isset($ltrRefSlipNo) ? $ltrRefSlipNo : '' }}</div>
                            </div>

                            <!-- School Name -->
                            <h1 class="text-center certificate-heading mb-4">St. Vivekanand Public School</h1>

                            <!-- School Details -->
                            <div class="text-center mb-3">
                                <p class="mb-2">(Managed by Helping Hands Society (Regd.))(An English Medium Upper Primary
                                    School)</p>
                                <p class="mb-2">Near Kabir Teela, Chirawa, JJN (Raj.), Ph. 01596 - 220877, 9829059133,
                                    9414080877</p>
                                <p class="mb-4">Email: svpscrw@yahoo.com, www.svpschirawa.com</p>
                            </div>

                            <!-- Certificate Title -->
                            <h2 class="text-center mb-4">Transfer Certificate</h2>

                            <!-- Certificate Content -->
                            <div class="mb-5 fs-5">
                                <p>Certified that: <span
                                        class="fw-bold">{{ isset($studentName) ? $studentName : 'Access Denied.' }}</span>
                                </p>
                            </div>

                            @if (isset($studentName) &&
                                    isset($fName) &&
                                    isset($mName) &&
                                    isset($stateName) &&
                                    isset($districtName) &&
                                    isset($stAddress) &&
                                    isset($stDob) &&
                                    isset($className) &&
                                    isset($adDate) &&
                                    isset($stSrno) &&
                                    isset($reasonText) &&
                                    isset($paymentStatus))
                                <div class="mb-5 fs-5">
                                    <p>{{ $stGender == 1 ? 'S/o ' : 'D/o ' }}<span><u>SH. {{ $fName }}</u></span> and
                                        <span><u>MRS. {{ $mName }}</u></span>
                                    </p>
                                    <p>Resident of : <span><u>{{ $stAddress }}</u></span> District : <span><u>
                                                {{ $districtName }}</u></span> State : <span><u>
                                                {{ $stateName }}</u></span>
                                    </p>
                                    <p>Born on : <span><u>{{ $stDob }}</u></span></p>

                                    <p>Joined this school in class : <span><u>{{ $className }}</u></span> Class on :
                                        <span><u>
                                                {{ $adDate }}</u></span> Vide admission No. : <span><u>
                                                {{ $stSrno }}</u></span><span> <span>{{ $reasonText }}
                                    </p>
                                    <p class="text-center">{{ $paymentStatus }}</p>
                                </div>
                            @endif

                            <div class="text-end mt-5">
                                <!-- Signature Image -->
                                <img src="{{ config('myconfig.mysignature') }}" alt="Principal Signature"
                                    class="signature-img">
                                <!-- School Seal -->
                                <br>
                                @isset($dateIssued)
                                    <span style="margin-right: 325px;">Date of issued of certificate :
                                        {{ $dateIssued }}</span>
                                @endisset
                                <span class="principal-signature">Principal's Seal & Signature</span>
                            </div>

                        </div>
                        <div class="mt-3">
                            <button type="button" id="print-receipt" class="btn btn-primary print-receipt">Print
                                Receipt</button>
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
                $('#tables').addClass('certificate-wrapper').print();
            });
        });
    </script>
@endsection
