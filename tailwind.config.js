module.exports = {
  content: [
    "./app/Views/**/*.php",
    "./app/Controllers/**/*.php",
    "./public/assets/app.js"
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ["ui-sans-serif", "system-ui", "sans-serif", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"],
        display: ["ui-sans-serif", "system-ui", "sans-serif", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"]
      },
      colors: {
        brand: {
          50: "#eef9ff",
          100: "#d9f1ff",
          200: "#b9e5ff",
          300: "#8fd5ff",
          400: "#57bdff",
          500: "#1d9bf0",
          600: "#0b7fd0",
          700: "#0a67a8",
          800: "#0c4f7e",
          900: "#103f62"
        }
      },
      borderRadius: {
        app: "1.125rem",
        chip: "9999px",
        card: "1.25rem"
      },
      boxShadow: {
        app: "0 0 0 1px rgba(15, 23, 42, 0.08)",
        dock: "0 -1px 0 rgba(15, 23, 42, 0.08)"
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

