import { UserFinance, Transaction, SavingsGoal } from '../types/finance';

export const initialFinanceData: UserFinance = {
  totalMoney: 1500000,
  mandatoryExpenses: 600000,
  savingsTarget: 400000,
  entertainmentBudget: 300000,
};

export const initialTransactions: Transaction[] = [
  {
    id: 'tx-1',
    nama: 'Uang Saku Bulanan',
    jumlah: 1500000,
    jenis: 'pemasukan',
    kategori: 'Tabungan',
    tanggal: '2026-09-01',
    keterangan: 'Pemasukan awal bulan dari orang tua',
  },
  {
    id: 'tx-2',
    nama: 'Makan Nasi Goreng',
    jumlah: 25000,
    jenis: 'pengeluaran',
    kategori: 'Makanan',
    tanggal: '2026-09-05',
    keterangan: 'Makan malam kantin',
  },
  {
    id: 'tx-3',
    nama: 'Beli Buku Catatan Sekolah',
    jumlah: 35000,
    jenis: 'pengeluaran',
    kategori: 'Pendidikan',
    tanggal: '2026-09-07',
    keterangan: 'Perlengkapan modul pelajaran',
  },
  {
    id: 'tx-4',
    nama: 'Nonton Bioskop Akhir Pekan',
    jumlah: 50000,
    jenis: 'pengeluaran',
    kategori: 'Hiburan',
    tanggal: '2026-09-10',
    keterangan: 'Hiburan weekend bersama teman',
  },
];

export const initialSavingsGoals: SavingsGoal[] = [
  {
    id: 'goal-1',
    namaTarget: 'Beli Laptop Baru',
    jumlahTarget: 8000000,
    tabunganAwal: 2500000,
    targetTanggal: '2026-12-31',
  },
  {
    id: 'goal-2',
    namaTarget: 'Dana Darurat Santri/Siswa',
    jumlahTarget: 2000000,
    tabunganAwal: 1200000,
    targetTanggal: '2026-10-15',
  },
];

