<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. VIVEKANAND Public School - Application for Admission</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
            color: #000;
            max-width: 210mm;
            /* A4 width */
            margin: 0 auto;
            padding: 20mm 20mm 20mm 20mm;
            /* A4 margins */
        }

        header {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        .logo-left,
        .logo-right {
            width: 100px;
            height: 100px;
            border: 1px solid #000;
            position: absolute;
            top: 0;
        }

        .logo-left {
            left: 0;
        }

        .logo-right {
            right: 0;
        }

        h1,
        h2,
        h3 {
            margin-bottom: 10px;
        }

        .school-info {
            font-size: 0.9em;
            margin-bottom: 20px;
        }

        .form-field {
            margin-bottom: 15px;
        }

        .form-field label {
            display: inline-block;
            font-weight: bold;
        }

        .form-field .input-line {
            display: inline-block;
            width: 60%;
            border-bottom: 1px solid #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        .declaration {
            margin-top: 20px;
        }

        .signature {
            text-align: right;
            margin-top: 20px;
        }

        .office-use {
            margin-top: 30px;
            border-top: 2px solid #000;
            padding-top: 20px;
        }

        @media print {
            body {
                padding: 0;
            }

            @page {
                size: A4;
                margin: 20mm;
            }

        }
    </style>
</head>

<body>
    <header>
        <div class="logo-left">
            {{-- <img src="{{ config('myconfig.blank') }}" alt="Loading..."> --}}
        </div>
        <h1>St. VIVEKANAND Public School</h1>
        <div class="school-info">
            <p>Near Baager, CHIRAWA-333026</p>
            <p>Ph. 01596-220877 Mob. 9829059133</p>
        </div>
        <h2>Application for Admission</h2>
        <div class="logo-right">
            {{-- <img src="{{ config('myconfig.blank') }}" alt="Loading..."> --}}
        </div>
    </header>

    <p><em>Note: Read the form carefully and fill it. The form should be filled in by the parents/guardian of the child.
            Strike off the entries which are not applicable.</em></p>

    <div class="form-field">
        <label>1. Name of the Student:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>2. Date of Birth:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>3. Nationality:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>Caste: Gen. / OBC / SC / ST / Other (Specify)</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>4. Father's Name:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>Father's Profession/occupation:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>With Office Address:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>Ph:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>5. Mother's Name:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>Mother's Profession/occupation:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>With Office Address:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>Ph:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>6. Guardian's Name:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>Guardian's Profession/Occupation:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>With Office Address:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>Ph:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>7. Permanent Address:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <h3>8. Details of last examination passed (if any):</h3>
    <table>
        <thead>
            <tr>
                <th>Name of Examination</th>
                <th>Passing Year</th>
                <th>Name of the School</th>
                <th>Marks % obtained</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        </tbody>
    </table>

    <div class="form-field">
        <label>9. Class in Which admission is sought:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <div class="form-field">
        <label>10. Whether Child's brother/Sister Studies in S.V.P.S.:</label>
        <span class="input-line">&nbsp;</span>
    </div>

    <h3>11. Enclosures : Attested Copies of :</h3>
    <ul>
        <li>Birth Certificate</li>
        <li>Last Progress Report(s)</li>
        <li>School Transfer Certificate (T.C.)</li>
        <li>Other (Pl. Specify)</li>
    </ul>

    <div class="signature">
        <p>Signature of Parents/Guardian</p>
    </div>

    <div class="declaration">
        <h3>Declaration by Parent or Guardian</h3>
        <p>I, _______________________ father/guardian/mother of _______________________ solemnly declare and affirm on
            oath that:</p>
        <ol>
            <li>I have read the rules and regulations of the play house as given in the Prospectus and agree to abide by
                them.</li>
            <li>All the information given in this form is correct to the best of my knowledge.</li>
            <li>I undertake to let my ward remain in the school as a disciplined student and abide by the orders issued
                by the school authorities from time to time.</li>
            <li>I understand that if any information provided in this form is found to be false, the admission of the
                child will stand cancelled.</li>
        </ol>
    </div>

    <div class="signature">
        <p>Signature of Parents/Guardian</p>
    </div>

    <div class="office-use">
        <h3>For Office Use</h3>
        <div class="form-field">
            <label>Admission No:</label>
            <span class="input-line">&nbsp;</span>
        </div>
        <div class="form-field">
            <label>Standard:</label>
            <span class="input-line">&nbsp;</span>
        </div>
        <div class="form-field">
            <label>Grade Provided to child at the time of interview:</label>
            <span class="input-line">&nbsp;</span>
        </div>
        <div class="form-field">
            <label>by:</label>
            <span class="input-line">&nbsp;</span>
        </div>
        <div class="signature">
            <p>Admission granted/rejected</p>
            <p>Principal</p>
        </div>
    </div>
</body>

</html>
