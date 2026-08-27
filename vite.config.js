import tailwindcss from '@tailwindcss/vite'

export default {
  plugins: [tailwindcss()],
  build: {
    assetsDir: '',
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      input: ['resources/js/mail-log.js', 'resources/css/mail-log.css'],
      output: {
        assetFileNames: '[name][extname]',
        entryFileNames: '[name].js',
      },
    },
  },
}
