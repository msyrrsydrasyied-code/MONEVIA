import React from 'react';

interface ProgressBarProps {
  percentage: number;
  color?: 'emerald' | 'blue' | 'purple' | 'amber' | 'red';
  height?: string;
  showLabel?: boolean;
}

export const ProgressBar: React.FC<ProgressBarProps> = ({
  percentage,
  color = 'emerald',
  height = 'h-3',
  showLabel = false,
}) => {
  const clamped = Math.min(100, Math.max(0, percentage));

  const colorStyles = {
    emerald: 'bg-emerald-500',
    blue: 'bg-blue-500',
    purple: 'bg-purple-500',
    amber: 'bg-amber-500',
    red: 'bg-red-500',
  };

  return (
    <div className="w-full">
      {showLabel && (
        <div className="flex justify-between text-xs font-semibold text-gray-600 mb-1">
          <span>Progress</span>
          <span>{clamped}%</span>
        </div>
      )}
      <div className={`w-full bg-gray-100 rounded-full overflow-hidden ${height}`}>
        <div
          className={`${height} ${colorStyles[color]} rounded-full transition-all duration-500 ease-out`}
          style={{ width: `${clamped}%` }}
        />
      </div>
    </div>
  );
};

