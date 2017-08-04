<?php

class Location
{
    /** @var int */
    public $id_slider;
    /** @var int */
    public $id_type;
    /** @var int */
    public $id_hook;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'cw_carousel_location',
        'primary' => 'id_location',
        'fields'  => [
            'id_type' => ['type' => ObjectModel::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_hook' => ['type' => ObjectModel::TYPE_INT, 'validate' => 'isUnsignedId'],
        ],
    ];
}
