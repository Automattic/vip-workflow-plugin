import {
	BaseControl,
	Button,
	DateTimePicker,
	Dropdown,
	__experimentalHeading as Heading,
	__experimentalHStack as HStack,
	__experimentalText as Text,
	TextControl,
	ToggleControl,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { dateI18n, getDate, getSettings } from '@wordpress/date';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useMemo, useState } from '@wordpress/element';
import { __, _x, isRTL } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';
import './editor.scss';

const editorialMetadatas = window.VW_EDITORIAL_METADATA.editorial_metadata_terms.map(
	editorialMetadata => ( {
		key: editorialMetadata.meta_key,
		label: editorialMetadata.name,
		type: editorialMetadata.type,
		term_id: editorialMetadata.term_id,
		description: editorialMetadata.description,
	} )
);

const noEditorialMetadatasToShow = editorialMetadatas.length === 0;

const CustomMetaPanel = ( { metaFields, setMetaFields } ) => (
	<PluginDocumentSettingPanel name="editorialMetadataPanel" title="Editorial Metadata">
		{ noEditorialMetadatasToShow && (
			<Text>
				{ __(
					'Configure your editorial metadata, within the VIP Workflow Plugin Settings.',
					'vip-workflow'
				) }
			</Text>
		) }
		{ editorialMetadatas.map( editorialMetadata =>
			getComponentByType( editorialMetadata, metaFields, setMetaFields )
		) }
	</PluginDocumentSettingPanel>
);

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

const CheckboxComponent = ( { editorialMetadata, metaFields, setMetaFields } ) => {
	return (
		<VStack __nextHasNoMarginBottom>
			<HStack __nextHasNoMarginBottom>
				<BaseControl __nextHasNoMarginBottom label={ editorialMetadata.label } />
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
			<BaseControl help={ editorialMetadata.description } />
		</VStack>
	);
};

const TextComponent = ( { editorialMetadata, metaFields, setMetaFields } ) => {
	return (
		<VStack __nextHasNoMarginBottom>
			<BaseControl __nextHasNoMarginBottom label={ editorialMetadata.label } />
			<TextControl
				__nextHasNoMarginBottom
				value={ metaFields?.[ editorialMetadata.key ] }
				className={ editorialMetadata.key }
				onChange={ value =>
					setMetaFields( {
						...metaFields,
						[ editorialMetadata.key ]: value,
					} )
				}
			/>
			<BaseControl help={ editorialMetadata.description } />
		</VStack>
	);
};

const DateComponent = ( { editorialMetadata, metaFields, setMetaFields } ) => {
	const [ popoverAnchor, setPopoverAnchor ] = useState( null );
	// Memoize popoverProps to avoid returning a new object every time.
	const popoverProps = useMemo(
		() => ( {
			// Anchor the popover to the middle of the entire row so that it doesn't
			// move around when the label changes.
			anchor: popoverAnchor,
			'aria-label': __( 'Select date' ),
			placement: 'left-start',
			offset: 36,
			shift: true,
		} ),
		[ popoverAnchor ]
	);

	let label = metaFields?.[ editorialMetadata.key ];

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
		label = __( 'Select date' );
	}

	return (
		<Dropdown
			ref={ setPopoverAnchor }
			popoverProps={ popoverProps }
			focusOnMount
			renderToggle={ ( { onToggle, isOpen } ) => (
				<VStack __nextHasNoMarginBottom>
					<HStack __nextHasNoMarginBottom>
						<BaseControl __nextHasNoMarginBottom label={ editorialMetadata.label } />
						<Button
							style={ { whiteSpace: 'pre-wrap' } }
							size="compact"
							variant="tertiary"
							onClick={ onToggle }
							aria-label={ editorialMetadata.label }
							aria-expanded={ isOpen }
						>
							{ label }
						</Button>
					</HStack>
					<BaseControl help={ editorialMetadata.description } />
				</VStack>
			) }
			renderContent={ ( { onClose } ) => (
				<VStack>
					<HStack>
						<Heading level={ 2 } size={ 13 }>
							{ editorialMetadata.label }
						</Heading>
						<Button label={ __( 'Close' ) } icon={ closeSmall } onClick={ onClose } />
					</HStack>
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
					<Button
						label={ __( 'Clear' ) }
						variant="tertiary"
						onClick={ () => {
							setMetaFields( {
								...metaFields,
								[ editorialMetadata.key ]: undefined,
							} );
							onClose();
						} }
					>
						{ __( 'Clear' ) }
					</Button>
				</VStack>
			) }
		/>
	);
};

const getTimezoneAbbreviation = () => {
	const { timezone } = getSettings();

	if ( timezone.abbr && isNaN( Number( timezone.abbr ) ) ) {
		return timezone.abbr;
	}

	const symbol = timezone.offset < 0 ? '' : '+';
	return `UTC${ symbol }${ timezone.offsetFormatted }`;
};

const getFormattedDate = ( { dateAttribute } ) => {
	const date = getDate( dateAttribute );

	const timezoneAbbreviation = getTimezoneAbbreviation();
	const formattedDate = dateI18n(
		// translators: If using a space between 'g:i' and 'a', use a non-breaking space.
		_x( 'F j, Y g:i\xa0a', 'post schedule full date format' ),
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
