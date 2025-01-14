// src/controllers/employeeController.ts
import { Request, Response } from 'express';
import { RowDataPacket } from 'mysql2';
import pool from '../config/db';

interface Employee extends RowDataPacket {
    staff_id: number;
    NAME: string;
    GENDER: string | null;
    DOB: Date | null;
    DOPA: Date | null;
    DEPTCD: number;
    POST: string | null;
    GRADE: string;
    STEP: string;
    MBSAL: number;
}

interface CountResult extends RowDataPacket {
    total: number;
}

export const employeeController = {
    async getEmployees(req: Request, res: Response) {
        try {
            const { page = 1, limit = 10, department, grade } = req.query;
            const offset = (Number(page) - 1) * Number(limit);

            // Base query
            let baseConditions = ['STATUSCD = "A"'];
            const params: any[] = [];

            if (department) {
                baseConditions.push('DEPTCD = ?');
                params.push(department);
            }

            if (grade) {
                baseConditions.push('GRADE = ?');
                params.push(grade);
            }

            const whereClause = baseConditions.join(' AND ');

            // Count total records
            const [countRows] = await pool.execute<CountResult[]>(
                `SELECT COUNT(*) as total FROM employee WHERE ${whereClause}`,
                params
            );

            // Get paginated data
            const [employees] = await pool.execute<Employee[]>(
                `SELECT staff_id, NAME, GENDER, DOB, DOPA, DEPTCD, POST, GRADE, STEP, MBSAL 
                FROM employee 
                WHERE ${whereClause}
                LIMIT ? OFFSET ?`,
                [...params, limit, offset]
            );

            return res.json({
                status: 'success',
                data: employees,
                pagination: {
                    total: Number(countRows[0].total),
                    page: Number(page),
                    limit: Number(limit)
                }
            });
        } catch (error) {
            console.error('Database error:', error);
            return res.status(500).json({
                status: 'error',
                message: 'Failed to fetch employees'
            });
        }
    },

    async getDepartmentStats(req: Request, res: Response) {
        try {
            const [rows] = await pool.execute<RowDataPacket[]>(`
                SELECT 
                    DEPTCD,
                    COUNT(*) as total_employees,
                    ROUND(AVG(MBSAL), 2) as avg_salary,
                    COUNT(DISTINCT GRADE) as grade_count
                FROM employee
                WHERE STATUSCD = 'A'
                GROUP BY DEPTCD
                ORDER BY DEPTCD
            `);

            return res.json({
                status: 'success',
                data: rows
            });
        } catch (error) {
            console.error('Database error:', error);
            return res.status(500).json({
                status: 'error',
                message: 'Failed to fetch department statistics'
            });
        }
    },

    async getQualificationStats(req: Request, res: Response) {
        try {
            const [rows] = await pool.execute<RowDataPacket[]>(`
                SELECT
                    COALESCE(q.quaification, 'Not Specified') as qualification_name,
                    COUNT(sq.id) as count
                FROM qualification q
                         LEFT JOIN staff_qualification sq ON q.id = sq.qua_id
                GROUP BY q.id, q.quaification
                ORDER BY count DESC
            `);

            return res.json({
                status: 'success',
                data: rows
            });
        } catch (error) {
            console.error('Database error:', error);
            return res.status(500).json({
                status: 'error',
                message: 'Failed to fetch qualification statistics'
            });
        }
    },

    async getRetirementProjections(req: Request, res: Response) {
        try {
            const [rows] = await pool.execute<RowDataPacket[]>(`
                SELECT
                    staff_id, NAME, DOB, DOPA, POST,
                    LEAST(
                            DATE_ADD(DOB, INTERVAL 60 YEAR),
                            DATE_ADD(DOPA, INTERVAL 35 YEAR)
                    ) as retirement_date
                FROM employee
                WHERE STATUSCD = 'A'
                HAVING retirement_date <= DATE_ADD(CURRENT_DATE, INTERVAL 5 YEAR)
                ORDER BY retirement_date
            `);

            return res.json({
                status: 'success',
                data: rows
            });
        } catch (error) {
            console.error('Database error:', error);
            return res.status(500).json({
                status: 'error',
                message: 'Failed to fetch retirement projections'
            });
        }
    }
};