import React from 'react';

interface NavbarProps {
  activeTab: string;
  setActiveTab: (tab: string) => void;
}

export const Navbar: React.FC<NavbarProps> = ({ activeTab, setActiveTab }) => {
  const navItems = [
    { id: 'home', label: 'Home', icon: '🏠' },
    { id: 'dashboard', label: 'Dashboard', icon: '📊' },
    { id: 'input', label: 'Input Keuangan', icon: '📝' },
    { id: 'recommendation', label: 'Rekomendasi', icon: '💡' },
    { id: 'goals', label: 'Target Tabungan', icon: '🎯' },
    { id: 'transactions', label: 'Transaksi', icon: '💸' },
  ];

  return (
    <header className="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-xs">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16 items-center">
          {/* Logo Monevia */}
          <div 
            onClick={() => setActiveTab('home')}
            className="flex items-center gap-2 cursor-pointer group"
          >
            <div className="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xl shadow-sm group-hover:bg-emerald-700 transition">
              M
            </div>
            <div>
              <span className="font-extrabold text-xl tracking-tight text-gray-900">Monevia</span>
              <span className="text-xs block text-emerald-600 font-semibold -mt-1">Personal Money Coach</span>
            </div>
          </div>

          {/* Desktop Navigation */}
          <nav className="hidden md:flex space-x-1">
            {navItems.map((item) => {
              const isActive = activeTab === item.id;
              return (
                <button
                  key={item.id}
                  onClick={() => setActiveTab(item.id)}
                  className={`px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1.5 cursor-pointer ${
                    isActive
                      ? 'bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200'
                      : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                  }`}
                >
                  <span>{item.icon}</span>
                  <span>{item.label}</span>
                </button>
              );
            })}
          </nav>
        </div>
      </div>

      {/* Mobile Navigation Bar */}
      <div className="md:hidden border-t border-gray-100 bg-gray-50 px-2 py-2 flex overflow-x-auto gap-1">
        {navItems.map((item) => {
          const isActive = activeTab === item.id;
          return (
            <button
              key={item.id}
              onClick={() => setActiveTab(item.id)}
              className={`px-3 py-1.5 rounded-md text-xs whitespace-nowrap font-medium transition flex items-center gap-1 cursor-pointer ${
                isActive
                  ? 'bg-emerald-600 text-white font-bold'
                  : 'bg-white text-gray-700 border border-gray-200'
              }`}
            >
              <span>{item.icon}</span>
              <span>{item.label}</span>
            </button>
          );
        })}
      </div>
    </header>
  );
};

