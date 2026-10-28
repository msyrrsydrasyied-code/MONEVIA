/**
 * Mengubah angka menjadi format Rupiah Indonesia
 * Contoh: 1000000 -> "Rp1.000.000"
 */
export const formatRupiah = (amount: number): string => {
  if (isNaN(amount) || amount === null || amount === undefined) {
    return 'Rp0';
  }
  const formatted = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);

  // Mengubah "RP 1.000.000" / "Rp 1.000.000" menjadi "Rp1.000.000" tanpa spasi berlebih
  return formatted.replace(/\s+/g, '');
};

/**
 * Mengonversi string angka bersihkan karakter non-digit
 */
export const parseNumberInput = (value: string): number => {
  const clean = value.replace(/[^0-9]/g, '');
  return clean ? parseInt(clean, 10) : 0;
};

