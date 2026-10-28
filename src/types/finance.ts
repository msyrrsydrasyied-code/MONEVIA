export type TransactionType = 'pemasukan' | 'pengeluaran';

export type CategoryType = 
  | 'Makanan'
  | 'Transportasi'
  | 'Pendidikan'
  | 'Hiburan'
  | 'Belanja'
  | 'Tabungan'
  | 'Lainnya';

export interface UserFinance {
  totalMoney: number;         // Jumlah uang yang dimiliki
  mandatoryExpenses: number;  // Pengeluaran wajib
  savingsTarget: number;      // Target tabungan per bulan
  entertainmentBudget: number;// Uang untuk hiburan/fun
}

export interface Transaction {
  id: string;
  nama: string;
  jumlah: number;
  jenis: TransactionType;
  kategori: CategoryType;
  tanggal: string;
  keterangan?: string;
}

export interface SavingsGoal {
  id: string;
  namaTarget: string;
  jumlahTarget: number;
  tabunganAwal: number;
  targetTanggal: string;
}

export interface FinanceRecommendation {
  kebutuhan: number;
  kebutuhanPersen: number;
  tabungan: number;
  tabunganPersen: number;
  fun: number;
  funPersen: number;
  totalAvailable: number;
  status: 'healthy' | 'warning' | 'critical';
  statusLabel: string;
  message: string;
  tips: string[];
}

