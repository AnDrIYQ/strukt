import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: './resources/js/tests/setup.js',
        include: ['resources/js/**/*.spec.{js,ts}'],
    }
})
