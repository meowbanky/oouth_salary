import { useState } from 'react';
import Dashboard from './pages/Dashboard';
import Employees from './pages/Employees';

export default function App() {
    const [currentPage, setCurrentPage] = useState<'dashboard' | 'employees'>('dashboard');

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="bg-white shadow-sm">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex items-center">
                            <h1 className="text-2xl font-bold text-gray-900">HR Analytics</h1>
                        </div>
                        <div className="flex items-center space-x-4">
                            <button
                                className={`px-3 py-2 rounded-md ${
                                    currentPage === 'dashboard'
                                        ? 'bg-gray-900 text-white'
                                        : 'text-gray-700 hover:bg-gray-100'
                                }`}
                                onClick={() => setCurrentPage('dashboard')}
                            >
                                Dashboard
                            </button>
                            <button
                                className={`px-3 py-2 rounded-md ${
                                    currentPage === 'employees'
                                        ? 'bg-gray-900 text-white'
                                        : 'text-gray-700 hover:bg-gray-100'
                                }`}
                                onClick={() => setCurrentPage('employees')}
                            >
                                Employees
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                {currentPage === 'dashboard' ? <Dashboard /> : <Employees />}
            </main>
        </div>
    );
}