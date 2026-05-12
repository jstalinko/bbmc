<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Anggota — {{ $member->nama_lengkap }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Georgia, 'Times New Roman', serif;
            background: #e5e7eb;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            gap: 24px;
        }

        /* ── TOPBAR (no-print) ── */
        .topbar {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-print {
            background: #b91c1c;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 10px rgba(185,28,28,0.35);
            letter-spacing: 0.3px;
            transition: background 0.15s;
        }
        .btn-print:hover { background: #991b1b; }

        .btn-back {
            background: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 10px 20px;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-back:hover { background: #f9fafb; }

        /* ── CARD ── */
        .card {
            width: 420px;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0,0,0,0.18);
            background: #fff;
        }

        /* Header */
        .card-header {
            background: linear-gradient(135deg, #7f0000 0%, #b91c1c 55%, #dc2626 100%);
            padding: 20px 22px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
            overflow: hidden;
        }
        .card-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: repeating-linear-gradient(
                45deg,
                rgba(255,255,255,0.05) 0px, rgba(255,255,255,0.05) 1px,
                transparent 1px, transparent 8px
            );
        }

        .header-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            position: relative;
            z-index: 1;
            flex-shrink: 0;
        }

        .header-text {
            position: relative;
            z-index: 1;
            flex: 1;
        }
        .header-text .club-name {
            font-family: Georgia, serif;
            font-size: 19px;
            font-weight: 900;
            color: #fff;
            line-height: 1.15;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-text .club-sub {
            font-family: Arial, sans-serif;
            font-size: 8px;
            color: rgba(255,255,255,0.65);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 3px;
        }

        .header-nokartu {
            position: relative;
            z-index: 1;
            text-align: right;
            flex-shrink: 0;
            padding-left: 14px;
            border-left: 1px solid rgba(255,255,255,0.3);
        }
        .header-nokartu .lbl {
            display: block;
            font-family: Arial, sans-serif;
            font-size: 7px;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .header-nokartu .val {
            display: block;
            font-family: 'Courier New', Courier, monospace;
            font-size: 18px;
            font-weight: bold;
            color: #fff;
            letter-spacing: 2px;
            line-height: 1.2;
        }

        /* Body */
        .card-body {
            display: flex;
            padding: 18px 22px 14px;
            gap: 16px;
        }

        .photo-wrap {
            width: 80px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e5e7eb;
            background: #f3f4f6;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-initial {
            font-family: Georgia, serif;
            font-size: 36px;
            font-weight: 900;
            color: #b91c1c;
        }

        .info-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .member-name {
            font-family: Arial, sans-serif;
            font-size: 15px;
            font-weight: 800;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.2;
        }
        .member-nick {
            font-family: Georgia, serif;
            font-size: 11px;
            color: #6b7280;
            font-style: italic;
            margin-top: -1px;
        }
        .sep {
            height: 1px;
            background: #e5e7eb;
            margin: 3px 0;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .info-row .lbl {
            font-family: Arial, sans-serif;
            font-size: 7.5px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 58px;
            flex-shrink: 0;
            padding-top: 1px;
        }
        .info-row .val {
            font-family: Arial, sans-serif;
            font-size: 9px;
            font-weight: 600;
            color: #1f2937;
            flex: 1;
            line-height: 1.35;
        }

        /* Status badge */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            font-size: 7.5px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border: 1px solid;
        }
        .badge-ss      { background:#f3e8ff; color:#7e22ce; border-color:#d8b4fe; }
        .badge-life    { background:#dbeafe; color:#1d4ed8; border-color:#93c5fd; }
        .badge-hon     { background:#fef3c7; color:#b45309; border-color:#fcd34d; }
        .badge-virgin  { background:#dcfce7; color:#15803d; border-color:#86efac; }
        .badge-prospect{ background:#fee2e2; color:#b91c1c; border-color:#fca5a5; }

        /* Footer */
        .card-footer {
            background: linear-gradient(90deg, #7f0000, #b91c1c 60%, #dc2626);
            padding: 8px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-footer .f-left {
            font-family: Arial, sans-serif;
            font-size: 7.5px;
            color: rgba(255,255,255,0.75);
            letter-spacing: 0.3px;
        }
        .card-footer .f-right {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.5px;
            color: rgba(255,255,255,0.6);
            letter-spacing: 1px;
        }

        /* ── PRINT STYLES ── */
        @media print {
            body {
                background: #fff;
                padding: 0;
                min-height: unset;
                justify-content: flex-start;
            }
            .topbar { display: none !important; }
            .card {
                box-shadow: none;
                border-radius: 0;
                width: 100%;
            }
            @page {
                size: A6 landscape;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Top action bar -->
    <div class="topbar">
        <button class="btn-back" onclick="history.back()">
            ← Kembali
        </button>
        <a href="{{ route('member.print.pdf', $member) }}" target="_blank" class="btn-print">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Cetak / Download PDF
        </a>
    </div>

    <!-- CARD PREVIEW -->
    <div class="card">

        <!-- Header -->
        <div class="card-header">
            <img src="{{ asset('bbmc-logo.png') }}" alt="BBMC" class="header-logo" />
            <div class="header-text">
                <div class="club-name">Bikers Brotherhood<br>Motor Club</div>
                <div class="club-sub">Indonesia &mdash; Since 1994</div>
            </div>
            <div class="header-nokartu">
                <span class="lbl">No. Kartu</span>
                <span class="val">{{ $member->no_kartu ? str_pad($member->no_kartu, 4, '0', STR_PAD_LEFT) : '——' }}</span>
            </div>
        </div>

        <!-- Body -->
        <div class="card-body">
            <div class="photo-wrap">
                @if($member->foto)
                    <img src="{{ asset('storage/' . $member->foto) }}" alt="Foto">
                @else
                    <span class="photo-initial">{{ strtoupper(substr($member->nama_lengkap, 0, 1)) }}</span>
                @endif
            </div>

            <div class="info-wrap">
                <div class="member-name">{{ $member->nama_lengkap }}</div>
                <div class="member-nick">"{{ $member->nama_panggilan }}"</div>
                <div class="sep"></div>

                <div class="info-row">
                    <span class="lbl">Status</span>
                    <span class="val">
                        @php
                            $bc = ['SS DIPONEGORO'=>'badge-ss','LIFE MEMBER'=>'badge-life','HONORARY'=>'badge-hon','VIRGIN'=>'badge-virgin','PROSPECT'=>'badge-prospect'][$member->status_keanggotaan] ?? 'badge-prospect';
                        @endphp
                        <span class="badge {{ $bc }}">{{ $member->status_keanggotaan }}</span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="lbl">Chapter</span>
                    <span class="val">{{ $member->chapter }}</span>
                </div>
                @if($member->checkpoint)
                <div class="info-row">
                    <span class="lbl">Checkpoint</span>
                    <span class="val">{{ $member->checkpoint }}@if($member->region) &mdash; {{ $member->region }}@endif</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="lbl">TTL</span>
                    <span class="val">{{ $member->tempat_lahir }}, {{ $member->tanggal_lahir }}</span>
                </div>
                <div class="info-row">
                    <span class="lbl">No. WA</span>
                    <span class="val" style="font-family:'Courier New',monospace;font-size:9px;">{{ $member->no_wa }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="card-footer">
            <span class="f-left">Terdaftar sejak {{ $member->terdaftar_sejak ?? '—' }} &bull; bikersbrotherhoodmc.id</span>
            <span class="f-right">BBMC-36-2026-{{ str_pad($member->no_kartu ?? '0000', 4, '0', STR_PAD_LEFT) }}</span>
        </div>

    </div>

</body>
</html>
