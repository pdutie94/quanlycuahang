module.exports = {
  content: [
    "./app/Views/**/*.php",
    "./app/Controllers/**/*.php",
    "./public/assets/app.js"
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ["Roboto Flex", "Roboto", "ui-sans-serif", "system-ui", "sans-serif", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"],
        display: ["Lexend", "Roboto Flex", "ui-sans-serif", "system-ui", "sans-serif", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"]
      },
      colors: {
        brand: {
          50: "#f0fdfa",
          100: "#ccfbf1",
          200: "#99f6e4",
          300: "#5eead4",
          400: "#2dd4bf",
          500: "#14b8a6",
          600: "#0d9488",
          700: "#0f766e",
          800: "#115e59",
          900: "#134e4a"
        }
      },
      borderRadius: {
        app: "1rem",
        chip: "1rem",
        card: "1rem"
      },
      boxShadow: {
        app: "none",
        dock: "none"
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

