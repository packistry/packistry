import globals from 'globals'
import pluginJs from '@eslint/js'
import tseslint from 'typescript-eslint'
import pluginReact from 'eslint-plugin-react'
import pluginQuery from '@tanstack/eslint-plugin-query'
import { fixupConfigRules } from '@eslint/compat'

export default [
    ...pluginQuery.configs['flat/recommended'],
    { languageOptions: { globals: globals.browser } },
    pluginJs.configs.recommended,
    ...tseslint.configs.recommended,
    ...fixupConfigRules([pluginReact.configs.flat.recommended]),
    {
        rules: {
            'react/prop-types': 'off',
        },
    },
    {
        settings: {
            react: {
                version: 'detect', // Automatically picks the version you have installed
            },
        },
    },
]
