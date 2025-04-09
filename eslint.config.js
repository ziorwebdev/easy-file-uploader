import js from "@eslint/js";
import globals from "globals";
import sortKeysFix from "eslint-plugin-sort-keys-fix";

export default [
	js.configs.recommended,
	{
		languageOptions: {
			ecmaVersion: "latest",
			sourceType: "module",
			globals: {
				...globals.browser,
				$: "readonly",
				console: "readonly",
				document: "readonly",
				elementorFrontend: "readonly",
				jQuery: "readonly",
				EasyDragDropUploader: "readonly",
				window: "readonly",
			},
		},
		plugins: {
			"sort-keys-fix": sortKeysFix,
		},
		rules: {
			// Enforce Alphabetical Order of Object Keys
			"sort-keys": "off",
			"sort-keys-fix/sort-keys-fix": "error",

			// Best Practices (JSLint style)
			"strict": ["error", "global"], // Require 'use strict' globally
			"curly": ["error", "all"], // Require curly braces
			"eqeqeq": ["error", "always"], // Require === and !==
			"no-alert": "error",
			"no-console": "off", // Allow console for debugging
			"no-constant-condition": "error",
			"no-debugger": "error",
			"no-duplicate-imports": "error",
			"no-implicit-globals": "error",
			"no-magic-numbers": ["warn", { ignore: [-1, 0, 1] }],
			"no-return-await": "error",
			"no-unused-vars": ["warn", { args: "none" }],
			"no-useless-constructor": "error",
			"no-var": "error",
			"no-throw-literal": "error",
			"no-with": "error",
			"no-bitwise": "error",
			"no-multi-str": "error",
			"no-empty": "error",
			"no-continue": "error",
			// "no-plusplus": "error",
			"no-template-curly-in-string": "warn",
			"dot-notation": "error",
			// "yoda": ["error", "never"],

			// Code Style
			"arrow-spacing": ["error", { before: true, after: true }],
			// "comma-dangle": ["error", "always-multiline"],
			"consistent-return": "error",
			"indent": ["error", 4], // Enforce 4-space indentation
			// "max-len": [
			// "error",
			// {
			// code: 80,
			// tabWidth: 4,
			// ignoreUrls: false,
			// ignoreStrings: false,
			// ignoreTemplateLiterals: false,
			// ignoreComments: false,
			// },
			// ],
			"object-curly-spacing": ["error", "always"],
			"quotes": ["error", "double", { allowTemplateLiterals: true }],
			"semi": ["error", "always"],
			"no-trailing-spaces": "error",
			// "eol-last": ["error", "always"],
			"prefer-const": "error",
			"prefer-template": "error",
		},
	},
	];