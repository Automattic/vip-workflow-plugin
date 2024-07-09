require( '@automattic/eslint-plugin-wpvip/init' );

module.exports = {
	parser: '@babel/eslint-parser',
	parserOptions: {
		babelOptions: {
			presets: [ '@wordpress/babel-preset-default', '@babel/preset-react' ],
		},
	},
	extends: [ 'plugin:@automattic/wpvip/recommended' ],
	root: true,
	env: {
		jest: true,
	},
	settings: {
		'import/resolver': {
			node: {
				extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
			},
			webpack: {
				config: 'webpack.config.js',
			},
		},
	},
	rules: {
		camelcase: 0,
		'no-undef': 0,
		'no-shadow': 0,
		'no-unused-vars': 0,
		'prefer-const': 0,
		'id-length': 0,
	},
};
