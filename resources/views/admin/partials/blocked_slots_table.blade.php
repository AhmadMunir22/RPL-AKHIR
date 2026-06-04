@forelse($blockedSlots as $block)
    <tr>
        <td class="fw-bold text-white">{{ $block->court->name }}</td>
        <td>{{ $block->date->format('d M Y') }}</td>
        <td>
            @foreach($block->slots as $slot)
                <span class="badge bg-danger text-white small me-1 mb-1">{{ $slot }}</span>
            @endforeach
        </td>
        <td>{{ $block->reason }}</td>
        <td>
            <form action="{{ route('admin.blocked-slots.destroy', $block->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membuka pemblokiran jam lapangan ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-lock-open"></i> Buka</button>
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="text-center py-4 text-secondary small">Belum ada jam lapangan yang diblokir saat ini.</td>
    </tr>
@endforelse
