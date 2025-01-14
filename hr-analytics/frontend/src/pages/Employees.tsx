import { useState, useEffect } from 'react';
import axios from 'axios';

interface Employee {
    staff_id: number;
    NAME: string;
    GENDER: string;
    DOB: string;
    DOPA: string;
    DEPTCD: number;
    POST: string;
    GRADE: string;
    MBSAL: number;
}

export default function Employees() {
    const [employees, setEmployees] = useState<Employee[]>([]);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [limit] = useState(10);
    const [filters, setFilters] = useState({
        department: '',
        grade: ''
    });
    const [total, setTotal] = useState(0);

    useEffect(() => {
        const fetchEmployees = async () => {
            try {
                const params = new URLSearchParams({
                    page: page.toString(),
                    limit: limit.toString(),
                    ...filters
                });

                const response = await axios.get(
                    `http://localhost:3001/api/employees?${params}`
                );
                setEmployees(response.data.data);
                setTotal(response.data.pagination.total);
            } catch (error) {
                console.error('Error fetching employees:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchEmployees();
    }, [page, limit, filters]);

    if (loading) {
        return <div className="flex items-center justify-center min-h-screen">Loading...</div>;
    }

    return (
        <div className="p-6">
            <h1 className="text-2xl font-bold mb-6">Employees</h1>

            {/* Filters */}
            <div className="flex gap-4 mb-6">
                <input
                    type="text"
                    placeholder="Department..."
                    className="p-2 border rounded"
                    onChange={(e) => setFilters(prev => ({ ...prev, department: e.target.value }))}
                />
                <input
                    type="text"
                    placeholder="Grade..."
                    className="p-2 border rounded"
                    onChange={(e) => setFilters(prev => ({ ...prev, grade: e.target.value }))}
                />
            </div>

            {/* Employee Table */}
            <div className="bg-white shadow-md rounded-lg overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                    <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Name
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Department
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Position
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Grade
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Salary
                        </th>
                    </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                    {employees.map((employee) => (
                        <tr key={employee.staff_id}>
                            <td className="px-6 py-4 whitespace-nowrap">{employee.NAME}</td>
                            <td className="px-6 py-4 whitespace-nowrap">{employee.DEPTCD}</td>
                            <td className="px-6 py-4 whitespace-nowrap">{employee.POST || 'N/A'}</td>
                            <td className="px-6 py-4 whitespace-nowrap">{employee.GRADE}</td>
                            <td className="px-6 py-4 whitespace-nowrap">₦{employee.MBSAL.toLocaleString()}</td>
                        </tr>
                    ))}
                    </tbody>
                </table>

                {/* Pagination */}
                <div className="bg-white px-6 py-4 border-t border-gray-200">
                    <div className="flex justify-between items-center">
                        <div>
                            Showing {(page - 1) * limit + 1} to {Math.min(page * limit, total)} of {total} results
                        </div>
                        <div className="flex gap-2">
                            <button
                                className="px-4 py-2 border rounded-md hover:bg-gray-50"
                                onClick={() => setPage(p => Math.max(1, p - 1))}
                                disabled={page === 1}
                            >
                                Previous
                            </button>
                            <button
                                className="px-4 py-2 border rounded-md hover:bg-gray-50"
                                onClick={() => setPage(p => p + 1)}
                                disabled={page * limit >= total}
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}