<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Anggota BBMC</title>
    <style>
        @page {
            size: A4 landscape;
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
        .no-card {
            font-family: monospace;
            font-weight: bold;
            color: #555555;
            background-color: #f0f0f0;
            padding: 2px 4px;
            border-radius: 3px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7.5pt;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-diponegoro { background-color: #e9d5ff; color: #6b21a8; }
        .badge-lifemember { background-color: #dbeafe; color: #1e40af; }
        .badge-honorary { background-color: #fef3c7; color: #92400e; }
        .badge-virgin { background-color: #dcfce7; color: #166534; }
        .badge-prospect { background-color: #fee2e2; color: #991b1b; }
        .badge-default { background-color: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <h1>Daftar Anggota BBMC</h1>
    <div class="subtitle">
        Diekspor pada: {{ date('d-m-Y H:i:s') }} | Total: {{ count($members) }} anggota
        @if($filter_desc)
            | Filter: {{ $filter_desc }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 140px;">No. Kartu</th>
                <th>Nama Lengkap</th>
                <th style="width: 110px;">No. WA</th>
                <th style="width: 120px;">Status</th>
                <th style="width: 120px;">Chapter</th>
                <th style="width: 110px;">Checkpoint</th>
                <th style="width: 60px;">Terdaftar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $index => $member)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <span class="no-card">
                            {{ $member->no_kartu ? 'BBMC 38 2026 ' . $member->no_kartu : '—' }}
                        </span>
                    </td>
                    <td style="font-weight: bold;">{{ $member->nama_lengkap }}</td>
                    <td>{{ $member->no_wa }}</td>
                    <td>
                        @php
                            $badgeClass = 'badge-default';
                            if ($member->status_keanggotaan === 'SS DIPONEGORO') $badgeClass = 'badge-diponegoro';
                            elseif ($member->status_keanggotaan === 'LIFE MEMBER') $badgeClass = 'badge-lifemember';
                            elseif ($member->status_keanggotaan === 'HONORARY') $badgeClass = 'badge-honorary';
                            elseif ($member->status_keanggotaan === 'VIRGIN') $badgeClass = 'badge-virgin';
                            elseif ($member->status_keanggotaan === 'PROSPECT') $badgeClass = 'badge-prospect';
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $member->status_keanggotaan }}</span>
                    </td>
                    <td>{{ $member->chapter }}</td>
                    <td>{{ $member->checkpoint ?? '—' }}</td>
                    <td>{{ $member->terdaftar_sejak ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px; color: #666666;">Tidak ada data anggota</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
