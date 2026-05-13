<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Anggota — {{ $member->nama_lengkap }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Bebas+Neue&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #1a1a1a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            gap: 20px;
        }

        /* ── TOPBAR ── */
        .topbar {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-print {
            background: linear-gradient(135deg, #b91c1c, #dc2626);
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
            box-shadow: 0 4px 15px rgba(185,28,28,0.5);
            text-decoration: none;
            letter-spacing: 0.5px;
            transition: all 0.2s;
        }
        .btn-print:hover { background: linear-gradient(135deg, #991b1b, #b91c1c); transform: translateY(-1px); }
        .btn-back {
            background: rgba(255,255,255,0.1);
            color: #d1d5db;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 8px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-back:hover { background: rgba(255,255,255,0.15); }

        /* ── CARD WRAPPER ── */
        .card-wrap {
            width: 540px;
            perspective: 1000px;
        }

        /* ── THE CARD ── */
        .card {
            width: 540px;
            height: 340px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,255,255,0.05);
            position: relative;
            background: #6b0f0f;
            display: flex;
            flex-direction: column;
        }

        /* Dark red textured background */
        .card::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 30% 30%, rgba(180,30,30,0.6) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 80%, rgba(100,5,5,0.8) 0%, transparent 50%),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix type='saturate' values='0'/%3E%3C/filter%3E%3Crect width='400' height='400' filter='url(%23noise)' opacity='0.08'/%3E%3C/svg%3E");
            background-color: #7a0d0d;
            pointer-events: none;
            z-index: 0;
        }
        /* Diagonal leather-like texture lines */
        .card::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: repeating-linear-gradient(
                -45deg,
                rgba(0,0,0,0.07) 0px, rgba(0,0,0,0.07) 1px,
                transparent 1px, transparent 6px
            );
            pointer-events: none;
            z-index: 0;
        }

        /* ── HEADING BAND ── */
        .card-heading {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 14px 20px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }
        .heading-main {
            font-family: 'Bebas Neue', 'Oswald', Impact, Arial Narrow, sans-serif;
            font-size: 30px;
            font-weight: 400;
            color: #fff;
            letter-spacing: 3px;
            text-transform: uppercase;
            line-height: 1;
            text-shadow: 0 2px 8px rgba(0,0,0,0.5), 0 0 30px rgba(255,100,100,0.2);
        }
        .heading-sub {
            font-family: 'Bebas Neue', 'Oswald', Impact, Arial Narrow, sans-serif;
            font-size: 18px;
            font-weight: 400;
            color: rgba(255,255,255,0.85);
            letter-spacing: 4px;
            text-transform: uppercase;
            line-height: 1;
            margin-top: 4px;
            text-shadow: 0 1px 4px rgba(0,0,0,0.5);
        }

        /* ── MAIN CONTENT ── */
        .card-body {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            align-items: center;
            padding: 12px 24px;
            gap: 16px;
        }

        /* LEFT: Logo */
        .logo-section {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 120px;
        }
        .logo-img {
            width: 110px;
            height: 110px;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.6));
        }

        /* CENTER: Card label */
        .center-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .reg-card-label {
            font-family: 'Oswald', 'Barlow Condensed', Arial, sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 3px;
            text-transform: uppercase;
            text-shadow: 0 2px 8px rgba(0,0,0,0.6);
        }
        /* Decorative wings + status box */
        .status-badge-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .wing {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5));
        }
        .wing.right {
            background: linear-gradient(90deg, rgba(255,255,255,0.5), transparent);
        }
        .status-box {
            border: 1.5px solid rgba(255,255,255,0.7);
            padding: 3px 12px;
            font-family: 'Barlow Condensed', 'Oswald', Arial, sans-serif;
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.9);
            letter-spacing: 2px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* RIGHT: QR Code */
        .qr-section {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 120px;
        }
        .qr-frame {
            background: #fff;
            padding: 6px;
            border-radius: 6px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.5);
            width: 110px;
            height: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-frame img {
            width: 96px;
            height: 96px;
            display: block;
        }

        /* ── FOOTER ── */
        .card-footer {
            position: relative;
            z-index: 2;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 10px 24px 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .no-kartu-box {
            background: rgba(255,255,255,0.12);
            border: 1.5px solid rgba(255,255,255,0.35);
            border-radius: 4px;
            padding: 4px 20px;
            font-family: 'Barlow Condensed', 'Oswald', 'Courier New', monospace;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .card-url {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: rgba(255,255,255,0.45);
            letter-spacing: 0.5px;
        }

        /* ── PRINT STYLES ── */
        @media print {
            body { background: #fff; padding: 0; min-height: unset; justify-content: flex-start; }
            .topbar { display: none !important; }
            .card-wrap, .card { width: 100%; }
            .card { box-shadow: none; border-radius: 0; }
            @page { size: 85.6mm 54mm landscape; margin: 0; }
        }
    </style>
</head>
<body>

    <!-- Top action bar -->
    <div class="topbar">
        <a href="javascript:history.back()" class="btn-back">← Kembali</a>
        <a href="{{ route('member.print.pdf', $member) }}" target="_blank" class="btn-print">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Cetak / Download PDF
        </a>
    </div>

    <!-- CARD PREVIEW -->
    <div class="card-wrap">
        <div class="card">

            <!-- HEADING -->
            <div class="card-heading">
                <div class="heading-main">BIKERS BROTHERHOOD</div>
                <div class="heading-sub">MOTORCYCLE INDONESIA</div>
            </div>

            <!-- BODY: Logo | Center | QR -->
            <div class="card-body">

                <!-- Kiri: Logo -->
                <div class="logo-section">
                    <img src="{{ asset('bbmc-logo.png') }}" alt="BBMC Logo" class="logo-img">
                </div>

                <!-- Tengah -->
                <div class="center-section">
                    <div class="reg-card-label">REGISTRATION CARD</div>
                    <div class="status-badge-row">
                        <div class="wing"></div>
                        <div class="status-box">{{ $member->status_keanggotaan }}</div>
                        <div class="wing right"></div>
                    </div>
                </div>

                <!-- Kanan: QR Code -->
                <div class="qr-section">
                    <div class="qr-frame">
                        <img src="{{ $qrDataUri }}" alt="QR Code">
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="card-footer">
                <div class="no-kartu-box">BBMC 38 2026 {{ $member->no_kartu }}</div>
                <div class="card-url">{{ url('member/register') }}</div>
            </div>

        </div>
    </div>

</body>
</html>
