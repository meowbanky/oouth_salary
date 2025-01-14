// src/middleware/validators.ts
import { Request, Response, NextFunction } from 'express';
import { body, param, query, validationResult } from 'express-validator';

// Validation middleware
export const validate = (validations: any[]) => {
    return async (req: Request, res: Response, next: NextFunction) => {
        await Promise.all(validations.map(validation => validation.run(req)));

        const errors = validationResult(req);
        if (errors.isEmpty()) {
            return next();
        }

        return res.status(400).json({ errors: errors.array() });
    };
};

// Employee validations
export const employeeValidators = {
    getEmployees: [
        query('page')
            .optional()
            .isInt({ min: 1 })
            .withMessage('Page must be a positive integer'),
        query('limit')
            .optional()
            .isInt({ min: 1, max: 100 })
            .withMessage('Limit must be between 1 and 100'),
        query('department')
            .optional()
            .isInt()
            .withMessage('Department must be a number'),
        query('grade')
            .optional()
            .isString()
            .withMessage('Grade must be a string')
    ],

    getEmployeeById: [
        param('id')
            .isInt()
            .withMessage('Employee ID must be a number')
    ],

    // Add more validations as needed
};