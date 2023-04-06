<?php

namespace Sprint\Editor\Blocks;

class Slideshare
{
    static public function getHtml($block, $params = [])
    {
        if (empty($block['embed_url'])) {
            return '';
        }

        $block = array_merge(
            [
                'width'  => '510',
                'height' => '420',
            ], $block, $params
        );

        return sprintf(
            '<iframe src="%s" width="%s" height="%s" frameborder="0" marginwidth="0" marginheight="0" scrolling="no" allowfullscreen> </iframe>',
            $block['embed_url'],
            $block['width'],
            $block['height']
        );
    }
}
