// src/components/Dashboard.tsx
import { useState, useEffect } from 'react';
import axios from 'axios';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from './ui/Table';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend } from 'recharts';

interface Employee {
    staff_id: number;
    NAME: string;
    GENDER: string | null;
    DOB: string | null;
    DOPA: string | null;
    DEPTCD: number;
    POST: string | null;
    GRADE: string;
    STEP: string;
    MBSAL: number;
}

interface PaginationInfo {
    total: number;
    page: number;
    limit: number;
}

export default function Dashboard() {
    const [employees, setEmployees] = useState<Employee[]>([]);
    const [pagination, setPagination] = useState<PaginationInfo>({
        total: 0,
        page: 1,
        limit: 10,
    });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchEmployees = async () => {
            try {
                const response = await axios.get(`http://localhost:3001/api/employees?page=${pagination.page}&limit=${pagination.limit}`);
                setEmployees(response.data.data);
                setPagination(response.data.pagination);
            } catch (error) {
                console.error('Error fetching employees:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchEmployees();
    }, [pagination.page, pagination.limit]);

    // Calculate grade distribution for chart
    const gradeDistribution = employees.reduce((acc: { [key: string]: number }, emp) => {
        acc[emp.GRADE] = (acc[emp.GRADE] || 0) + 1;
        return acc;
    }, {});

    const chartData = Object.entries(gradeDistribution).map(([grade, count]) => ({
        grade,
        count,
    }));

    if (loading) {
        return <div>Loading...</div>;
    }

    return (
        <div className="container mx-auto p-4">
            <h1 className="text-2xl font-bold mb-6">Employee Dashboard</h1>

            {/* Grade Distribution Chart */}
            <div className="bg-white p-4 rounded-lg shadow mb-6">
                <h2 className="text-xl font-semibold mb-4">Grade Distribution</h2>
                <div className="h-[300px]">
                    <BarChart width={800} height={300} data={chartData}>
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis dataKey="grade" />
                        <YAxis />
                        <Tooltip />
                        <Legend />
                        <Bar dataKey="count" fill="#8884d8" name="Employees" />
                    </BarChart>
                </div>
            </div>

            {/* Employee Table */}
            <div className="bg-white rounded-lg shadow overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Department</TableHead>
                            <TableHead>Position</TableHead>
                            <TableHead>Grade</TableHead>
                            <TableHead>Step</TableHead>
                            <TableHead>Salary</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {employees.map((employee) => (
                            <TableRow key={employee.staff_id}>
                                <TableCell>{employee.NAME}</TableCell>
                                <TableCell>{employee.DEPTCD}</TableCell>
                                <TableCell>{employee.POST || 'N/A'}</TableCell>
                                <TableCell>{employee.GRADE}</TableCell>
                                <TableCell>{employee.STEP}</TableCell>
                                <TableCell>₦{employee.MBSAL.toLocaleString()}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                {/* Pagination */}
                <div className="flex justify-between items-center p-4">
                    <div>
                        Showing {(pagination.page - 1) * pagination.limit + 1} to {Math.min(pagination.page * pagination.limit, pagination.total)} of {pagination.total} entries
                    </div>
                    <div className="flex gap-2">
                        <button
                            className="px-4 py-2 border rounded hover:bg-gray-100 disabled:opacity-50"
                            disabled={pagination.page === 1}
                            onClick={() => setPagination(prev => ({ ...prev, page: prev.page - 1 }))}
                        >
                            Previous
                        </button>
                        <button
                            className="px-4 py-2 border rounded hover:bg-gray-100 disabled:opacity-50"
                            disabled={pagination.page * pagination.limit >= pagination.total}
                            onClick={() => setPagination(prev => ({ ...prev, page: prev.page + 1 }))}
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}