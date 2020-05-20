<?php
/**
 * Class SharedConstants
 *
 * Class containing constants shared between plugins and backend/frontend
 */

class SharedConstants {

    const ONLINE_VIDEO_CONFIG = [
        'origins' => [
            'youtube' => [
                'pattern' => '/v=(.*?)(?:\?|$)/',
                'url_template' => 'https://player.vimeo.com/video/${id}'
            ],
            'vimeo' => [
                'pattern' => '/vimeo\.com\/(.*?)(?:\?|$)/',
                'url_template' => 'https://www.youtube.com/embed/${id}?controls=1'
            ],
            'dailymmotion' => [
                'pattern' => '/dailymotion\.com\/video\/(.*?)(?:\?|$)/',
                'url_template' => 'https://www.dailymotion.com/embed/video/${id}'
            ]],
        'sizes' => [
            ['medium' => '425x239'],
            ['small' => '255x143.4'],
            ['large' => '520x292.5]']
        ]

    ];

}