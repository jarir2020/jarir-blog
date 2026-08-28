/* eslint-env node */
module.exports = {
    root: true,
    env: {
        browser: true,
        es2022: true,
        node: true,
    },
    extends: [
        'eslint:recommended',
        'plugin:vue/vue3-recommended',
        'prettier',
    ],
    parserOptions: {
        ecmaVersion: 2022,
        sourceType: 'module',
    },
    rules: {
        // The project mixes PascalCase component files with camelCase
        // utility files. Allow either; just don't break a build.
        'vue/multi-word-component-names': 'off',
        'vue/no-reserved-component-names': 'off',
        'vue/no-multiple-template-root': 'off',
        'vue/html-self-closing': 'off',
        'vue/max-attributes-per-line': 'off',
        'vue/singleline-html-element-content-newline': 'off',
        'vue/html-indent': 'off',
        'vue/html-closing-bracket-newline': 'off',
        'vue/attributes-order': 'off',
        'vue/first-attribute-linebreak': 'off',
        'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
    },
    overrides: [
        {
            files: ['*.config.js', '*.cjs', '*.mjs'],
            env: { node: true, browser: false },
        },
    ],
    ignorePatterns: [
        'public/',
        'node_modules/',
        'vendor/',
        'storage/',
        'bootstrap/cache/',
    ],
};
