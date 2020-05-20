<?php
/**
 * Class SharedConstants
 *
 * Class containing constants shared between plugins and backend/frontend
 */

class SharedConstants {

    const ONLINE_VIDEO_CONFIG = [
        'origins' => [
            'vimeo' => [
                'pattern' => 'vimeo\.com\/(.*?)(?:\?|$)',
                'url_template' => 'https://player.vimeo.com/video/${id}'
            ],
            'youtube' => [
                'pattern' => 'v=(.*?)(?:\?|$)',
                'url_template' => 'https://www.youtube.com/embed/${id}?controls=1'
            ],
            'dailymotion' => [
                'pattern' => 'dailymotion\.com\/video\/(.*?)(?:\?|$)',
                'url_template' => 'https://www.dailymotion.com/embed/video/${id}'
            ]],
        'sizes' => [
            'small' => '255x143.4',
            'medium' => '425x239',
            'large' => '520x292.5
            '
        ]
    ];

    static public function getConstantsAsArray() {
        return [
          'ONLINE_VIDEO_CONFIG' => self::ONLINE_VIDEO_CONFIG
        ];
    }

}