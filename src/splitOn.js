// Based on https://stackoverflow.com/a/61397091/4285005
export const splitOn = ( slicable, ...indices ) =>
	[ 0, ...indices ].map( ( n, i, m ) => slicable.slice( n, m[ i + 1 ] ) );
