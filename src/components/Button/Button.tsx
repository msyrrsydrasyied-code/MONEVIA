import React from 'react';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'danger' | 'outline';
  size?: 'sm' | 'md' | 'lg';
  children: React.ReactNode;
}

export const Button: React.FC<ButtonProps> = ({
  variant = 'primary',
  size = 'md',
  children,
  className = '',
  ...props
}) => {
  const baseStyle = 'inline-flex items-center justify-center font-semibold rounded-xl transition duration-200 focus:outline-hidden disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer';

  const variantStyles = {
    primary: 'bg-emerald-600 text-white hover:bg-emerald-700 active:bg-emerald-800 shadow-xs',
    secondary: 'bg-gray-100 text-gray-800 hover:bg-gray-200 active:bg-gray-300',
    danger: 'bg-red-500 text-white hover:bg-red-600 active:bg-red-700',
    outline: 'border border-gray-300 text-gray-700 hover:bg-gray-50 active:bg-gray-100',
  };

  const sizeStyles = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2.5 text-sm',
    lg: 'px-6 py-3.5 text-base',
  };

  return (
    <button
      className={`${baseStyle} ${variantStyles[variant]} ${sizeStyles[size]} ${className}`}
      {...props}
    >
      {children}
    </button>
  );
};

