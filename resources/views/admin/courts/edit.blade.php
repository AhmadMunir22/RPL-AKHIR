@extends('layouts.admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="glass-card p-4 p-md-5 border-0">
            <h3 class="text-white fw-bold display-font mb-4"><i class="fa-solid fa-pen-to-square text-success me-2"></i> Edit Detail Lapangan Padel</h3>

            <form action="{{ route('admin.courts.update', $court->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-3">
                    <label for="name" class="form-label text-secondary small">Nama Lapangan</label>
                    <input type="text" class="form-control form-control-sporty" id="name" name="name" value="{{ old('name', $court->name) }}" required>
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label text-secondary small">Tipe Lapangan</label>
                    <select class="form-select form-control-sporty" id="type" name="type" required>
                        <option value="Indoor" {{ $court->type === 'Indoor' ? 'selected' : '' }}>Indoor (Dalam Ruangan)</option>
                        <option value="Outdoor" {{ $court->type === 'Outdoor' ? 'selected' : '' }}>Outdoor (Luar Ruangan)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="price_per_hour" class="form-label text-secondary small">Harga Sewa Per Jam (Rp)</label>
                    <input type="number" class="form-control form-control-sporty" id="price_per_hour" name="price_per_hour" value="{{ old('price_per_hour', $court->price_per_hour) }}" required>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label text-secondary small">Status Lapangan</label>
                    <select class="form-select form-control-sporty" id="status" name="status" required>
                        <option value="active" {{ $court->status === 'active' ? 'selected' : '' }}>Active (Aktif & Bisa Disewa)</option>
                        <option value="maintenance" {{ $court->status === 'maintenance' ? 'selected' : '' }}>Maintenance (Sedang Pemeliharaan)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label text-secondary small">Deskripsi Lapangan</label>
                    <textarea class="form-control form-control-sporty" id="description" name="description" rows="4" required>{{ old('description', $court->description) }}</textarea>
                </div>

                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-sporty w-100 py-3">
                        <i class="fa-solid fa-circle-check me-2"></i> Perbarui Detail Lapangan
                    </button>
                    <a href="{{ route('admin.index') }}" class="btn btn-outline-danger w-100 py-3">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
