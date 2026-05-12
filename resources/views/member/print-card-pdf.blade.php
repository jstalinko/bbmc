<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu {{ $member->nama_lengkap }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
            font-size: 10pt;
        }

        /* ── HEADER ── */
        .header-table {
            width: 100%;
            background-color: #b91c1c;
            border-collapse: collapse;
        }
        .header-table td {
            padding: 12px 16px;
            vertical-align: middle;
            color: #fff;
        }
        .header-logo-cell {
            width: 64px;
        }
        .header-logo {
            width: 56px;
            height: 56px;
        }
        .club-name {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 18pt;
            font-weight: bold;
            color: #fff;
            line-height: 1.1;
            text-transform: uppercase;
        }
        .club-sub {
            font-size: 6.5pt;
            color: #fecaca;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 3px;
        }
        .nokartu-cell {
            width: 100px;
            text-align: right;
            border-left: 1px solid rgba(255,255,255,0.3);
            padding-left: 14px;
        }
        .nokartu-lbl {
            font-size: 6pt;
            color: #fca5a5;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .nokartu-val {
            font-family: 'Courier New', Courier, monospace;
            font-size: 16pt;
            font-weight: bold;
            color: #fff;
            letter-spacing: 2px;
        }

        /* ── BODY ── */
        .body-table {
            width: 100%;
            border-collapse: collapse;
            padding: 14px 16px;
        }
        .body-table td {
            padding: 14px 16px;
            vertical-align: top;
        }
        .photo-cell {
            width: 76px;
        }
        .photo-box {
            width: 74px;
            height: 92px;
            border: 1.5px solid #d1d5db;
            text-align: center;
            vertical-align: middle;
            overflow: hidden;
            background: #f3f4f6;
        }
        .photo-box img {
            width: 74px;
            height: 92px;
        }
        .photo-initial {
            font-family: Georgia, serif;
            font-size: 28pt;
            font-weight: bold;
            color: #b91c1c;
            line-height: 92px;
        }

        .member-name {
            font-size: 13pt;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.2;
        }
        .member-nick {
            font-family: Georgia, serif;
            font-size: 9pt;
            color: #6b7280;
            font-style: italic;
            margin-top: 2px;
            margin-bottom: 6px;
        }
        .sep {
            height: 1px;
            background: #e5e7eb;
            margin: 4px 0 6px;
            border: none;
        }

        /* Info rows */
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .info-lbl {
            font-size: 6.5pt;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 58px;
            white-space: nowrap;
        }
        .info-val {
            font-size: 8.5pt;
            font-weight: bold;
            color: #1f2937;
        }

        /* Badge — pakai background-color solid untuk DomPDF */
        .badge {
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 1px 5px;
            border: 0.5px solid;
        }
        .badge-ss       { background-color: #f3e8ff; color: #7e22ce; border-color: #d8b4fe; }
        .badge-life     { background-color: #dbeafe; color: #1d4ed8; border-color: #93c5fd; }
        .badge-hon      { background-color: #fef3c7; color: #b45309; border-color: #fcd34d; }
        .badge-virgin   { background-color: #dcfce7; color: #15803d; border-color: #86efac; }
        .badge-prospect { background-color: #fee2e2; color: #b91c1c; border-color: #fca5a5; }

        /* ── FOOTER ── */
        .footer-table {
            width: 100%;
            background-color: #991b1b;
            border-collapse: collapse;
        }
        .footer-table td {
            padding: 6px 16px;
            font-size: 6.5pt;
            color: #fecaca;
            vertical-align: middle;
        }
        .footer-right {
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
            color: #fca5a5;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<table class="header-table">
    <tr>
        <td class="header-logo-cell">
            <img src="{{ public_path('bbmc-logo.png') }}" class="header-logo" alt="BBMC">
        </td>
        <td>
            <div class="club-name">Bikers Brotherhood<br>Motor Club</div>
            <div class="club-sub">Indonesia &mdash; Since 1994</div>
        </td>
        <td class="nokartu-cell">
            <div class="nokartu-lbl">No. Kartu</div>
            <div class="nokartu-val">{{ $member->no_kartu ? str_pad($member->no_kartu, 4, '0', STR_PAD_LEFT) : '——' }}</div>
        </td>
    </tr>
</table>

<!-- BODY -->
<table class="body-table">
    <tr>
        <!-- Foto -->
        <td class="photo-cell">
            <table class="photo-box">
                <tr><td style="text-align:center;vertical-align:middle;height:92px;width:74px;">
                    @if($member->foto && file_exists(storage_path('app/public/' . $member->foto)))
                        <img src="{{ storage_path('app/public/' . $member->foto) }}" alt="Foto">
                    @else
                        <span class="photo-initial">{{ strtoupper(substr($member->nama_lengkap, 0, 1)) }}</span>
                    @endif
                </td></tr>
            </table>
        </td>

        <!-- Info -->
        <td>
            <div class="member-name">{{ $member->nama_lengkap }}</div>
            <div class="member-nick">"{{ $member->nama_panggilan }}"</div>
            <hr class="sep">

            <table class="info-table">
                <tr>
                    <td class="info-lbl">Status</td>
                    <td class="info-val">
                        @php
                            $bc = ['SS DIPONEGORO'=>'badge-ss','LIFE MEMBER'=>'badge-life','HONORARY'=>'badge-hon','VIRGIN'=>'badge-virgin','PROSPECT'=>'badge-prospect'][$member->status_keanggotaan] ?? 'badge-prospect';
                        @endphp
                        <span class="badge {{ $bc }}">{{ $member->status_keanggotaan }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="info-lbl">Chapter</td>
                    <td class="info-val">{{ $member->chapter }}</td>
                </tr>
                @if($member->checkpoint)
                <tr>
                    <td class="info-lbl">Checkpoint</td>
                    <td class="info-val">{{ $member->checkpoint }}@if($member->region) &mdash; {{ $member->region }}@endif</td>
                </tr>
                @endif
                <tr>
                    <td class="info-lbl">TTL</td>
                    <td class="info-val">{{ $member->tempat_lahir }}, {{ $member->tanggal_lahir }}</td>
                </tr>
                <tr>
                    <td class="info-lbl">No. WA</td>
                    <td class="info-val" style="font-family:'Courier New',monospace;font-size:8pt;">{{ $member->no_wa }}</td>
                </tr>
                @if($member->terdaftar_sejak)
                <tr>
                    <td class="info-lbl">Terdaftar</td>
                    <td class="info-val">Sejak {{ $member->terdaftar_sejak }}</td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

<!-- FOOTER -->
<table class="footer-table">
    <tr>
        <td>bikersbrotherhoodmc.id</td>
        <td class="footer-right">BBMC-36-2026-{{ str_pad($member->no_kartu ?? '0000', 4, '0', STR_PAD_LEFT) }}</td>
    </tr>
</table>

</body>
</html>
