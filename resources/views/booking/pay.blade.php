@extends('layouts.app')

@section('title', 'Pilihan Pembayaran - PadelBook')

@section('styles')
<style>
.payment-method-card {
    background: var(--glass-bg);
    border: 2px solid var(--border-glass);
    border-radius: 16px;
    padding: 18px 14px;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s ease;
    user-select: none;
}
.payment-method-card:hover {
    border-color: var(--accent);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}
.payment-method-card.selected {
    border-color: var(--accent);
    background: var(--accent-subtle);
    box-shadow: 0 0 0 3px rgba(224,122,95,0.18);
}
.payment-method-card .pm-logo {
    font-size: 2rem;
    margin-bottom: 8px;
    display: block;
}
.payment-method-card .pm-name {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text-primary);
    font-family: var(--font-display);
    letter-spacing: 0.01em;
}
.payment-method-card .pm-badge {
    font-size: 0.65rem;
    color: var(--text-muted);
    margin-top: 2px;
}
.payment-method-card.selected .pm-name {
    color: var(--accent);
}
.pm-check {
    width: 20px; height: 20px;
    border-radius: 50%;
    background: var(--accent);
    display: flex; align-items: center; justify-content: center;
    margin: 6px auto 0;
    font-size: 0.7rem;
    color: #fff;
}
</style>
@endsection

