@extends('layouts.admin')

@section('content')
<div class="row">
    <!-- Block Slot scheduling panel -->
    <div class="col-lg-4 mb-4">
        <div class="glass-card p-4 border-0">
            <h4 class="text-white mb-4 fw-bold display-font"><i class="fa-solid fa-calendar-minus text-danger me-2"></i> Blokir Slot Waktu</h4>

            <form action="{{ route('admin.blocked-slots.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="court_id" class="form-label text-secondary small">Pilih Lapangan</label>
                    <select class="form-select form-control-sporty" id="court_id" name="court_id" required>
                        @foreach($courts as $court)
                            <option value="{{ $court->id }}">{{ $court->name }} ({{ $court->type }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="date" class="form-label text-secondary small">Pilih Tanggal</label>
                    <input type="date" class="form-control form-control-sporty" id="date" name="date" required>
                </div>

                <!-- Select hours to block checkboxes -->
                <div class="mb-3">
                    <label class="form-label text-secondary small d-block mb-2">Pilih Jam Blokir</label>
                    <div class="row g-2" style="max-height: 200px; overflow-y: auto;">
                        @foreach($slots as $slot)
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input bg-dark border-secondary" type="checkbox" name="slots[]" value="{{ $slot }}" id="slot-{{ $slot }}">
                                    <label class="form-check-label text-white small" for="slot-{{ $slot }}">{{ $slot }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-4">
                    <label for="reason" class="form-label text-secondary small">Alasan Pemblokiran</label>
                    <input type="text" class="form-control form-control-sporty" id="reason" name="reason" placeholder="Contoh: Pemeliharaan jaring court" required>
                </div>

                <button type="submit" class="btn btn-sporty w-100 py-3">
                    <i class="fa-solid fa-lock me-2"></i> Blokir Jam Pilihan
                </button>
            </form>
        </div>
    </div>

    <!-- Active blocks list -->
    <div class="col-lg-8">
        <div class="glass-card p-4 border-0">
            <h4 class="text-white mb-4 fw-bold display-font"><i class="fa-solid fa-list-check me-2 text-success"></i> Slot Terblokir Saat Ini</h4>

            <div class="table-responsive">
                <table class="table table-dark table-sporty align-middle">
                    <thead>
                        <tr>
                            <th>Lapangan</th>
                            <th>Tanggal</th>
                            <th>Jam Diblokir</th>
                            <th>Alasan / Deskripsi</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody id="blocked-slots-table-body">
                        @include('admin.partials.blocked_slots_table')
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function pollBlockedSlotsData() {
    axios.get('/admin/blocked-slots', { params: { ajax: 1 } })
        .then(response => {
            document.getElementById('blocked-slots-table-body').innerHTML = response.data.tableHtml;
        })
        .catch(error => {
            console.error('Error polling blocked slots:', error);
        });
}

// Poll every 5 seconds
setInterval(pollBlockedSlotsData, 5000);
</script>
@endsection
