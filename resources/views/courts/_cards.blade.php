@forelse($courts as $court)
<div class="col-md-6 mb-4">
    <div class="court-card-wrap" style="position:relative;">
        <div class="court-thumb">
            @if($court->primary_photo)
                <img src="{{ $court->primary_photo }}" alt="{{ $court->name }}">
            @else
                <div class="court-thumb-placeholder d-flex align-items-center justify-content-center"
                     style="background:linear-gradient(135deg,rgba(224,122,95,0.12),rgba(26,45,82,0.60));">
                    <i class="fa-solid fa-table-tennis-paddle-ball" style="font-size:3.5rem;color:var(--accent);opacity:0.4;"></i>
                </div>
            @endif
            <!-- Type Badge -->
            <div style="position:absolute;top:12px;right:12px;padding:4px 12px;background:rgba(11,19,41,0.85);backdrop-filter:blur(8px);border:1px solid var(--border-highlight);border-radius:20px;font-family:var(--font-display);font-size:0.7rem;font-weight:700;color:var(--accent);letter-spacing:0.06em;text-transform:uppercase;">
                <i class="fa-solid fa-{{ $court->type === 'Indoor' ? 'house' : 'sun' }} me-1"></i>{{ $court->type }}
            </div>
            <!-- Status -->
            <div style="position:absolute;top:12px;left:12px;padding:4px 10px;background:rgba(74,222,128,0.12);backdrop-filter:blur(8px);border:1px solid rgba(74,222,128,0.25);border-radius:20px;font-size:0.68rem;font-weight:700;font-family:var(--font-display);color:#4ade80;text-transform:uppercase;letter-spacing:0.05em;">
                <i class="fa-solid fa-circle" style="font-size:0.4rem;"></i> Tersedia
            </div>
        </div>

        <div class="p-4">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 style="font-family:var(--font-display);font-weight:700;color:var(--text-primary);margin:0;line-height:1.3;flex:1;">{{ $court->name }}</h5>
                <div class="d-flex align-items-center gap-1 ms-2 flex-shrink-0">
                    <i class="fa-solid fa-star" style="color:#fbbf24;font-size:0.8rem;"></i>
                    <span style="font-family:var(--font-display);font-weight:700;font-size:0.88rem;color:var(--text-primary);">{{ number_format($court->rating_avg, 1) }}</span>
                </div>
            </div>

            <p style="color:var(--text-muted);font-size:0.84rem;line-height:1.6;margin-bottom:16px;">
                {{ Str::limit($court->description, 90) }}
            </p>

            <div class="divider"></div>

            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:2px;">Mulai dari</div>
                    <div style="font-family:var(--font-display);font-size:1.2rem;font-weight:800;color:var(--accent);">
                        Rp {{ number_format($court->price_per_hour, 0, ',', '.') }}<span style="font-size:0.72rem;font-weight:500;color:var(--text-muted);">/jam</span>
                    </div>
                </div>
                <a href="{{ route('courts.show', $court->id) }}" class="btn btn-sporty">
                    <i class="fa-solid fa-calendar-check" style="font-size:0.85rem;"></i> Sewa
                </a>
            </div>
        </div>
    </div>
</div>
@empty
<div class="col-12">
    <div class="text-center py-5">
        <div style="width:80px;height:80px;background:var(--accent-subtle);border:1.5px solid var(--border-highlight);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <i class="fa-solid fa-magnifying-glass" style="font-size:2rem;color:var(--accent);"></i>
        </div>
        <h5 style="font-family:var(--font-display);color:var(--text-secondary);">Tidak ada lapangan ditemukan</h5>
        <p style="color:var(--text-muted);font-size:0.9rem;">Coba ubah kata kunci pencarian atau filter Anda.</p>
    </div>
</div>
@endforelse

@if($courts->hasPages())
<div class="col-12 mt-2">
    <nav>
        <ul class="pagination pagination-sporty justify-content-center flex-wrap gap-1">
            {{-- Previous --}}
            @if($courts->onFirstPage())
                <li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-left"></i></span></li>
            @else
                <li class="page-item"><a class="page-link" href="{{ $courts->previousPageUrl() }}"><i class="fa-solid fa-chevron-left"></i></a></li>
            @endif

            @foreach($courts->getUrlRange(1, $courts->lastPage()) as $page => $url)
                <li class="page-item {{ $page == $courts->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </li>
            @endforeach

            {{-- Next --}}
            @if($courts->hasMorePages())
                <li class="page-item"><a class="page-link" href="{{ $courts->nextPageUrl() }}"><i class="fa-solid fa-chevron-right"></i></a></li>
            @else
                <li class="page-item disabled"><span class="page-link"><i class="fa-solid fa-chevron-right"></i></span></li>
            @endif
        </ul>
    </nav>
</div>
@endif
