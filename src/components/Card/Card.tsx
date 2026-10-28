import React from 'react';

interface CardProps {
  title?: string;
  subtitle?: string;
  className?: string;
  children: React.ReactNode;
  headerAction?: React.ReactNode;
}

export const Card: React.FC<CardProps> = ({
  title,
  subtitle,
  className = '',
  children,
  headerAction,
}) => {
  return (
    <div className={`bg-white rounded-2xl border border-gray-100 shadow-sm p-6 ${className}`}>
      {(title || subtitle || headerAction) && (
        <div className="flex justify-between items-start mb-4">
          <div>
            {title && <h3 className="text-lg font-bold text-gray-900">{title}</h3>}
            {subtitle && <p className="text-sm text-gray-500 mt-0.5">{subtitle}</p>}
          </div>
          {headerAction && <div>{headerAction}</div>}
        </div>
      )}
      {children}
    </div>
  );
};

