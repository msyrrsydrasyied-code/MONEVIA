@extends('layouts.app')

@section('title', 'Tambah Target Tabungan — Monevia')

@section('content')
<div class="page-head fade-up">
    <h2>Tambah target tabungan</h2>
    <p>Tulis dulu targetnya, nanti isi saldonya pelan-pelan.</p>
</div>

@if($errors->any())
    <div class="alert-danger fade-up">
        <strong>Ada yang perlu diperbaiki:</strong>
        <ul class="alert-list">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card form-narrow fade-up-delay-1">
    <form action="{{ url('/target-tabungan/simpan') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="nama_target">Nama target</label>
            <input type="text" id="nama_target" name="nama_target" value="{{ old('nama_target') }}"
                   placeholder="Contoh: Beli laptop" required>
            <small class="help">Boleh satu atau beberapa kata, misalnya "Beli laptop kuliah".</small>
            @error('nama_target')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="jumlah_target">Jumlah target (Rp)</label>
            <input type="number" id="jumlah_target" name="jumlah_target" value="{{ old('jumlah_target') }}"
                   placeholder="Contoh: 8000000" step="1000" min="1" required>
            <small class="help">Total nominal yang ingin kamu kumpulkan.</small>
            @error('jumlah_target')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="tabungan_saat_ini">Sudah terkumpul (Rp) <span class="help">— opsional</span></label>
            <input type="number" id="tabungan_saat_ini" name="tabungan_saat_ini" value="{{ old('tabungan_saat_ini', 0) }}"
                   placeholder="Contoh: 500000" step="1000" min="0">
            <small class="help">Kalau sudah ada uang yang ditabung untuk target ini.</small>
            @error('tabungan_saat_ini')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="target_tanggal">Tanggal target <span class="help">— opsional</span></label>
            <input type="date" id="target_tanggal" name="target_tanggal" value="{{ old('target_tanggal') }}">
            <small class="help">Kapan kamu ingin mencapainya. Kosongkan kalau belum ada rencana.</small>
            @error('target_tanggal')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="row-item is-highlight mt-18" id="progressPreview" hidden>
            <div class="flex-1">
                <span class="row-label">Pratinjau progres</span>
                <div class="progress-bar-bg progress-bar-sm">
                    <div class="progress-bar-fill is-blue" id="previewProgressBar" style="width:0%;"></div>
                </div>
                <small class="help" id="previewSisa">Sisa: Rp 0</small>
            </div>
            <div class="row-tail text-blue" style="font-size:16px;" id="previewProgress">0%</div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan target</button>
            <a href="{{ url('/target-tabungan') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var jumlah = document.getElementById('jumlah_target');
    var terkumpul = document.getElementById('tabungan_saat_ini');
    var preview = document.getElementById('progressPreview');
    var bar = document.getElementById('previewProgressBar');
    var labelPersen = document.getElementById('previewProgress');
    var labelSisa = document.getElementById('previewSisa');

    function rupiah(angka) {
        return 'Rp ' + Number(angka || 0).toLocaleString('id-ID');
    }

    function segarkan() {
        var target = parseFloat(jumlah.value) || 0;
        var ada = parseFloat(terkumpul.value) || 0;

        if (target <= 0) {
            preview.hidden = true;
            return;
        }

        preview.hidden = false;
        var persen = Math.min(100, Math.round(ada / target * 100));
        bar.style.width = persen + '%';
        labelPersen.textContent = persen + '%';
        labelSisa.textContent = 'Sisa: ' + rupiah(Math.max(0, target - ada));
    }

    jumlah.addEventListener('input', segarkan);
    terkumpul.addEventListener('input', segarkan);
    segarkan();
})();
</script>
@endpush
