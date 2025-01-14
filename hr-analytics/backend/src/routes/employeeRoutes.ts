// src/routes/employeeRoutes.ts
import express from 'express';
import { employeeController } from '../controllers/employeeController';

const router = express.Router();

// Employee routes
router.get('/employees', employeeController.getEmployees);
router.get('/employees/:id', employeeController.getEmployeeById);
router.get('/department-stats', employeeController.getDepartmentStats);
router.get('/retirement-projections', employeeController.getRetirementProjections);
router.get('/qualification-stats', employeeController.getQualificationStats);

export default router;