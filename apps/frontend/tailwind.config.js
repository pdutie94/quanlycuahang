/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,ts,js}'],
  theme: {
    extend: {
      colors: {
        surface: '#f7f5ef',
        ink: '#1f2421',
        pine: '#1f5c42',
        clay: '#c76f3d',
      },
      fontFamily: {
        sans: ['"Lexend"', '"Segoe UI"', 'sans-serif'],
      },
      boxShadow: {
        card: '0 10px 30px rgba(0,0,0,0.08)',
      },
    },
  },
  plugins: [],
}
