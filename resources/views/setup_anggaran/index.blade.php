@extends('layouts.app')

@section('title', 'Monevia - Atur Anggaran')

@section('content')
<div class="page-head fade-up">
    <h2>Atur Anggaranmu</h2>
    <p>Tiga langkah singkat: masukkan uangmu, pilih periode, lalu bagi sendiri. Monevia hitung sisanya.</p>
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

@if($sudahSetup)
    <div class="card active-budget fade-up">
        <div>
            <span class="badge badge-active">Anggaran Aktif</span>
            <p class="active-budget-text">
                Total uang <strong>Rp {{ number_format($jumlahUang, 0, ',', '.') }}</strong> dipakai
                {{ $periode }} hari sejak {{ $targetInfo['tanggal_mulai'] }} sampai {{ $targetInfo['tanggal_selesai'] }}.
                Batas harian saat ini: <strong>Rp {{ number_format($targetInfo['batas_harian'], 0, ',', '.') }}</strong>.
            </p>
        </div>
        <a href="{{ route('target-harian.index') }}" class="btn btn-secondary">Lihat Target Harian</a>
    </div>
@endif

<form action="{{ route('setup-anggaran.simpan') }}" method="POST" id="form-setup">
    @csrf

    <!-- Langkah 1: Jumlah uang -->
    <div class="card setup-step fade-up-delay-1">
        <div class="step-head">
            <span class="step-num">1</span>
            <div>
                <h3>Berapa uang yang kamu punya?</h3>
                <p class="help">Tulis angkanya saja, boleh pakai titik atau tulis 1,5 juta.</p>
            </div>
        </div>

        <div class="form-group">
            <label for="uang_disisihkan">💼 Uang yang sudah kamu sisihkan</label>
            <div class="nominal-input">
                <span class="nominal-prefix">Rp</span>
                <input type="text" inputmode="numeric" id="uang_disisihkan" name="uang_disisihkan"
                       value="{{ old('uang_disisihkan', $uangDisisihkan) }}"
                       placeholder="0" autocomplete="off">
            </div>
            <small class="help">Uang yang benar-benar dikunci dan tidak bisa dibelanjakan. Target Tabungan
                <strong>tidak</strong> termasuk di sini, karena target hanyalah tujuan.</small>
            @error('uang_disisihkan')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="jumlah_uang" class="sr-label">Jumlah uang</label>
            <div class="nominal-input">
                <span class="nominal-prefix">Rp</span>
                <input type="text" inputmode="numeric" id="jumlah_uang" name="jumlah_uang"
                       value="{{ old('jumlah_uang', $jumlahUang) }}"
                       placeholder="1.500.000" autocomplete="off">
            </div>
            <small class="help" id="bacaUang">Boleh juga tulis 200k atau 1,5 juta.</small>
            @error('jumlah_uang')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <!-- Langkah 2: Periode -->
    <div class="card setup-step fade-up-delay-2">
        <div class="step-head">
            <span class="step-num">2</span>
            <div>
                <h3>Uangnya mau dipakai berapa lama?</h3>
                <p class="help">Pilih yang paling mendekati rencanamu.</p>
            </div>
        </div>

        <div class="periode-grid">
            @foreach($periodeOpsi as $hari => $label)
                <label class="periode-card">
                    <input type="radio" name="periode_hari" value="{{ $hari }}"
                           {{ (int) old('periode_hari', $periode) === $hari ? 'checked' : '' }}>
                    <span class="periode-days">{{ $hari }}</span>
                    <span class="periode-label">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('periode_hari')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>

    <!-- Langkah 3: Pembagian anggaran -->
    <div class="card setup-step fade-up-delay-3">
        <div class="step-head">
            <span class="step-num">3</span>
            <div>
                <h3>Bagi uangmu sendiri</h3>
                <p class="help">Geser sesuai kebutuhanmu. Totalnya selalu dijaga 100%.</p>
            </div>
        </div>

        @php
            $daftarAlokasi = [
                'kebutuhan' => ['label' => 'Kebutuhan', 'help' => 'Makan, kos, transport, listrik, internet', 'warna' => 'var(--mv-green)', 'nilai' => $persen['kebutuhan']],
                'tabungan' => ['label' => 'Tabungan', 'help' => 'Disisihkan untuk cita-cita atau dana darurat', 'warna' => 'var(--mv-blue)', 'nilai' => $persen['tabungan']],
                'hiburan' => ['label' => 'Hiburan', 'help' => 'Jajan, nongkrong, hiburan', 'warna' => 'var(--mv-gold)', 'nilai' => $persen['hiburan']],
            ];
        @endphp

        <div class="alloc-list">
            @foreach($daftarAlokasi as $key => $item)
                <div class="alloc-item" data-alokasi="{{ $key }}">
                    <div class="alloc-head">
                        <div>
                            <strong>{{ $item['label'] }}</strong>
                            <small class="help">{{ $item['help'] }}</small>
                        </div>
                        <div class="alloc-figure">
                            <span class="alloc-percent" id="label_{{ $key }}">{{ (int) old('persen_' . $key, $item['nilai']) }}%</span>
                            <span class="alloc-rupiah" id="nominal_{{ $key }}">Rp 0</span>
                        </div>
                    </div>
                    <input type="range" class="alloc-range" id="range_{{ $key }}" min="0" max="100" step="5"
                           value="{{ (int) old('persen_' . $key, $item['nilai']) }}"
                           style="--mv-accent: {{ $item['warna'] }};"
                           aria-label="Persentase {{ $item['label'] }}">
                    <input type="hidden" name="persen_{{ $key }}" id="persen_{{ $key }}"
                           value="{{ (int) old('persen_' . $key, $item['nilai']) }}">
                </div>
            @endforeach
        </div>

        <div class="alloc-foot">
            <span class="total-pill" id="totalPersen">Total 100%</span>
            <button type="button" class="btn btn-secondary" id="btnSeimbangkan">Pakai 50 / 30 / 20</button>
        </div>
        @error('persen_kebutuhan')
            <span class="text-danger">{{ $message }}</span>
        @enderror
        @error('persen_tabungan')
            <span class="text-danger">{{ $message }}</span>
        @enderror
        @error('persen_hiburan')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>

    <!-- Ringkasan -->
    <div class="card summary-card fade-up-delay-4">
        <h3>Ringkasan Anggaranmu</h3>
        <div class="summary-list">
            <div class="summary-row">
                <span>Total uang</span>
                <strong id="sumTotal">Rp 0</strong>
            </div>
            <div class="summary-row">
                <span>Uang disisihkan</span>
                <strong id="sumDisisihkan">Rp 0</strong>
            </div>
            <div class="summary-row">
                <span>Uang tersedia untuk belanja</span>
                <strong id="sumTersedia">Rp 0</strong>
            </div>
            <div class="summary-row">
                <span>Periode anggaran</span>
                <strong id="sumPeriode">30 hari</strong>
            </div>
            <div class="summary-row">
                <span>Alokasi kebutuhan</span>
                <strong id="sumKebutuhan">Rp 0</strong>
            </div>
            <div class="summary-row">
                <span>Alokasi tabungan</span>
                <strong id="sumTabungan">Rp 0</strong>
            </div>
            <div class="summary-row">
                <span>Alokasi hiburan</span>
                <strong id="sumHiburan">Rp 0</strong>
            </div>
            <div class="summary-row summary-row-highlight">
                <span>Perkiraan batas pengeluaran harian</span>
                <strong id="sumHarian">Rp 0</strong>
            </div>
        </div>

        @if($tanggalMulaiBerjalan)
            <label class="reset-option">
                <input type="checkbox" name="mulai_ulang" value="1" {{ old('mulai_ulang') ? 'checked' : '' }}>
                <span>
                    <strong>Mulai periode baru hari ini</strong>
                    <small class="help">Tanpa centang, periode berjalan dilanjutkan dari tanggal {{ $tanggalMulaiBerjalan }}. Transaksi lamamu tetap aman dan tidak dihitung dua kali.</small>
                </span>
            </label>
        @endif

        <button type="submit" class="btn btn-primary btn-block" id="btnSimpan">Mulai Atur Uangku</button>
        <p class="help summary-note">Setelah disimpan kamu langsung diarahkan ke dashboard.</p>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('form-setup');
    if (!form) { return; }

    var KUNCI = ['kebutuhan', 'tabungan', 'hiburan'];

    var inputUang = document.getElementById('jumlah_uang');
    var inputDisisihkan = document.getElementById('uang_disisihkan');
    var bacaUang = document.getElementById('bacaUang');
    var totalPersenLabel = document.getElementById('totalPersen');
    var btnSeimbangkan = document.getElementById('btnSeimbangkan');

    var alokasi = {};
    KUNCI.forEach(function (key) {
        alokasi[key] = {
            tersembunyi: document.getElementById('persen_' + key),
            slider: document.getElementById('range_' + key),
            label: document.getElementById('label_' + key),
            nominal: document.getElementById('nominal_' + key)
        };
    });

    function rupiah(angka) {
        return 'Rp ' + Number(angka || 0).toLocaleString('id-ID');
    }

    function angkaPolat(angka) {
        return Number(angka || 0).toLocaleString('id-ID');
    }

    function uraiNominal(teks) {
        var nilai = String(teks || '').trim().toLowerCase();
        if (nilai === '' || !/[0-9]/.test(nilai)) { return 0; }

        var faktor = 1;
        if (nilai.indexOf('juta') !== -1 || nilai.indexOf('jt') !== -1) {
            faktor = 1000000;
        } else if (nilai.indexOf('ribu') !== -1 || nilai.indexOf('rb') !== -1 || /[0-9]\s*k$/.test(nilai)) {
            faktor = 1000;
        }

        var hasil;
        if (faktor === 1000000) {
            var desimal = nilai.replace(/[a-z]+/g, '').trim().replace(',', '.');
            hasil = Math.round(parseFloat(desimal) * 1000000);
        } else {
            var angka = nilai.replace(/[^0-9]/g, '');
            hasil = angka === '' ? 0 : parseInt(angka, 10) * faktor;
        }

        if (isNaN(hasil)) { return 0; }

        return String(teks).indexOf('-') !== -1 ? -Math.abs(hasil) : hasil;
    }

    function totalPersen() {
        return KUNCI.reduce(function (jumlah, key) {
            return jumlah + (parseInt(alokasi[key].tersembunyi.value, 10) || 0);
        }, 0);
    }

    function setPersen(key, nilaiBaru) {
        var dibatasi = Math.max(0, Math.min(100, Math.round(nilaiBaru)));
        alokasi[key].tersembunyi.value = dibatasi;
        alokasi[key].slider.value = dibatasi;

        var sisa = 100 - dibatasi;
        var lain = KUNCI.filter(function (k) { return k !== key; });
        var totalLain = lain.reduce(function (jumlah, k) {
            return jumlah + (parseInt(alokasi[k].tersembunyi.value, 10) || 0);
        }, 0);

        if (totalLain > 0) {
            var kalan = 100;
            lain.forEach(function (k, i) {
                var nilai;
                if (i === lain.length - 1) {
                    nilai = kalan;
                } else {
                    nilai = Math.round((parseInt(alokasi[k].tersembunyi.value, 10) || 0) / totalLain * sisa);
                    nilai = Math.max(0, Math.min(nilai, kalan));
                    kalan -= nilai;
                }
                alokasi[k].tersembunyi.value = nilai;
                alokasi[k].slider.value = nilai;
            });
        } else {
            var setengah = Math.round(sisa / 2);
            alokasi[lain[0]].tersembunyi.value = setengah;
            alokasi[lain[0]].slider.value = setengah;
            alokasi[lain[1]].tersembunyi.value = Math.max(0, sisa - setengah);
            alokasi[lain[1]].slider.value = Math.max(0, sisa - setengah);
        }
    }

    function periodeDipilih() {
        var dipilih = form.querySelector('input[name="periode_hari"]:checked');
        return dipilih ? parseInt(dipilih.value, 10) : 30;
    }

    function segarkan() {
        var total = Math.max(0, uraiNominal(inputUang.value));
        var kebutuhan = Math.round(total * (parseInt(alokasi.kebutuhan.tersembunyi.value, 10) || 0) / 100);
        var tabungan = Math.round(total * (parseInt(alokasi.tabungan.tersembunyi.value, 10) || 0) / 100);
        var hiburan = Math.max(0, total - kebutuhan - tabungan);
        var periode = periodeDipilih();

        KUNCI.forEach(function (key) {
            alokasi[key].label.textContent = (parseInt(alokasi[key].tersembunyi.value, 10) || 0) + '%';
        });

        alokasi.kebutuhan.nominal.textContent = rupiah(kebutuhan);
        alokasi.tabungan.nominal.textContent = rupiah(tabungan);
        alokasi.hiburan.nominal.textContent = rupiah(hiburan);

        var disisihkan = Math.min(Math.max(0, uraiNominal(inputDisisihkan ? inputDisisihkan.value : 0)), Math.max(0, total));
        var tersedia = total - disisihkan;

        document.getElementById('sumTotal').textContent = rupiah(total);
        document.getElementById('sumDisisihkan').textContent = rupiah(disisihkan);
        document.getElementById('sumTersedia').textContent = rupiah(tersedia);
        document.getElementById('sumKebutuhan').textContent = rupiah(kebutuhan);
        document.getElementById('sumTabungan').textContent = rupiah(tabungan);
        document.getElementById('sumHiburan').textContent = rupiah(hiburan);
        document.getElementById('sumHarian').textContent = rupiah(periode > 0 ? Math.round(total / periode) : 0);
        document.getElementById('sumPeriode').textContent = periode + ' hari';

        if (total > 0) {
            bacaUang.textContent = 'Terdeteksi: ' + rupiah(total);
        } else {
            bacaUang.textContent = 'Boleh juga tulis 200k atau 1,5 juta.';
        }

        var jumlahPersen = totalPersen();
        if (jumlahPersen === 100) {
            totalPersenLabel.textContent = 'Total 100%';
            totalPersenLabel.classList.remove('is-invalid');
            totalPersenLabel.classList.add('is-valid');
        } else {
            totalPersenLabel.textContent = 'Total ' + jumlahPersen + '%';
            totalPersenLabel.classList.remove('is-valid');
            totalPersenLabel.classList.add('is-invalid');
        }
    }

    KUNCI.forEach(function (key) {
        alokasi[key].slider.addEventListener('input', function () {
            setPersen(key, parseInt(alokasi[key].slider.value, 10) || 0);
            segarkan();
        });
    });

    btnSeimbangkan.addEventListener('click', function () {
        setPersen('kebutuhan', 50);
        setPersen('tabungan', 30);
        setPersen('hiburan', 20);
        segarkan();
    });

    inputUang.addEventListener('input', segarkan);
    if (inputDisisihkan) { inputDisisihkan.addEventListener('input', segarkan); }
    inputUang.addEventListener('blur', function () {
        var angka = inputUang.value.replace(/[^0-9]/g, '');
        if (angka !== '') {
            inputUang.value = angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        segarkan();
    });

    form.querySelectorAll('input[name="periode_hari"]').forEach(function (radio) {
        radio.addEventListener('change', segarkan);
    });

    form.addEventListener('submit', function () {
        var btn = document.getElementById('btnSimpan');
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';
    });

    segarkan();
})();
</script>
@endpush
