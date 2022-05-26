const colors = require('tailwindcss/colors')

module.exports = {
    purge: ['./src/**/*.{vue,js,blade.php}'],
    darkMode: false,
    theme: {
        extend: {
            colors: {
                amber: colors.amber,
                emerald: colors.emerald,
            }
        }
    },
    variants: {
        extend: {},
    },
    plugins: [],
}
