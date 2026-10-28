import React, { useState } from 'react';
import { UserFinance } from '../types/finance';
import { formatRupiah, parseNumberInput } from '../utils/currency';
import { Card } from '../components/Card/Card';
import { Input } from '../components/Input/Input';
import { Button } from '../components/Button/Button';

interface FinanceInputPageProps {
  initialFinance: UserFinance;
  onSave: (finance: UserFinance) => void;
  onNavigate: (tab: string) => void;
}

export const FinanceInputPage: React.FC<FinanceInputPageProps> = ({
  initialFinance,
  onSave,
  onNavigate,
}) => {
  const [totalMoney, setTotalMoney] = useState<number>(initialFinance.totalMoney);
  const [mandatoryExpenses, setMandatoryExpenses] = useState<number>(initialFinance.mandatoryExpenses);
  const [savingsTarget, setSavingsTarget] = useState<number>(initialFinance.savingsTarget);
  const [entertainmentBudget, setEntertainmentBudget] = useState<number>(initialFinance.entertainmentBudget);

  const [errors, setErrors] = useState<Record<string, string>>({});

  const validate = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (isNaN(totalMoney) || totalMoney <= 0) {
      newErrors.totalMoney = 'Jumlah uang yang dimiliki wajib diisi dan harus lebih besar dari 0.';
    } else if (totalMoney < 0) {
      newErrors.totalMoney = 'Jumlah uang tidak boleh bernilai negatif.';
    }

    if (mandatoryExpenses < 0) {
      newErrors.mandatoryExpenses = 'Pengeluaran wajib tidak boleh bernilai negatif.';
    }

    if (savingsTarget < 0) {
      newErrors.savingsTarget = 'Target tabungan tidak boleh bernilai negatif.';
    }

    if (entertainmentBudget < 0) {
      newErrors.entertainmentBudget = 'Uang hiburan tidak boleh bernilai negatif.';
    }

    const totalAllocated = mandatoryExpenses + savingsTarget + entertainmentBudget;
    if (totalMoney > 0 && totalAllocated > totalMoney) {
      newErrors.totalAllocated = `Total pengeluaran & alokasi (${formatRupiah(totalAllocated)}) melebihi uang yang kamu miliki (${formatRupiah(totalMoney)}).`;
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (validate()) {
      const updatedFinance: UserFinance = {
        totalMoney,
        mandatoryExpenses,
        savingsTarget,
        entertainmentBudget,
      };
      onSave(updatedFinance);
      onNavigate('recommendation');
    }
  };

  return (
    <div className="max-w-2xl mx-auto space-y-6 py-2">
      <div>
        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
          Input Kondisi Keuangan
        </h1>
        <p className="text-sm text-gray-500 mt-1">
          Masukkan kondisi uang saku, gaji, dan rencana pengeluaranmu. Monevia akan menghitung pembagian terbaik untukmu.
        </p>
      </div>

      <Card title="📝 Form Data Keuangan">
        <form onSubmit={handleSubmit} className="space-y-5">
          {/* Total Uang */}
          <Input
            label="1. Total Uang Yang Dimiliki / Sisa Saldo saat ini (Rp)"
            type="text"
            prefixText="Rp"
            value={totalMoney ? totalMoney.toLocaleString('id-ID') : ''}
            onChange={(e) => setTotalMoney(parseNumberInput(e.target.value))}
            placeholder="Contoh: 1.000.000"
            helperText={`Format Tampilan: ${formatRupiah(totalMoney)}`}
            error={errors.totalMoney}
          />

          {/* Pengeluaran Wajib */}
          <Input
            label="2. Pengeluaran Wajib Per Bulan (Makan Pokok, Transport, SPP, Kost/Asrama)"
            type="text"
            prefixText="Rp"
            value={mandatoryExpenses ? mandatoryExpenses.toLocaleString('id-ID') : ''}
            onChange={(e) => setMandatoryExpenses(parseNumberInput(e.target.value))}
            placeholder="Contoh: 500.000"
            helperText={`Format Tampilan: ${formatRupiah(mandatoryExpenses)}`}
            error={errors.mandatoryExpenses}
          />

          {/* Target Tabungan */}
          <Input
            label="3. Target Tabungan Yang Ingin Disimpan Per Bulan"
            type="text"
            prefixText="Rp"
            value={savingsTarget ? savingsTarget.toLocaleString('id-ID') : ''}
            onChange={(e) => setSavingsTarget(parseNumberInput(e.target.value))}
            placeholder="Contoh: 300.000"
            helperText={`Format Tampilan: ${formatRupiah(savingsTarget)}`}
            error={errors.savingsTarget}
          />

          {/* Uang Hiburan/Fun */}
          <Input
            label="4. Uang Alokasi Hiburan / Jajan / Fun (Bebas)"
            type="text"
            prefixText="Rp"
            value={entertainmentBudget ? entertainmentBudget.toLocaleString('id-ID') : ''}
            onChange={(e) => setEntertainmentBudget(parseNumberInput(e.target.value))}
            placeholder="Contoh: 200.000"
            helperText={`Format Tampilan: ${formatRupiah(entertainmentBudget)}`}
            error={errors.entertainmentBudget}
          />

          {/* Error Total Alokasi Banner */}
          {errors.totalAllocated && (
            <div className="p-3.5 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold leading-relaxed">
              ⚠️ {errors.totalAllocated}
            </div>
          )}

          {/* Tombol Aksi */}
          <div className="pt-2 flex flex-col sm:flex-row gap-3">
            <Button type="submit" size="lg" className="w-full sm:flex-1">
              💡 Hitung & Lihat Rekomendasi
            </Button>
            <Button
              type="button"
              variant="outline"
              size="lg"
              onClick={() => {
                setTotalMoney(1000000);
                setMandatoryExpenses(500000);
                setSavingsTarget(300000);
                setEntertainmentBudget(200000);
                setErrors({});
              }}
            >
              🔄 Reset Contoh
            </Button>
          </div>
        </form>
      </Card>
    </div>
  );
};

