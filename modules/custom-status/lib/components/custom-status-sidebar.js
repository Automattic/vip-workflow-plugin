import {
	Button,
	Dropdown,
	Flex,
	__experimentalHeading as Heading,
	__experimentalHStack as HStack,
	RadioControl,
	__experimentalTruncate as Truncate,
} from '@wordpress/components';
import { dispatch, select, subscribe } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { store as editorStore } from '@wordpress/editor';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';

import { isUsingWorkflowStatus } from '../util';

/**
 * Map Custom Statuses as options for SelectControl
 */
const statusOptions = VW_CUSTOM_STATUSES.status_terms.map( customStatus => ( {
	label: customStatus.name,
	value: customStatus.slug,
} ) );

/**
 * Subscribe to changes so we can set a default status.
 */
subscribe( function () {
	const postId = select( editorStore ).getCurrentPostId();
	if ( ! postId ) {
		// Post isn't ready yet so don't do anything.
		return;
	}

	// For new posts, we need to force the default custom status which is the first entry.
	const isCleanNewPost = select( editorStore ).isCleanNewPost();

	if ( isCleanNewPost ) {
		dispatch( editorStore ).editPost( {
			status: statusOptions[ 0 ].value,
		} );
	}

	// Keeping this anywhere else causes the labels to not be available.
	if ( VW_CUSTOM_STATUSES.is_publish_guard_enabled ) {
		const editorialMetadataLabels = document.querySelectorAll(
			'#vip-workflow-editorial-metadata-label'
		);

		if ( ! editorialMetadataLabels || editorialMetadataLabels.length === 0 ) {
			return;
		}

		const customStatus = VW_CUSTOM_STATUSES.status_terms.find(
			customStatus =>
				customStatus.slug === select( editorStore ).getCurrentPostAttribute( 'status' )
		);

		if ( ! customStatus ) {
			return;
		}

		// Add a class to the editorial metadata labels so we can style them.
		editorialMetadataLabels.forEach( label => {
			if ( ! label?.classList ) {
				return;
			}

			if (
				customStatus?.meta?.required_metadatas &&
				customStatus?.meta?.required_metadatas.length > 0 &&
				customStatus?.meta?.required_metadatas.some( requiredMetadata =>
					label.classList.contains( requiredMetadata.slug )
				)
			) {
				label.classList.add( 'vip-workflow-editorial-metadata-required' );
			} else {
				label.classList.remove( 'vip-workflow-editorial-metadata-required' );
			}
		} );
	}
} );

/**
 * Get the dropdown button component, which is the button that triggers a popover. The button text is truncated if it's too long.
 */
const getDropdownButton = ( { label, onToggle, shouldTruncate } ) => {
	return (
		<HStack __nextHasNoMarginBottom>
			<label
				title={ __( 'View the current status, or rollback to a previous status', 'vip-workflow' ) }
			>
				{ __( 'Extended Post Status', 'vip-workflow' ) }
			</label>
			<Button
				// Gutenberg uses whiteSpace: nowrap, but we need to wrap the text so it has to be set here so as to not be overriden
				style={ { whiteSpace: 'normal' } }
				size="compact"
				variant="tertiary"
				onClick={ onToggle }
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
 * Custom status component
 * @param object props
 */
export default function CustomStatusSidebar( { onUpdateStatus, postType, status } ) {
	const [ popoverAnchor, setPopoverAnchor ] = useState( null );

	const popoverProps = getMemoizedPopoverProps( { popoverAnchor, text: __( 'Select Status' ) } );

	const handleChangeStatus = statusSlug => {
		onUpdateStatus( statusSlug );
		dispatch( editorStore ).savePost();
	};

	if ( ! isUsingWorkflowStatus( postType, status ) ) {
		return null;
	}

	// filter out all statuses after the current status in the list
	const statusIndex = statusOptions.findIndex( option => option.value === status );
	const filteredStatusOptions = statusOptions.slice( 0, statusIndex + 1 );

	const label = VW_CUSTOM_STATUSES.status_terms.find( t => t.slug === status )?.name;

	const shouldTruncate = true;

	return (
		<PluginPostStatusInfo
			__nextHasNoMarginBottom
			className={ `vip-workflow-extended-post-status vip-workflow-extended-post-status-${ status }` }
		>
			<Dropdown
				__nextHasNoMarginBottom
				ref={ setPopoverAnchor }
				popoverProps={ popoverProps }
				focusOnMount
				renderToggle={ ( { onToggle } ) =>
					getDropdownButton( { label, onToggle, shouldTruncate } )
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
								{ __( 'Extended Post Status', 'vip-workflow' ) }
							</Heading>
							<Flex direction={ [ 'row' ] } justify={ 'end' } align={ 'end' }>
								<Button label={ __( 'Close' ) } icon={ closeSmall } onClick={ onClose } />
							</Flex>
						</Flex>
						<RadioControl
							selected={ status }
							options={ filteredStatusOptions }
							onChange={ handleChangeStatus }
						/>
					</Flex>
				) }
			/>
		</PluginPostStatusInfo>
	);
}
