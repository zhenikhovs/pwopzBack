<?php

namespace Sprint\Editor\AdminBlocks;

use Sprint\Editor\Locale;
use Sprint\Editor\Tools\Medialib;

class MedialibCollections
{
    protected $params = [];

    public function __construct()
    {
        $ids = !empty($_REQUEST['collections']) ? $_REQUEST['collections'] : [];
        $ids = array_map(
            function ($val) {
                return intval($val);
            }, $ids
        );

        $ids = array_unique($ids);

        $this->params['collections'] = $ids;
    }

    public function execute()
    {
        $collections = [];

        $dbresult = Medialib::GetCollections(
            [
                'type' => 'image',
            ]
        );
        foreach ($dbresult as $aItem) {
            $collections[] = [
                'title'    => Locale::truncateText($aItem['NAME']),
                'id'       => $aItem['ID'],
                'selected' => in_array($aItem['ID'], $this->params['collections']),
            ];
        }

        header('Content-type: application/json; charset=utf-8');
        echo json_encode(
            Locale::convertToUtf8IfNeed(
                [
                    'collections' => $collections,
                ]
            )
        );
    }
}
