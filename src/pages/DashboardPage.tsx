import React from 'react';
import { UserFinance, FinanceRecommendation, SavingsGoal, Transaction } from '../types/finance';
import { formatRupiah } from '../utils/currency';
import { Card } from '../components/Card/Card';
import { Button } from '../components/Button/Button';
import { ProgressBar } from '../components/ProgressBar/ProgressBar';
import { RecommendationCard } from '../components/RecommendationCard/RecommendationCard';

interface DashboardPageProps {
  finance: UserFinance;
  recommendation: FinanceRecommendation;
  goals: SavingsGoal[];
  transactions: Transaction[];
  onNavigate: (tab: string) => void;
}

export const DashboardPage: React.FC<DashboardPageProps> = ({
  finance,
  recommendation,
  goals,
  transactions,
  onNavigate,
}) => {
  return (
    <div className="space-y-6 max-w-5xl mx-auto py-2">
      {/* Header Dashboard */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
            Dashboard Ringkasan Keuangan
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            Pantau arus kas, alokasi anggaran, dan rekomendasi Money Coach Monevia.
          </p>
        </div>
        <Button onClick={() => onNavigate('input')} className="self-start sm:self-auto">
          ✏️ Input Data Keuangan
        </Button>
      </div>

      {/* Overview Metric Cards (4 Cards) */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Card Total Uang */}
        <div className="bg-gradient-to-br from-emerald-500 to-emerald-700 text-white rounded-2xl p-5 shadow-sm">
          <span className="text-xs font-semibold uppercase tracking-wider text-emerald-100 block">
            Total Uang Tersedia
          </span>
          <span className="text-2xl font-extrabold block mt-1">
            {formatRupiah(finance.totalMoney)}
          </span>
          <span className="text-xs text-emerald-200 block mt-2">
            Dari input & alokasi utama
          </span>
        </div>

        {/* Card Kebutuhan */}
        <div className="bg-white rounded-2xl p-5 border border-gray-100 shadow-2xs">
          <div className="flex justify-between items-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
            <span>Kebutuhan</span>
            <span className="bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full font-bold">{recommendation.kebutuhanPersen}%</span>
          </div>
          <span className="text-xl font-bold text-gray-900 block mt-2">
            {formatRupiah(recommendation.kebutuhan)}
          </span>
          <span className="text-xs text-gray-400 block mt-1">
            Pos wajib & harian
          </span>
        </div>

        {/* Card Tabungan */}
        <div className="bg-white rounded-2xl p-5 border border-gray-100 shadow-2xs">
          <div className="flex justify-between items-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
            <span>Tabungan</span>
            <span className="bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-full font-bold">{recommendation.tabunganPersen}%</span>
          </div>
          <span className="text-xl font-bold text-gray-900 block mt-2">
            {formatRupiah(recommendation.tabungan)}
          </span>
          <span className="text-xs text-gray-400 block mt-1">
            Simpanan & dana masa depan
          </span>
        </div>

        {/* Card Fun/Keinginan */}
        <div className="bg-white rounded-2xl p-5 border border-gray-100 shadow-2xs">
          <div className="flex justify-between items-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
            <span>Keinginan / Fun</span>
            <span className="bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full font-bold">{recommendation.funPersen}%</span>
          </div>
          <span className="text-xl font-bold text-gray-900 block mt-2">
            {formatRupiah(recommendation.fun)}
          </span>
          <span className="text-xs text-gray-400 block mt-1">
            Hiburan & jajan santai
          </span>
        </div>
      </div>

      {/* Money Coach Banner Card */}
      <RecommendationCard
        recommendation={recommendation}
        onGoToInput={() => onNavigate('input')}
      />

      {/* Progress Visual & Summary Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Visual Alokasi 50/30/20 */}
        <Card title="📊 Visual Pembagian Anggaran">
          <div className="space-y-4">
            <div>
              <div className="flex justify-between text-xs font-semibold mb-1">
                <span className="text-gray-700">Kebutuhan Pokok</span>
                <span className="text-blue-600 font-bold">{formatRupiah(recommendation.kebutuhan)} ({recommendation.kebutuhanPersen}%)</span>
              </div>
              <ProgressBar percentage={recommendation.kebutuhanPersen} color="blue" height="h-3" />
            </div>

            <div>
              <div className="flex justify-between text-xs font-semibold mb-1">
                <span className="text-gray-700">Tabungan & Impian</span>
                <span className="text-emerald-600 font-bold">{formatRupiah(recommendation.tabungan)} ({recommendation.tabunganPersen}%)</span>
              </div>
              <ProgressBar percentage={recommendation.tabunganPersen} color="emerald" height="h-3" />
            </div>

            <div>
              <div className="flex justify-between text-xs font-semibold mb-1">
                <span className="text-gray-700">Hiburan & Fun</span>
                <span className="text-purple-600 font-bold">{formatRupiah(recommendation.fun)} ({recommendation.funPersen}%)</span>
              </div>
              <ProgressBar percentage={recommendation.funPersen} color="purple" height="h-3" />
            </div>
          </div>
        </Card>

        {/* Quick Summary: Target Tabungan */}
        <Card title="🎯 Snapshot Target Tabungan">
          {goals.length > 0 ? (
            <div className="space-y-3">
              {goals.slice(0, 2).map((goal) => {
                const progress = Math.min(100, Math.round((goal.tabunganAwal / goal.jumlahTarget) * 100));
                return (
                  <div key={goal.id} className="p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <div className="flex justify-between text-xs font-bold text-gray-800 mb-1">
                      <span>{goal.namaTarget}</span>
                      <span className="text-emerald-600">{progress}%</span>
                    </div>
                    <ProgressBar percentage={progress} color="emerald" height="h-2" />
                    <div className="flex justify-between text-[11px] text-gray-500 mt-1">
                      <span>Terkumpul: {formatRupiah(goal.tabunganAwal)}</span>
                      <span>Target: {formatRupiah(goal.jumlahTarget)}</span>
                    </div>
                  </div>
                );
              })}
              <Button
                variant="outline"
                size="sm"
                className="w-full mt-2"
                onClick={() => onNavigate('goals')}
              >
                Kelola Semua Target Tabungan →
              </Button>
            </div>
          ) : (
            <p className="text-xs text-gray-500">Belum ada target tabungan.</p>
          )}
        </Card>
      </div>

      {/* Quick Action Bar */}
      <div className="bg-emerald-900 text-white rounded-2xl p-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div>
          <h3 className="font-bold text-lg">Ingin Menambah Catatan Transaksi Harian?</h3>
          <p className="text-xs text-emerald-200 mt-0.5">Catat setiap uang masuk dan uang keluar agar catatan keuanganmu selalu akurat.</p>
        </div>
        <Button variant="primary" className="bg-emerald-500 hover:bg-emerald-400 text-emerald-950 font-bold whitespace-nowrap" onClick={() => onNavigate('transactions')}>
          + Catat Transaksi
        </Button>
      </div>
    </div>
  );
};

