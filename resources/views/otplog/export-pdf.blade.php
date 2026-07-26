<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Log OTP BBMC</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20px;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #333333;
            line-height: 1.3;
        }
        h1 {
            color: #830000;
            font-size: 16pt;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .subtitle {
            font-size: 9pt;
            color: #666666;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #830000;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            font-size: 8pt;
            text-transform: uppercase;
        }
        td {
            padding: 6px 8px;
            border-bottom: 0.5pt solid #dddddd;
            font-size: 8.5pt;
        }
        tr:nth-child(even) td {
            background-color: #f9f9f9;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7.5pt;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-verified { background-color: #dcfce7; color: #166534; }
        .badge-unverified { background-color: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <h1>Daftar Log OTP</h1>
    <div class="subtitle">
        Diekspor pada: {{ date('d-m-Y H:i:s') }} | Total: {{ count($otps) }} data
        @if($filter_desc)
            | Filter: {{ $filter_desc }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 100px;">Waktu</th>
                <th style="width: 180px;">Anggota</th>
                <th style="width: 100px;">No. HP</th>
                <th style="width: 80px;">OTP</th>
                <th style="width: 100px;">Status</th>
                <th>Kedaluwarsa</th>
            </tr>
        </thead>
        <tbody>
            @forelse($otps as $index => $otp)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $otp->created_at ? $otp->created_at->format('d/m/Y H:i') : '—' }}
                    </td>
                    <td>
                        @if($otp->member)
                            <div style="font-weight: bold;">{{ $otp->member->nama_lengkap }}</div>
                            <div style="font-size: 8pt; color: #666;">{{ $otp->member->no_kartu }}</div>
                        @else
                            <span style="font-style: italic; color: #999;">Member Deleted</span>
                        @endif
                    </td>
                    <td>{{ $otp->phone }}</td>
                    <td style="font-family: monospace; font-size: 9pt; letter-spacing: 1px;">{{ $otp->otp }}</td>
                    <td>
                        @if($otp->is_verified)
                            <span class="badge badge-verified">Terverifikasi</span>
                        @else
                            <span class="badge badge-unverified">Menunggu</span>
                        @endif
                    </td>
                    <td>{{ $otp->expires_at ? \Carbon\Carbon::parse($otp->expires_at)->format('d/m/Y H:i') : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #666666;">Tidak ada data log OTP</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
