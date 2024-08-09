const defaultScriptsConfig = require( '@wordpress/scripts/config/webpack.config' );
const glob = require( 'glob' );
require( 'dotenv' ).config();

const wpScriptsModules = [ 'custom-status', 'secure-preview' ];
const wpScriptsModulesGlob =
	wpScriptsModules.length === 1 ? wpScriptsModules[ 0 ] : `{${ wpScriptsModules.join( ',' ) }}`;

const wpScriptsModulesEntries = glob
	.sync( `./modules/${ wpScriptsModulesGlob }/lib/*.js` )
	.reduce( ( acc, item ) => {
		const name = item.replace( /modules\/(.*)\/lib\/(.*).js/, '$1/$2' );
		acc[ `${ name }` ] = `./${ item }`;
		return acc;
	}, {} );

const host = process.env.HOST || 'localhost';

module.exports = [
	{
		...defaultScriptsConfig,
		entry: {
			...wpScriptsModulesEntries,
		},
		output: {
			...defaultScriptsConfig.output,
			path: __dirname + '/dist/modules/',
		},
		devServer: {
			...defaultScriptsConfig.devServer,
			host,
		},
	},
];
