import React, { useState } from 'react';
import { SavingsGoal } from '../types/finance';
import { formatRupiah, parseNumberInput } from '../utils/currency';
import { Card } from '../components/Card/Card';
import { Input } from '../components/Input/Input';
import { Button } from '../components/Button/Button';
import { ProgressBar } from '../components/ProgressBar/ProgressBar';

interface SavingsGoalPageProps {
  goals: SavingsGoal[];
  onAddGoal: (goal: SavingsGoal) => void;
  onUpdateGoal: (id: string, addedAmount: number) => void;
  onDeleteGoal: (id: string) => void;
}

export const SavingsGoalPage: React.FC<SavingsGoalPageProps> = ({
  goals,
  onAddGoal,
  onUpdateGoal,
  onDeleteGoal,
}) => {
  const [namaTarget, setNamaTarget] = useState('');
  const [jumlahTarget, setJumlahTarget] = useState<number>(0);
  const [tabunganAwal, setTabunganAwal] = useState<number>(0);
  const [targetTanggal, setTargetTanggal] = useState('');

  const [showAddForm, setShowAddForm] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!namaTarget.trim()) {
      setError('Nama target tabungan wajib diisi.');
      return;
    }
    if (jumlahTarget <= 0) {
      setError('Jumlah target tabungan harus lebih dari 0.');
      return;
    }

    const newGoal: SavingsGoal = {
      id: `goal-${Date.now()}`,
      namaTarget,
      jumlahTarget,
      tabunganAwal: tabunganAwal || 0,
      targetTanggal: targetTanggal || new Date().toISOString().split('T')[0],
    };

    onAddGoal(newGoal);
    setNamaTarget('');
    setJumlahTarget(0);
    setTabunganAwal(0);
    setTargetTanggal('');
    setError('');
    setShowAddForm(false);
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6 py-2">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
            Target Tabungan Impian
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            Tetapkan impian keuanganmu dan pantau persentase pencapaiannya secara teratur.
          </p>
        </div>
        <Button onClick={() => setShowAddForm(!showAddForm)}>
          {showAddForm ? '❌ Batal' : '🎯 + Buat Target Baru'}
        </Button>
      </div>

      {/* Form Tambah Target Baru (Collapsible) */}
      {showAddForm && (
        <Card title="🎯 Buat Target Tabungan Baru" className="border-2 border-emerald-200">
          <form onSubmit={handleSubmit} className="space-y-4">
            <Input
              label="Nama Target Impian"
              value={namaTarget}
              onChange={(e) => setNamaTarget(e.target.value)}
              placeholder="Contoh: Beli Laptop Baru / Dana KKN / Modal Usaha"
            />

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Input
                label="Jumlah Target Biaya (Rp)"
                prefixText="Rp"
                value={jumlahTarget ? jumlahTarget.toLocaleString('id-ID') : ''}
                onChange={(e) => setJumlahTarget(parseNumberInput(e.target.value))}
                placeholder="Contoh: 8.000.000"
                helperText={formatRupiah(jumlahTarget)}
              />

              <Input
                label="Jumlah Tabungan Awal (Rp)"
                prefixText="Rp"
                value={tabunganAwal ? tabunganAwal.toLocaleString('id-ID') : ''}
                onChange={(e) => setTabunganAwal(parseNumberInput(e.target.value))}
                placeholder="Contoh: 1.000.000"
                helperText={formatRupiah(tabunganAwal)}
              />
            </div>

            <Input
              label="Target Tanggal Pencapaian"
              type="date"
              value={targetTanggal}
              onChange={(e) => setTargetTanggal(e.target.value)}
            />

            {error && <p className="text-xs text-red-500 font-bold">{error}</p>}

            <div className="pt-2 flex gap-3">
              <Button type="submit" className="flex-1">Simpan Target Impian</Button>
              <Button type="button" variant="outline" onClick={() => setShowAddForm(false)}>Batal</Button>
            </div>
          </form>
        </Card>
      )}

      {/* Daftar Target Tabungan */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {goals.map((goal) => {
          const progress = Math.min(100, Math.round((goal.tabunganAwal / goal.jumlahTarget) * 100));
          const sisaTarget = Math.max(0, goal.jumlahTarget - goal.tabunganAwal);
          const isAchieved = progress >= 100;

          return (
            <Card key={goal.id} className="flex flex-col justify-between space-y-4">
              <div>
                <div className="flex justify-between items-start">
                  <div>
                    <span className="text-xs font-semibold text-gray-400 block">Target Impian</span>
                    <h3 className="text-lg font-extrabold text-gray-900">{goal.namaTarget}</h3>
                  </div>
                  <span className={`text-xs px-2.5 py-1 rounded-full font-bold ${
                    isAchieved ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-50 text-blue-700'
                  }`}>
                    {isAchieved ? '🎉 Tercapai!' : `${progress}%`}
                  </span>
                </div>

                <div className="mt-4 space-y-2">
                  <div className="flex justify-between text-xs font-semibold">
                    <span className="text-gray-500">Terkumpul:</span>
                    <span className="text-emerald-600 font-bold">{formatRupiah(goal.tabunganAwal)}</span>
                  </div>
                  <ProgressBar percentage={progress} color={isAchieved ? 'emerald' : 'blue'} height="h-3" />
                  <div className="flex justify-between text-xs text-gray-500">
                    <span>Target: {formatRupiah(goal.jumlahTarget)}</span>
                    <span>Sisa: {formatRupiah(sisaTarget)}</span>
                  </div>
                </div>

                {goal.targetTanggal && (
                  <p className="text-[11px] text-gray-400 mt-3 flex items-center gap-1">
                    <span>🗓️ Target Tanggal:</span>
                    <span className="font-semibold text-gray-600">{goal.targetTanggal}</span>
                  </p>
                )}
              </div>

              {/* Action Buttons */}
              <div className="pt-2 border-t border-gray-100 flex items-center justify-between gap-2">
                <button
                  onClick={() => {
                    const addStr = prompt('Masukkan nominal tabungan yang ingin ditambahkan (Rp):', '50000');
                    if (addStr) {
                      const addVal = parseNumberInput(addStr);
                      if (addVal > 0) onUpdateGoal(goal.id, addVal);
                    }
                  }}
                  className="text-xs font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-lg hover:bg-emerald-100 transition cursor-pointer"
                >
                  + Tambah Tabungan
                </button>

                <button
                  onClick={() => {
                    if (confirm(`Hapus target "${goal.namaTarget}"?`)) {
                      onDeleteGoal(goal.id);
                    }
                  }}
                  className="text-xs text-gray-400 hover:text-red-500 p-1.5 rounded-lg transition cursor-pointer"
                  title="Hapus Target"
                >
                  🗑️ Hapus
                </button>
              </div>
            </Card>
          );
        })}
      </div>

      {goals.length === 0 && (
        <Card className="text-center py-12">
          <span className="text-4xl block mb-2">🎯</span>
          <h3 className="text-base font-bold text-gray-800">Belum ada target tabungan</h3>
          <p className="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
            Mulai buat target impianmu seperti membeli peralatan sekolah, laptop, atau tabungan kurban.
          </p>
          <Button className="mt-4" onClick={() => setShowAddForm(true)}>
            + Buat Target Sekarang
          </Button>
        </Card>
      )}
    </div>
  );
};

