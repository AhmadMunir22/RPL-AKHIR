@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="glass-card p-4 border-0">
            <h3 class="text-white fw-bold display-font mb-4"><i class="fa-solid fa-ticket text-success me-2"></i> Kelola Reservasi Lapangan</h3>

            <div class="table-responsive">
                <table class="table table-dark table-sporty align-middle">
                    <thead>
                        <tr>
                            <th>Kode Tiket</th>
                            <th>Nama Member</th>
                            <th>Lapangan</th>
                            <th>Tanggal Main</th>
                            <th>Jam Sesi</th>
                            <th>Total Harga</th>
                            <th>Status Sesi</th>
                            <th>Pembayaran</th>
                            <th>Aksi Operasional</th>
                        </tr>
                    </thead>
                    <tbody id="bookings-table-body">
                        @include('admin.partials.bookings_table')
                    </tbody>
                </table>
            </div>

            <!-- Paginations -->
            <div class="d-flex justify-content-center mt-4" id="pagination-container">
                {!! $bookings->links('pagination::bootstrap-5') !!}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function pollBookingsData() {
    const activeEl = document.activeElement;
    const isInteractingInTable = activeEl && document.getElementById('bookings-table-body').contains(activeEl);
    
    if (isInteractingInTable) return;
    
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page') || 1;

    axios.get('/admin/bookings', { params: { ajax: 1, page: page } })
        .then(response => {
            document.getElementById('bookings-table-body').innerHTML = response.data.tableHtml;
            document.getElementById('pagination-container').innerHTML = response.data.paginationHtml;
        })
        .catch(error => {
            console.error('Error polling bookings:', error);
        });
}

// Poll every 5 seconds
setInterval(pollBookingsData, 5000);
</script>
@endsection
