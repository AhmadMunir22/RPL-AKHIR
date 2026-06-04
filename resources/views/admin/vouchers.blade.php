@extends('layouts.admin')

@section('content')
<div class="row">
    <!-- Create voucher code side card -->
    <div class="col-lg-4 mb-4">
        <div class="glass-card p-4 border-0">
            <h4 class="text-white mb-4 fw-bold display-font"><i class="fa-solid fa-tag text-success me-2"></i> Daftarkan Voucher Baru</h4>

            <form action="{{ route('admin.vouchers.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="code" class="form-label text-secondary small">Kode Voucher</label>
                    <input type="text" class="form-control form-control-sporty" id="code" name="code" placeholder="PROMO25" required>
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label text-secondary small">Tipe Diskon</label>
                    <select class="form-select form-control-sporty" id="type" name="type" required>
                        <option value="fixed">Fixed (Potongan Rupiah Tetap)</option>
                        <option value="percentage">Percentage (Potongan Persen %)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="value" class="form-label text-secondary small">Nilai Potongan</label>
                    <input type="number" class="form-control form-control-sporty" id="value" name="value" min="0" placeholder="25000" required>
                </div>

                <div class="mb-3">
                    <label for="min_booking" class="form-label text-secondary small">Minimal Transaksi Booking (Rp)</label>
                    <input type="number" class="form-control form-control-sporty" id="min_booking" name="min_booking" min="0" placeholder="50000" required>
                </div>

                <div class="mb-3">
                    <label for="quota" class="form-label text-secondary small">Kuota Penggunaan</label>
                    <input type="number" class="form-control form-control-sporty" id="quota" name="quota" min="1" placeholder="50" required>
                </div>

                <div class="mb-4">
                    <label for="expired_at" class="form-label text-secondary small">Tanggal Kedaluwarsa</label>
                    <input type="date" class="form-control form-control-sporty" id="expired_at" name="expired_at" required>
                </div>

                <button type="submit" class="btn btn-sporty w-100 py-3">
                    <i class="fa-solid fa-save me-2"></i> Simpan Promo Voucher
                </button>
            </form>
        </div>
    </div>

    <!-- Vouchers list panel -->
    <div class="col-lg-8">
        <div class="glass-card p-4 border-0">
            <h4 class="text-white mb-4 fw-bold display-font"><i class="fa-solid fa-tags me-2 text-success"></i> Promo Voucher Terdaftar</h4>

            <div class="table-responsive">
                <table class="table table-dark table-sporty align-middle">
                    <thead>
                        <tr>
                            <th>Kode Promo</th>
                            <th>Tipe</th>
                            <th>Nilai Diskon</th>
                            <th>Min. Sesi Sewa</th>
                            <th>Kuota Sisa</th>
                            <th>Masa Berlaku</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $voucher)
                            <tr>
                                <td class="fw-bold text-success">{{ $voucher->code }}</td>
                                <td>{{ strtoupper($voucher->type) }}</td>
                                <td class="text-white fw-bold">
                                    {{ $voucher->type === 'percentage' ? $voucher->value . '%' : 'Rp ' . number_format($voucher->value, 0, ',', '.') }}
                                </td>
                                <td>Rp {{ number_format($voucher->min_booking, 0, ',', '.') }}</td>
                                <td>{{ $voucher->quota }} Kali</td>
                                <td>
                                    <span class="badge bg-{{ $voucher->expired_at->isPast() ? 'danger' : 'success' }}-subtle text-{{ $voucher->expired_at->isPast() ? 'danger' : 'success' }} border border-{{ $voucher->expired_at->isPast() ? 'danger' : 'success' }}">
                                        {{ $voucher->expired_at->format('d M Y') }}
                                    </span>
                                </td>
                                <td>
                                    <form action="{{ route('admin.vouchers.destroy', $voucher->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus voucher promo ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash"></i> Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary small">Belum ada promo voucher terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
