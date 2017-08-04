<?php

class Slider extends ObjectModel
{
    /**
     * Fields related to custom CSS values.
     *
     * @see self::getSlidersOptions()
     *
     * @var array
     */
    const CUSTOM_PROPERTIES = [
        'navigation_color',
        'navigation_color_active',
        'arrow_color',
        'arrow_color_hover',
        'arrow_bg',
    ];

    /**
     * Locations to show slider on.
     *
     * @var array
     */
    const LOCATIONS = [
        'hook' => [
            ['name' => 'displayHome',           'label' => 'Home page'],
            ['name' => 'displayFullWidthTop',   'label' => 'Home page (full width)'], // ST Transformer
            ['name' => 'displayStBlogHome',     'label' => 'Blog home page'],         // ST Transformer
            ['name' => 'displayCategoryFooter', 'label' => 'Category footer'],        // ST Transformer
            ['name' => 'displayFooterProduct',  'label' => 'Product footer'],
        ],
        'category',
        'cms',
        'cms_category',
        'manufacturer',
        'product',
    ];

    /** @var string */
    public $name;
    /** @var bool */
    public $active = true;
    /** @var bool */
    public $resize = true;
    /** @var bool */
    public $autoplay = true;
    /** @var bool */
    public $loop = true;
    /** @var int */
    public $duration = 7000;
    /** @var int */
    public $id_transition;
    /** @var int */
    public $transition_duration = 400;
    /** @var bool */
    public $navigation = false;
    /** @var string */
    public $navigation_color = '#aaaaaa';
    /** @var string */
    public $navigation_color_active = '#ffffff';
    /** @var int */
    public $arrow = 1;
    /** @var string */
    public $arrow_color = '#aaaaaa';
    /** @var string */
    public $arrow_color_hover = '#ffffff';
    /** @var string */
    public $arrow_bg = '#111111';

    /** @var array */
    public $items = [];

    /** @var array */
    public $locations = [];

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'cw_carousel_slider',
        'primary'   => 'id_slider',
        'multishop' => true,
        'fields'    => [
            'name' => [
                'type'     => ObjectModel::TYPE_STRING,
                'validate' => 'isGenericName',
                'size'     => 255,
            ],
            'active' => [
                'type'     => ObjectModel::TYPE_BOOL,
                'validate' => 'isBool',
                'default'  => 0,
            ],
            'resize' => [
                'type'     => ObjectModel::TYPE_BOOL,
                'validate' => 'isBool',
                'default'  => 1,
            ],
            'autoplay' => [
                'type' => ObjectModel::TYPE_BOOL,
                'validate' => 'isBool',
                'default'  => 1,
            ],
            'loop' => [
                'type'     => ObjectModel::TYPE_BOOL,
                'validate' => 'isBool',
                'default'  => 1,
            ],
            'duration' => [
                'type'     => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'default'  => 7000,
            ],
            'id_transition' => [
                'type'     => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'default'  => 1,
            ],
            'transition_duration' => [
                'type'     => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'default'  => 400,
            ],
            'navigation' => [
                'type'     => ObjectModel::TYPE_BOOL,
                'validate' => 'isBool',
                'default'  => 0,
            ],
            'navigation_color' => [
                'type'     => ObjectModel::TYPE_STRING,
                'validate' => 'isColor',
                'default'  => '#aaaaaa',
                'size'     => 7,
            ],
            'navigation_color_active' => [
                'type'     => ObjectModel::TYPE_STRING,
                'validate' => 'isColor',
                'default'  => '#ffffff',
                'size'     => 7,
            ],
            'arrow' => [
                'type'     => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedId',
                'default'  => 1,
            ],
            'arrow_color' => [
                'type'     => ObjectModel::TYPE_STRING,
                'validate' => 'isColor',
                'default'  => '#aaaaaa',
                'size'     => 7,
            ],
            'arrow_color_hover' => [
                'type'     => ObjectModel::TYPE_STRING,
                'validate' => 'isColor',
                'default'  => '#ffffff',
                'size'     => 7,
            ],
            'arrow_bg' => [
                'type'     => ObjectModel::TYPE_STRING,
                'validate' => 'isColor',
                'default'  => '#111111',
                'size'     => 7,
            ],
        ],
        'associations' => [
            'items' => [
                'type'        => ObjectModel::HAS_MANY,
                'association' => 'cw_carousel_item',
                'field'       => 'id_item',
                'object'      => 'Item',
                'fields'      => [
                    'position' => [
                        'type'     => ObjectModel::TYPE_INT,
                        'validate' => 'isUnsignedInt',
                        'default'  => 0,
                    ],
                ],
            ],
            'locations' => [
                'type'        => ObjectModel::HAS_MANY,
                'association' => 'cw_carousel_location',
                'field'       => 'id_location',
                'object'      => 'Location',
            ],
        ],
    ];

    /**
     * Add shop table association.
     */
    public function __construct(int $id_slider)
    {
        Shop::addTableAssociation(static::$definition['table'], ['type' => 'shop']);
        parent::__construct($id_slider);
    }

    /**
     * Delete slider and its relations.
     */
    public function delete(): bool
    {
        return parent::delete()
               and Db::getInstance()->delete(Location::$definition['table'], "id_slider = $this->id")
               and Db::getInstance()->delete(self::$definition['table'].'_item', "id_slider = $this->id");
    }

    /**
     * Get slider locations.
     */
    public function getLocations(): array
    {
        return Db::getInstance()->executeS((new DbQuery())
            ->select('l.id_type, l.id_hook')
            ->from(Location::$definition['table'], 'l')
            ->naturalJoin(static::$definition['table'], 's')
            ->where("l.id_slider = $this->id")
        ) ?: [];
    }

    /**
     * Add slider location.
     */
    public function addLocation(int $id_type, int $id_hook): bool
    {
        return Db::getInstance()->insert(Location::$definition['table'], [
            'id_slider' => $this->id,
            'id_type'   => $id_type,
            'id_hook'   => $id_hook,
        ]);
    }

    /**
     * Remove slider location.
     */
    public function removeLocation(int $id_type, int $id_hook): bool
    {
        return Db::getInstance()->delete(Location::$definition['table'],
            "id_slider = $this->id AND id_type = $id_type AND id_hook = $id_hook"
        );
    }

    /**
     * Get sliders.
     */
    public static function getSliders(): array
    {
        return Db::getInstance()->executeS((new DbQuery())
            ->select('id_slider, name, active')
            ->from(static::$definition['table'])
        ) ?: [];
    }

    /**
     * Get sliders options.
     */
    public static function getSlidersOptions(): array
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
            ->select(implode(', ', static::CUSTOM_PROPERTIES))
            ->from(static::$definition['table'])
            ->where('active = 1 AND ('.implode(" != '' AND ", static::CUSTOM_PROPERTIES).')')
        ) ?: [];
    }

    /**
     * Get item sliders.
     */
    public static function getSlidersByItem(int $id_item): array
    {
        return Db::getInstance()->executeS((new DbQuery())
            ->select('*')
            ->from(static::$definition['table'].'_item')
            ->where("id_item = $id_item")
        ) ?: [];
    }

    /**
     * Get slider by location.
     */
    public static function getSliderByLocation(int $id_type, int $id_hook): array
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow((new DbQuery())
            ->select('s.*')
            ->from(static::$definition['table'], 's')
            ->innerJoin(Location::$definition['table'], 'l', 'l.'.static::$definition['primary'].' = s.'.static::$definition['primary'])
            ->where("l.id_type = $id_type")
            ->where("l.id_hook = $id_hook")
            ->where('s.active = 1')
        ) ?: [];
    }
}
