@extends('layouts.app')

@section('title', 'Kondisi Keuangan — Monevia')

@section('content')
<div class="page-head fade-up">
    <div class="page-head-row">
        <div>
            <h2>Kondisi Keuangan</h2>
            <p>Masukkan data keuangan untuk mendapatkan rekomendasi dari Money Coach Monevia.</p>
        </div>
        <div class="page-head-actions">
            <a href="{{ route('setup-anggaran.index') }}" class="btn btn-secondary">Atur Anggaran Terpadu</a>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert-danger fade-up">
        <strong>Periksa input berikut:</strong>
        <ul class="alert-list">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card fade-up-delay-1">
    <h3 class="section-title"><span class="icon">📝</span> Data keuangan kamu</h3>

    <form action="{{ url('/keuangan/simpan') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="jumlah_uang">💰 Berapa uang yang kamu punya?</label>
            <input type="number" id="jumlah_uang" name="jumlah_uang"
                   value="{{ old('jumlah_uang', $keuangan->jumlah_uang ?? '') }}"
                   placeholder="Contoh: 1000000" step="1000" min="0" class="form-control">
            @error('jumlah_uang')
                <span class="text-danger">{{ $message }}</span>
            @else
                <small class="help">Total uang yang kamu miliki saat ini (tabungan + uang saku + dll)</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="pengeluaran_wajib">🏠 Pengeluaran Wajib</label>
            <input type="number" id="pengeluaran_wajib" name="pengeluaran_wajib"
                   value="{{ old('pengeluaran_wajib', $keuangan->pengeluaran_wajib ?? '') }}"
                   placeholder="Contoh: 500000" step="1000" min="0" class="form-control">
            @error('pengeluaran_wajib')
                <span class="text-danger">{{ $message }}</span>
            @else
                <small class="help">Biaya tetap bulanan: kos, transport, listrik, internet, dll</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="target_tabungan">🎯 Target Tabungan</label>
            <input type="number" id="target_tabungan" name="target_tabungan"
                   value="{{ old('target_tabungan', $keuangan->target_tabungan ?? '') }}"
                   placeholder="Contoh: 300000" step="1000" min="0" class="form-control">
            @error('target_tabungan')
                <span class="text-danger">{{ $message }}</span>
            @else
                <small class="help">Berapa yang ingin kamu tabungkan setiap bulan</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="uang_disisihkan">💼 Uang yang sudah disisihkan</label>
            <input type="number" id="uang_disisihkan" name="uang_disisihkan"
                   value="{{ old('uang_disisihkan', $keuangan->uang_disisihkan ?? 0) }}"
                   placeholder="Contoh: 0" step="1000" min="0" class="form-control">
            @error('uang_disisihkan')
                <span class="text-danger">{{ $message }}</span>
            @else
                <small class="help">Uang yang dikunci dan tidak bisa dibelanjakan. Target Tabungan tidak termasuk di sini.</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="budget_hiburan">🎮 Budget Hiburan</label>
            <input type="number" id="budget_hiburan" name="budget_hiburan"
                   value="{{ old('budget_hiburan', $keuangan->budget_hiburan ?? '') }}"
                   placeholder="Contoh: 200000" step="1000" min="0" class="form-control">
            @error('budget_hiburan')
                <span class="text-danger">{{ $message }}</span>
            @else
                <small class="help">Uang untuk hiburan, nongkrong, belanja non-kebutuhan, dll</small>
            @enderror
        </div>

        <!-- RINGKASAN OTOMATIS -->
        <div class="panel-cta is-plain">
            <div class="flex-1">
                <strong>📊 Ringkasan otomatis</strong>
                <small class="help">Total alokasi <span id="totalAlokasi">Rp 0</span></small>
                <br>
                <small class="help">Uang disisihkan <span id="totalDisisihkan">Rp 0</span></small>
            </div>
            <div style="text-align: right;">
                <small class="help">Uang tersedia</small><br>
                <strong id="sisaUang" class="text-green">Rp 0</strong>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Analisis Keuanganku</button>
            <a href="{{ url('/dashboard') }}" class="btn btn-secondary">Kembali ke Dashboard</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var watched = ['jumlah_uang', 'pengeluaran_wajib', 'target_tabungan', 'budget_hiburan', 'uang_disisihkan'];

    function formatRupiah(angka) {
        return 'Rp ' + Number(angka || 0).toLocaleString('id-ID');
    }

    function updateRingkasan() {
        var nilai = function (id) { return parseFloat(document.getElementById(id).value) || 0; };

        var total = nilai('pengeluaran_wajib') + nilai('target_tabungan') + nilai('budget_hiburan');
        var disisihkan = nilai('uang_disisihkan');
        var sisa = nilai('jumlah_uang') - total - disisihkan;
        var sisaEl = document.getElementById('sisaUang');

        document.getElementById('totalAlokasi').textContent = formatRupiah(total);
        document.getElementById('totalDisisihkan').textContent = formatRupiah(disisihkan);
        sisaEl.textContent = (sisa < 0 ? '- ' : '') + formatRupiah(Math.abs(sisa));
        sisaEl.classList.toggle('text-green', sisa >= 0);
        sisaEl.classList.toggle('text-red', sisa < 0);
    }

    watched.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { el.addEventListener('input', updateRingkasan); }
    });

    updateRingkasan();
})();
</script>
@endpush
