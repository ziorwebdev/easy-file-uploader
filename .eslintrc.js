module.exports = {
    "extends": ["wordpress", "plugin:jsdoc/recommended"],
    "plugins": ["perfectionist", "jsdoc"],
    "env": {
        "browser": true,
        "es6": true,
        "node": true
    },
    "parserOptions": {
        "ecmaVersion": 2020,
        "sourceType": "module"
    },
    "rules": {
        "eqeqeq": ["error", "always"],
        "semi": ["error", "always"],
        "quotes": ["error", "double"],
        "no-trailing-spaces": "error",
        "perfectionist/sort-objects": [
            "error",
            {
                "order": "asc",
                "type": "natural"
            }
        ],
        "space-in-parens": ["error", "never"],
        "array-bracket-spacing": ["error", "never"],
        "object-curly-spacing": ["error", "never"],
        "indent": ["error", 4],

        // Require JSDoc for function documentation
        "jsdoc/require-jsdoc": [
            "error",
            {
                "require": {
                    "FunctionDeclaration": true,
                    "MethodDefinition": true,
                    "ClassDeclaration": true,
                    "ArrowFunctionExpression": false, // Set to true if you want arrow functions documented
                    "FunctionExpression": false
                }
            }
        ],

        // Ensure JSDoc comments are valid
        "jsdoc/check-alignment": "error",
        "jsdoc/check-param-names": "error",
        "jsdoc/check-tag-names": "error",
        "jsdoc/check-types": "error",
        "jsdoc/implements-on-classes": "error",
        "jsdoc/no-undefined-types": "error",
        "jsdoc/require-description": "error",
        "jsdoc/require-param": "error",
        "jsdoc/require-param-description": "error",
        "jsdoc/require-param-name": "error",
        "jsdoc/require-param-type": "error",
        "jsdoc/require-returns": "error",
        "jsdoc/require-returns-check": "error",
        "jsdoc/require-returns-description": "error",
        "jsdoc/require-returns-type": "error",

        // Enforce Unused Variables & Functions
        "no-unused-vars": ["error", {
            "vars": "all",
            "args": "after-used",
            "ignoreRestSiblings": false
        }],

        // Enforce Unused Private Class Members
        "no-unused-private-class-members": "error"
    }
};
