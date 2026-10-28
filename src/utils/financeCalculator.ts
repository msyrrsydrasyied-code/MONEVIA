import { UserFinance, FinanceRecommendation } from '../types/finance';

/**
 * Menghitung pembagian keuangan dan rekomendasi Money Coach Monevia
 */
export const calculateFinanceRecommendation = (
  finance: UserFinance
): FinanceRecommendation => {
  const { totalMoney, mandatoryExpenses, savingsTarget, entertainmentBudget } = finance;

  // Jika uang 0 atau belum diisi
  if (totalMoney <= 0) {
    return {
      kebutuhan: 0,
      kebutuhanPersen: 50,
      tabungan: 0,
      tabunganPersen: 30,
      fun: 0,
      funPersen: 20,
      totalAvailable: 0,
      status: 'critical',
      statusLabel: 'Belum Ada Data',
      message: 'Silakan masukkan jumlah uang yang kamu miliki terlebih dahulu di menu Input Keuangan.',
      tips: [
        'Masukkan total uang saku, gaji, atau saldo yang tersedia saat ini.',
        'Monevia akan membantu membaginya secara otomatis.',
      ],
    };
  }

  // Menghitung persentase kebutuhan standar (50/30/20 rule)
  let idealKebutuhan = Math.round(totalMoney * 0.5);
  let idealTabungan = Math.round(totalMoney * 0.3);
  let idealFun = Math.round(totalMoney * 0.2);

  // Menyesuaikan dengan pengeluaran wajib nyata dari user jika lebih besar dari standar 50%
  let actualKebutuhan = Math.max(idealKebutuhan, mandatoryExpenses);
  
  // Jika pengeluaran wajib melebihi total uang
  if (actualKebutuhan > totalMoney) {
    actualKebutuhan = totalMoney;
  }

  const sisaUang = Math.max(0, totalMoney - actualKebutuhan);

  // Jika sisa uang ada, bagi untuk tabungan & fun
  let actualTabungan = Math.min(sisaUang, savingsTarget > 0 ? savingsTarget : Math.round(sisaUang * 0.6));
  let actualFun = Math.max(0, sisaUang - actualTabungan);

  // Jika user juga menginput entertainmentBudget secara spesifik
  if (entertainmentBudget > 0 && entertainmentBudget <= sisaUang) {
    actualFun = entertainmentBudget;
    actualTabungan = Math.max(0, sisaUang - actualFun);
  }

  // Hitung persentase aktual
  const kebutuhanPersen = Math.round((actualKebutuhan / totalMoney) * 100);
  const tabunganPersen = Math.round((actualTabungan / totalMoney) * 100);
  const funPersen = Math.max(0, 100 - kebutuhanPersen - tabunganPersen);

  // Evaluasi Status Keuangan & Pesan Money Coach
  let status: 'healthy' | 'warning' | 'critical' = 'healthy';
  let statusLabel = 'Keuangan Aman';
  let message = 'Keuangan kamu cukup aman! Kamu masih memiliki ruang yang cukup untuk menabung dan memenuhi kebutuhan harian.';
  const tips: string[] = [];

  const mandatoryRatio = mandatoryExpenses / totalMoney;

  if (totalMoney < 200000) {
    status = 'critical';
    statusLabel = 'Perhatian Khusus';
    message = 'Uang yang kamu miliki saat ini relatif sedikit. Prioritaskan kebutuhan utama (makan/transportasi) terlebih dahulu sebelum menambah pengeluaran lainnya.';
    tips.push('Fokus pada kebutuhan pokok seperti makan dan ongkos harian.');
    tips.push('Tunda pembelian barang keinginan atau hiburan sampai keuangan stabil.');
  } else if (mandatoryRatio > 0.6) {
    status = 'warning';
    statusLabel = 'Peringatan Waspada';
    message = 'Pengeluaran wajib kamu cukup besar (melebihi 60% dari total uang). Coba kurangi pengeluaran yang tidak terlalu penting agar bisa menabung lebih banyak.';
    tips.push('Catat pengeluaran harian untuk menemukan pos mana yang bisa dihemat.');
    tips.push('Usahakan porsi kebutuhan tidak melebihi 50% di bulan berikutnya.');
  } else {
    status = 'healthy';
    statusLabel = 'Kondisi Sehat';
    message = 'Keuangan kamu teratur dengan baik! Alokasi kebutuhan, tabungan, dan hiburan dalam batas ideal.';
    tips.push('Pertahankan kebiasaan menabung minimal 20-30% setiap mendapat uang.');
    tips.push('Gunakan sisa anggaran hiburan dengan bijak tanpa melebihi batas.');
  }

  return {
    kebutuhan: actualKebutuhan,
    kebutuhanPersen,
    tabungan: actualTabungan,
    tabunganPersen,
    fun: actualFun,
    funPersen,
    totalAvailable: totalMoney,
    status,
    statusLabel,
    message,
    tips,
  };
};

