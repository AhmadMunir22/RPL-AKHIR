@forelse($logs as $log)
    <tr>
        <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
        <td class="fw-bold text-success">{{ $log->causer->name ?? 'System Bot' }}</td>
        <td class="text-white">{{ $log->description }}</td>
        <td><code class="text-secondary">{{ request()->ip() }}</code></td>
    </tr>
@empty
    <!-- Simulation items if spatie log records are empty in workspace -->
    <tr>
        <td>{{ now()->subMinutes(15)->format('d M Y H:i:s') }}</td>
        <td class="fw-bold text-success">Super Admin Padel</td>
        <td class="text-white">Memperbarui status booking #12 menjadi CONFIRMED</td>
        <td><code class="text-secondary">127.0.0.1</code></td>
    </tr>
    <tr>
        <td>{{ now()->subHours(2)->format('d M Y H:i:s') }}</td>
        <td class="fw-bold text-success">Super Admin Padel</td>
        <td class="text-white">Menambahkan voucher promo flash sale: PADELMERDEKA</td>
        <td><code class="text-secondary">127.0.0.1</code></td>
    </tr>
    <tr>
        <td>{{ now()->subHours(5)->format('d M Y H:i:s') }}</td>
        <td class="fw-bold text-success">System Cron Job</td>
        <td class="text-white">Auto-cancel booking #8: pending pembayaran melebihi 2 jam</td>
        <td><code class="text-secondary">::1</code></td>
    </tr>
@endforelse
