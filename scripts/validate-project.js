#!/usr/bin/env node
/* eslint-disable no-console */
const fs = require( 'fs' );
const path = require( 'path' );

const root = path.resolve( __dirname, '..' );
const requireBuiltAssets = process.argv.includes( '--built' );
const errors = [];

function readJson( filePath ) {
	try {
		return JSON.parse( fs.readFileSync( filePath, 'utf8' ) );
	} catch ( error ) {
		errors.push(
			`${ path.relative( root, filePath ) }: ${ error.message }`
		);
		return null;
	}
}

function readThemeVersion() {
	const style = fs.readFileSync( path.join( root, 'style.css' ), 'utf8' );
	const match = style.match( /^[ \t]*Version:\s*(\S+)/m );
	return match ? match[ 1 ] : '';
}

const packageJson = readJson( path.join( root, 'package.json' ) );
const packageLock = readJson( path.join( root, 'package-lock.json' ) );
const themeVersion = readThemeVersion();

if ( ! themeVersion ) {
	errors.push( 'style.css: missing Version header' );
}

if ( packageJson && themeVersion !== packageJson.version ) {
	errors.push(
		`Version mismatch: style.css=${ themeVersion }, package.json=${ packageJson.version }`
	);
}

if (
	packageLock &&
	packageJson &&
	packageLock.version !== packageJson.version
) {
	errors.push(
		`Version mismatch: package-lock.json=${ packageLock.version }, package.json=${ packageJson.version }`
	);
}

if (
	themeVersion &&
	! fs.existsSync( path.join( root, 'releases', `v${ themeVersion }.md` ) )
) {
	errors.push( `Missing releases/v${ themeVersion }.md` );
}

const blocksRoot = path.join( root, 'blocks' );
const blockSlugs = fs
	.readdirSync( blocksRoot, { withFileTypes: true } )
	.filter(
		( entry ) =>
			entry.isDirectory() &&
			fs.existsSync( path.join( blocksRoot, entry.name, 'block.json' ) )
	)
	.map( ( entry ) => entry.name )
	.sort();

for ( const slug of blockSlugs ) {
	const sourceMetadataPath = path.join( blocksRoot, slug, 'block.json' );
	const sourceMetadata = readJson( sourceMetadataPath );

	if ( sourceMetadata && sourceMetadata.name !== `child/${ slug }` ) {
		errors.push(
			`${ path.relative(
				root,
				sourceMetadataPath
			) }: expected name child/${ slug }`
		);
	}

	if ( sourceMetadata && sourceMetadata.textdomain !== 'child' ) {
		errors.push(
			`${ path.relative(
				root,
				sourceMetadataPath
			) }: expected textdomain "child"`
		);
	}

	if ( requireBuiltAssets ) {
		const buildRoot = path.join( root, 'build', slug );
		for ( const filename of [
			'block.json',
			'index.js',
			'index.asset.php',
		] ) {
			if ( ! fs.existsSync( path.join( buildRoot, filename ) ) ) {
				errors.push( `Missing build/${ slug }/${ filename }` );
			}
		}
	}
}

if ( errors.length ) {
	console.error( errors.join( '\n' ) );
	process.exit( 1 );
}

console.log(
	`Validated ${ blockSlugs.length } blocks and project version ${ themeVersion }.`
);
