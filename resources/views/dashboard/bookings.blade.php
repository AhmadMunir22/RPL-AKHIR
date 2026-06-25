@extends('layouts.app')

@section('title', 'Riwayat Reservasi - PadelBook')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="glass-card p-4 border-0">
            <h3 class="fw-bold display-font mb-4"><i class="fa-solid fa-history text-success me-2"></i> Riwayat Reservasi Lapangan</h3>

            <div class="table-responsive">
                <table class="table table-dark table-sporty align-middle">
                    <thead>
                        <tr>
                            <th>ID Booking</th>
                            <th>Lapangan</th>
                            <th>Tanggal Main</th>
                            <th>Jam Sesi</th>
                            <th>Total Harga</th>
                            <th>Status Sesi</th>
                            <th>Pembayaran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td class="fw-bold text-success">#{{ $booking->id }}</td>
                                <td class="fw-semibold text-adaptive">{{ $booking->court->name }}</td>
                                <td>{{ $booking->date->format('d F Y') }}</td>
                                <td>
                                    @foreach($booking->slots as $slot)
                                        <span class="badge bg-secondary-subtle text-adaptive small">{{ $slot }}</span>
                                    @endforeach
                                </td>
                                <td>Rp {{ number_format($booking->total_price, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge bg-{{ $booking->status === 'confirmed' ? 'info' : ($booking->status === 'completed' ? 'success' : 'warning') }}-subtle text-{{ $booking->status === 'confirmed' ? 'info' : ($booking->status === 'completed' ? 'success' : 'warning') }} border border-{{ $booking->status === 'confirmed' ? 'info' : ($booking->status === 'completed' ? 'success' : 'warning') }} px-2 py-1">
                                        @if($booking->status === 'pending') PENDING 
                                        @elseif($booking->status === 'confirmed') PROSES 
                                        @elseif($booking->status === 'completed') COMPLETE 
                                        @elseif($booking->status === 'cancelled') CANCELLED 
                                        @else {{ strtoupper($booking->status) }} @endif
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $psColor = match($booking->payment_status) {
                                            'paid' => 'success',
                                            'partial' => 'warning',
                                            'awaiting_approval' => 'info',
                                            'failed' => 'danger',
                                            'refunded' => 'secondary',
                                            default => 'danger'
                                        };
                                        $psLabel = match($booking->payment_status) {
                                            'paid' => 'LUNAS',
                                            'partial' => 'DP TERBAYAR',
                                            'awaiting_approval' => 'MENUNGGU PERSETUJUAN',
                                            'failed' => 'GAGAL',
                                            'refunded' => 'DIKEMBALIKAN',
                                            default => 'BELUM BAYAR'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $psColor }}-subtle text-{{ $psColor }} border border-{{ $psColor }} px-2 py-1">
                                        {{ $psLabel }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @if($booking->status === 'pending' && $booking->payment_status === 'pending')
                                            <a href="{{ route('booking.pay', $booking->id) }}" class="btn btn-sporty btn-sm">
                                                <i class="fa-solid fa-credit-card me-1"></i> Lanjut Bayar
                                            </a>
                                        @endif

                                        @if(in_array($booking->status, ['confirmed', 'completed']))
                                            <a href="{{ route('dashboard.bookings.ticket', $booking->id) }}" class="btn btn-outline-sporty btn-sm" target="_blank">
                                                <i class="fa-solid fa-ticket me-1"></i> Buka Tiket
                                            </a>
                                        @endif

                                        @if($booking->status === 'confirmed')
                                            <span class="text-info small" style="margin-top:2px;"><i class="fa-solid fa-circle-notch fa-spin me-1"></i>Menunggu sesi selesai</span>
                                        @endif

                                        @if(in_array($booking->status, ['completed']))
                                            <button type="button" class="btn btn-outline-sporty btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal-{{ $booking->id }}">
                                                <i class="fa-solid fa-star"></i> Ulasan
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- Review Modal -->
                            <div class="modal fade" id="reviewModal-{{ $booking->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content glass-card border-0 text-adaptive bg-adaptive">
                                        <div class="modal-header border-bottom border-secondary border-opacity-25">
                                            <h5 class="modal-title display-font fw-bold">Ulasan & Rating Lapangan</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('dashboard.bookings.review', $booking->id) }}" method="POST" x-data="{ rating: 5 }">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="mb-3 text-center">
                                                    <label class="form-label text-secondary small d-block mb-3">Berikan Rating Lapangan</label>
                                                    <div class="d-flex justify-content-center gap-3 fs-3 text-warning">
                                                        <button type="button" class="btn btn-link p-0 text-warning text-decoration-none shadow-none" style="outline: none;" @click="rating = 1">
                                                            <i :class="rating >= 1 ? 'fa-solid fa-star' : 'fa-regular fa-star'"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-link p-0 text-warning text-decoration-none shadow-none" style="outline: none;" @click="rating = 2">
                                                            <i :class="rating >= 2 ? 'fa-solid fa-star' : 'fa-regular fa-star'"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-link p-0 text-warning text-decoration-none shadow-none" style="outline: none;" @click="rating = 3">
                                                            <i :class="rating >= 3 ? 'fa-solid fa-star' : 'fa-regular fa-star'"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-link p-0 text-warning text-decoration-none shadow-none" style="outline: none;" @click="rating = 4">
                                                            <i :class="rating >= 4 ? 'fa-solid fa-star' : 'fa-regular fa-star'"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-link p-0 text-warning text-decoration-none shadow-none" style="outline: none;" @click="rating = 5">
                                                            <i :class="rating >= 5 ? 'fa-solid fa-star' : 'fa-regular fa-star'"></i>
                                                        </button>
                                                    </div>
                                                    <div class="mt-2 text-center text-accent fw-bold" x-text="rating + ' Bintang'"></div>
                                                    <input type="hidden" name="rating" :value="rating">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="comment" class="form-label text-secondary small">Komentar / Ulasan Anda</label>
                                                    <textarea class="form-control form-control-sporty" name="comment" rows="3" placeholder="Bagikan pengalaman bermain Anda di lapangan padel ini..." required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top border-secondary border-opacity-25">
                                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Tutup</button>
                                                <button type="submit" class="btn btn-sporty">Kirim Ulasan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-secondary small">
                                    <i class="fa-solid fa-folder-open fs-2 mb-3 d-block text-muted"></i>
                                    Anda belum memiliki riwayat sewa lapangan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {!! $bookings->links('pagination::bootstrap-5') !!}
            </div>
        </div>
    </div>
</div>
@endsection
