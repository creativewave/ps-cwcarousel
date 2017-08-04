<?php

class Item extends ObjectModel
{
    /**
     * Sizes of item images.
     *
     * @var array
     */
    const IMAGE_SIZES = [
        'thumb'  =>  300, // Only used in admin lists.
        'small'  => 1000, // Tablets and old mobiles.
        'medium' => 1400, // Laptops and high dpi mobiles/tablets.
        'large'  => 1920, // Full HD screens.
    ];

    /** @var bool */
    public $active = true;
    /** @var string */
    public $image;
    /** @var int */
    public $width;
    /** @var int */
    public $height;
    /** @var bool */
    public $new_window = true;
    /** @var string */
    public $text_position = 3;
    /** @var string */
    public $text_align = 1;

    /** @var string */
    public $url;
    /** @var string */
    public $title;
    /** @var string */
    public $caption;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'cw_carousel_item',
        'primary'   => 'id_item',
        'multilang' => true,
        'fields'    => [
            'active' => [
                'type'     => ObjectModel::TYPE_BOOL,
                'validate' => 'isBool',
                'default'  => 0,
            ],
            'image' => [
                'type'     => ObjectModel::TYPE_STRING,
                'validate' => 'isFileName',
                'required' => true,
            ],
            'width' => [
                'type'     => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
            ],
            'height' => [
                'type'     => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
            ],
            'new_window' => [
                'type'     => ObjectModel::TYPE_BOOL,
                'validate' => 'isBool',
                'default'  => 0,
            ],
            'text_position' => [
                'type'     => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'default'  => 3,
            ],
            'text_align' => [
                'type'     => ObjectModel::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'default'  => 1,
            ],
            // Multilang
            'url' => [
                'type'     => ObjectModel::TYPE_STRING,
                'lang'     => true,
                'validate' => 'isUrlOrEmpty',
            ],
            'title' => [
                'type'     => ObjectModel::TYPE_STRING,
                'lang'     => true,
                'validate' => 'isString',
            ],
            'caption' => [
                'type'     => ObjectModel::TYPE_HTML,
                'lang'     => true,
                'validate' => 'isString',
            ],
        ],
    ];

    /**
     * Delete item and its images files.
     */
    public function delete(): bool
    {
        if (2 > count(Slider::getSlidersByItem($this->id)) and $this->image and !$this->deleteImages()) {
            return false;
        }

        return parent::delete() and Db::getInstance()->delete(Slider::$definition['table'].'_item', "id_item = $this->id");
    }

    /**
     * Update position.
     */
    public function updatePosition(int $id_slider, int $way, int $old, int $new): bool
    {
        return Db::getInstance()->execute('
            UPDATE '._DB_PREFIX_.Slider::$definition['table'].'_item'.'
            SET `position` = `position` '.($way ? '- 1' : '+ 1').'
            WHERE `position` BETWEEN '.($way ? "$old AND $new" : "$new AND $old").'
            AND `id_slider` = '.$id_slider
        ) and Db::getInstance()->update(Slider::$definition['table'].'_item',
            ['position' => $new],
            "id_item = $this->id AND id_slider = $id_slider"
        );
    }

    /**
     * Add slider relation.
     */
    public function addSlider(int $id_slider): bool
    {
        return Db::getInstance()->insert(Slider::$definition['table'].'_item', [
            'id_slider' => $id_slider,
            'id_item'   => $this->id,
            'position'  => Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue((new DbQuery())
                ->select('COUNT(*)')
                ->from(_DB_PREFIX_.Slider::$definition['table'].'_item')
                ->where("id_slider = $id_slider")
            ),
        ]);
    }

    /**
     * Remove slider relation.
     */
    public function removeSlider(int $id_slider): bool
    {
        return Db::getInstance()->delete(Slider::$definition['table'].'_item',
            "id_item = $this->id AND id_slider = $id_slider"
        );
    }

    /**
     * Get items.
     */
    public static function getItems(): array
    {
        return Db::getInstance()->executeS((new DbQuery())
            ->select('i.id_item, i.image, i.active, il.title')
            ->from(static::$definition['table'], 'i')
            ->naturalJoin(static::$definition['table'].'_lang', 'il')
            ->where('il.id_lang = '.Context::getContext()->language->id)
        ) ?: [];
    }

    /**
     * Get slider items.
     */
    public static function getItemsBySlider(int $id_slider, bool $only_active = false): array
    {
        return Db::getInstance()->executeS((new DbQuery())
            ->select('i.*, il.*, si.position')
            ->from(static::$definition['table'], 'i')
            ->naturalJoin(Slider::$definition['table'].'_item', 'si')
            ->naturalJoin(static::$definition['table'].'_lang', 'il')
            ->where("si.id_slider = $id_slider")
            ->where('il.id_lang = '.Context::getContext()->language->id)
            ->where($only_active ? 'active = 1' : '')
            ->orderBy('si.position')
        ) ?: [];
    }

    /**
     * Delete item images.
     */
    protected function deleteImages(): bool
    {
        $files = array_map(function ($size) {
            return _PS_UPLOAD_DIR_.'cwcarousel/'.substr_replace($this->image, "_$size", strrpos($this->image, '.'), 0);
        }, array_keys(static::IMAGE_SIZES));
        $files[] = _PS_UPLOAD_DIR_.'cwcarousel/'.$this->image;

        return array_product(array_map(function ($file) {
            return !file_exists($path) or unlink($path);
        }, $files));
    }
}
