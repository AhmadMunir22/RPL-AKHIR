@extends('layouts.admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="glass-card p-4 p-md-5 border-0">
            <h3 class="text-white fw-bold display-font mb-4"><i class="fa-solid fa-file-invoice-dollar text-success me-2"></i> Laporan Keuangan & Pendapatan</h3>
            <p class="text-secondary small mb-5">Filter dan unduh laporan transaksi penyewaan lapangan padel secara langsung. Laporan mencakup data booking lunas, metode pembayaran, dan total omset bersih.</p>

            <div class="row g-4 text-center">
                <!-- PDF Download Card -->
                <div class="col-md-6">
                    <div class="p-4 bg-dark bg-opacity-50 rounded-4 border border-secondary border-opacity-25 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="p-3 bg-danger bg-opacity-25 text-danger rounded-circle d-inline-flex mb-3">
                                <i class="fa-solid fa-file-pdf fs-3"></i>
                            </span>
                            <h4 class="text-white fw-bold mb-2">Ekspor PDF</h4>
                            <p class="text-secondary small">Dokumen formal berformat PDF yang bersih dan rapi untuk kebutuhan cetak laporan bulanan.</p>
                        </div>
                        <a href="{{ route('admin.reports.pdf') }}" class="btn btn-danger w-100 py-3 mt-3">
                            <i class="fa-solid fa-download me-2"></i> Unduh Laporan PDF
                        </a>
                    </div>
                </div>

                <!-- CSV / Excel Download Card -->
                <div class="col-md-6">
                    <div class="p-4 bg-dark bg-opacity-50 rounded-4 border border-secondary border-opacity-25 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <span class="p-3 bg-success bg-opacity-25 text-success rounded-circle d-inline-flex mb-3">
                                <i class="fa-solid fa-file-csv fs-3"></i>
                            </span>
                            <h4 class="text-white fw-bold mb-2">Ekspor CSV / Excel</h4>
                            <p class="text-secondary small">Format data mentah tabular untuk olah data lebih lanjut di aplikasi spreadsheet seperti MS Excel.</p>
                        </div>
                        <a href="{{ route('admin.reports.excel') }}" class="btn btn-success w-100 py-3 mt-3 text-dark fw-bold">
                            <i class="fa-solid fa-file-export me-2"></i> Unduh Spreadsheet Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
