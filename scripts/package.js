#!/usr/bin/env node
/* eslint-disable no-console */
const fs = require( 'fs' );
const fsp = fs.promises;
const path = require( 'path' );
const { spawnSync } = require( 'child_process' );

const root = process.cwd();
const distRoot = path.join( root, 'dist' );
const themeSlug = 'twentytwentyfive-child';
const outDir = path.join( distRoot, themeSlug );
const blockSlugs = fs
	.readdirSync( path.join( root, 'blocks' ), { withFileTypes: true } )
	.filter(
		( entry ) =>
			entry.isDirectory() &&
			fs.existsSync(
				path.join( root, 'blocks', entry.name, 'block.json' )
			)
	)
	.map( ( entry ) => entry.name )
	.sort();

async function rimraf( p ) {
	if ( fs.existsSync( p ) ) {
		await fsp.rm( p, { recursive: true, force: true } );
	}
}

async function ensureDir( p ) {
	await fsp.mkdir( p, { recursive: true } );
}

async function safeCopy( src, dest ) {
	if ( ! fs.existsSync( src ) ) {
		return;
	}
	await ensureDir( path.dirname( dest ) );
	await fsp.cp( src, dest, { recursive: true } );
}

async function copyIfExists( src, dest ) {
	if ( ! fs.existsSync( src ) ) {
		return;
	}
	await safeCopy( src, dest );
}

async function copyBlocksRuntimePhp() {
	const blocksDir = path.join( root, 'blocks' );
	if ( ! fs.existsSync( blocksDir ) ) {
		return;
	}
	const entries = await fsp.readdir( blocksDir, { withFileTypes: true } );
	for ( const ent of entries ) {
		if ( ent.isDirectory() ) {
			for ( const runtimeFile of [ 'render.php', 'utils.php' ] ) {
				const source = path.join( blocksDir, ent.name, runtimeFile );
				if ( fs.existsSync( source ) ) {
					const dest = path.join(
						outDir,
						'blocks',
						ent.name,
						runtimeFile
					);
					await safeCopy( source, dest );
				}
			}
		}
	}
}

function assertPathExists( targetPath, label ) {
	if ( ! fs.existsSync( targetPath ) ) {
		throw new Error( `Missing required ${ label }: ${ targetPath }` );
	}
}

function runArchive( command, args ) {
	const result = spawnSync( command, args, {
		cwd: distRoot,
		encoding: 'utf8',
	} );

	if ( result.error ) {
		throw result.error;
	}

	if ( result.status !== 0 ) {
		throw new Error(
			result.stderr ||
				`${ command } exited with status ${ result.status }`
		);
	}
}

function validateBuild() {
	for ( const slug of blockSlugs ) {
		const blockDir = path.join( root, 'build', slug );
		assertPathExists(
			path.join( blockDir, 'block.json' ),
			`compiled block metadata for ${ slug }`
		);
		assertPathExists(
			path.join( blockDir, 'index.js' ),
			`compiled editor script for ${ slug }`
		);
		assertPathExists(
			path.join( blockDir, 'index.asset.php' ),
			`compiled asset manifest for ${ slug }`
		);
	}
}

async function main() {
	validateBuild();
	await ensureDir( distRoot );
	await rimraf( outDir );
	await ensureDir( outDir );

	// Core theme files
	await safeCopy(
		path.join( root, 'style.css' ),
		path.join( outDir, 'style.css' )
	);
	await safeCopy(
		path.join( root, 'functions.php' ),
		path.join( outDir, 'functions.php' )
	);

	// Runtime assets
	await copyIfExists(
		path.join( root, 'screenshot.png' ),
		path.join( outDir, 'screenshot.png' )
	);

	// PHP modules and built blocks
	await copyIfExists( path.join( root, 'inc' ), path.join( outDir, 'inc' ) );
	await copyIfExists(
		path.join( root, 'build' ),
		path.join( outDir, 'build' )
	);
	await copyBlocksRuntimePhp();

	// Optional readme
	await copyIfExists(
		path.join( root, 'README.md' ),
		path.join( outDir, 'README.md' )
	);

	// Create zip
	const zipPath = path.join( distRoot, `${ themeSlug }.zip` );
	await rimraf( zipPath );
	try {
		runArchive( 'zip', [ '-rq', `${ themeSlug }.zip`, themeSlug ] );
		console.log( `Created ${ zipPath }` );
	} catch {
		console.warn( 'zip not available, creating tar.gz instead' );
		runArchive( 'tar', [ '-czf', `${ themeSlug }.tar.gz`, themeSlug ] );
		console.log(
			`Created ${ path.join( distRoot, themeSlug + '.tar.gz' ) }`
		);
	}
}

main().catch( ( err ) => {
	console.error( err );
	process.exit( 1 );
} );