@section('content')
<div class="row justify-content-center" x-data="paymentGateway()">
    <div class="col-lg-7">
        <div class="glass-card p-4 p-md-5 border-0">
            <h2 class="fw-bold display-font mb-1 text-center"><i class="fa-solid fa-shield-halved text-success me-2"></i> Pembayaran Aman</h2>
            <p class="text-secondary text-center small mb-4">Pilih metode pembayaran yang Anda inginkan</p>

            {{-- Booking Summary --}}
            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 mb-5"
                 style="background: var(--accent-subtle); border: 1px solid var(--border-highlight);">
                <div>
                    <div class="fw-bold" style="font-size:0.95rem;">{{ $booking->court->name }}</div>
                    <div class="text-secondary small">{{ $booking->date->format('d F Y') }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">Total Bayar</div>
                    <div class="fw-bold fs-5 text-success">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</div>
                </div>
            </div>

            {{-- Payment Methods Grid --}}
            <div class="row g-3 mb-4">

                {{-- QRIS --}}
                <div class="col-4 col-md-4">
                    <div class="payment-method-card" :class="{ selected: method === 'qris' }" @click="method = 'qris'">
                        <span class="pm-logo">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="40" height="40" rx="8" fill="#e8192c"/>
                                <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="9" font-weight="bold" font-family="Arial">QRIS</text>
                            </svg>
                        </span>
                        <div class="pm-name">QRIS</div>
                        <div class="pm-badge">Scan & Pay</div>
                        <div class="pm-check" x-show="method === 'qris'"><i class="fa-solid fa-check"></i></div>
                    </div>
                </div>

                {{-- GoPay --}}
                <div class="col-4 col-md-4">
                    <div class="payment-method-card" :class="{ selected: method === 'gopay' }" @click="method = 'gopay'">
                        <span class="pm-logo">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="40" height="40" rx="8" fill="#00aeef"/>
                                <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="8" font-weight="bold" font-family="Arial">GoPay</text>
                            </svg>
                        </span>
                        <div class="pm-name">GoPay</div>
                        <div class="pm-badge">E-Wallet</div>
                        <div class="pm-check" x-show="method === 'gopay'"><i class="fa-solid fa-check"></i></div>
                    </div>
                </div>

                {{-- DANA --}}
                <div class="col-4 col-md-4">
                    <div class="payment-method-card" :class="{ selected: method === 'dana' }" @click="method = 'dana'">
                        <span class="pm-logo">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="40" height="40" rx="8" fill="#108ee9"/>
                                <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="9" font-weight="bold" font-family="Arial">DANA</text>
                            </svg>
                        </span>
                        <div class="pm-name">DANA</div>
                        <div class="pm-badge">E-Wallet</div>
                        <div class="pm-check" x-show="method === 'dana'"><i class="fa-solid fa-check"></i></div>
                    </div>
                </div>

                {{-- ShopeePay --}}
                <div class="col-4 col-md-4">
                    <div class="payment-method-card" :class="{ selected: method === 'shopeepay' }" @click="method = 'shopeepay'">
                        <span class="pm-logo">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="40" height="40" rx="8" fill="#ee4d2d"/>
                                <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="6.5" font-weight="bold" font-family="Arial">ShopeePay</text>
                            </svg>
                        </span>
                        <div class="pm-name">ShopeePay</div>
                        <div class="pm-badge">E-Wallet</div>
                        <div class="pm-check" x-show="method === 'shopeepay'"><i class="fa-solid fa-check"></i></div>
                    </div>
                </div>

                {{-- Virtual Account --}}
                <div class="col-4 col-md-4">
                    <div class="payment-method-card" :class="{ selected: method === 'bank_transfer' }" @click="method = 'bank_transfer'">
                        <span class="pm-logo">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="40" height="40" rx="8" fill="#003f87"/>
                                <text x="50%" y="45%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="7" font-weight="bold" font-family="Arial">Virtual</text>
                                <text x="50%" y="68%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="7" font-weight="bold" font-family="Arial">Account</text>
                            </svg>
                        </span>
                        <div class="pm-name">Transfer Bank</div>
                        <div class="pm-badge">BCA, BNI, BRI, dll</div>
                        <div class="pm-check" x-show="method === 'bank_transfer'"><i class="fa-solid fa-check"></i></div>
                    </div>
                </div>

                {{-- Alfamart/Indomaret --}}
                <div class="col-4 col-md-4">
                    <div class="payment-method-card" :class="{ selected: method === 'cstore' }" @click="method = 'cstore'">
                        <span class="pm-logo">
                            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="40" height="40" rx="8" fill="#e31e24"/>
                                <text x="50%" y="42%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="6" font-weight="bold" font-family="Arial">Alfamart</text>
                                <text x="50%" y="66%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="6" font-weight="bold" font-family="Arial">Indomaret</text>
                            </svg>
                        </span>
                        <div class="pm-name">Minimarket</div>
                        <div class="pm-badge">Alfamart / Indomaret</div>
                        <div class="pm-check" x-show="method === 'cstore'"><i class="fa-solid fa-check"></i></div>
                    </div>
                </div>

            </div>

            {{-- Error: no method selected --}}
            <div x-show="showError" class="alert alert-danger py-2 small mb-3" x-cloak>
                <i class="fa-solid fa-circle-exclamation me-1"></i> Pilih metode pembayaran terlebih dahulu.
            </div>

            {{-- Pay Button --}}
            <button class="btn btn-sporty w-100 py-3 fs-6" :disabled="loading" @click="pay()">
                <span x-show="!loading"><i class="fa-solid fa-lock me-2"></i> Bayar Sekarang</span>
                <span x-show="loading"><i class="fa-solid fa-spinner fa-spin me-2"></i> Sedang Memproses...</span>
            </button>

            <p class="text-muted text-center small mt-3 mb-0">
                <i class="fa-solid fa-shield-halved me-1"></i> Pembayaran diproses aman
            </p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{{-- Load Midtrans Snap.js --}}
<script src="{{ config('services.midtrans.snap_url') }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
<script>
    function paymentGateway() {
        return {
            method: '',
            amountToPay: {{ $booking->total_price }},
            loading: false,
            showError: false,

            pay() {
                if (!this.method) {
                    this.showError = true;
                    return;
                }
                this.showError = false;
                this.loading = true;

                // Request Snap Token dari backend kita
                axios.post('{{ route("booking.pay-midtrans") }}', {
                    booking_id: {{ $booking->id }},
                    pay_amount: this.amountToPay,
                    payment_type: this.method
                })
                .then(response => {
                    if (response.data.success && response.data.snap_token) {
                        // Tampilkan popup Midtrans Snap
                        snap.pay(response.data.snap_token, {
                            // Callback saat transaksi berhasil (misal GoPay/QRIS lunas)
                            onSuccess: function(result) {
                                window.location.href = '{{ route("dashboard.bookings") }}';
                            },
                            // Callback saat transaksi pending (misal VA BNI sedang menunggu transfer)
                            onPending: function(result) {
                                window.location.href = '{{ route("dashboard.bookings") }}';
                            },
                            // Callback saat transaksi gagal
                            onError: function(result) {
                                alert('Pembayaran gagal atau ditolak. Silakan coba lagi.');
                                this.loading = false;
                            }.bind(this),
                            // Callback saat user menutup popup tanpa membayar
                            onClose: function() {
                                this.loading = false;
                            }.bind(this)
                        });
                    } else {
                        alert(response.data.message || 'Gagal memulai pembayaran dengan Midtrans.');
                        this.loading = false;
                    }
                })
                .catch(error => {
                    console.error('Midtrans error:', error);
                    alert('Terjadi kesalahan saat menghubungi server: ' + (error.response?.data?.message || error.message));
                    this.loading = false;
                });
            }
        }
    }
</script>
@endsection
