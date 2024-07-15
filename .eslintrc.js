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
		'id-length': 0,
	},
	parser: '@babel/eslint-parser',
	parserOptions: {
		babelOptions: {
			presets: [ '@babel/preset-react' ],
		},
	},
};
