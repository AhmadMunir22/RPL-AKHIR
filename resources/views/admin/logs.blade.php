@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="glass-card p-4 border-0">
            <h3 class="text-white fw-bold display-font mb-4"><i class="fa-solid fa-history text-success me-2"></i> Log Aktivitas Audit Sistem</h3>
            <p class="text-secondary small mb-4">Daftar rekaman perubahan data lapangan, blokir slot, voucher promo, dan transaksi penting lainnya untuk keamanan internal.</p>

            <div class="table-responsive">
                <table class="table table-dark table-sporty align-middle">
                    <thead>
                        <tr>
                            <th>Waktu Aktivitas</th>
                            <th>Pelaku Audit</th>
                            <th>Keterangan Deskripsi</th>
                            <th>IP Client</th>
                        </tr>
                    </thead>
                    <tbody id="logs-table-body">
                        @include('admin.partials.logs_table')
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4" id="pagination-container">
                @if(is_object($logs) && method_exists($logs, 'links'))
                    {!! $logs->links('pagination::bootstrap-5') !!}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function pollLogsData() {
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page') || 1;

    axios.get('/admin/logs', { params: { ajax: 1, page: page } })
        .then(response => {
            document.getElementById('logs-table-body').innerHTML = response.data.tableHtml;
            document.getElementById('pagination-container').innerHTML = response.data.paginationHtml;
        })
        .catch(error => {
            console.error('Error polling logs:', error);
        });
}

// Poll every 5 seconds
setInterval(pollLogsData, 5000);
</script>
@endsection
