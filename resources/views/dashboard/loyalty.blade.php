@extends('layouts.app')

@section('title', 'Poin Loyalitas - PadelBook')

@section('content')
<div class="row">
    <!-- Redeem Info card -->
    <div class="col-lg-4 mb-4">
        <div class="glass-card p-4 border-0 text-center mb-4">
            <span class="p-3 bg-success-subtle text-success rounded-circle border border-success d-inline-flex mb-3">
                <i class="fa-solid fa-gift fs-3"></i>
            </span>
            <h4 class="mb-2 fw-bold display-font">Klaim Hadiah</h4>
            <p class="text-secondary small mb-4">Tukarkan 10 poin loyalitas PadelBook Anda dengan Voucher Main Gratis 1 Jam.</p>

            <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-25 mb-4">
                <small class="text-muted d-block small">Poin Anda</small>
                <h3 class="text-success fw-bold display-font mb-0">{{ Auth::user()->points }} Poin</h3>
            </div>

            <form action="{{ route('dashboard.loyalty.redeem') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sporty w-100 py-3" 
                        {{ Auth::user()->points < 10 ? 'disabled' : '' }}
                        :class="{'disabled opacity-50': {{ Auth::user()->points }} < 10}">
                    <i class="fa-solid fa-gift me-2"></i> Tukarkan 10 Poin
                </button>
            </form>
            @if(Auth::user()->points < 10)
                <div class="text-danger small mt-2">Poin Anda belum mencukupi (Minimal 10 poin).</div>
            @endif
        </div>
    </div>

    <!-- Points history list -->
    <div class="col-lg-8">
        <div class="glass-card p-4 border-0">
            <h4 class="mb-4 fw-bold display-font"><i class="fa-solid fa-history me-2 text-success"></i> Riwayat Poin Loyalitas</h4>

            <div class="table-responsive">
                <table class="table table-dark table-sporty align-middle">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Keterangan</th>
                            <th>Tipe</th>
                            <th>Jumlah Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($points as $lp)
                            <tr>
                                <td>{{ $lp->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $lp->description }}</td>
                                <td>
                                    <span class="badge bg-{{ $lp->type === 'earn' ? 'success' : 'danger' }}-subtle text-{{ $lp->type === 'earn' ? 'success' : 'danger' }} border border-{{ $lp->type === 'earn' ? 'success' : 'danger' }} px-2 py-1">
                                        {{ strtoupper($lp->type) }}
                                    </span>
                                </td>
                                <td class="fw-bold text-{{ $lp->type === 'earn' ? 'success' : 'danger' }}">
                                    {{ $lp->type === 'earn' ? '+' : '' }}{{ $lp->points }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-secondary small">Belum ada aktivitas poin.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
