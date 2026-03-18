module.exports = {
  content: [
    "./app/Views/**/*.php",
    "./app/Controllers/**/*.php",
    "./public/assets/app.js"
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: "#ecfdf5",
          100: "#d1fae5",
          500: "#10b981",
          600: "#059669",
          700: "#047857"
        },
        ink: {
          950: "#0f172a"
        },
        surface: {
          50: "#f8fafc",
          100: "#f1f5f9",
          200: "#e2e8f0"
        }
      },
      borderRadius: {
        app: "1rem",
        chip: "9999px"
      },
      boxShadow: {
        app: "0 8px 24px rgba(15, 23, 42, 0.08)",
        dock: "0 -8px 20px rgba(15, 23, 42, 0.08)"
      },
      keyframes: {
        "slide-up-soft": {
          "0%": { opacity: "0", transform: "translateY(12px)" },
          "100%": { opacity: "1", transform: "translateY(0)" }
        },
        "fade-in-soft": {
          "0%": { opacity: "0" },
          "100%": { opacity: "1" }
        }
      },
      animation: {
        "slide-up-soft": "slide-up-soft 200ms ease-out",
        "fade-in-soft": "fade-in-soft 180ms ease-out"
      }
    }
  },
  plugins: []
};

