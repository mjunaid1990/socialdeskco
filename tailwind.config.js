/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/views/**/*.blade.php",
    "./resources/views/**/*.js",
    "./resources/views/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        // Add custom colors if needed
        'ai-indigo': '#4f46e5',
        'ai-purple': '#9333ea',
        'ai-green': '#16a34a',
        'ai-blue': '#2563eb',
        'ai-yellow': '#eab308',
        'ai-gray': '#6b7280',
      }
    }
  }
}
