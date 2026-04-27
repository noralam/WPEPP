/**
 * AI Crawler Blocker settings — block AI bots via robots.txt and user-agent.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	CheckboxControl,
	TextareaControl,
	Notice,
	Spinner,
} from '@wordpress/components';
import { useMemo, useCallback } from '@wordpress/element';
import { useSaveBar } from '../../components/SaveBar';
import { SECURITY_DEFAULTS } from '../../utils/defaults';
import ProBadge from '../../components/ProBadge';
import ProLock from '../../components/ProLock';

/**
 * Known AI bot list — must stay in sync with WPEPP_Security::get_known_ai_bots().
 */
const KNOWN_AI_BOTS = {
	GPTBot:               'GPTBot (OpenAI)',
	'ChatGPT-User':       'ChatGPT-User (OpenAI)',
	'OAI-SearchBot':      'OAI-SearchBot (OpenAI)',
	CCBot:                'CCBot (Common Crawl)',
	'Google-Extended':    'Google-Extended (Gemini)',
	GoogleOther:          'GoogleOther (Google)',
	ClaudeBot:            'ClaudeBot (Anthropic)',
	'anthropic-ai':       'anthropic-ai (Anthropic)',
	'Claude-Web':         'Claude-Web (Anthropic)',
	Bytespider:           'Bytespider (ByteDance)',
	Amazonbot:            'Amazonbot (Amazon)',
	FacebookBot:          'FacebookBot (Meta)',
	'Meta-ExternalAgent': 'Meta-ExternalAgent (Meta)',
	PerplexityBot:        'PerplexityBot (Perplexity)',
	YouBot:               'YouBot (You.com)',
	'Applebot-Extended':  'Applebot-Extended (Apple)',
	'cohere-ai':          'cohere-ai (Cohere)',
	Diffbot:              'Diffbot',
	Timpibot:             'Timpibot (Timpi)',
	Omgilibot:            'Omgilibot (Webz.io)',
	img2dataset:          'img2dataset (LAION)',
};

const allBotKeys = Object.keys( KNOWN_AI_BOTS );

