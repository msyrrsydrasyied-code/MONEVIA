import React from 'react';
import { Transaction } from '../../types/finance';
import { formatRupiah } from '../../utils/currency';

interface TransactionItemProps {
  transaction: Transaction;
  onDelete?: (id: string) => void;
}

export const TransactionItem: React.FC<TransactionItemProps> = ({
  transaction,
  onDelete,
}) => {
  const isPemasukan = transaction.jenis === 'pemasukan';

  const categoryIcons: Record<string, string> = {
    Makanan: '🍔',
    Transportasi: '🛵',
    Pendidikan: '📚',
    Hiburan: '🎮',
    Belanja: '🛍️',
    Tabungan: '💰',
    Lainnya: '📌',
  };

  return (
    <div className="flex items-center justify-between p-4 bg-white rounded-xl border border-gray-100 hover:border-gray-200 transition shadow-2xs">
      <div className="flex items-center gap-3.5">
        <div className={`w-11 h-11 rounded-xl flex items-center justify-center text-lg ${
          isPemasukan ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'
        }`}>
          {categoryIcons[transaction.kategori] || '💸'}
        </div>
        <div>
          <h4 className="font-semibold text-gray-900 text-sm">{transaction.nama}</h4>
          <div className="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
            <span className="bg-gray-100 px-2 py-0.5 rounded-md font-medium text-gray-600">
              {transaction.kategori}
            </span>
            <span>•</span>
            <span>{transaction.tanggal}</span>
          </div>
          {transaction.keterangan && (
            <p className="text-xs text-gray-400 mt-1 italic">{transaction.keterangan}</p>
          )}
        </div>
      </div>

      <div className="flex items-center gap-3">
        <div className="text-right">
          <span className={`font-bold text-sm block ${
            isPemasukan ? 'text-emerald-600' : 'text-red-600'
          }`}>
            {isPemasukan ? '+' : '-'} {formatRupiah(transaction.jumlah)}
          </span>
          <span className="text-xs capitalize text-gray-400">
            {transaction.jenis}
          </span>
        </div>
        {onDelete && (
          <button
            onClick={() => onDelete(transaction.id)}
            className="text-gray-400 hover:text-red-500 p-1.5 rounded-lg hover:bg-red-50 transition"
            title="Hapus Transaksi"
          >
            🗑️
          </button>
        )}
      </div>
    </div>
  );
};

