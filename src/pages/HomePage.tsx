import React from 'react';
import { Button } from '../components/Button/Button';
import { Card } from '../components/Card/Card';

interface HomePageProps {
  onNavigate: (tab: string) => void;
}

export const HomePage: React.FC<HomePageProps> = ({ onNavigate }) => {
  return (
    <div className="space-y-8 max-w-5xl mx-auto py-4">
      {/* Hero Banner */}
      <div className="bg-gradient-to-br from-emerald-600 to-teal-800 text-white rounded-3xl p-8 sm:p-12 shadow-lg relative overflow-hidden">
        <div className="relative z-10 max-w-2xl space-y-4">
          <span className="bg-emerald-500/30 text-emerald-100 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider border border-emerald-400/30">
            Personal Money Coach Pelajar & Santri
          </span>
          <h1 className="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">
            Bingung Uang Rp100rb Sebaiknya Dipakai Buat Apa?
          </h1>
          <p className="text-emerald-100 text-base sm:text-lg leading-relaxed">
            Monevia hadir membantu pelajar, santri, dan anak muda mengatur alokasi uang saku, menabung target impian, dan mencatat transaksi harian dengan bijak.
          </p>
          <div className="pt-4 flex flex-wrap gap-3">
            <Button 
              size="lg" 
              className="bg-white text-emerald-800 hover:bg-emerald-50 shadow-md font-bold"
              onClick={() => onNavigate('input')}
            >
              🚀 Atur Keuangan Sekarang
            </Button>
            <Button 
              size="lg" 
              variant="outline" 
              className="border-white/40 text-white hover:bg-white/10 font-bold"
              onClick={() => onNavigate('dashboard')}
            >
              📊 Lihat Dashboard
            </Button>
          </div>
        </div>

        {/* Decorative background circles */}
        <div className="absolute -right-10 -bottom-10 w-72 h-72 bg-emerald-500/20 rounded-full blur-2xl pointer-events-none"></div>
        <div className="absolute right-20 top-10 w-48 h-48 bg-teal-400/20 rounded-full blur-xl pointer-events-none"></div>
      </div>

      {/* Feature Cards Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card className="hover:shadow-md transition border-t-4 border-t-emerald-500" title="💡 Atur Alokasi Uang">
          <p className="text-gray-600 text-sm mb-4 leading-relaxed">
            Gunakan rumus 50/30/20 yang disesuaikan dengan kebutuhan harianmu agar tidak kehabisan uang di akhir bulan.
          </p>
          <Button size="sm" variant="secondary" onClick={() => onNavigate('input')}>
            Mulai Input →
          </Button>
        </Card>

        <Card className="hover:shadow-md transition border-t-4 border-t-blue-500" title="🎯 Target Tabungan">
          <p className="text-gray-600 text-sm mb-4 leading-relaxed">
            Punya impian beli laptop, HP, atau dana darurat? Buat target dan pantau progress persentasenya secara nyata.
          </p>
          <Button size="sm" variant="secondary" onClick={() => onNavigate('goals')}>
            Lihat Target →
          </Button>
        </Card>

        <Card className="hover:shadow-md transition border-t-4 border-t-purple-500" title="💸 Catat Transaksi">
          <p className="text-gray-600 text-sm mb-4 leading-relaxed">
            Catat setiap pemasukan uang saku dan pengeluaran harianmu untuk menjaga arus kas tetap seimbang.
          </p>
          <Button size="sm" variant="secondary" onClick={() => onNavigate('transactions')}>
            Catat Transaksi →
          </Button>
        </Card>
      </div>
    </div>
  );
};

