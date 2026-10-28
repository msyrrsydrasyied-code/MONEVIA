import React, { useState, useEffect } from 'react';
import { UserFinance, SavingsGoal, Transaction } from './types/finance';
import { initialFinanceData, initialSavingsGoals, initialTransactions } from './data/dummyData';
import { calculateFinanceRecommendation } from './utils/financeCalculator';
import { Navbar } from './components/Navbar';
import { HomePage } from './pages/HomePage';
import { DashboardPage } from './pages/DashboardPage';
import { FinanceInputPage } from './pages/FinanceInputPage';
import { RecommendationPage } from './pages/RecommendationPage';
import { SavingsGoalPage } from './pages/SavingsGoalPage';
import { TransactionsPage } from './pages/TransactionsPage';

export const App: React.FC = () => {
  const [activeTab, setActiveTab] = useState<string>('home');

  // State Keuangan User
  const [finance, setFinance] = useState<UserFinance>(() => {
    const saved = localStorage.getItem('monevia_finance');
    return saved ? JSON.parse(saved) : initialFinanceData;
  });

  // State Target Tabungan
  const [goals, setGoals] = useState<SavingsGoal[]>(() => {
    const saved = localStorage.getItem('monevia_goals');
    return saved ? JSON.parse(saved) : initialSavingsGoals;
  });

  // State Transaksi
  const [transactions, setTransactions] = useState<Transaction[]>(() => {
    const saved = localStorage.getItem('monevia_transactions');
    return saved ? JSON.parse(saved) : initialTransactions;
  });

  // Simpan ke localStorage saat state berubah
  useEffect(() => {
    localStorage.setItem('monevia_finance', JSON.stringify(finance));
  }, [finance]);

  useEffect(() => {
    localStorage.setItem('monevia_goals', JSON.stringify(goals));
  }, [goals]);

  useEffect(() => {
    localStorage.setItem('monevia_transactions', JSON.stringify(transactions));
  }, [transactions]);

  // Kalkulasi rekomendasi Money Coach secara terpusat
  const recommendation = calculateFinanceRecommendation(finance);

  // Handlers untuk Target Tabungan
  const handleAddGoal = (newGoal: SavingsGoal) => {
    setGoals((prev) => [newGoal, ...prev]);
  };

  const handleUpdateGoal = (id: string, addedAmount: number) => {
    setGoals((prev) =>
      prev.map((g) => (g.id === id ? { ...g, tabunganAwal: g.tabunganAwal + addedAmount } : g))
    );
  };

  const handleDeleteGoal = (id: string) => {
    setGoals((prev) => prev.filter((g) => g.id !== id));
  };

  // Handlers untuk Transaksi
  const handleAddTransaction = (newTx: Transaction) => {
    setTransactions((prev) => [newTx, ...prev]);

    // Jika transaksi adalah pengeluaran, perbarui sisa saldo/total uang di finance state secara logis
    if (newTx.jenis === 'pengeluaran') {
      setFinance((prev) => ({
        ...prev,
        totalMoney: Math.max(0, prev.totalMoney - newTx.jumlah),
      }));
    } else if (newTx.jenis === 'pemasukan') {
      setFinance((prev) => ({
        ...prev,
        totalMoney: prev.totalMoney + newTx.jumlah,
      }));
    }
  };

  const handleDeleteTransaction = (id: string) => {
    setTransactions((prev) => prev.filter((t) => t.id !== id));
  };

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col font-sans text-gray-900 selection:bg-emerald-100 selection:text-emerald-800">
      {/* Top Navigation */}
      <Navbar activeTab={activeTab} setActiveTab={setActiveTab} />

      {/* Main Content Container */}
      <main className="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {activeTab === 'home' && <HomePage onNavigate={setActiveTab} />}
        {activeTab === 'dashboard' && (
          <DashboardPage
            finance={finance}
            recommendation={recommendation}
            goals={goals}
            transactions={transactions}
            onNavigate={setActiveTab}
          />
        )}
        {activeTab === 'input' && (
          <FinanceInputPage
            initialFinance={finance}
            onSave={setFinance}
            onNavigate={setActiveTab}
          />
        )}
        {activeTab === 'recommendation' && (
          <RecommendationPage
            recommendation={recommendation}
            onNavigate={setActiveTab}
          />
        )}
        {activeTab === 'goals' && (
          <SavingsGoalPage
            goals={goals}
            onAddGoal={handleAddGoal}
            onUpdateGoal={handleUpdateGoal}
            onDeleteGoal={handleDeleteGoal}
          />
        )}
        {activeTab === 'transactions' && (
          <TransactionsPage
            transactions={transactions}
            onAddTransaction={handleAddTransaction}
            onDeleteTransaction={handleDeleteTransaction}
          />
        )}
      </main>

      {/* Footer */}
      <footer className="bg-white border-t border-gray-200 py-6 text-center text-xs text-gray-500">
        <p>© 2026 Monevia - Personal Money Coach Pelajar & Santri. Hak Cipta Dilindungi.</p>
      </footer>
    </div>
  );
};

export default App;

