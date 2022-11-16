import React from 'react'
import { render } from 'react-dom'
// Shared UI styles.
import './banner.scss'
// Banner module.
import { NoticeBlack } from '@wpmudev/shared-notifications-black-friday'
import { postRequest } from './helpers/request'

/**
 * Dimiss banner permanently.
 *
 * @since 1.0
 */
const dismissBanner = async (action) => {
	await postRequest(window.ajaxurl, {
		action: 'wpmudev_bf_dismiss',
		nonce: wpmudevBFBanner.nonce,
	})
}

/**
 * The main black friday banner.
 *
 * @returns {JSX.Element}
 *
 * @constructor
 */
const BlackFridayBanner = () => {
	return (
		<NoticeBlack
			action={{
				label: wpmudevBFBanner.labels.get_deal,
				link: wpmudevBFBanner.utm
			}}
			content={{
				close: wpmudevBFBanner.labels.close,
				intro: wpmudevBFBanner.labels.intro,
				off: wpmudevBFBanner.labels.off,
				title: wpmudevBFBanner.labels.title
			}}
			discount={wpmudevBFBanner.labels.discount}
			price={wpmudevBFBanner.labels.price}
			onCloseClick={() => dismissBanner()}
		>
			<p>{wpmudevBFBanner.labels.description}</p>
		</NoticeBlack>
	);
}

wp.domReady(() => {
	const bannerContainer = document.getElementById('wpmudev-bf-common-notice');
	if (bannerContainer) {
		render(<BlackFridayBanner/>, bannerContainer);
	}
});
