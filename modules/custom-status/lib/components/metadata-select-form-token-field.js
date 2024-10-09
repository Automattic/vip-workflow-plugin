import { BaseControl, FormTokenField } from '@wordpress/components';
import { debounce } from '@wordpress/compose';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
/**
 * Custom status component
 * @param object props
 */
export default function MetadataSelectFormTokenField( {
	requiredMetadatas,
	editorialMetadatas,
	onMetadatasChanged,
	help,
	...formTokenFieldProps
} ) {
	const [ metadataSearch, setMetadataSearch ] = useState( '' );
	const debouncedSetMetadataSearch = debounce( setMetadataSearch, 200 );
	const [ searchedMetadatas, setSearchedMetadatas ] = useState( [] );

	const [ selectedMetadataTokens, setSelectedMetadataTokens ] = useState(
		// Map login strings to TokenItem objects
		requiredMetadatas.map( requiredMetadata => ( {
			title: requiredMetadata.name,
			value: requiredMetadata.name,
			metadata: requiredMetadata,
		} ) )
	);

	useEffect( () => {
		if (
			metadataSearch.trim().length === 0 ||
			! editorialMetadatas ||
			editorialMetadatas.length === 0
		) {
			return;
		}

		const matchedMetadatas = editorialMetadatas.filter( metadata =>
			metadata.name.toLowerCase().includes( metadataSearch.toLowerCase() )
		);

		setSearchedMetadatas( matchedMetadatas );
	}, [ editorialMetadatas, metadataSearch ] );

	const suggestions = useMemo( () => {
		let metadatasToSuggest = searchedMetadatas;

		if ( searchedMetadatas.length > 0 && requiredMetadatas.length > 0 ) {
			// Remove already-selected editorial metadatas from suggestions
			const selectedMetadataMap = {};
			requiredMetadatas.forEach( metadata => {
				selectedMetadataMap[ metadata.id ] = true;
			} );

			metadatasToSuggest = searchedMetadatas.filter(
				metadata => ! ( metadata.term_id in selectedMetadataMap )
			);
		}

		return metadatasToSuggest.map( metadata => {
			return `${ metadata.name }`;
		} );
	}, [ searchedMetadatas, requiredMetadatas ] );

	const handleOnChange = selectedTokens => {
		const proccessedTokens = [];
		selectedTokens.forEach( token => {
			if ( typeof token === 'string' || token instanceof String ) {
				// This is an unprocessed token that represents a string representation of
				// a metadata selected from the dropdown. Convert it to a TokenItem object.
				const metadata = searchedMetadatas.find( metadata => metadata.name === token );

				if ( metadata !== undefined ) {
					proccessedTokens.push( convertMetadataToToken( metadata ) );
				}
			} else {
				// This token has already been processed into a TokenItem.
				proccessedTokens.push( token );
			}
			return token;
		} );

		setSelectedMetadataTokens( proccessedTokens );

		const metadatas = proccessedTokens.map( token => token.metadata );
		onMetadatasChanged( metadatas );
	};

	return (
		<>
			<FormTokenField
				{ ...formTokenFieldProps }
				onChange={ handleOnChange }
				onInputChange={ debouncedSetMetadataSearch }
				suggestions={ suggestions }
				value={ selectedMetadataTokens }
				// Remove "Separate with commas or the Enter key" text that doesn't apply here
				__experimentalShowHowTo={ false }
				// Auto-select first match, so that it's possible to press <Enter> and immediately choose it
				__experimentalAutoSelectFirstMatch={ true }
				__experimentalExpandOnFocus={ true }
			/>
			<BaseControl
				help={ __(
					'Select editorial metadata fields that users must complete to proceed.',
					'vip-workflow'
				) }
			/>
		</>
	);
}

/**
 * Given a metadata object, convert it to a TokenItem object.
 * @param object metadata
 */
const convertMetadataToToken = metadata => {
	return {
		// In a TokenItem, the "title" is an HTML title displayed on hover
		title: metadata.name,

		// The "value" is what's shown in the UI
		value: metadata.name,

		// Store the metadata with this token so we can pass it to the parent component easily
		metadata,
	};
};
