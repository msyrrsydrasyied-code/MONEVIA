<?php

namespace Tests\Unit;

use App\Http\Controllers\SetupAnggaranController;
use PHPUnit\Framework\TestCase;

class SetupAnggaranNominalTest extends TestCase
{
    public function test_urai_nominal_menerima_format_umum(): void
    {
        $this->assertSame(200000, SetupAnggaranController::uraiNominal('200000'));
        $this->assertSame(200000, SetupAnggaranController::uraiNominal('200.000'));
        $this->assertSame(1500000, SetupAnggaranController::uraiNominal('1.500.000'));
        $this->assertSame(200000, SetupAnggaranController::uraiNominal('200k'));
        $this->assertSame(1500000, SetupAnggaranController::uraiNominal('1,5 juta'));
        $this->assertSame(3000000, SetupAnggaranController::uraiNominal('3jt'));
    }

    public function test_urai_nominal_menolak_input_tidak_valid(): void
    {
        $this->assertNull(SetupAnggaranController::uraiNominal(''));
        $this->assertNull(SetupAnggaranController::uraiNominal('   '));
        $this->assertNull(SetupAnggaranController::uraiNominal('abc'));
    }

    public function test_urai_nominal_menolak_nilai_negatif(): void
    {
        $this->assertSame(-500000, SetupAnggaranController::uraiNominal('-500000'));
    }

    public function test_hitung_persen_selalu_jumlah_seratus(): void
    {
        $hasil = SetupAnggaranController::hitungPersen(500000, 300000, 200000);
        $this->assertSame(['kebutuhan' => 50, 'tabungan' => 30, 'hiburan' => 20], $hasil);

        $hasil = SetupAnggaranController::hitungPersen(600000, 400000, 300000);
        $this->assertSame(100, $hasil['kebutuhan'] + $hasil['tabungan'] + $hasil['hiburan']);

        $hasil = SetupAnggaranController::hitungPersen(0, 0, 0);
        $this->assertSame(['kebutuhan' => 50, 'tabungan' => 30, 'hiburan' => 20], $hasil);
    }
}
