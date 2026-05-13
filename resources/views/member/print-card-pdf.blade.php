<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu {{ $member->nama_lengkap }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            width: 242.64pt;
            height: 153.07pt;
            overflow: hidden;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #830000;
        }

        .card {
            width: 242.64pt;
            height: 153.07pt;
            background-color: #830000;
            overflow: hidden;
        }

        /* ═══ HEADING ═══ */
        .heading {
            width: 100%;
            text-align: center;
            padding: 5pt 4pt 3pt;
            border-bottom: 0.5pt solid rgba(255,255,255,0.2);
            line-height: 1;
        }

        .heading-main {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 14pt;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 2.5pt;
            text-transform: uppercase;
            line-height: 1;
        }

        .heading-sub {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 8pt;
            font-weight: bold;
            color: rgba(255,255,255,0.8);
            letter-spacing: 2.5pt;
            text-transform: uppercase;
            line-height: 1;
            margin-top: 1pt;
        }

        /* ═══ BODY TABLE ═══ */
        .body-table {
            width: 100%;
            border-collapse: collapse;
        }

        .col-logo {
            width: 64pt;
            text-align: center;
            padding: 4pt 2pt 3pt 5pt;
            vertical-align: middle;
        }

        .logo-img {
            width: 54pt;
            height: 54pt;
        }

        .col-center {
            text-align: center;
            padding: 4pt 3pt 3pt;
            vertical-align: middle;
        }

        .reg-label {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 10pt;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 1.5pt;
            text-transform: uppercase;
            line-height: 1;
        }

        .status-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3pt;
        }

        .status-table td {
            vertical-align: middle;
            padding: 0;
            line-height: 1;
        }

        .wing-td { width: 28%; }

        .wing-line {
            height: 0.5pt;
            background-color: rgba(255,255,255,0.4);
            font-size: 0;
            line-height: 0;
        }

        .status-box {
            border: 0.7pt solid rgba(255,255,255,0.65);
            padding: 1pt 4pt;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 6pt;
            font-weight: bold;
            color: rgba(255,255,255,0.9);
            letter-spacing: 0.6pt;
            text-transform: uppercase;
            white-space: nowrap;
            text-align: center;
            line-height: 1.2;
        }

        .col-qr {
            width: 64pt;
            text-align: center;
            padding: 4pt 5pt 3pt 2pt;
            vertical-align: middle;
        }

        .qr-frame {
            display: inline-block;
            background-color: #ffffff;
            padding: 3pt;
            width: 54pt;
            height: 54pt;
            font-size: 0;
            line-height: 0;
        }

        .qr-img {
            width: 48pt;
            height: 48pt;
            display: block;
        }

        /* ═══ FOOTER ═══ */
        .footer {
            width: 100%;
            border-top: 0.5pt solid rgba(255,255,255,0.15);
            text-align: center;
            padding: 3pt 6pt 3pt;
            line-height: 1;
        }

        .no-kartu-box {
            display: inline-block;
            border: 0.7pt solid rgba(255,255,255,0.5);
            background-color: rgba(255,255,255,0.08);
            padding: 2pt 10pt;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 1.5pt;
            text-transform: uppercase;
            line-height: 1;
        }

        .card-url {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 5pt;
            color: rgba(255,255,255,0.35);
            line-height: 1;
            margin-top: 2pt;
        }
    </style>
</head>
<body>
<div class="card">

    <div class="heading">
        <div class="heading-main">BIKERS BROTHERHOOD</div>
        <div class="heading-sub">MOTORCYCLE INDONESIA</div>
    </div>

    <table class="body-table">
        <tr>
            <td class="col-logo">
                <img src="{{ public_path('bbmc-logo.png') }}" class="logo-img" alt="BBMC">
            </td>

            <td class="col-center">
                <div class="reg-label">REGISTRATION CARD</div>
                <table class="status-table">
                    <tr>
                        <td class="wing-td"><div class="wing-line"></div></td>
                        <td><div class="status-box">{{ $member->status_keanggotaan }}</div></td>
                        <td class="wing-td"><div class="wing-line"></div></td>
                    </tr>
                </table>
            </td>

            <td class="col-qr">
                <div class="qr-frame">
                    <img class="qr-img" src="{{ $qrDataUri }}" alt="QR">
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <div class="no-kartu-box">BBMC 38 2026 {{ $member->no_kartu }}</div>
        <div class="card-url">{{ url('member/register') }}</div>
    </div>

</div>
</body>
</html>
