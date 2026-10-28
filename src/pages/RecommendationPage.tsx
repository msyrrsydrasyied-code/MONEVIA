import React from 'react';
import { FinanceRecommendation } from '../types/finance';
import { formatRupiah } from '../utils/currency';
import { Card } from '../components/Card/Card';
import { Button } from '../components/Button/Button';
import { ProgressBar } from '../components/ProgressBar/ProgressBar';
import { RecommendationCard } from '../components/RecommendationCard/RecommendationCard';

interface RecommendationPageProps {
  recommendation: FinanceRecommendation;
  onNavigate: (tab: string) => void;
}

export const RecommendationPage: React.FC<RecommendationPageProps> = ({
  recommendation,
  onNavigate,
}) => {
  return (
    <div className="max-w-3xl mx-auto space-y-6 py-2">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
            Hasil Rekomendasi Pengelolaan Uang
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            Menurut Monevia, uang kamu sebesar <strong className="text-emerald-700">{formatRupiah(recommendation.totalAvailable)}</strong> sebaiknya dibagi seperti ini:
          </p>
        </div>
        <Button variant="outline" size="sm" onClick={() => onNavigate('input')}>
          ✏️ Edit Input
        </Button>
      </div>

      {/* Money Coach Banner */}
      <RecommendationCard recommendation={recommendation} />

      {/* Allocation Breakdown List */}
      <div className="space-y-4">
        {/* 1. Kebutuhan */}
        <Card className="border-l-4 border-l-blue-500">
          <div className="flex justify-between items-start mb-2">
            <div>
              <span className="text-xs font-bold uppercase tracking-wider text-blue-600">Alokasi Pokok</span>
              <h3 className="text-lg font-bold text-gray-900">Kebutuhan Pokok</h3>
            </div>
            <div className="text-right">
              <span className="text-xl font-extrabold text-gray-900">{formatRupiah(recommendation.kebutuhan)}</span>
              <span className="text-xs font-bold text-blue-600 block">{recommendation.kebutuhanPersen}% dari total</span>
            </div>
          </div>
          <ProgressBar percentage={recommendation.kebutuhanPersen} color="blue" height="h-3" />
          <p className="text-xs text-gray-500 mt-3">
            Gunakan anggaran ini untuk kebutuhan harian wajib seperti makan, transportasi sekolah/kampus, perlengkapan belajar, dan biaya pokok lainnya.
          </p>
        </Card>

        {/* 2. Tabungan */}
        <Card className="border-l-4 border-l-emerald-500">
          <div className="flex justify-between items-start mb-2">
            <div>
              <span className="text-xs font-bold uppercase tracking-wider text-emerald-600">Alokasi Masa Depan</span>
              <h3 className="text-lg font-bold text-gray-900">Tabungan & Target Impian</h3>
            </div>
            <div className="text-right">
              <span className="text-xl font-extrabold text-gray-900">{formatRupiah(recommendation.tabungan)}</span>
              <span className="text-xs font-bold text-emerald-600 block">{recommendation.tabunganPersen}% dari total</span>
            </div>
          </div>
          <ProgressBar percentage={recommendation.tabunganPersen} color="emerald" height="h-3" />
          <p className="text-xs text-gray-500 mt-3">
            Langsung pisahkan nominal ini di awal setelah menerima uang saku. Jangan menunggu sisa uang di akhir bulan!
          </p>
        </Card>

        {/* 3. Fun / Hiburan */}
        <Card className="border-l-4 border-l-purple-500">
          <div className="flex justify-between items-start mb-2">
            <div>
              <span className="text-xs font-bold uppercase tracking-wider text-purple-600">Alokasi Bebas</span>
              <h3 className="text-lg font-bold text-gray-900">Keinginan / Hiburan (Fun)</h3>
            </div>
            <div className="text-right">
              <span className="text-xl font-extrabold text-gray-900">{formatRupiah(recommendation.fun)}</span>
              <span className="text-xs font-bold text-purple-600 block">{recommendation.funPersen}% dari total</span>
            </div>
          </div>
          <ProgressBar percentage={recommendation.funPersen} color="purple" height="h-3" />
          <p className="text-xs text-gray-500 mt-3">
            Gunakan untuk jajan santai, langganan aplikasi, nonton, atau hobi tanpa rasa bersalah selama tetap berada dalam batas ini.
          </p>
        </Card>
      </div>

      {/* Bottom Action Buttons */}
      <div className="flex flex-col sm:flex-row gap-3 pt-2">
        <Button className="flex-1" onClick={() => onNavigate('dashboard')}>
          📊 Ke Dashboard Utama
        </Button>
        <Button variant="outline" className="flex-1" onClick={() => onNavigate('goals')}>
          🎯 Buat Target Tabungan
        </Button>
        <Button variant="secondary" className="flex-1" onClick={() => onNavigate('transactions')}>
          💸 Catat Transaksi Harian
        </Button>
      </div>
    </div>
  );
};

