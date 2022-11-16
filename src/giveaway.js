/* global wp, wpmudevNoticeGiveaway */
import GiveawayBanner from './giveaway/giveaway'
// Styles.
import './giveaway/giveaway.scss'

const { render } = wp.element

const container = document.getElementById('wpmudev-plugin-notices')

// Only if container found.
if (container !== null) {
	render(
		<GiveawayBanner
			apiUrl={wpmudevNoticeGiveaway.apiUrl}
			pluginId={wpmudevNoticeGiveaway.pluginId}
			images={wpmudevNoticeGiveaway.images}
			nonce={wpmudevNoticeGiveaway.nonce}
		/>,
		container
	)
}