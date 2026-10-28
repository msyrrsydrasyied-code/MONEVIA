import React from 'react';

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
  helperText?: string;
  prefixText?: string;
}

export const Input: React.FC<InputProps> = ({
  label,
  error,
  helperText,
  prefixText,
  className = '',
  id,
  ...props
}) => {
  const inputId = id || `input-${label.toLowerCase().replace(/\s+/g, '-')}`;

  return (
    <div className="flex flex-col gap-1.5 w-full">
      <label htmlFor={inputId} className="text-sm font-semibold text-gray-800">
        {label}
      </label>
      <div className="relative flex items-center">
        {prefixText && (
          <span className="absolute left-3 text-sm font-medium text-gray-500 pointer-events-none">
            {prefixText}
          </span>
        )}
        <input
          id={inputId}
          className={`w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm transition focus:outline-hidden focus:ring-2 ${
            prefixText ? 'pl-9' : ''
          } ${
            error
              ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
              : 'border-gray-300 focus:border-emerald-500 focus:ring-emerald-100'
          } ${className}`}
          {...props}
        />
      </div>
      {error && <span className="text-xs text-red-500 font-medium">{error}</span>}
      {helperText && !error && <span className="text-xs text-gray-500">{helperText}</span>}
    </div>
  );
};

