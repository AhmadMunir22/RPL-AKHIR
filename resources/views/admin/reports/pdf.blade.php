<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pendapatan PadelBook</title>
    <style>
        body {
            font-family: sans-serif;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .meta {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-size: 12px;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .total-box {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            padding-top: 10px;
            border-top: 2px solid #333;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">LAPORAN PENDAPATAN PADELBOOK</div>
        <div class="meta">Tanggal Cetak: {{ $date }} | Format Dokumen Resmi</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Lapangan</th>
                <th>Tanggal Main</th>
                <th>Jam Sesi</th>
                <th>Total Pembayaran</th>
                <th>Metode</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td>#{{ $booking->id }}</td>
                    <td>{{ $booking->user->name }}</td>
                    <td>{{ $booking->court->name }}</td>
                    <td>{{ $booking->date->format('Y-m-d') }}</td>
                    <td>{{ implode(', ', $booking->slots) }}</td>
                    <td>Rp {{ number_format($booking->total_price, 0, ',', '.') }}</td>
                    <td>{{ strtoupper($booking->payment_method ?? 'doku') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">Belum ada data pendapatan lunas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total-box">
        Total Akumulasi Pendapatan Bersih: Rp {{ number_format($total, 0, ',', '.') }}
    </div>

</body>
</html>
