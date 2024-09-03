/**
 * Check if the post type is using a workflow status and supported post type
 *
 * @param {string} postType
 * @param {string} statusSlug
 *
 * @return {boolean}
 */
export function isUsingWorkflowStatus( postType, statusSlug ) {
	const isSupportedPostType = VW_CUSTOM_STATUSES.supported_post_types.includes( postType );
	const isSupportedStatusTerm = VW_CUSTOM_STATUSES.status_terms
		.map( t => t.slug )
		.includes( statusSlug );

	return isSupportedPostType && isSupportedStatusTerm;
}
