import React, { useState } from 'react';
import { Transaction, TransactionType, CategoryType } from '../types/finance';
import { formatRupiah, parseNumberInput } from '../utils/currency';
import { Card } from '../components/Card/Card';
import { Input } from '../components/Input/Input';
import { Button } from '../components/Button/Button';
import { TransactionItem } from '../components/TransactionItem/TransactionItem';

interface TransactionsPageProps {
  transactions: Transaction[];
  onAddTransaction: (transaction: Transaction) => void;
  onDeleteTransaction: (id: string) => void;
}

export const TransactionsPage: React.FC<TransactionsPageProps> = ({
  transactions,
  onAddTransaction,
  onDeleteTransaction,
}) => {
  const [nama, setNama] = useState('');
  const [jumlah, setJumlah] = useState<number>(0);
  const [jenis, setJenis] = useState<TransactionType>('pengeluaran');
  const [kategori, setKategori] = useState<CategoryType>('Makanan');
  const [tanggal, setTanggal] = useState(new Date().toISOString().split('T')[0]);
  const [keterangan, setKeterangan] = useState('');

  const [showForm, setShowForm] = useState(false);
  const [error, setError] = useState('');

  const categoriesList: CategoryType[] = [
    'Makanan',
    'Transportasi',
    'Pendidikan',
    'Hiburan',
    'Belanja',
    'Tabungan',
    'Lainnya',
  ];

  // Hitung statistik transaksi
  const totalPemasukan = transactions
    .filter((t) => t.jenis === 'pemasukan')
    .reduce((sum, t) => sum + t.jumlah, 0);

  const totalPengeluaran = transactions
    .filter((t) => t.jenis === 'pengeluaran')
    .reduce((sum, t) => sum + t.jumlah, 0);

  const saldo = totalPemasukan - totalPengeluaran;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!nama.trim()) {
      setError('Nama transaksi wajib diisi.');
      return;
    }
    if (jumlah <= 0) {
      setError('Nominal transaksi harus lebih besar dari 0.');
      return;
    }

    const newTx: Transaction = {
      id: `tx-${Date.now()}`,
      nama,
      jumlah,
      jenis,
      kategori,
      tanggal,
      keterangan,
    };

    onAddTransaction(newTx);

    // Reset form
    setNama('');
    setJumlah(0);
    setKeterangan('');
    setError('');
    setShowForm(false);
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6 py-2">
      {/* Header & Action */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
            Catatan & Riwayat Transaksi
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            Catat semua pemasukan dan pengeluaran harianmu untuk menjaga saldo tetap terkontrol.
          </p>
        </div>
        <Button onClick={() => setShowForm(!showForm)}>
          {showForm ? '❌ Batal' : '💸 + Tambah Transaksi'}
        </Button>
      </div>

      {/* Financial Overview Metric Header */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {/* Total Pemasukan */}
        <div className="bg-emerald-50 rounded-2xl p-4 border border-emerald-100">
          <span className="text-xs font-semibold uppercase tracking-wider text-emerald-700 block">
            Total Pemasukan
          </span>
          <span className="text-xl font-extrabold text-emerald-800 block mt-1">
            + {formatRupiah(totalPemasukan)}
          </span>
        </div>

        {/* Total Pengeluaran */}
        <div className="bg-red-50 rounded-2xl p-4 border border-red-100">
          <span className="text-xs font-semibold uppercase tracking-wider text-red-700 block">
            Total Pengeluaran
          </span>
          <span className="text-xl font-extrabold text-red-800 block mt-1">
            - {formatRupiah(totalPengeluaran)}
          </span>
        </div>

        {/* Saldo Kas */}
        <div className="bg-gray-900 text-white rounded-2xl p-4">
          <span className="text-xs font-semibold uppercase tracking-wider text-gray-400 block">
            Saldo Kas Saat Ini
          </span>
          <span className="text-xl font-extrabold text-white block mt-1">
            {formatRupiah(saldo)}
          </span>
        </div>
      </div>

      {/* Collapsible Form Tambah Transaksi */}
      {showForm && (
        <Card title="💸 Form Tambah Transaksi" className="border-2 border-emerald-200">
          <form onSubmit={handleSubmit} className="space-y-4">
            <Input
              label="Nama Transaksi"
              value={nama}
              onChange={(e) => setNama(e.target.value)}
              placeholder="Contoh: Makan Nasi Goreng / Uang Saku Ortu"
            />

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Input
                label="Nominal (Rp)"
                prefixText="Rp"
                value={jumlah ? jumlah.toLocaleString('id-ID') : ''}
                onChange={(e) => setJumlah(parseNumberInput(e.target.value))}
                placeholder="Contoh: 25.000"
                helperText={formatRupiah(jumlah)}
              />

              <div className="flex flex-col gap-1.5 w-full">
                <label className="text-sm font-semibold text-gray-800">Tipe Transaksi</label>
                <select
                  value={jenis}
                  onChange={(e) => setJenis(e.target.value as TransactionType)}
                  className="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm transition focus:border-emerald-500 focus:outline-hidden"
                >
                  <option value="pengeluaran">Pengeluaran (-)</option>
                  <option value="pemasukan">Pemasukan (+)</option>
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="flex flex-col gap-1.5 w-full">
                <label className="text-sm font-semibold text-gray-800">Kategori</label>
                <select
                  value={kategori}
                  onChange={(e) => setKategori(e.target.value as CategoryType)}
                  className="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm transition focus:border-emerald-500 focus:outline-hidden"
                >
                  {categoriesList.map((cat) => (
                    <option key={cat} value={cat}>
                      {cat}
                    </option>
                  ))}
                </select>
              </div>

              <Input
                label="Tanggal Transaksi"
                type="date"
                value={tanggal}
                onChange={(e) => setTanggal(e.target.value)}
              />
            </div>

            <Input
              label="Keterangan (Opsional)"
              value={keterangan}
              onChange={(e) => setKeterangan(e.target.value)}
              placeholder="Catatan tambahan..."
            />

            {error && <p className="text-xs text-red-500 font-bold">{error}</p>}

            <div className="pt-2 flex gap-3">
              <Button type="submit" className="flex-1">Simpan Transaksi</Button>
              <Button type="button" variant="outline" onClick={() => setShowForm(false)}>Batal</Button>
            </div>
          </form>
        </Card>
      )}

      {/* Riwayat Transaksi List */}
      <Card title="📜 Riwayat Transaksi">
        <div className="space-y-3">
          {transactions.map((tx) => (
            <TransactionItem
              key={tx.id}
              transaction={tx}
              onDelete={onDeleteTransaction}
            />
          ))}

          {transactions.length === 0 && (
            <div className="text-center py-8 text-gray-400 text-xs">
              Belum ada transaksi. Klik <strong>+ Tambah Transaksi</strong> di atas.
            </div>
          )}
        </div>
      </Card>
    </div>
  );
};

