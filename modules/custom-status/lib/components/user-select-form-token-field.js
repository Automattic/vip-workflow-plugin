import apiFetch from '@wordpress/api-fetch';
import { FormTokenField } from '@wordpress/components';
import { debounce } from '@wordpress/compose';
import { useEffect, useMemo, useState } from '@wordpress/element';
/**
 * Custom status component
 * @param object props
 */
export default function UserSelectFormTokenField( {
	requiredUsers,
	onUsersChanged,
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
		const proccessedTokens = [];
		selectedTokens.forEach( token => {
			if ( typeof token === 'string' || token instanceof String ) {
				// This is an unprocessed token that represents a string representation of
				// a user selected from the dropdown. Convert it to a TokenItem object.
				const user = getUserFromSuggestionString( token, searchedUsers );

				if ( user !== undefined ) {
					proccessedTokens.push( convertUserToToken( user ) );
				}
			} else {
				// This token has already been processed into a TokenItem.
				proccessedTokens.push( token );
			}
			return token;
		} );

		setSelectedUserTokens( proccessedTokens );

		const users = proccessedTokens.map( token => token.user );
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
		</>
	);
}

/**
 * Given a suggestion string like "Display Name (user_login)", return the associated user object from a set of users.
 * @param string suggestionString
 * @param object[] users
 * @return object|undefined
 */
const getUserFromSuggestionString = ( suggestionString, users ) => {
	// From a selection of "Display Name (user_login)", extract the user_login

	// Grab last ' (' in string
	const userLoginPart = suggestionString.split( ' (' ).pop();
	// Remove trailing ')'
	const userLogin = userLoginPart.split( ')' )[ 0 ];

	// Find the full user object that matches the user_login
	return users.find( user => user.slug === userLogin );
};

/**
 * Given a user object, convert it to a TokenItem object.
 * @param object user
 */
const convertUserToToken = user => {
	return {
		// In a TokenItem, the "title" is an HTML title displayed on hover
		title: user.slug,

		// The "value" is what's shown in the UI
		value: user.slug,

		// Store metadata about the user with this token so we can pass it to the parent component easily
		user,
	};
};
