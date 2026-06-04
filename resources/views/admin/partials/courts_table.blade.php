@forelse($courts as $court)
<tr>
    <td style="font-weight:600;color:var(--text-primary);">{{ $court->name }}</td>
    <td>
        <span class="badge-sporty badge-terracotta">{{ $court->type }}</span>
    </td>
    <td style="font-family:var(--font-display);font-weight:700;color:var(--accent);">
        Rp {{ number_format($court->price_per_hour, 0, ',', '.') }}
    </td>
    <td>
        <span style="color:#fbbf24;font-weight:700;font-family:var(--font-display);">
            <i class="fa-solid fa-star" style="font-size:0.8rem;"></i>
            {{ number_format($court->rating_avg, 1) }}
        </span>
    </td>
    <td>
        <span class="badge-sporty {{ $court->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
            {{ strtoupper($court->status) }}
        </span>
    </td>
    <td>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.courts.edit', $court->id) }}" class="btn btn-ghost py-1 px-3" style="font-size:0.8rem;">
                <i class="fa-solid fa-edit"></i> Edit
            </a>
            <button class="btn btn-outline-sporty py-1 px-3" style="font-size:0.8rem;" onclick="triggerPhotoUpload({{ $court->id }})">
                <i class="fa-solid fa-images"></i>
            </button>
            <form action="{{ route('admin.courts.destroy', $court->id) }}" method="POST"
                  onsubmit="return confirm('Hapus lapangan {{ $court->name }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn py-1 px-3" style="font-size:0.8rem;background:rgba(248,113,113,0.10);color:#f87171;border:1px solid rgba(248,113,113,0.25);border-radius:8px;">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </form>
            <form id="photo-upload-form-{{ $court->id }}"
                  action="{{ route('admin.courts.photos', $court->id) }}"
                  method="POST" enctype="multipart/form-data" class="d-none">
                @csrf
                <input type="file" name="photos[]" multiple id="photo-input-{{ $court->id }}"
                       onchange="submitPhotoUpload({{ $court->id }})">
            </form>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6" class="text-center py-5" style="color:var(--text-muted);">
        <i class="fa-solid fa-table-tennis-paddle-ball mb-2" style="font-size:1.5rem;display:block;opacity:0.3;"></i>
        Belum ada lapangan terdaftar.
    </td>
</tr>
@endforelse
