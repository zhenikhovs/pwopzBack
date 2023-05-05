<?php
$arUrlRewrite=array (
    0 =>
        array(
            'CONDITION' => '#^/api/([a-zA-Z0-9.]+)/?($|index.*|\\?.*)#',
            'RULE' => 'method=$1',
            'PATH' => '/local/api/index.php',
        ),
    1 =>
        array(
            'CONDITION' => '#^(.*)#',
            'PATH' => '/app/index.php',
        ),
);
