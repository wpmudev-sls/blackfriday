import React from 'react';
import { NoticeBlack } from '@wpmudev/shared-notifications-black-friday';

const { __, sprintf } = wp.i18n;

const MyApp = () => {
    return (
        <NoticeBlack
            link="https://wpmudev.com/"
            sourceLang={{
                discount: "50% Off",
                closeLabel: "Close",
                linkLabel: "See the deal"
            }}
        >
            <p>
                <strong>
                    {__( 'Black Friday Offer!', 'broken-link-checker' )}
                Black Friday Offer!
                </strong>

                {__( 'Get a Pro plugin for free and much more with 50% OFF.', 'broken-link-checker' )}
            </p>
            <p><small>*Only admin users can see this message</small></p>
        </NoticeBlack>
    );
}

ReactDOM.render(
    <MyApp />,
    document.getElementById("wpmudev-bf-common-notice")
);