const AiCrawlerBlocker = () => {
	const { settings, isLoading, isPro } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'security' ),
			isLoading: store.isLoading(),
			isPro: store.isPro(),
		};
	} );

	const { updateSetting, saveSettings, resetSettings } = useDispatch( 'wpepp/settings' );

	const s = useMemo( () => ( { ...SECURITY_DEFAULTS, ...settings } ), [ settings ] );

	const update = useCallback( ( key, value ) => {
		updateSetting( 'security', key, value );
	}, [ updateSetting ] );

	const handleSave = useCallback( () => {
		saveSettings( 'security', s );
	}, [ saveSettings, s ] );

	const handleReset = useCallback( () => {
		resetSettings( 'security' );
	}, [ resetSettings ] );

	useSaveBar( handleSave, handleReset );

	if ( isLoading ) {
		return <Spinner />;
	}

	const selectedBots = ( s.ai_crawler_bots === null || ! Array.isArray( s.ai_crawler_bots ) )
		? allBotKeys
		: s.ai_crawler_bots;

	const toggleBot = ( botKey ) => {
		const current = [ ...selectedBots ];
		const index = current.indexOf( botKey );
		if ( index > -1 ) {
			current.splice( index, 1 );
		} else {
			current.push( botKey );
		}
		update( 'ai_crawler_bots', current );
	};

	const selectAll = () => {
		update( 'ai_crawler_bots', [ ...allBotKeys ] );
	};

	const deselectAll = () => {
		update( 'ai_crawler_bots', [] );
	};

	const blockedCount = selectedBots.length;
	const siteUrl = ( window.wpeppData?.siteUrl || window.location.origin );

	return (
		<div className="wpepp-ai-crawler-blocker">
			<h3>{ __( 'AI Crawler Blocker', 'wp-edit-password-protected' ) }</h3>

			<PanelBody title={ __( 'Enable AI Crawler Blocking', 'wp-edit-password-protected' ) } initialOpen>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Enable AI Crawler Blocker', 'wp-edit-password-protected' ) }
						checked={ !! s.ai_crawler_blocker_enabled }
						onChange={ ( v ) => update( 'ai_crawler_blocker_enabled', v ) }
					/>
				</PanelRow>
				{ s.ai_crawler_blocker_enabled && (
					<Notice status="info" isDismissible={ false }>
						{ __( 'When enabled, selected AI bots are blocked from crawling your site. Rules are added to your robots.txt and optionally bots are blocked with a 403 response by checking the User-Agent header.', 'wp-edit-password-protected' ) }
					</Notice>
				) }
			</PanelBody>

			{ s.ai_crawler_blocker_enabled && (
				<>
					<PanelBody title={ __( 'Enforcement Method', 'wp-edit-password-protected' ) } initialOpen>
						<Notice status="info" isDismissible={ false }>
							{ __( 'robots.txt rules are always added (polite bots respect them). Enable the toggle below to also actively block bots that ignore robots.txt by returning a 403 Forbidden response.', 'wp-edit-password-protected' ) }
						</Notice>
						<PanelRow>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __( 'Block via User-Agent header (403 response)', 'wp-edit-password-protected' ) }
								help={ __( 'Actively blocks bots that ignore robots.txt by checking the HTTP User-Agent on every frontend request.', 'wp-edit-password-protected' ) }
								checked={ s.ai_crawler_block_ua !== false }
								onChange={ ( v ) => update( 'ai_crawler_block_ua', v ) }
							/>
						</PanelRow>
					</PanelBody>

					<PanelBody title={ __( 'Select AI Bots to Block', 'wp-edit-password-protected' ) } initialOpen>
						<Notice status="warning" isDismissible={ false }>
							{ blockedCount === allBotKeys.length
								? __( 'All known AI bots are selected.', 'wp-edit-password-protected' )
								: blockedCount + __( ' of ', 'wp-edit-password-protected' ) + allBotKeys.length + __( ' AI bots selected.', 'wp-edit-password-protected' )
							}
						</Notice>
						<PanelRow>
							<div style={ { display: 'flex', gap: '8px', marginBottom: '12px' } }>
								<button
									type="button"
									className="components-button is-secondary is-small"
									onClick={ selectAll }
								>
									{ __( 'Select All', 'wp-edit-password-protected' ) }
								</button>
								<button
									type="button"
									className="components-button is-secondary is-small"
									onClick={ deselectAll }
								>
									{ __( 'Deselect All', 'wp-edit-password-protected' ) }
								</button>
							</div>
						</PanelRow>
						<div className="wpepp-ai-bots-grid" style={ {
							display: 'grid',
							gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))',
							gap: '4px',
						} }>
							{ allBotKeys.map( ( key ) => (
								<CheckboxControl
									key={ key }
									label={ KNOWN_AI_BOTS[ key ] }
									checked={ selectedBots.includes( key ) }
									onChange={ () => toggleBot( key ) }
								/>
							) ) }
						</div>
					</PanelBody>

					<PanelBody
						title={
							<>
								{ __( 'Custom User-Agent Strings', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</>
						}
						initialOpen={ false }
					>
						<ProLock isPro={ isPro } featureName={ __( 'Custom User-Agent Strings', 'wp-edit-password-protected' ) }>
							<Notice status="info" isDismissible={ false }>
								{ __( 'Add custom user-agent strings to block (one per line). These are matched as substrings against the visitor\'s User-Agent header. Only applies when User-Agent blocking is enabled above.', 'wp-edit-password-protected' ) }
							</Notice>
							<PanelRow>
								<TextareaControl
									label={ __( 'Custom User-Agents', 'wp-edit-password-protected' ) }
									help={ __( 'One user-agent string per line. Case-insensitive matching.', 'wp-edit-password-protected' ) }
									value={ s.ai_crawler_custom_ua || '' }
									onChange={ ( v ) => update( 'ai_crawler_custom_ua', v ) }
									rows={ 5 }
									placeholder={ 'SomeCustomBot\nAnotherCrawler' }
								/>
							</PanelRow>
						</ProLock>
					</PanelBody>

					<PanelBody title={ __( 'Preview robots.txt Rules', 'wp-edit-password-protected' ) } initialOpen={ false }>
						<Notice status="info" isDismissible={ false }>
							{ __( 'Below is a preview of the robots.txt rules that will be appended to your site\'s robots.txt file.', 'wp-edit-password-protected' ) }
							{ ' ' }
							<a href={ siteUrl + '/robots.txt' } target="_blank" rel="noopener noreferrer">
								{ __( 'View live robots.txt', 'wp-edit-password-protected' ) }
							</a>
						</Notice>
						<pre style={ {
							background: '#1e1e2e',
							color: '#e0e0e0',
							padding: '16px',
							borderRadius: '6px',
							fontSize: '13px',
							lineHeight: '1.7',
							whiteSpace: 'pre-wrap',
							maxHeight: '350px',
							overflow: 'auto',
							border: '1px solid rgba(255,255,255,0.1)',
						} }>{ '# AI Crawler Blocker — added by WPEPP\n' +
selectedBots.map( ( bot ) => `User-agent: ${ bot }\nDisallow: /` ).join( '\n\n' ) }</pre>
					</PanelBody>
				</>
			) }
		</div>
	);
};

export default AiCrawlerBlocker;
