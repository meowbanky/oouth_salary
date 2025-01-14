// src/types/env.d.ts
declare global {
    namespace NodeJS {
        interface ProcessEnv {
            DB_HOST: string;
            DB_USER: string;
            DB_PASSWORD: string;
            DB_DATABASE: string;
            PORT: string;
            NODE_ENV: 'development' | 'production';
        }
    }
}