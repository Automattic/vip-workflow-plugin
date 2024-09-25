import {
	Button,
	DateTimePicker,
	__experimentalDivider as Divider,
	Dropdown,
	Flex,
	__experimentalHeading as Heading,
	__experimentalHStack as HStack,
	TextControl,
	ToggleControl,
	__experimentalTruncate as Truncate,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { dateI18n, getDate, getSettings } from '@wordpress/date';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { useMemo, useState } from '@wordpress/element';
import { __, _x, isRTL } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';
import './editor.scss';

const editorialMetadatas = window.VW_EDITORIAL_METADATA.editorial_metadata_terms.map(
	editorialMetadata => ( {
		key: editorialMetadata.meta.postmeta_key,
		label: editorialMetadata.name,
		type: editorialMetadata.meta.type,
		term_id: editorialMetadata.term_id,
		description: editorialMetadata.description,
	} )
);

// Check if there are any editorial metadatas to show
const editorialMetadatasToShow = editorialMetadatas.length !== 0;

/**
 * This component is the main component that renders the custom meta panel in the post summary sidebar.
 */
const CustomMetaPanel = ( { metaFields, setMetaFields } ) => (
	<PluginPostStatusInfo className="vip-workflow-editorial-metadata">
		<div className="vip-workflow-editorial-metadata-row">
			<Divider />
			<VStack spacing={ 4 }>
				{ editorialMetadatasToShow &&
					editorialMetadatas.map( editorialMetadata =>
						getComponentByType( editorialMetadata, metaFields, setMetaFields )
					) }
			</VStack>
		</div>
	</PluginPostStatusInfo>
);

/**
 * Get the component based on the type of the editorial metadata.
 *
 * Currently supported types are:
 * - checkbox
 * - text
 * - date
 */
const getComponentByType = ( editorialMetadata, metaFields, setMetaFields ) => {
	switch ( editorialMetadata.type ) {
		case 'checkbox':
			return (
				<CheckboxComponent
					key={ editorialMetadata.key }
					editorialMetadata={ editorialMetadata }
					metaFields={ metaFields }
					setMetaFields={ setMetaFields }
				/>
			);
		case 'text':
			return (
				<TextComponent
					key={ editorialMetadata.key }
					editorialMetadata={ editorialMetadata }
					metaFields={ metaFields }
					setMetaFields={ setMetaFields }
				/>
			);
		case 'date':
			return (
				<DateComponent
					key={ editorialMetadata.key }
					editorialMetadata={ editorialMetadata }
					metaFields={ metaFields }
					setMetaFields={ setMetaFields }
				/>
			);
		default:
			return null;
	}
};

/**
 * This component renders a checkbox component.
 */
const CheckboxComponent = ( { editorialMetadata, metaFields, setMetaFields } ) => {
	return (
		<HStack __nextHasNoMarginBottom>
			<label title={ editorialMetadata.description }>{ editorialMetadata.label }</label>
			<ToggleControl
				__nextHasNoMarginBottom
				checked={ metaFields?.[ editorialMetadata.key ] }
				onChange={ value =>
					setMetaFields( {
						...metaFields,
						[ editorialMetadata.key ]: value,
					} )
				}
			/>
		</HStack>
	);
};

/**
 * Get the popover props for the dropdown component. It's been memoized to avoid re-rendering the popover.
 */
const getMemoizedPopoverProps = ( { popoverAnchor, text } ) => {
	return useMemo(
		() => ( {
			// Anchor the popover to the middle of the entire row so that it doesn't
			// move around when the label changes.
			anchor: popoverAnchor,
			'aria-label': text,
			placement: 'left-start',
			offset: 36,
			shift: true,
		} ),
		[ popoverAnchor ]
	);
};

/**
 * Get the dropdown button component, which is the button that triggers the popover. The button text is truncated if it's too long.
 */
const getDropdownButton = ( { editorialMetadata, label, onToggle, isOpen, shouldTruncate } ) => {
	return (
		<HStack __nextHasNoMarginBottom>
			<label title={ editorialMetadata.description }>{ editorialMetadata.label }</label>
			<Button
				// Gutenberg uses whiteSpace: nowrap, but we need to wrap the text so it has to be set here so as to not be overriden
				style={ { whiteSpace: 'normal' } }
				size="compact"
				variant="tertiary"
				onClick={ onToggle }
				aria-label={ editorialMetadata.label }
				aria-expanded={ isOpen }
			>
				{ shouldTruncate ? (
					<Truncate limit={ 15 } ellipsizeMode="tail">
						{ label }
					</Truncate>
				) : (
					label
				) }
			</Button>
		</HStack>
	);
};

/**
 * This component renders a dropdown component, that is a button. When clicked, it shows a popover with a textarea.
 */
const TextComponent = ( { editorialMetadata, metaFields, setMetaFields } ) => {
	const [ popoverAnchor, setPopoverAnchor ] = useState( null );

	const popoverProps = getMemoizedPopoverProps( { popoverAnchor, text: __( 'Enter Text' ) } );

	const label = metaFields?.[ editorialMetadata.key ] || __( 'None' );

	const shouldTruncate = true;

	return (
		<Dropdown
			ref={ setPopoverAnchor }
			popoverProps={ popoverProps }
			focusOnMount
			renderToggle={ ( { onToggle, isOpen } ) =>
				getDropdownButton( { editorialMetadata, label, onToggle, isOpen, shouldTruncate } )
			}
			renderContent={ ( { onClose } ) => (
				<Flex
					direction={ [ 'column' ] }
					justify={ 'start' }
					align={ 'centre' }
					className={ 'vip-workflow-text-popover' }
				>
					<Flex direction={ [ 'row' ] } justify={ 'start' } align={ 'end' }>
						<Heading level={ 2 } size={ 13 }>
							{ editorialMetadata.label }
						</Heading>
						<Flex direction={ [ 'row' ] } justify={ 'end' } align={ 'end' }>
							<Button label={ __( 'Close' ) } icon={ closeSmall } onClick={ onClose } />
						</Flex>
					</Flex>
					<TextControl
						__nextHasNoMarginBottom
						value={ metaFields?.[ editorialMetadata.key ] }
						onChange={ value => {
							setMetaFields( {
								...metaFields,
								[ editorialMetadata.key ]: value,
							} );
						} }
					/>
					<Flex direction={ [ 'row' ] } justify={ 'end' } align={ 'end' }>
						<Button
							label={ __( 'Clear' ) }
							variant="tertiary"
							onClick={ () => {
								setMetaFields( {
									...metaFields,
									[ editorialMetadata.key ]: '',
								} );
								onClose();
							} }
						>
							{ __( 'Clear' ) }
						</Button>
					</Flex>
				</Flex>
			) }
		/>
	);
};

/**
 * This component renders a dropdown component, that is a button. When clicked, it shows a popover with a datetime selector.
 */
const DateComponent = ( { editorialMetadata, metaFields, setMetaFields } ) => {
	const [ popoverAnchor, setPopoverAnchor ] = useState( null );

	const popoverProps = getMemoizedPopoverProps( { popoverAnchor, text: __( 'Select date' ) } );

	let label = metaFields?.[ editorialMetadata.key ];

	const shouldTruncate = false;

	const settings = getSettings();

	// To know if the current timezone is a 12 hour time with look for "a" in the time format
	// We also make sure this a is not escaped by a "/"
	const is12HourTime = /a(?!\\)/i.test(
		settings.formats.time
			.toLowerCase() // Test only the lower case a.
			.replace( /\\\\/g, '' ) // Replace "//" with empty strings.
			.split( '' )
			.reverse()
			.join( '' ) // Reverse the string and test for "a" not followed by a slash.
	);

	// format the datetime string in a human-readable format
	if ( label ) {
		label = getFormattedDate( { dateAttribute: label } );
	} else {
		label = __( 'None' );
	}

	return (
		<Dropdown
			ref={ setPopoverAnchor }
			popoverProps={ popoverProps }
			focusOnMount
			renderToggle={ ( { onToggle, isOpen } ) =>
				getDropdownButton( { editorialMetadata, label, onToggle, isOpen, shouldTruncate } )
			}
			renderContent={ ( { onClose } ) => (
				<Flex direction={ [ 'column' ] } justify={ 'start' } align={ 'centre' }>
					<Flex direction={ [ 'row' ] } justify={ 'start' } align={ 'end' }>
						<Heading level={ 2 } size={ 13 }>
							{ editorialMetadata.label }
						</Heading>
						<Flex direction={ [ 'row' ] } justify={ 'end' } align={ 'end' }>
							<Button
								label={ __( 'Now' ) }
								variant="tertiary"
								onClick={ () => {
									setMetaFields( {
										...metaFields,
										[ editorialMetadata.key ]: new Date(),
									} );
								} }
							>
								{ __( 'Now' ) }
							</Button>
							<Button label={ __( 'Close' ) } icon={ closeSmall } onClick={ onClose } />
						</Flex>
					</Flex>
					<DateTimePicker
						currentDate={ metaFields?.[ editorialMetadata.key ] ?? undefined }
						value={ metaFields?.[ editorialMetadata.key ] }
						is12Hour={ is12HourTime }
						onChange={ value =>
							setMetaFields( {
								...metaFields,
								[ editorialMetadata.key ]: value,
							} )
						}
						onClose={ onClose }
					/>
					<Flex direction={ [ 'row' ] } justify={ 'end' } align={ 'end' }>
						<Button
							label={ __( 'Clear' ) }
							variant="tertiary"
							onClick={ () => {
								setMetaFields( {
									...metaFields,
									[ editorialMetadata.key ]: '',
								} );
								onClose();
							} }
						>
							{ __( 'Clear' ) }
						</Button>
					</Flex>
				</Flex>
			) }
		/>
	);
};

// Taken from https://github.com/WordPress/gutenberg/blob/cbcc28c5511dc87b81bca515b2e88fc1ec55e7e9/packages/editor/src/components/post-schedule/label.js
const getTimezoneAbbreviation = () => {
	const { timezone } = getSettings();

	if ( timezone.abbr && isNaN( Number( timezone.abbr ) ) ) {
		return timezone.abbr;
	}

	const symbol = timezone.offset < 0 ? '' : '+';
	return `UTC${ symbol }${ timezone.offsetFormatted }`;
};

// Taken from https://github.com/WordPress/gutenberg/blob/cbcc28c5511dc87b81bca515b2e88fc1ec55e7e9/packages/editor/src/components/post-schedule/label.js
const getFormattedDate = ( { dateAttribute } ) => {
	const date = getDate( dateAttribute );

	const timezoneAbbreviation = getTimezoneAbbreviation();
	const formattedDate = dateI18n(
		// translators: If using a space between 'g:i' and 'a', use a non-breaking space.
		_x( 'F j, Y g:i\xa0a', 'full date format' ),
		date
	);
	return isRTL()
		? `${ timezoneAbbreviation } ${ formattedDate }`
		: `${ formattedDate } ${ timezoneAbbreviation }`;
};

const applyWithSelect = select => {
	return {
		metaFields: select( 'core/editor' ).getEditedPostAttribute( 'meta' ),
	};
};

const applyWithDispatch = dispatch => {
	return {
		setMetaFields( newValue ) {
			dispatch( 'core/editor' ).editPost( { meta: newValue } );
		},
	};
};

const plugin = compose(
	withSelect( applyWithSelect ),
	withDispatch( applyWithDispatch )
)( CustomMetaPanel );

registerPlugin( 'vip-workflow-editorial-metadata', {
	render: plugin,
	icon: 'vip-workflow',
} );
