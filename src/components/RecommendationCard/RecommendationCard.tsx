import React from 'react';
import { FinanceRecommendation } from '../../types/finance';
import { Card } from '../Card/Card';

interface RecommendationCardProps {
  recommendation: FinanceRecommendation;
  onGoToInput?: () => void;
}

export const RecommendationCard: React.FC<RecommendationCardProps> = ({
  recommendation,
  onGoToInput,
}) => {
  const statusColors = {
    healthy: 'bg-emerald-50 border-emerald-200 text-emerald-900',
    warning: 'bg-amber-50 border-amber-200 text-amber-900',
    critical: 'bg-red-50 border-red-200 text-red-900',
  };

  const badgeColors = {
    healthy: 'bg-emerald-600 text-white',
    warning: 'bg-amber-500 text-white',
    critical: 'bg-red-600 text-white',
  };

  return (
    <Card className={`border ${statusColors[recommendation.status]}`}>
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center gap-2">
          <span className="text-xl">💡</span>
          <h3 className="font-extrabold text-base tracking-tight">Rekomendasi Money Coach</h3>
        </div>
        <span className={`text-xs px-2.5 py-1 rounded-full font-bold uppercase tracking-wider ${badgeColors[recommendation.status]}`}>
          {recommendation.statusLabel}
        </span>
      </div>

      <p className="text-sm font-medium leading-relaxed mb-4">
        {recommendation.message}
      </p>

      {recommendation.tips.length > 0 && (
        <div className="bg-white/80 rounded-xl p-3.5 border border-black/5">
          <h4 className="text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Tips Monevia Untukmu:</h4>
          <ul className="space-y-1.5 text-xs text-gray-700">
            {recommendation.tips.map((tip, idx) => (
              <li key={idx} className="flex items-start gap-2">
                <span className="text-emerald-500 font-bold">•</span>
                <span>{tip}</span>
              </li>
            ))}
          </ul>
        </div>
      )}

      {onGoToInput && (
        <button
          onClick={onGoToInput}
          className="mt-4 text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1 cursor-pointer"
        >
          <span>Ubah Alokasi & Pengeluaran Wajib</span>
          <span>→</span>
        </button>
      )}
    </Card>
  );
};

