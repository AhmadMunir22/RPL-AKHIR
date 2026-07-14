<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            background: #fff;
            color: #1a1a2e;
            padding: 30px;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #e07a5f;
            padding-bottom: 20px;
            margin-bottom: 28px;
        }

        .header h1 {
            font-size: 22px;
            font-weight: 800;
            color: #e07a5f;
            letter-spacing: 1px;
        }

        .header p {
            color: #555;
            font-size: 12px;
            margin-top: 4px;
        }

        .summary-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
        }

        .summary-box {
            flex: 1;
            background: #f8f8f8;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 14px 18px;
            text-align: center;
        }

        .summary-box .label {
            font-size: 11px;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-box .value {
            font-size: 18px;
            font-weight: 700;
            color: #e07a5f;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        thead tr {
            background: #e07a5f;
            color: #fff;
        }

        thead th {
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr:nth-child(even) {
            background: #fafafa;
        }

        tbody tr:hover {
            background: #fff3f0;
        }

        tbody td {
            padding: 9px 12px;
            border-bottom: 1px solid #eee;
            color: #333;
        }

        .badge-lunas {
            background: #dcfce7;
            color: #166534;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .total-row {
            background: #fff3f0 !important;
            font-weight: 700;
        }

        .total-row td {
            border-top: 2px solid #e07a5f;
            color: #e07a5f;
            font-size: 14px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 14px;
        }

        .print-btn {
            display: block;
            margin: 0 auto 24px auto;
            padding: 10px 28px;
            background: #e07a5f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        @media print {
            .print-btn { display: none !important; }
            body { padding: 10px; }
        }
    </style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">
        🖨️ Cetak / Simpan PDF
    </button>

    <div class="header">
        <h1>🎾 {{ $title }}</h1>
        <p>Dicetak pada: {{ $date }} &nbsp;|&nbsp; PadelBook Management System</p>
    </div>

    <div class="summary-bar">
        <div class="summary-box">
            <div class="label">Total Transaksi Lunas</div>
            <div class="value">{{ $bookings->count() }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Total Pendapatan Bersih</div>
            <div class="value">Rp {{ number_format($total, 0, ',', '.') }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Rata-rata per Transaksi</div>
            <div class="value">
                Rp {{ $bookings->count() > 0 ? number_format($total / $bookings->count(), 0, ',', '.') : 0 }}
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Kode Tiket</th>
                <th>Nama Member</th>
                <th>Lapangan</th>
                <th>Tanggal Main</th>
                <th>Jam Sesi</th>
                <th>Metode Bayar</th>
                <th>Total Harga</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $i => $booking)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $booking->qr_code }}</strong></td>
                <td>{{ $booking->user->name ?? '-' }}</td>
                <td>{{ $booking->court->name ?? '-' }}</td>
                <td>{{ $booking->date ? $booking->date->format('d M Y') : '-' }}</td>
                <td>{{ is_array($booking->slots) ? implode(', ', $booking->slots) : $booking->slots }}</td>
                <td>{{ $booking->payment_method ?? 'Midtrans' }}</td>
                <td><strong>Rp {{ number_format($booking->total_price, 0, ',', '.') }}</strong></td>
                <td><span class="badge-lunas">LUNAS</span></td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center; padding: 30px; color: #999;">
                    Belum ada data transaksi yang lunas.
                </td>
            </tr>
            @endforelse
            @if($bookings->count() > 0)
            <tr class="total-row">
                <td colspan="7" style="text-align:right; padding-right: 16px;">TOTAL PENDAPATAN BERSIH</td>
                <td colspan="2">Rp {{ number_format($total, 0, ',', '.') }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Laporan ini digenerate otomatis oleh sistem PadelBook &mdash; {{ $date }}
    </div>

</body>
</html>
