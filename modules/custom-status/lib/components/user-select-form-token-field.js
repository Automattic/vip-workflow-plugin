import apiFetch from '@wordpress/api-fetch';
import { FormTokenField, BaseControl } from '@wordpress/components';
import { debounce } from '@wordpress/compose';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { sprintf } from '@wordpress/i18n';
/**
 * Custom status component
 * @param object props
 */
export default function UserSelectFormTokenField( {
	requiredUserLogins,
	onSelectionChange,
	help,
	...formTokenFieldProps
} ) {
	const [ userSearch, setUserSearch ] = useState( '' );
	const debouncedSetUserSearch = debounce( setUserSearch, 200 );
	const [ searchedUsers, setSearchedUsers ] = useState( [] );

	const [ selectedUserTokens, setSelectedUserTokens ] = useState(
		// Map login strings to TokenItem objects
		requiredUserLogins.map( userLogin => ( { title: userLogin, value: userLogin } ) )
	);

	useEffect( () => {
		if ( userSearch.trim().length === 0 ) {
			return;
		}

		const fetchUsers = async () => {
			const result = await apiFetch( {
				path: `/wp/v2/users?search=${ encodeURIComponent( userSearch ) }`,
			} );

			setSearchedUsers( result );
		};

		fetchUsers();
	}, [ userSearch ] );

	const suggestions = useMemo( () => {
		let usersToSuggest = searchedUsers;

		if ( searchedUsers.length > 0 && selectedUserTokens.length > 0 ) {
			// Remove already-selected users from suggestions
			const selectedUserMap = {};
			selectedUserTokens.forEach( token => {
				selectedUserMap[ token.value ] = true;
			} );

			usersToSuggest = searchedUsers.filter( user => ! ( user.user_login in selectedUserMap ) );
		}

		return usersToSuggest.map( user => {
			return `${ user.name } (${ user.slug })`;
		} );
	}, [ searchedUsers, selectedUserTokens ] );

	const handleOnChange = selectedTokens => {
		// When a user is selected from the dropdown, it's a string. Convert to a TokenItem object.
		const tokenItems = selectedTokens.map( token => {
			if ( typeof token === 'string' || token instanceof String ) {
				return convertUserStringToToken( token );
			}
			return token;
		} );

		setSelectedUserTokens( tokenItems );

		const userLogins = tokenItems.map( token => token.value );
		onSelectionChange( userLogins );
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

	// Grab last ' (' in string
	const userLoginPart = tokenString.split( ' (' ).pop();
	// Remove trailing ')'
	const userLogin = userLoginPart.split( ')' )[ 0 ];

	return {
		// In a TokenItem, the "title" is an HTML title displayed on hover
		title: userLogin,

		// The "value" is what's shown in the UI
		value: userLogin,
	};
};
