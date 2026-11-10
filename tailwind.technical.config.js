/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./resources/views/admin/system-overview.php'],
  prefix: 'tw-',
  important: '#technical-overview',
  corePlugins: {
    preflight: false,
  },
  theme: {
    extend: {},
  },
  plugins: [],
};
