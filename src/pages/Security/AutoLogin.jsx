/**
 * Auto Login Link management — create magic login URLs.
 * Basic link creation is free; conditions (expiry, role, max uses) are Pro.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	Button,
	TextControl,
	SelectControl,
	RangeControl,
	Notice,
	__experimentalHStack as HStack,
} from '@wordpress/components';
import Card from '../../components/Card';
import { TableSkeleton } from '../../components/Skeleton';
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { copy, trash } from '@wordpress/icons';
import ProBadge from '../../components/ProBadge';

const AutoLogin = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	const [ links, setLinks ] = useState( [] );
	const [ users, setUsers ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ creating, setCreating ] = useState( false );
	const [ copied, setCopied ] = useState( null );
	const [ createError, setCreateError ] = useState( '' );

	// Form state.
	const [ userId, setUserId ] = useState( '' );
	const [ label, setLabel ] = useState( '' );
	const [ expiresIn, setExpiresIn ] = useState( 0 ); // hours, 0 = never
	const [ maxUses, setMaxUses ] = useState( 0 ); // 0 = unlimited
	const [ roleRestriction, setRoleRestriction ] = useState( '' );
	const [ redirectUrl, setRedirectUrl ] = useState( '' );

	const reachedFreeLimit = ! isPro && links.length >= 3;

	// Fetch links + users on mount.
	useEffect( () => {
		Promise.all( [
			apiFetch( { path: '/wpepp/v1/auto-login' } ),
			apiFetch( { path: '/wp/v2/users?per_page=100&context=edit' } ),
		] ).then( ( [ linksData, usersData ] ) => {
			setLinks( Array.isArray( linksData ) ? linksData : [] );
			setUsers( Array.isArray( usersData ) ? usersData : [] );
			setLoading( false );
		} ).catch( () => {
			setLoading( false );
		} );
	}, [] );

	const userOptions = useMemo( () => {
		const opts = [ { label: __( '— Select User —', 'wp-edit-password-protected' ), value: '' } ];
		users.forEach( ( u ) => {
			opts.push( { label: `${ u.name } (${ u.username || u.slug })`, value: String( u.id ) } );
		} );
		return opts;
	}, [ users ] );

	const roleOptions = useMemo( () => {
		return [
			{ label: __( 'Any role', 'wp-edit-password-protected' ), value: '' },
			{ label: __( 'Administrator', 'wp-edit-password-protected' ), value: 'administrator' },
			{ label: __( 'Editor', 'wp-edit-password-protected' ), value: 'editor' },
			{ label: __( 'Author', 'wp-edit-password-protected' ), value: 'author' },
			{ label: __( 'Contributor', 'wp-edit-password-protected' ), value: 'contributor' },
			{ label: __( 'Subscriber', 'wp-edit-password-protected' ), value: 'subscriber' },
		];
	}, [] );

	const handleCreate = useCallback( async () => {
		if ( ! userId || ( ! isPro && links.length >= 3 ) ) {
			return;
		}
		setCreating( true );
		setCreateError( '' );
		try {
			const body = {
				user_id: parseInt( userId, 10 ),
				label: label || __( 'Auto Login Link', 'wp-edit-password-protected' ),
			};

			// Pro conditions.
			if ( isPro ) {
				if ( expiresIn > 0 ) {
					body.expires_in = expiresIn;
				}
				if ( maxUses > 0 ) {
					body.max_uses = maxUses;
				}
				if ( roleRestriction ) {
					body.role_restriction = roleRestriction;
				}
			}

			if ( redirectUrl ) {
				body.redirect_url = redirectUrl;
			}

			const result = await apiFetch( {
				path: '/wpepp/v1/auto-login',
				method: 'POST',
				data: body,
			} );

			setLinks( ( prev ) => [ result, ...prev ] );
			setLabel( '' );
			setExpiresIn( 0 );
			setMaxUses( 0 );
			setRoleRestriction( '' );
			setRedirectUrl( '' );
		} catch ( err ) {
			setCreateError( err?.message || __( 'Failed to generate link.', 'wp-edit-password-protected' ) );
		}
		setCreating( false );
	}, [ userId, label, expiresIn, maxUses, roleRestriction, redirectUrl, isPro, links.length ] );

	const handleDelete = useCallback( async ( id ) => {
		try {
			await apiFetch( {
				path: `/wpepp/v1/auto-login/${ id }`,
				method: 'DELETE',
			} );
			setLinks( ( prev ) => prev.filter( ( l ) => l.id !== id ) );
		} catch ( err ) {
			// Error handled silently.
		}
	}, [] );

	const handleCopy = useCallback( ( url, id ) => {
		navigator.clipboard.writeText( url ).then( () => {
			setCopied( id );
			setTimeout( () => setCopied( null ), 2000 );
		} );
	}, [] );

	if ( loading ) {
		return <TableSkeleton rows={ 5 } columns={ 5 } />;
	}

	return (
		<div className="wpepp-auto-login">
			<h3>{ __( 'Auto Login Links', 'wp-edit-password-protected' ) }</h3>

			<Notice status="info" isDismissible={ false }>
				{ __( 'Generate magic login URLs that allow users to log in without entering credentials. Share these links carefully — anyone with the link can log in as the selected user.', 'wp-edit-password-protected' ) }
			</Notice>

			{ reachedFreeLimit && (
				<Notice status="warning" isDismissible={ false } className="wpepp-mb-4">
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: 10 } }>
						<span>
							{ __( 'Free version is limited to 3 active auto-login links. Upgrade to Pro for unlimited links, expiration dates, usage limits, and role restrictions.', 'wp-edit-password-protected' ) }
						</span>
						<Button
							variant="secondary"
							isSmall
							href={ window.wpeppData?.proUrl || '#' }
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __( 'Upgrade to Pro', 'wp-edit-password-protected' ) }
						</Button>
					</div>
				</Notice>
			) }

			{ createError && (
				<Notice status="error" isDismissible onDismiss={ () => setCreateError( '' ) } className="wpepp-mb-4">
					{ createError }
				</Notice>
			) }

			<Card
				title={
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', width: '100%' } }>
						<span>{ __( 'Create New Link', 'wp-edit-password-protected' ) }</span>
						{ ! isPro && (
							<span style={ { fontSize: 12, fontWeight: 500, color: reachedFreeLimit ? '#dc2626' : 'var(--wpepp-text-2)' } }>
								{ sprintf( __( '%d / 3 Active Links Used (Free)', 'wp-edit-password-protected' ), links.length ) }
							</span>
						) }
					</div>
				}
			>
				<div className="wpepp-form-grid">
					<SelectControl
						__next40pxDefaultSize
						label={ __( 'User', 'wp-edit-password-protected' ) }
						value={ userId }
						options={ userOptions }
						onChange={ setUserId }
					/>
					<TextControl
						label={ __( 'Label', 'wp-edit-password-protected' ) }
						help={ __( 'A name to identify this link (e.g. "Client preview link").', 'wp-edit-password-protected' ) }
						value={ label }
						onChange={ setLabel }
						placeholder={ __( 'Auto Login Link', 'wp-edit-password-protected' ) }
					/>
					<div className="wpepp-form-grid__full">
						<TextControl
							label={ __( 'Redirect URL (optional)', 'wp-edit-password-protected' ) }
							help={ __( 'Where to redirect after auto-login. Leave empty for homepage.', 'wp-edit-password-protected' ) }
							value={ redirectUrl }
							onChange={ setRedirectUrl }
							placeholder="https://example.com/welcome"
						/>
					</div>

					<SelectControl
						__next40pxDefaultSize
						label={
							<span>
								{ __( 'Expiration', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						help={ ! isPro ? __( 'Requires WPEPP Pro to customize expiration.', 'wp-edit-password-protected' ) : null }
						value={ String( isPro ? expiresIn : 0 ) }
						options={ [
							{ label: __( 'Never expires', 'wp-edit-password-protected' ), value: '0' },
							{ label: __( '1 hour', 'wp-edit-password-protected' ), value: '1' },
							{ label: __( '6 hours', 'wp-edit-password-protected' ), value: '6' },
							{ label: __( '24 hours', 'wp-edit-password-protected' ), value: '24' },
							{ label: __( '48 hours', 'wp-edit-password-protected' ), value: '48' },
							{ label: __( '7 days', 'wp-edit-password-protected' ), value: '168' },
							{ label: __( '30 days', 'wp-edit-password-protected' ), value: '720' },
						] }
						onChange={ ( v ) => setExpiresIn( parseInt( v, 10 ) ) }
						disabled={ ! isPro }
					/>
					<RangeControl
						label={ isPro ? __( 'Max Uses', 'wp-edit-password-protected' ) : sprintf( __( 'Max Uses (%s)', 'wp-edit-password-protected' ), 'Pro' ) }
						help={ ! isPro ? __( 'Requires WPEPP Pro to limit uses.', 'wp-edit-password-protected' ) : __( '0 = unlimited', 'wp-edit-password-protected' ) }
						value={ isPro ? maxUses : 0 }
						onChange={ setMaxUses }
						min={ 0 }
						max={ 100 }
						disabled={ ! isPro }
					/>
					<div className="wpepp-form-grid__full">
						<SelectControl
							__next40pxDefaultSize
							label={
								<span>
									{ __( 'Role Restriction', 'wp-edit-password-protected' ) }
									{ ! isPro && <ProBadge /> }
								</span>
							}
							help={ ! isPro ? __( 'Requires WPEPP Pro for role restriction.', 'wp-edit-password-protected' ) : __( 'Only create a link for users with this role.', 'wp-edit-password-protected' ) }
							value={ isPro ? roleRestriction : '' }
							options={ roleOptions }
							onChange={ setRoleRestriction }
							disabled={ ! isPro }
						/>
					</div>

					<div className="wpepp-form-grid__full wpepp-form-actions">
						<Button
							variant="primary"
							onClick={ handleCreate }
							isBusy={ creating }
							disabled={ ! userId || creating || reachedFreeLimit }
						>
							{ __( 'Generate Link', 'wp-edit-password-protected' ) }
						</Button>
					</div>
				</div>
			</Card>

			{ links.length > 0 && (
				<Card title={ __( 'Existing Links', 'wp-edit-password-protected' ) }>
					<div className="wpepp-auto-login-table">
						<table className="widefat">
							<thead>
								<tr>
									<th>{ __( 'Label', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'User', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'Uses', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'Expires', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'Actions', 'wp-edit-password-protected' ) }</th>
								</tr>
							</thead>
							<tbody>
								{ links.map( ( link ) => (
									<tr key={ link.id }>
										<td>{ link.label }</td>
										<td>{ link.user_name || `#${ link.user_id }` }</td>
										<td>
											{ link.max_uses > 0
												? `${ link.use_count }/${ link.max_uses }`
												: `${ link.use_count }` }
										</td>
										<td>
											{ link.expires
												? link.expires
												: __( 'Never', 'wp-edit-password-protected' ) }
										</td>
										<td>
											<HStack spacing={ 1 }>
												<Button
													icon={ copy }
													label={ copied === link.id
														? __( 'Copied!', 'wp-edit-password-protected' )
														: __( 'Copy URL', 'wp-edit-password-protected' ) }
													onClick={ () => handleCopy( link.url, link.id ) }
													isSmall
													variant={ copied === link.id ? 'primary' : 'secondary' }
												/>
												<Button
													icon={ trash }
													label={ __( 'Delete', 'wp-edit-password-protected' ) }
													onClick={ () => handleDelete( link.id ) }
													isSmall
													isDestructive
												/>
											</HStack>
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</div>
				</Card>
			) }

			{ links.length === 0 && (
				<Notice status="warning" isDismissible={ false }>
					{ __( 'No auto-login links created yet. Use the form above to generate one.', 'wp-edit-password-protected' ) }
				</Notice>
			) }
		</div>
	);
};

export default AutoLogin;
