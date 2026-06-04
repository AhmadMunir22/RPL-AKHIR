@extends('layouts.admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="glass-card p-4 p-md-5 border-0">
            <h3 class="text-white fw-bold display-font mb-4"><i class="fa-solid fa-square-plus text-success me-2"></i> Tambah Lapangan Padel Baru</h3>

            <form action="{{ route('admin.courts.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label text-secondary small">Nama Lapangan</label>
                    <input type="text" class="form-control form-control-sporty" id="name" name="name" placeholder="Lapangan 1 - Center Court" required>
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label text-secondary small">Tipe Lapangan</label>
                    <select class="form-select form-control-sporty" id="type" name="type" required>
                        <option value="Indoor">Indoor (Dalam Ruangan)</option>
                        <option value="Outdoor">Outdoor (Luar Ruangan)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="price_per_hour" class="form-label text-secondary small">Harga Sewa Per Jam (Rp)</label>
                    <input type="number" class="form-control form-control-sporty" id="price_per_hour" name="price_per_hour" min="0" step="5000" placeholder="150000" required>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label text-secondary small">Deskripsi Lapangan</label>
                    <textarea class="form-control form-control-sporty" id="description" name="description" rows="4" placeholder="Detail spesifikasi lapangan padel, merk matras, lampu penerangan LED, dll..."></textarea>
                </div>

                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-sporty w-100 py-3">
                        <i class="fa-solid fa-save me-2"></i> Simpan Lapangan
                    </button>
                    <a href="{{ route('admin.index') }}" class="btn btn-outline-danger w-100 py-3">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
