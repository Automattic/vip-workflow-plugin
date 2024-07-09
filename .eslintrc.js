require( '@automattic/eslint-plugin-wpvip/init' );

module.exports = {
	extends: [ 'plugin:@automattic/wpvip/recommended' ],
	root: true,
	env: {
		jest: true,
	},
	rules: {
		camelcase: 0,
		'no-undef': 0,
		'no-shadow': 0,
		'no-unused-vars': 0,
		'prefer-const': 0,
	},
	parser: '@babel/eslint-parser',
	parserOptions: {
		babelOptions: {
			presets: [ '@wordpress/babel-preset-default', '@babel/preset-react' ],
		},
	},
};
