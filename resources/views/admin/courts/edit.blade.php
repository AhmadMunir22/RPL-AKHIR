@extends('layouts.admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="glass-card p-4 p-md-5 border-0">
            <h3 class="text-white fw-bold display-font mb-4"><i class="fa-solid fa-pen-to-square text-success me-2"></i> Edit Detail Lapangan Padel</h3>

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
            <form action="{{ route('admin.courts.update', $court->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Nama Lapangan --}}
                <div class="mb-3">
                    <label for="name" class="form-label text-secondary small">Nama Lapangan</label>
                    <input type="text" class="form-control form-control-sporty" id="name" name="name"
                           value="{{ old('name', $court->name) }}" required>
                </div>

                {{-- Tipe Lapangan --}}
                <div class="mb-3">
                    <label for="type" class="form-label text-secondary small">Tipe Lapangan</label>
                    <select class="form-select form-control-sporty" id="type" name="type" required>
                        <option value="Indoor"  {{ old('type', $court->type) === 'Indoor'  ? 'selected' : '' }}>Indoor (Dalam Ruangan)</option>
                        <option value="Outdoor" {{ old('type', $court->type) === 'Outdoor' ? 'selected' : '' }}>Outdoor (Luar Ruangan)</option>
                    </select>
                </div>

                {{-- Harga Per Jam --}}
                <div class="mb-3">
                    <label for="price_per_hour" class="form-label text-secondary small">Harga Sewa Per Jam (Rp)</label>
                    <input type="number" class="form-control form-control-sporty" id="price_per_hour"
                           name="price_per_hour" min="0" step="5000"
                           value="{{ old('price_per_hour', $court->price_per_hour) }}" required>
                </div>

                {{-- Status Lapangan --}}
                <div class="mb-3">
                    <label for="status" class="form-label text-secondary small">Status Lapangan</label>
                    <select class="form-select form-control-sporty" id="status" name="status" required>
                        <option value="active"      {{ old('status', $court->status) === 'active'      ? 'selected' : '' }}>Active (Aktif &amp; Bisa Disewa)</option>
                        <option value="maintenance" {{ old('status', $court->status) === 'maintenance' ? 'selected' : '' }}>Maintenance (Sedang Pemeliharaan)</option>
                    </select>
                </div>

                {{-- Deskripsi --}}
                <div class="mb-4">
                    <label for="description" class="form-label text-secondary small">Deskripsi Lapangan</label>
                    <textarea class="form-control form-control-sporty" id="description" name="description" rows="4">{{ old('description', $court->description) }}</textarea>
                </div>

                {{-- ===== FOTO EXISTING ===== --}}
                @php $existingPhotos = $court->photos ?? []; @endphp
                @if(!empty($existingPhotos))
                <div class="mb-4">
                    <label class="form-label text-secondary small">
                        <i class="fa-solid fa-images me-1"></i> Foto Lapangan Saat Ini
                    </label>
                    <p class="text-muted" style="font-size:.78rem;">Centang foto yang ingin <span class="text-danger fw-semibold">dihapus</span>, lalu klik Simpan.</p>
                    <div class="d-flex flex-wrap gap-3" id="existingPhotosGrid">
                        @foreach($existingPhotos as $photoPath)
                        <div class="photo-item position-relative" style="width:110px;">
                            <img src="{{ \App\Models\Court::toPublicPhotoUrl($photoPath) }}"
                                 alt="Foto Lapangan"
                                 style="width:110px; height:110px; object-fit:cover; border-radius:10px; border:2px solid #444;">

                            {{-- Overlay tanda centang hapus --}}
                            <label class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
                                   style="cursor:pointer; border-radius:10px; background:transparent; transition:.2s;"
                                   title="Centang untuk hapus foto ini">
                                <input type="checkbox"
                                       name="photo_remove[]"
                                       value="{{ $photoPath }}"
                                       class="photo-remove-cb d-none"
                                       onchange="togglePhotoMark(this)">
                                <span class="delete-badge d-none position-absolute top-0 start-0 w-100 h-100 rounded"
                                      style="background:rgba(220,38,38,.6); border-radius:10px!important;"></span>
                                <i class="fa-solid fa-trash-can fa-lg text-danger photo-trash-icon"></i>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                {{-- ===== END FOTO EXISTING ===== --}}

                {{-- ===== UPLOAD FOTO BARU ===== --}}
                <div class="mb-4">
                    <label class="form-label text-secondary small">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Tambah Foto Baru
                        <span class="text-muted" style="font-size:.78rem;">(Opsional – maks. 3 MB/foto, JPG/PNG/WebP)</span>
                    </label>

                    <div id="photoDropArea"
                         class="rounded-3 border border-secondary text-center p-4"
                         style="cursor:pointer; background:rgba(255,255,255,.04); border-style:dashed!important; transition:.2s;"
                         onclick="document.getElementById('photoInput').click()"
                         ondragover="event.preventDefault(); this.style.background='rgba(34,197,94,.08)'"
                         ondragleave="this.style.background='rgba(255,255,255,.04)'"
                         ondrop="handleDrop(event)">
                        <i class="fa-solid fa-cloud-arrow-up fa-2x text-success mb-2"></i>
                        <p class="text-secondary mb-0" style="font-size:.9rem;">
                            Klik atau seret foto ke sini untuk menambah foto baru
                        </p>
                    </div>

                    <input type="file" id="photoInput" name="photos[]"
                           multiple accept="image/jpg,image/jpeg,image/png,image/webp"
                           class="d-none" onchange="previewPhotos(this.files)">

                    <div id="photoPreviewGrid" class="d-flex flex-wrap gap-2 mt-3"></div>
                </div>
                {{-- ===== END UPLOAD FOTO BARU ===== --}}

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

@section('scripts')
<script>
    let allFiles = new DataTransfer();

    // Toggle overlay merah di atas foto existing yang akan dihapus
    function togglePhotoMark(checkbox) {
        const item  = checkbox.closest('.photo-item');
        const badge = item.querySelector('.delete-badge');
        const icon  = item.querySelector('.photo-trash-icon');
        if (checkbox.checked) {
            badge.classList.remove('d-none');
            icon.style.color = '#fff';
            item.querySelector('img').style.opacity = '.4';
        } else {
            badge.classList.add('d-none');
            icon.style.color = '';
            item.querySelector('img').style.opacity = '1';
        }
    }

    function handleDrop(event) {
        event.preventDefault();
        document.getElementById('photoDropArea').style.background = 'rgba(255,255,255,.04)';
        previewPhotos(event.dataTransfer.files);
    }

    function previewPhotos(files) {
        const input = document.getElementById('photoInput');
        const grid  = document.getElementById('photoPreviewGrid');

        for (const file of files) allFiles.items.add(file);
        input.files = allFiles.files;

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

                const del = document.createElement('button');
                del.type      = 'button';
                del.innerHTML = '&times;';
                del.title     = 'Batalkan foto ini';
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
        previewPhotos([]);
    }
</script>
@endsection
@endsection
