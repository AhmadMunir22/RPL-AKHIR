@extends('layouts.admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="glass-card p-4 p-md-5 border-0">
            <h3 class="text-white fw-bold display-font mb-4"><i class="fa-solid fa-square-plus text-success me-2"></i> Tambah Lapangan Padel Baru</h3>

            {{-- Tampilkan error validasi --}}
            @if($errors->any())
                <div class="alert alert-danger rounded-3 mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Form harus pakai enctype multipart agar bisa upload file foto --}}
            <form action="{{ route('admin.courts.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Nama Lapangan --}}
                <div class="mb-3">
                    <label for="name" class="form-label text-secondary small">Nama Lapangan</label>
                    <input type="text" class="form-control form-control-sporty" id="name" name="name"
                           value="{{ old('name') }}" placeholder="Lapangan 1 - Center Court" required>
                </div>

                {{-- Tipe Lapangan --}}
                <div class="mb-3">
                    <label for="type" class="form-label text-secondary small">Tipe Lapangan</label>
                    <select class="form-select form-control-sporty" id="type" name="type" required>
                        <option value="Indoor"  {{ old('type') === 'Indoor'  ? 'selected' : '' }}>Indoor (Dalam Ruangan)</option>
                        <option value="Outdoor" {{ old('type') === 'Outdoor' ? 'selected' : '' }}>Outdoor (Luar Ruangan)</option>
                    </select>
                </div>

                {{-- Harga Per Jam --}}
                <div class="mb-3">
                    <label for="price_per_hour" class="form-label text-secondary small">Harga Sewa Per Jam (Rp)</label>
                    <input type="number" class="form-control form-control-sporty" id="price_per_hour"
                           name="price_per_hour" min="0" step="5000"
                           value="{{ old('price_per_hour') }}" placeholder="150000" required>
                </div>

                {{-- Deskripsi --}}
                <div class="mb-4">
                    <label for="description" class="form-label text-secondary small">Deskripsi Lapangan</label>
                    <textarea class="form-control form-control-sporty" id="description" name="description"
                              rows="4" placeholder="Detail spesifikasi lapangan padel, merk matras, lampu penerangan LED, dll...">{{ old('description') }}</textarea>
                </div>

                {{-- ===== FOTO LAPANGAN ===== --}}
                <div class="mb-4">
                    <label class="form-label text-secondary small">
                        <i class="fa-solid fa-images me-1"></i> Foto Lapangan
                        <span class="text-muted" style="font-size:.78rem;">(Opsional – maks. 3 MB/foto, JPG/PNG/WebP)</span>
                    </label>

                    {{-- Area drop zone kustom --}}
                    <div id="photoDropArea"
                         class="rounded-3 border border-secondary border-dashed text-center p-4"
                         style="cursor:pointer; background:rgba(255,255,255,.04); border-style:dashed!important; transition:.2s;"
                         onclick="document.getElementById('photoInput').click()"
                         ondragover="event.preventDefault(); this.style.background='rgba(34,197,94,.08)'"
                         ondragleave="this.style.background='rgba(255,255,255,.04)'"
                         ondrop="handleDrop(event)">
                        <i class="fa-solid fa-cloud-arrow-up fa-2x text-success mb-2"></i>
                        <p class="text-secondary mb-0" style="font-size:.9rem;">
                            Klik atau seret foto ke sini untuk diunggah
                        </p>
                    </div>

                    {{-- Input file tersembunyi (multiple) --}}
                    <input type="file" id="photoInput" name="photos[]"
                           multiple accept="image/jpg,image/jpeg,image/png,image/webp"
                           class="d-none" onchange="previewPhotos(this.files)">

                    {{-- Grid preview thumbnail --}}
                    <div id="photoPreviewGrid" class="d-flex flex-wrap gap-2 mt-3"></div>
                </div>
                {{-- ===== END FOTO LAPANGAN ===== --}}

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

{{-- Script preview foto sebelum upload --}}
@section('scripts')
<script>
    // Kumpulkan semua file yang dipilih (termasuk via drag & drop) ke dalam satu DataTransfer
    let allFiles = new DataTransfer();

    function handleDrop(event) {
        event.preventDefault();
        document.getElementById('photoDropArea').style.background = 'rgba(255,255,255,.04)';
        previewPhotos(event.dataTransfer.files);
    }

    function previewPhotos(files) {
        const input   = document.getElementById('photoInput');
        const grid    = document.getElementById('photoPreviewGrid');

        // Tambahkan file baru ke koleksi existing
        for (const file of files) {
            allFiles.items.add(file);
        }
        input.files = allFiles.files;

        // Refresh tampilan grid preview
        grid.innerHTML = '';
        for (let i = 0; i < allFiles.files.length; i++) {
            const file   = allFiles.files[i];
            const reader = new FileReader();
            const index  = i;

            reader.onload = (e) => {
                const wrap = document.createElement('div');
                wrap.style.cssText = 'position:relative; width:100px; height:100px;';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = 'width:100px; height:100px; object-fit:cover; border-radius:8px; border:2px solid #333;';

                // Tombol × untuk batal pilih foto ini
                const del = document.createElement('button');
                del.type      = 'button';
                del.innerHTML = '&times;';
                del.title     = 'Hapus foto ini';
                del.style.cssText = 'position:absolute; top:4px; right:4px; background:rgba(220,38,38,.85); border:none; color:#fff; border-radius:50%; width:22px; height:22px; font-size:14px; line-height:1; cursor:pointer; padding:0;';
                del.onclick = () => removePreviewFile(index);

                wrap.appendChild(img);
                wrap.appendChild(del);
                grid.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        }
    }

    function removePreviewFile(index) {
        const newData = new DataTransfer();
        for (let i = 0; i < allFiles.files.length; i++) {
            if (i !== index) newData.items.add(allFiles.files[i]);
        }
        allFiles = newData;
        document.getElementById('photoInput').files = allFiles.files;
        // Refresh preview
        previewPhotos([]);
    }
</script>
@endsection
@endsection
