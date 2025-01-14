import { useState, useEffect } from 'react';
import {
    BarChart, Bar, XAxis, YAxis, CartesianGrid,
    Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell
} from 'recharts';
import axios from 'axios';

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884d8'];

interface DashboardData {
    departmentStats: Array<{
        DEPTCD: number;
        total_employees: number;
        grade_count: number;
    }>;
    salaryStats: Array<any>;
    genderDistribution: Array<{
        GENDER: string;
        count: number;
    }>;
    qualificationStats: Array<any>;
}

export default function Dashboard() {
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState<DashboardData>({
        departmentStats: [],
        salaryStats: [],
        genderDistribution: [],
        qualificationStats: []
    });

    useEffect(() => {
        const fetchData = async () => {
            try {
                const [deptRes, salaryRes, genderRes, qualRes] = await Promise.all([
                    axios.get('http://localhost:3001/api/department-stats'),
                    axios.get('http://localhost:3001/api/salary-stats'),
                    axios.get('http://localhost:3001/api/gender-distribution'),
                    axios.get('http://localhost:3001/api/qualification-stats')
                ]);

                setData({
                    departmentStats: deptRes.data.data || [],
                    salaryStats: salaryRes.data.data || [],
                    genderDistribution: genderRes.data.data || [],
                    qualificationStats: qualRes.data.data || []
                });
            } catch (error) {
                console.error('Error fetching data:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, []);

    if (loading) {
        return <div className="flex items-center justify-center min-h-screen">Loading...</div>;
    }

    return (
        <div className="p-6">
            <h1 className="text-2xl font-bold mb-6">Dashboard</h1>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Department Statistics */}
                <div className="bg-white p-6 rounded-lg shadow">
                    <h2 className="text-lg font-semibold mb-4">Department Statistics</h2>
                    <div className="h-80">
                        <ResponsiveContainer>
                            <BarChart data={data.departmentStats}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="DEPTCD" />
                                <YAxis />
                                <Tooltip />
                                <Legend />
                                <Bar dataKey="total_employees" fill="#8884d8" name="Employees" />
                                <Bar dataKey="grade_count" fill="#82ca9d" name="Grades" />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Gender Distribution */}
                <div className="bg-white p-6 rounded-lg shadow">
                    <h2 className="text-lg font-semibold mb-4">Gender Distribution</h2>
                    <div className="h-80">
                        <ResponsiveContainer>
                            <PieChart>
                                <Pie
                                    data={data.genderDistribution}
                                    dataKey="count"
                                    nameKey="GENDER"
                                    cx="50%"
                                    cy="50%"
                                    outerRadius={100}
                                    label
                                >
                                    {data.genderDistribution.map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                    ))}
                                </Pie>
                                <Tooltip />
                                <Legend />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>
        </div>
    );
}