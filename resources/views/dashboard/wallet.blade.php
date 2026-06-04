@extends('layouts.app')

@section('title', 'Dompet Digital PadelBook')

@section('content')
<div class="row">
    <!-- Top up Side Panel -->
    <div class="col-lg-4 mb-4">
        <div class="glass-card p-4 border-0 mb-4">
            <h4 class="mb-3 fw-bold display-font"><i class="fa-solid fa-wallet text-success me-2"></i> Dompet Digital</h4>
            
            <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-25 mb-4 text-center">
                <small class="text-muted d-block small">Saldo Tersedia</small>
                <h3 class="text-success fw-bold display-font mb-0">Rp {{ number_format(Auth::user()->wallet_balance, 0, ',', '.') }}</h3>
            </div>

            <form action="{{ route('dashboard.wallet.topup') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="amount" class="form-label text-secondary small">Jumlah Top Up (Rp)</label>
                    <input type="number" class="form-control form-control-sporty" id="amount" name="amount" min="10000" step="5000" placeholder="100000" required>
                    <div class="form-text text-muted small">Minimal pengisian saldo Rp 10.000</div>
                </div>

                <!-- Instant select presets buttons -->
                <div class="row g-2 mb-4" x-data="{ amountInput: 100000 }">
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-secondary w-100 btn-sm text-adaptive" @click="document.getElementById('amount').value = 50000">50K</button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-secondary w-100 btn-sm text-adaptive" @click="document.getElementById('amount').value = 100000">100K</button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-secondary w-100 btn-sm text-adaptive" @click="document.getElementById('amount').value = 250000">250K</button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-secondary w-100 btn-sm text-adaptive" @click="document.getElementById('amount').value = 500000">500K</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-sporty w-100 py-3">
                    <i class="fa-solid fa-plus me-2"></i> Isi Saldo Sekarang
                </button>
            </form>
        </div>
    </div>

    <!-- Ledger history panel -->
    <div class="col-lg-8">
        <div class="glass-card p-4 border-0">
            <h4 class="mb-4 fw-bold display-font"><i class="fa-solid fa-file-invoice me-2 text-success"></i> Riwayat Transaksi Dompet</h4>

            <div class="table-responsive">
                <table class="table table-dark table-sporty align-middle">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Kode Transaksi</th>
                            <th>Keterangan</th>
                            <th>Tipe</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr>
                                <td>{{ $tx->created_at->format('d M Y H:i') }}</td>
                                <td class="fw-semibold text-adaptive">{{ $tx->ref_id ?? '-' }}</td>
                                <td>{{ $tx->description }}</td>
                                <td>
                                    <span class="badge bg-{{ $tx->type === 'deposit' ? 'success' : ($tx->type === 'refund' ? 'primary' : 'danger') }}-subtle text-{{ $tx->type === 'deposit' ? 'success' : ($tx->type === 'refund' ? 'primary' : 'danger') }} border border-{{ $tx->type === 'deposit' ? 'success' : ($tx->type === 'refund' ? 'primary' : 'danger') }} px-2 py-1">
                                        {{ strtoupper($tx->type) }}
                                    </span>
                                </td>
                                <td class="fw-bold text-{{ $tx->type === 'deposit' || $tx->type === 'refund' ? 'success' : 'danger' }}">
                                    {{ $tx->type === 'deposit' || $tx->type === 'refund' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-secondary small">Belum ada transaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginations -->
            <div class="d-flex justify-content-center mt-4">
                {!! $transactions->links('pagination::bootstrap-5') !!}
            </div>
        </div>
    </div>
</div>
@endsection
