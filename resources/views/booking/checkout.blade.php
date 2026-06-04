@extends('layouts.app')

@section('title', 'Checkout Reservasi - PadelBook')

@section('content')
<div class="row" x-data="checkoutWizard()">
    <!-- Booking Specifications Summary -->
    <div class="col-lg-7 mb-4">
        <div class="glass-card p-4 border-0 mb-4">
            <h3 class="fw-bold display-font mb-4"><i class="fa-solid fa-receipt text-success me-2"></i> Rincian Reservasi</h3>
            
            <div class="d-flex align-items-center gap-4 mb-4">
                <div class="bg-success bg-opacity-25 rounded border border-success p-3 text-center" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-table-tennis-paddle-ball text-success fs-2 mt-1"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-1">{{ $court->name }}</h4>
                    <span class="badge bg-secondary-subtle text-adaptive border border-secondary px-2 py-1 small">{{ $court->type }}</span>
                </div>
            </div>

            <table class="table table-borderless" style="--bs-table-bg: transparent;">
                <tbody>
                    <tr class="border-bottom border-secondary border-opacity-25">
                        <td class="text-secondary ps-0 py-3">Tanggal Main</td>
                        <td class="text-adaptive text-end fw-semibold py-3">{{ \Carbon\Carbon::parse($date)->format('d F Y') }}</td>
                    </tr>
                    <tr class="border-bottom border-secondary border-opacity-25">
                        <td class="text-secondary ps-0 py-3">Sesi Jam Pilihan</td>
                        <td class="text-adaptive text-end fw-semibold py-3">
                            @foreach($slots as $slot)
                                <span class="badge bg-success text-dark px-2 py-1 small">{{ $slot }}</span>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <td class="text-secondary ps-0 py-3">Harga Per Jam</td>
                        <td class="text-success text-end fw-bold py-3">Rp {{ number_format($court->price_per_hour, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Extra notes card -->
        <div class="glass-card p-4 border-0">
            <h4 class="mb-3 fw-bold display-font"><i class="fa-solid fa-note-sticky text-success me-2"></i> Catatan Tambahan</h4>
            <textarea form="checkout-form" name="notes" class="form-control form-control-sporty" rows="3" placeholder="Contoh: Butuh sewa raket tambahan (opsional)..."></textarea>
        </div>
    </div>

    <!-- Payments Summary Panel -->
    <div class="col-lg-5">
        <div class="glass-card p-4 border-0 mb-4">
            <h3 class="fw-bold display-font mb-4"><i class="fa-solid fa-wallet text-success me-2"></i> Pembayaran</h3>

            <!-- Voucher Box -->
            <div class="mb-4">
                <label class="form-label text-secondary small">Punya Kode Voucher?</label>
                <div class="input-group" x-show="!voucherApplied">
                    <input type="text" class="form-control form-control-sporty" x-model="voucherCode" placeholder="Masukkan kode promo" :disabled="voucherLoading">
                    <button class="btn btn-sporty px-3" type="button" @click="checkVoucher()" :disabled="voucherLoading">
                        <span x-show="!voucherLoading">Gunakan</span>
                        <span x-show="voucherLoading"><i class="fa-solid fa-spinner fa-spin"></i></span>
                    </button>
                </div>
                <div x-show="voucherApplied" class="d-flex align-items-center justify-content-between mt-2 p-2 bg-success bg-opacity-10 rounded border border-success border-opacity-25">
                    <span class="text-success small"><i class="fa-solid fa-circle-check me-1"></i> Voucher <strong x-text="voucherCodeApplied"></strong> berhasil diterapkan!</span>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" @click="removeVoucher()" title="Hapus voucher"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>

            <!-- Cost Breakdown -->
            <div class="p-3 bg-adaptive rounded-3 border border-secondary border-opacity-25 mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-secondary small">Subtotal</span>
                    <span class="text-adaptive fw-bold">Rp <span x-text="formatCurrency(subtotal)"></span></span>
                </div>
                <div class="d-flex justify-content-between mb-2" x-show="discount > 0" style="display: none;">
                    <span class="text-secondary small">Potongan Promo</span>
                    <span class="text-danger fw-bold">- Rp <span x-text="formatCurrency(discount)"></span></span>
                </div>
                <hr class="border-secondary my-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-adaptive fw-bold">Total Pembayaran</span>
                    <span class="text-success fw-bold fs-4">Rp <span x-text="formatCurrency(totalPrice)"></span></span>
                </div>
            </div>

            <!-- Checkout Booking Confirmation Form -->
            <form id="checkout-form" action="{{ route('booking.reserve') }}" method="POST">
                @csrf
                <input type="hidden" name="court_id" value="{{ $court->id }}">
                <input type="hidden" name="date" value="{{ $date }}">
                @foreach($slots as $slot)
                    <input type="hidden" name="slots[]" value="{{ $slot }}">
                @endforeach
                <input type="hidden" name="voucher_code" :value="voucherCodeApplied">

                <button type="submit" class="btn btn-sporty w-100 py-3 mb-2">
                    <i class="fa-solid fa-lock me-2"></i>Konfirmasi Reservasi
                </button>
                <a href="{{ route('courts.show', $court->id) }}" class="btn btn-outline-danger w-100 py-3 mt-2">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function checkoutWizard() {
        return {
            subtotal: {{ $pricing['subtotal'] }},
            discount: {{ $pricing['discount'] }},
            totalPrice: {{ $pricing['total_price'] }},
            voucherCode: '',
            voucherCodeApplied: '',
            voucherApplied: false,
            voucherLoading: false,

            checkVoucher() {
                const code = this.voucherCode.trim();
                if (!code) return;

                this.voucherLoading = true;
                const self = this;

                axios.get('{{ route("booking.check-voucher") }}', {
                    params: {
                        court_id: {{ $court->id }},
                        slots: @json($slots),
                        code: code
                    }
                })
                .then(response => {
                    const data = response.data;
                    if (data.valid) {
                        self.discount          = data.discount;
                        self.totalPrice        = data.total;
                        self.voucherCodeApplied = code;
                        self.voucherApplied    = true;
                    }
                })
                .catch(error => {
                    const msg = error.response?.data?.message || 'Kode voucher tidak valid atau quota habis.';
                    alert(msg);
                    self.discount           = 0;
                    self.totalPrice         = self.subtotal;
                    self.voucherCodeApplied = '';
                    self.voucherApplied     = false;
                })
                .finally(() => {
                    self.voucherLoading = false;
                });
            },

            removeVoucher() {
                this.discount           = 0;
                this.totalPrice         = this.subtotal;
                this.voucherCode        = '';
                this.voucherCodeApplied = '';
                this.voucherApplied     = false;
            },

            formatCurrency(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            }
        }
    }
</script>
@endsection
