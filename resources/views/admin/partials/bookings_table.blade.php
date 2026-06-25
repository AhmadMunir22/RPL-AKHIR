@forelse($bookings as $booking)
    <tr>
        <td class="fw-bold text-success">{{ $booking->qr_code }}</td>
        <td class="text-white">{{ $booking->user->name }}</td>
        <td class="fw-semibold text-white">{{ $booking->court->name }}</td>
        <td>{{ $booking->date->format('d M Y') }}</td>
        <td>
            @foreach($booking->slots as $slot)
                <span class="badge bg-secondary-subtle text-white small">{{ $slot }}</span>
            @endforeach
        </td>
        <td>Rp {{ number_format($booking->total_price, 0, ',', '.') }}</td>
        <td>
            <!-- Status update quick dropdown form -->
            <form action="{{ route('admin.bookings.status', $booking->id) }}" method="POST" class="d-inline">
                @csrf
                <select name="status" onchange="this.form.submit()" class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-50 py-1" style="width: 120px;">
                    <option value="pending" {{ $booking->status === 'pending' ? 'selected' : '' }}>PENDING</option>
                    <option value="confirmed" {{ $booking->status === 'confirmed' ? 'selected' : '' }}>PROSES</option>
                    <option value="completed" {{ $booking->status === 'completed' ? 'selected' : '' }}>COMPLETE</option>
                    <option value="cancelled" {{ $booking->status === 'cancelled' ? 'selected' : '' }}>CANCELLED</option>
                </select>
            </form>
        </td>
        <td>
            @php
                $psBadgeColor = match($booking->payment_status) {
                    'paid' => 'success',
                    'partial' => 'warning',
                    'awaiting_approval' => 'info',
                    'failed' => 'danger',
                    'refunded' => 'secondary',
                    default => 'danger'
                };
                $psBadgeLabel = match($booking->payment_status) {
                    'paid' => 'LUNAS',
                    'partial' => 'DP TERBAYAR',
                    'awaiting_approval' => 'MENUNGGU PERSETUJUAN',
                    'failed' => 'GAGAL',
                    'refunded' => 'DIKEMBALIKAN',
                    default => 'BELUM BAYAR'
                };
            @endphp
            <span class="badge bg-{{ $psBadgeColor }}-subtle text-{{ $psBadgeColor }} border border-{{ $psBadgeColor }} px-2 py-1">
                {{ $psBadgeLabel }}
            </span>
        </td>
        <td>
            @if(in_array($booking->payment_status, ['paid', 'partial']))
                <form action="{{ route('admin.bookings.refund', $booking->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin melakukan refund saldo penuh untuk booking ini?')" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-undo"></i> Refund Wallet</button>
                </form>
            @else
                <span class="text-muted small">—</span>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center py-4 text-secondary small">Belum ada data reservasi masuk.</td>
    </tr>
@endforelse
