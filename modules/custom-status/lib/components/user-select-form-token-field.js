import apiFetch from '@wordpress/api-fetch';
import { FormTokenField, BaseControl } from '@wordpress/components';
import { debounce } from '@wordpress/compose';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
/**
 * Custom status component
 * @param object props
 */
export default function UserSelectFormTokenField( {
	requiredUserLoginToIdMap,
	onUserIdSelectionChange,
	help,
	...formTokenFieldProps
} ) {
	const [ userSearch, setUserSearch ] = useState( '' );
	const debouncedSetUserSearch = debounce( setUserSearch, 200 );
	const [ searchedUsers, setSearchedUsers ] = useState( [] );
	const [ userLoginToUserIdMap, setUserLoginToUserIdMap ] = useState( requiredUserLoginToIdMap );

	const [ selectedUserTokens, setSelectedUserTokens ] = useState(
		Object.keys( requiredUserLoginToIdMap ).map( userLogin => {
			return {
				title: userLogin,
				value: userLogin,
			};
		} )
	);

	useEffect( () => {
		if ( userSearch.trim().length === 0 ) {
			return;
		}

		const fetchUsers = async () => {
			const userSearchUrl = sprintf(
				'%s%s',
				VW_CUSTOM_STATUS_CONFIGURE.url_search_user,
				userSearch
			);

			const result = await apiFetch( {
				url: userSearchUrl,
			} );

			setSearchedUsers( result );

			// Create a map of user_login to user ID, so we can easily pass user IDs back to the parent component
			const userLoginMap = {};
			for ( const user of result ) {
				userLoginMap[ user.user_login ] = user.ID;
			}
			setUserLoginToUserIdMap( userLoginMap );
		};

		fetchUsers();
	}, [ userSearch ] );

	const suggestions = useMemo( () => {
		return searchedUsers.map( user => {
			return `${ user.display_name } (${ user.user_login })`;
		} );
	}, [ searchedUsers ] );

	const handleOnChange = selectedTokens => {
		// When a user is selected from the dropdown, it's a string. Convert to a TokenItem object.
		const tokenItems = selectedTokens.map( token => {
			if ( typeof token === 'string' || token instanceof String ) {
				return convertUserStringToToken( token );
			}
			return token;
		} );

		setSelectedUserTokens( tokenItems );

		const userIds = tokenItems.map( token => userLoginToUserIdMap[ token.value ] );
		onUserIdSelectionChange( userIds );
	};

	return (
		<>
			<FormTokenField
				{ ...formTokenFieldProps }
				onChange={ handleOnChange }
				onInputChange={ debouncedSetUserSearch }
				suggestions={ suggestions }
				value={ selectedUserTokens }
				// Remove "Separate with commas or the Enter key" text that doesn't apply here
				__experimentalShowHowTo={ false }
				// Auto-select first match, so that it's possible to press <Enter> and immediately choose it
				__experimentalAutoSelectFirstMatch={ true }
			/>

			{ /* <FormTokenField> doesn't support help text. Provide a BaseControl with the help text instead. */ }
			{ help && <BaseControl help={ help }></BaseControl> }
		</>
	);
}

/**
 * Given a tokenString like "Display Name (user_login)", convert it to a TokenItem object.
 * @param string tokenString
 */
const convertUserStringToToken = tokenString => {
	// From a selection of "Display Name (user_login)", extract the user_login
	const userLogin = tokenString.split( '(' )[ 1 ].split( ')' )[ 0 ];

	return {
		// In a TokenItem, the "title" is an HTML title displayed on hover
		title: userLogin,

		// The "value" is what's shown in the UI
		value: userLogin,
	};
};
