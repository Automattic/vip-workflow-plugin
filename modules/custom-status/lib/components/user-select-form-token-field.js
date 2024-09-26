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
	requiredUsers,
	onUsersChanged,
	help,
	...formTokenFieldProps
} ) {
	const [ userSearch, setUserSearch ] = useState( '' );
	const debouncedSetUserSearch = debounce( setUserSearch, 200 );
	const [ searchedUsers, setSearchedUsers ] = useState( [] );

	const [ selectedUserTokens, setSelectedUserTokens ] = useState(
		// Map login strings to TokenItem objects
		requiredUsers.map( user => ( { title: user.slug, value: user.slug, user } ) )
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

		if ( searchedUsers.length > 0 && requiredUsers.length > 0 ) {
			// Remove already-selected users from suggestions
			const selectedUserMap = {};
			requiredUsers.forEach( user => {
				selectedUserMap[ user.id ] = true;
			} );

			usersToSuggest = searchedUsers.filter( user => ! ( user.id in selectedUserMap ) );
		}

		return usersToSuggest.map( user => {
			return `${ user.name } (${ user.slug })`;
		} );
	}, [ searchedUsers, requiredUsers ] );

	const handleOnChange = selectedTokens => {
		// When a user is selected from the dropdown, it's a string. Convert to a TokenItem object.
		const tokenItems = selectedTokens.map( token => {
			if ( typeof token === 'string' || token instanceof String ) {
				return convertSearchedUserToToken( token, searchedUsers );
			}
			return token;
		} );

		setSelectedUserTokens( tokenItems );

		const users = tokenItems.map( token => token.user );
		onUsersChanged( users );
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
const convertSearchedUserToToken = ( tokenString, searchedUsers ) => {
	// From a selection of "Display Name (user_login)", extract the user_login

	// Grab last ' (' in string
	const userLoginPart = tokenString.split( ' (' ).pop();
	// Remove trailing ')'
	const userLogin = userLoginPart.split( ')' )[ 0 ];

	// Find the full user object that matches the user_login
	const matchingUser = searchedUsers.find( user => user.slug === userLogin );

	return {
		// In a TokenItem, the "title" is an HTML title displayed on hover
		title: userLogin,

		// The "value" is what's shown in the UI
		value: userLogin,

		// Store metadata about the user with this token so we can pass it to the parent component easily
		user: matchingUser,
	};
};
