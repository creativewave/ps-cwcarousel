<?php

require_once _PS_ROOT_DIR_.'/vendor/autoload.php';

class CWCarousel extends Module
{
    /**
     * Registered hooks.
     *
     * @var array
     */
    const HOOKS = [
        'actionObjectCategoryDeleteAfter',
        'actionObjectCMSDeleteAfter',
        'actionObjectCMSCategoryDeleteAfter',
        'actionObjectManufacturerDeleteAfter',
        'actionObjectProductDeleteAfter',
        'displayHeader',
    ];

    /**
     * Installed models.
     *
     * @var array
     */
    const MODELS = [
        'Item',
        'Location',
        'Slider',
    ];

    /**
     * Slider transitions.
     *
     * @var array
     */
    const TRANSITIONS = ['fade', 'fadeUp', 'backSlide', 'goDown'];

    /**
     * @see ModuleCore
     */
    public $name    = 'cwcarousel';
    public $tab     = 'slideshows';
    public $version = '1.0.0';
    public $author  = 'Creative Wave';
    public $bootstrap = 1;
    public $need_instance = 0;
    public $ps_versions_compliancy = [
        'min' => '1.6',
        'max' => '1.6.99.99',
    ];

    /**
     * Initialize module.
     */
    public function __construct()
    {
        parent::__construct();

        $this->displayName      = $this->l('Sliders');
        $this->description      = $this->l('Display and manage sliders.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Install module.
     */
    public function install(): bool
    {
        $hooks = array_merge(static::HOOKS, array_column(Slider::LOCATIONS['hook'], 'name'));

        return parent::install()
               and $this->addHooks($hooks)
               and $this->addModels(static::MODELS)
               and $this->addDirectory($this->getUploadPath());
    }

    /**
     * Uninstall module.
     */
    public function uninstall(): bool
    {
        $this->_clearCache('*');

        return parent::uninstall()
               and $this->removeModels(static::MODELS)
               and $this->removeDirectory($this->getUploadPath());
    }

    /**
     * Get module admin page content.
     *
     * @todo use admin module controllers.
     */
    public function getContent(): string
    {
        $error = '';
        if (Tools::isSubmit('saveSlider') or Tools::isSubmit('saveSliderAndStay')) {
            $error .= $this->saveSlider();
        } elseif (Tools::isSubmit('saveItem') or Tools::isSubmit('saveItemAndStay')) {
            $error .= $this->saveItem();
        } elseif (Tools::isSubmit('deleteSlider')) {
            $error .= $this->deleteSlider();
        } elseif (Tools::isSubmit('deleteItem')) {
            $error .= $this->deleteItem();
        } elseif (Tools::isSubmit('activeSlider')) {
            $error .= $this->activeSlider();
        } elseif (Tools::isSubmit('activeItem')) {
            $error .= $this->activeItem();
        } elseif ('updatePositions' === Tools::getValue('action')) {
            $error .= $this->processAjaxUpdateItemPositions();
        } elseif (Tools::isSubmit('cancelUpdate')) {
            Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'));
        } elseif (Tools::isSubmit('updateSlider')) {
            return $this->renderSliderForm();
        } elseif (Tools::isSubmit('updateItem')) {
            return $this->renderItemForm();
        } elseif (Tools::isSubmit('viewSlider')) {
            return $this->renderItemsList();
        }

        return $error.$this->renderSlidersList().$this->renderItemsList();
    }

    /**
     * Remove slider located on deleted category page.
     */
    public function hookActionObjectCategoryDeleteAfter(array $params): bool
    {
        return $this->removeLocations(2, $params['object']->id);
    }

    /**
     * Remove slider located on deleted CMS category page.
     */
    public function hookActionObjectCMSCategoryDeleteAfter(array $params): bool
    {
        return $this->removeLocations(4, $params['object']->id);
    }

    /**
     * Remove slider located on deleted CMS page.
     */
    public function hookActionObjectCMSDeleteAfter(array $params): bool
    {
        return $this->removeLocations(3, $params['object']->id);
    }

    /**
     * Remove slider located on deleted manufacturer page.
     */
    public function hookActionObjectManufacturerDeleteAfter(array $params): bool
    {
        return $this->removeLocations(5, $params['object']->id);
    }

    /**
     * Remove slider located on deleted product page.
     */
    public function hookActionObjectProductDeleteAfter(array $params): bool
    {
        return $this->removeLocations(6, $params['object']->id);
    }

    /**
     * Get item thumbnail.
     *
     * @see CWCarousel::renderItemForm()
     * @see CWCarousel::renderItemsList()
     */
    public function getItemThumbnail(string $value, array $row): string
    {
        $filename = substr_replace($value, '_thumb', strrpos($value, '.'), 0);

        return '<img src="'.$this->getUploadUrl($filename).'" />';
    }

    /**
     * Get custom CSS.
     *
     * @todo remove dependency to `stowlcarousel` styles.
     * @todo use CSS variables instead of inlining CSS.
     */
    public function hookDisplayHeader(array $params): string
    {
        $template_name = 'header.tpl';
        $id_cache = $this->getCacheId();
        if (!$this->isCached($template_name, $id_cache)) {
            $custom_css  = '.owl-caption{position:absolute;top:0;display:flex;flex-direction:column;width:100%;height:100%;padding:2rem;color:white;}';
            $custom_css .= '@media (min-width:50rem){.owl-caption{padding:2rem 6rem;}}';
            foreach (Slider::getSlidersOptions() as $slider) {
                if ($slider['navigation_color']) {
                    $custom_css .= '#cw-slider-'.$slider['id_slider'].' .owl-page span{background-color:'.$slider['pag_nav_bg'].';}';
                }
                if ($slider['navigation_color_active']) {
                    $custom_css .= '#cw-slider-'.$slider['id_slider'].' .owl-page.active span{background-color:'.$slider['pag_nav_bg_active'].';}';
                }
                if ($slider['arrow_color']) {
                    $custom_css .= '#cw-slider-'.$slider['id_slider'].' .owl-buttons div{color:'.$slider['prev_next_color'].';}';
                }
                if ($slider['arrow_color_hover']) {
                    $custom_css .= '#cw-slider-'.$slider['id_slider'].' .owl-buttons div:hover{color:'.$slider['prev_next_hover'].';}';
                }
                if ($slider['arrow_bg']) {
                    $custom_css .= '#cw-slider-'.$slider['id_slider'].' .owl-buttons div{background-color:'.$slider['arrow_bg'].';}';
                    $custom_css .= '#cw-slider-'.$slider['id_slider'].' .owl-buttons div{background-color:rgba('.static::hex2rgb($slider['arrow_bg']).',0.4);}';
                    $custom_css .= '#cw-slider-'.$slider['id_slider'].' .owl-buttons div:hover{background-color:rgba('.static::hex2rgb($slider['arrow_bg']).',0.8);}';
                }
            }
            $this->setTemplateVars(['custom_css' => preg_replace('/s\s+/', ' ', $custom_css)]);
        }

        return $this->display(__FILE__, $template_name, $id_cache);
    }

    /**
     * Display slider on home page.
     */
    public function hookDisplayHome(array $params): string
    {
        return $this->displaySlider(0, 0);
    }

    /**
     * Display slider on custom hook 'DisplayFullWidthTop'.
     */
    public function hookDisplayFullWidthTop(array $params): string
    {
        $controller = Context::getContext()->controller->php_self;
        if ('index' === $controller) {
            return $this->displaySlider(0, 1);
        }
        if ('category' === $controller and $id_category = Tools::getValue('id_category')) {
            return $this->displaySlider(1, $id_category);
        }
        if ('cms' === $controller and $id_cms = Tools::getValue('id_cms')) {
            return $this->displaySlider(2, $id_cms);
        }
        if ('cms' === $controller and $id_cms_category = Tools::getValue('id_cms_category')) {
            return $this->displaySlider(3, $id_cms_category);
        }
        if ('manufacturer' === $controller and $id_manufacturer = Tools::getValue('id_manufacturer')) {
            return $this->displaySlider(4, $id_manufacturer);
        }
        if ('product' === $controller and $id_product = Tools::getValue('id_product')) {
            return $this->displaySlider(5, $id_product);
        }

        return '';
    }

    /**
     * Display slider on custom hook 'DisplayStBlogHome'.
     */
    public function hookDisplayStBlogHome(array $params): string
    {
        return $this->displaySlider(0, 2);
    }

    /**
     * Display slider on custom hook 'DisplayCategoryFooter'.
     */
    public function hookDisplayCategoryFooter(array $params): string
    {
        return $this->displaySlider(0, 3);
    }

    /**
     * Display slider on product footer page.
     */
    public function hookDisplayFooterProduct(array $params): string
    {
        return $this->displaySlider(0, 4);
    }

    /**
     * Add hooks.
     */
    protected function addHooks(array $hooks): bool
    {
        return array_product(array_map([$this, 'registerHook'], $hooks));
    }

    /**
     * Add models.
     */
    protected function addModels(array $models): bool
    {
        return array_product(array_map(function ($model) {
            return (new CW\ObjectModel\Extension(new $model(), $this->getDatabase()))->install();
        }, $models));
    }

    /**
     * Remove models.
     */
    protected function removeModels(array $models): bool
    {
        return array_product(array_map(function ($model) {
            return (new CW\ObjectModel\Extension(new $model(), $this->getDatabase()))->uninstall();
        }, $models));
    }

    /**
     * Add directory.
     */
    protected function addDirectory(string $path): bool
    {
        return mkdir($path, 0775);
    }

    /**
     * Remove directory.
     */
    protected function removeDirectory(string $path): bool
    {
        return !file_exists($path) or Tools::deleteDirectory($path);
    }

    /**
     * Render sliders list.
     *
     * @see CWCarousel::getContent()
     */
    protected function renderSlidersList(): string
    {
        $fields_list = [
            'id_slider' => [
                'title'   => $this->l('ID'),
                'search'  => false,
            ],
            'name' => [
                'title'   => $this->l('Name'),
                'width'   => 'auto',
                'search'  => false,
            ],
            'active' => [
                'title'  => $this->l('Status'),
                'type'   => 'bool',
                'active' => 'active',
                /*
                 * Only one Ajax field can exist per page.
                 * See CWCarousel::renderItemsList()
                 * TODO: separate sliders and items list.
                 */
                /* 'ajax'   => true, */
                'align'  => 'center',
                'width'  => 25,
                'search' => false,
            ],
        ];

        $helper = $helper = new HelperList();

        // List configuration.
        $helper->title         = $this->l('Sliders');
        $helper->table         = 'Slider';
        $helper->identifier    = 'id_slider';
        $helper->shopLinkType  = '';
        $helper->token         = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex  = AdminController::$currentIndex.'&configure='.$this->name;

        // List actions.
        $helper->actions  = ['view', 'edit', 'delete'];
        $helper->toolbar_btn['new'] =  [
            'href' => $helper->currentIndex.'&updateSlider&token='.$helper->token,
            'desc' => $this->l('Add a slider'),
        ];

        // List pagination.
        $helper->listTotal = count($sliders = Slider::getSliders());
        $page        = ($page = Tools::getValue('submitFilter'.$helper->table)) ? $page : 1;
        $pagination  = ($pagination = Tools::getValue($helper->table.'_pagination')) ? $pagination : 50;
        $sliders     = $this->paginate($sliders, $page, $pagination);

        return $helper->generateList(Slider::getSliders(), $fields_list);
    }

    /**
     * Render items list.
     *
     * @see CWCarousel::getContent()
     */
    protected function renderItemsList(): string
    {
        // Default fields.
        $fields_list = [
            'id_item' => [
                'title'  => $this->l('ID'),
                'search' => false,
            ],
            'title' => [
                'title'  => $this->l('Title'),
                'width'  => 'auto',
                'search' => false,
            ],
            'image' => [
                'title'  => $this->l('Preview'),
                'search' => false,
                'callback' => 'getItemThumbnail',
                'callback_object' => $this,
            ],
            'active' => [
                'title'  => $this->l('Status'),
                'type'   => 'bool',
                'active' => 'active',
                'ajax'   => true,
                'align'  => 'center',
                'search' => false,
            ],
            'position' => [
                'title'    => $this->l('Position'),
                'position' => 'position',
                'align'    => 'left',
                'search'   => false,
            ],
        ];

        $helper = new HelperList();

        // List configuration.
        $helper->title        = $this->l('Slides');
        $helper->table        = 'Item';
        $helper->identifier   = 'id_item';
        $helper->shopLinkType = '';
        $helper->token        = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        if (Tools::isSubmit('viewSlider')) {
            unset($fields_list['active']);
            // List actions.
            $helper->actions  = ['edit', 'delete'];
            // List ordering.
            $helper->orderBy  = 'position';
            $helper->orderWay = 'position';
            $helper->position_identifier = 'id_item';
            $helper->position_group_identifier = Tools::getValue('id_slider');
            // List toolbar.
            $helper->toolbar_btn['back'] =  [
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&token='.$helper->token,
                'desc' => $this->l('Back to lists'),
                'force_desc' => true,
            ];
            // List paginatation.
            $helper->listTotal = count($items = Item::getItemsBySlider(Tools::getValue('id_slider')));
            $page        = ($page = Tools::getValue('submitFilter'.$helper->table)) ? $page : 1;
            $pagination  = ($pagination = Tools::getValue($helper->table.'_pagination')) ? $pagination : 50;
            $items       = $this->paginate($items, $page, $pagination);

            return $helper->generateList($items, $fields_list).'<script>var currentIndex="'.$helper->currentIndex.'";</script>';
        }

        unset($fields_list['position']);

        // List toolbar.
        $helper->actions  = ['edit', 'delete'];
        $helper->toolbar_btn['new'] = [
            'desc' => $this->l('Add a slide'),
            'href' => $helper->currentIndex.'&updateItem&token='.$helper->token,
        ];

        // List paginatation.
        $helper->listTotal = count($items = Item::getItems());
        $page        = ($page = Tools::getValue('submitFilter'.$helper->table)) ? $page : 1;
        $pagination  = ($pagination = Tools::getValue($helper->table.'_pagination')) ? $pagination : 50;
        $items       = $this->paginate($items, $page, $pagination);

        return $helper->generateList($items, $fields_list);
    }

    /**
     * Get subset of an array.
     *
     * @see CWCarousel::renderItemsList()
     */
    protected function paginate(array $items, int $page = 1, int $pagination = 50): array
    {
        if (count($items) > $pagination) {
            $items = array_slice($items, $pagination * (--$page), $pagination);
        }

        return $items;
    }

    /**
     * Render slider form.
     *
     * @todo use an admin module controller.
     *
     * @see CWCarousel::getContent()
     */
    protected function renderSliderForm(): string
    {
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
                'icon'  => 'icon-cogs',
            ],
            'input' => [
                [
                    'type'  => 'text',
                    'name'  => 'name',
                    'label' => $this->l('Name'),
                    'hint'  => $this->l('Only used to identify slider.'),
                    'class' => 'fixed-width-xxl',
                ],
                // TODO: use an admin module controller and updateAssoShop.
                [
                    'type'  => 'shop',
                    'name'  => 'checkBoxShopAsso',
                    'label' => $this->l('Shop association'),
                ], [
                    'type'     => 'select',
                    'name'     => 'locations',
                    'label'    => $this->l('Show on'),
                    'class'    => 'fixed-width-xxl',
                    'size'     => 20,
                    'multiple' => true,
                    'options'  => [
                        'optiongroup' => [
                            'query' => $this->getLocationsOptions(),
                            'label' => 'group',
                        ],
                        'options' => [
                            'query' => 'query',
                            'id'    => 'location',
                            'name'  => 'name',
                        ],
                    ],
                ], [
                    'type'    => 'select',
                    'name'    => 'id_transition',
                    'label'   => $this->l('Transition'),
                    'class'   => 'fixed-width-xxl',
                    'options' => [
                        'query' => $this->getTransitionsOptions(),
                        'id'    => 'id_transition',
                        'name'  => 'name',
                    ],
                ], [
                    'type'  => 'text',
                    'name'  => 'duration',
                    'label' => $this->l('Duration'),
                    'class' => 'fixed-width-sm',
                ], [
                    'type'  => 'text',
                    'name'  => 'transition_duration',
                    'label' => $this->l('Transition speed'),
                    'class' => 'fixed-width-sm',
                ], [
                    'type'   => 'switch',
                    'name'   => 'active',
                    'label'  => $this->l('Status'),
                    'values' => [['value' => 1], ['value' => 0]],
                ], [
                    'type'   => 'switch',
                    'name'   => 'loop',
                    'label'  => $this->l('Loop'),
                    'values' => [['value' => 1], ['value' => 0]],
                ], [
                    'type'   => 'switch',
                    'name'   => 'resize',
                    'label'  => $this->l('Autoresize'),
                    'values' => [['value' => 1], ['value' => 0]],
                ], [
                    'type'   => 'switch',
                    'name'   => 'autoplay',
                    'label'  => $this->l('Autoplay'),
                    'values' => [['value' => 1], ['value' => 0]],
                ], [
                    'type'   => 'radio',
                    'name'   => 'arrow',
                    'label'  => $this->l('Arrows'),
                    'hint'   => $this->l('Prev/Next links.'),
                    'values' => [
                        [
                            'id'    => 'none',
                            'value' => 0,
                            'label' => $this->l('None'),
                        ], [
                            'id'    => 'square',
                            'value' => 1,
                            'label' => $this->l('Square'),
                        ], [
                            'id'    => 'circle',
                            'value' => 2,
                            'label' => $this->l('Circle'),
                        ], [
                            'id'    => 'rectangle',
                            'value' => 3,
                            'label' => $this->l('Rectangle'),
                        ], [
                            'id'    => 'full',
                            'value' => 4,
                            'label' => $this->l('Full height'),
                        ],
                    ],
                ], [
                    'type'  => 'color',
                    'name'  => 'arrow_color',
                    'label' => $this->l('Arrows color'),
                ], [
                    'type'  => 'color',
                    'name'  => 'arrow_color_hover',
                    'label' => $this->l('Arrows hover color'),
                    'size'  => 7,
                ], [
                    'type'  => 'color',
                    'name'  => 'arrow_bg',
                    'label' => $this->l('Arrows background color'),
                    'size'  => 7,
                ], [
                    'type'   => 'switch',
                    'name'   => 'navigation',
                    'label'  => $this->l('Show navigation'),
                    'hint'   => $this->l('Bullets located at the bottom.'),
                    'values' => [['value' => 1], ['value' => 0]],
                ], [
                    'type'  => 'color',
                    'name'  => 'navigation_color',
                    'label' => $this->l('Navigation color'),
                    'size'  => 7,
                ], [
                    'type'  => 'color',
                    'name'  => 'navigation_color_active',
                    'label' => $this->l('Navigation active color'),
                    'size'  => 7,
                ],
            ],
            'buttons' => [
                [
                    'type'  => 'submit',
                    'name'  => 'saveSlider',
                    'title' => $this->l('Save'),
                    'icon'  => 'process-icon-save',
                    'class' => 'pull-right',
                ], [
                    'type'  => 'submit',
                    'name'  => 'cancelUpdate',
                    'title' => $this->l('Back to lists'),
                    'icon'  => 'process-icon-back',
                ],
            ],
            'submit' => [
                'name'  => 'saveSlider',
                'title' => $this->l('Save and stay'),
                'stay'  => true,
            ],
        ];

        $helper = new HelperForm();

        // Form configuration.
        $helper->module          = $this;
        $helper->name_controller = $this->name;
        $helper->token           = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex    = AdminController::$currentIndex.'&configure='.$this->name.'&updateSlider'.
            (Tools::getIsset('id_slider') ? '&id_slider='.Tools::getValue('id_slider') : '');

        // Form language.
        $helper->default_form_language = Configuration::get('PS_LANG_DEFAULT');
        $helper->languages             = $this->context->controller->getLanguages();

        // Form fields values.
        $slider = new Slider(Tools::getValue('id_slider'));
        foreach ($fields_form[0]['form']['input'] as $input) {
            if ('locations' === $input['name']) {
                $helper->fields_value['locations[]'] = $slider->id
                    ? array_map(function ($location) {
                        return $location['id_type'].'-'.$location['id_hook'];
                    }, $slider->getLocations())
                    : [];
            } elseif (property_exists($slider, $input['name'])) {
                $helper->fields_value[$input['name']] = $slider->{$input['name']};
            }
        }

        $helper->id = $slider->id;
        $helper->identifier = 'id_slider';
        $helper->table = Slider::$definition['table'];

        return $helper->generateForm($fields_form);
    }

    /**
     * Get options of locations.
     *
     * @see CWCarousel::renderSliderForm()
     */
    protected function getLocationsOptions(): array
    {
        $hooks = [];
        foreach (array_column(Slider::LOCATIONS['hook'], 'label') as $id => $hook) {
            $hooks[] = ['location' => "0-$id", 'name' => $hook];
        }
        $categories = [];
        foreach (Category::getSimpleCategories($this->context->language->id) as $category) {
            $categories[] = ['location' => "1-{$category['id_category']}", 'name' => $category['name']];
        }
        $cms = [];
        foreach (CMS::listCms($this->context->language->id) as $cms_page) {
            $cms[] = ['location' => "2-{$cms_page['id_cms']}", 'name' => $cms_page['meta_title']];
        }
        $cms_categories = [];
        foreach (CMSCategory::getSimpleCategories($this->context->language->id) as $cms_category) {
            $cms_categories[] = ['location' => "3-{$cms_category['id_cms_category']}", 'name' => $cms_category['name']];
        }
        $manufacturers = [];
        foreach (Manufacturer::getManufacturers(false, $this->context->language->id) as $manufacturer) {
            $manufacturers[] = ['location' => "4-{$manufacturer['id_manufacturer']}", 'name' => $manufacturer['name']];
        }
        $products = [];
        foreach (Product::getSimpleProducts($this->context->language->id) as $product) {
            $products[] = ['location' => "5-{$product['id_product']}", 'name' => $product['name']];
        }

        return [
            ['group' => $this->l('Hook'),         'query' => $hooks],
            ['group' => $this->l('Category'),     'query' => $categories],
            ['group' => $this->l('CMS'),          'query' => $cms],
            ['group' => $this->l('CMS Category'), 'query' => $cms_categories],
            ['group' => $this->l('Manufacturer'), 'query' => $manufacturers],
            ['group'=>  $this->l('Product'),      'query' => $products],
        ];
    }

    /**
     * Get options of transitions.
     *
     * @see CWCarousel::renderSliderForm()
     */
    protected function getTransitionsOptions(): array
    {
        return array_map(function ($id, $name) {
            return ['id_transition' => $id, 'name' => $name];
        }, array_keys(static::TRANSITIONS), static::TRANSITIONS);
    }

    /**
     * Save slider.
     *
     * @see CWCarousel::getContent()
     *
     * @todo use an admin module controller and updateAssoShop.
     */
    protected function saveSlider(): string
    {
        $slider = new Slider(Tools::getValue('id_slider'));

        // Validate and save slider.
        if ($errors = $slider->validateController() /* -> copy from post */ or !$slider->save()) {
            return $this->displayError($this->l('Unable to save slider.').implode('<br>', $errors));
        }
        // Validate and add/remove slider location(s).
        if (Tools::getIsset('locations')) {
            foreach (Tools::getValue('locations') as $location) {
                if (!preg_match('/^[0-9]+-[0-9]+$/', $location)) {
                    return $this->displayError($this->l('Invalid slider location.'));
                }
            }
            if (!$this->saveSliderLocations($slider, Tools::getValue('locations'))) {
                return $this->displayError($this->l('Unable to save slider location.'));
            }
        }
        $this->_clearCache('*');

        if (Tools::isSubmit('saveSliderAndStay')) {
            Tools::redirectAdmin(
                AdminController::$currentIndex.'&configure='.$this->name.
                '&updateSlider&id_slider='.$slider->id.'&conf='.(Tools::getIsset('id_slider') ? '4' : '3').
                '&token='.Tools::getAdminTokenLite('AdminModules')
            );
        }
        Tools::redirectAdmin(
            AdminController::$currentIndex.'&configure='.$this->name.
            '&conf='.(Tools::getIsset('id_slider') ? '4' : '3').
            '&token='.Tools::getAdminTokenLite('AdminModules'));
    }

    /**
     * Save slider locations.
     *
     * @see CWCarousel::saveSlider()
     */
    protected function saveSliderLocations(Slider $slider, array $locations): bool
    {
        $old = $slider->id ? $slider->getLocations() : [];
        $new = array_map(function ($location) {
            return [
                'id_type' => strstr($location, '-', true),
                'id_hook' => substr($location, strpos($location, '-') + 1),
            ];
        }, $locations);
        foreach ($this->filterSliderLocations($old, $new) as $location) {
            if (!$slider->removeLocation($location['id_type'], $location['id_hook'])) {
                return false;
            }
        }
        foreach ($this->filterSliderLocations($new, $old) as $location) {
            if (!$slider->addLocation($location['id_type'], $location['id_hook'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filter slider locations.
     *
     * @see CWCarousel::saveSliderLocations()
     */
    protected function filterSliderLocations(array $locations, array $comparison): array
    {
        return array_filter($locations, function ($location) use ($comparison) {
            return !in_array($location, $comparison, true);
        });
    }

    /**
     * Toggle slider active value.
     *
     * @see CWCarousel::getContent()
     */
    protected function activeSlider(): string
    {
        if (!(new Slider(Tools::getValue('id_slider')))->toggleStatus()) {
            if (Tools::getValue('ajax')) {
                die('{"text": "'.$this->l('Unable to toggle slider status.').'"}');
            }

            return $this->displayError($this->l('Unable to toggle slider status.'));
        }
        $this->_clearCache('*');

        if (Tools::getValue('ajax')) {
            die('{"success": 1, "text": "'.$this->l('Slider status successfully updated').'"}');
        }
        Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&conf=5&token='.Tools::getAdminTokenLite('AdminModules'));
    }

    /**
     * Delete slider.
     *
     * @see CWCarousel::getContent()
     */
    protected function deleteSlider(): string
    {
        if (!(new Slider(Tools::getValue('id_slider')))->delete()) {
            return $this->displayError($this->l('Unable to delete slider.'));
        }
        $this->_clearCache('*');

        Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&conf=1&token='.Tools::getAdminTokenLite('AdminModules'));
    }

    /**
     * Render item form.
     *
     * @todo use an admin module controller.
     *
     * @see CWCarousel::getContent()
     */
    protected function renderItemForm(): string
    {
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Slide'),
                'icon'  => 'icon-cogs',
            ],
            'input' => [
                [
                    'type'  => 'text',
                    'name'  => 'title',
                    'label' => $this->l('Title'),
                    'hint'  => $this->l('Used as alternate text.'),
                    'lang'  => true,
                ], [
                    'type'  => 'text',
                    'name'  => 'url',
                    'label' => $this->l('Link'),
                    'lang'  => true,
                ], [
                    'type'     => 'file',
                    'name'     => 'image',
                    'label'    => $this->l('Image'),
                    'desc'     => $this->l('Please ensure the image name is unique, or it will override existing image with this name.'),
                    'required' => true,
                ], [
                    'type'         => 'textarea',
                    'name'         => 'caption',
                    'label'        => $this->l('Caption'),
                    'lang'         => true,
                    'autoload_rte' => true,
                ], [
                    'type'     => 'select',
                    'name'     => 'sliders',
                    'label'    => $this->l('Sliders'),
                    'multiple' => true,
                    'options'  => [
                        'query' => $this->getSlidersOptions(),
                        'id'    => 'id_slider',
                        'name'  => 'name',
                    ],
                ], [
                    'type'   => 'switch',
                    'label'  => $this->l('Status'),
                    'name'   => 'active',
                    'values' => [['value' => 1], ['value' => 0]],
                ], [
                    'type'   => 'switch',
                    'name'   => 'new_window',
                    'label'  => $this->l('Open link in a new window'),
                    'values' => [['value' => 1], ['value' => 0]],
                ], [
                    'type'   => 'radio',
                    'name'   => 'text_position',
                    'label'  => $this->l('Position'),
                    'values' => [
                        [
                            'id'    => 'text_position_top',
                            'value' => 1,
                            'label' => $this->l('Top'),
                        ], [
                            'id'    => 'text_position_center',
                            'value' => 2,
                            'label' => $this->l('Center'),
                        ], [
                            'id'    => 'text_position_bottom',
                            'value' => 3,
                            'label' => $this->l('Bottom'),
                        ],
                    ],
                ], [
                    'type'   => 'radio',
                    'name'   => 'text_align',
                    'label'  => $this->l('Alignment'),
                    'values' => [
                        [
                            'id'    => 'text_align_left',
                            'value' => 1,
                            'label' => $this->l('Left'),
                        ], [
                            'id'    => 'text_align_center',
                            'value' => 2,
                            'label' => $this->l('Center'),
                        ], [
                            'id'    => 'text_align_right',
                            'value' => 3,
                            'label' => $this->l('Right'),
                        ],
                    ],
                ],
            ],
            'buttons' => [
                [
                    'type'  => 'submit',
                    'name'  => 'saveItem',
                    'title' => $this->l('Save'),
                    'icon'  => 'process-icon-save',
                    'class' => 'pull-right',
                ], [
                    'type'  => 'submit',
                    'name'  => 'cancelUpdate',
                    'title' => $this->l('Back to lists'),
                    'icon'  => 'process-icon-back',
                ],
            ],
            'submit' => [
                'name'  => 'saveItem',
                'title' => $this->l('Save and stay'),
                'stay'  => true,
            ],
        ];

        $helper = new HelperForm();

        // Form configuration.
        $helper->module          = $this;
        $helper->name_controller = $this->name;
        $helper->token           = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex    = AdminController::$currentIndex.'&configure='.$this->name.'&updateItem'.
            (Tools::getIsset('id_item') ? '&id_item='.Tools::getValue('id_item') : '');

        // Form language.
        $helper->default_form_language = Configuration::get('PS_LANG_DEFAULT');
        $helper->languages             = $this->context->controller->getLanguages();

        // Form fields values.
        $item = new Item(Tools::getValue('id_item'));
        foreach ($fields_form[0]['form']['input'] as &$input) {
            if (!empty($input['lang'])) {
                foreach (Language::getLanguages(false) as $language) {
                    if ($item->id and isset($item->{$input['name']}[$language['id_lang']])) {
                        $helper->fields_value[$input['name']][$language['id_lang']] = $item->{$input['name']}[$language['id_lang']];
                    } else {
                        $helper->fields_value[$input['name']][$language['id_lang']] = '';
                    }
                }
            } elseif ('sliders' === $input['name']) {
                $helper->fields_value['sliders[]'] = $item->id ? array_column(Slider::getSlidersByItem($item->id), 'id_slider') : [];
            } elseif ('image' === $input['name']) {
                $input['desc'] .= ($item->id ? '<p>'.$this->getItemThumbnail($item->image, []).'</p>' : '');
            } elseif (property_exists($item, $input['name'])) {
                $helper->fields_value[$input['name']] = $item->{$input['name']};
            }
        }

        return $helper->generateForm($fields_form);
    }

    /**
     * Save item.
     *
     * @see CWCarousel::getContent()
     */
    protected function saveItem(): string
    {
        $item = new Item(Tools::getValue('id_item'));

        // Validate and save item images files.
        if (!empty($_FILES['image']['name'])) {
            if (!$this->saveItemImages($item)) {
                return $this->displayError($this->l('An error occurred while saving image.'));
            }
        }
        // Copy item values from post.
        foreach (Item::$definition['fields'] as $field => $params) {
            if (!empty($params['lang'])) {
                foreach (Language::getIDs(false) as $id_lang) {
                    $item->{$field}[$id_lang] = Tools::getValue($field.'_'.$id_lang);
                    // Apply default lang value to empty lang value (only for new slider).
                    if (!$item->id and !$item->{$field}[$id_lang]) {
                        $item->{$field}[$id_lang] = Tools::getValue($field.'_'.Configuration::get('PS_LANG_DEFAULT'));
                    }
                }
                continue;
            }
            $item->$field = Tools::getValue($field, $item->$field);
        }
        // Validate item.
        if (true !== $error = $item->validateFields(false, true)) {
            return $this->displayError($error);
        }
        if (true !== $error = $item->validateFieldsLang(false, true)) {
            return $this->displayError($error);
        }
        // Save item.
        if (!$item->save()) {
            return $this->displayError($this->l('Unable to save slide.'));
        }
        // Validate and add/remove item slider(s) (relations).
        if (Tools::getIsset('sliders')) {
            foreach (Tools::getValue('sliders') as $slider) {
                if (!Validate::isUnsignedId($slider)) {
                    return $this->displayError(sprintf($this->l('%s is not a valid slider ID.'), $slider));
                }
            }
            if (!$this->saveItemSliders($item, Tools::getValue('sliders'))) {
                return $this->displayError($this->l('Unable to save slider(s) association(s).'));
            }
        }
        $this->_clearCache('*');

        if (Tools::isSubmit('saveItemAndStay')) {
            Tools::redirectAdmin(
                AdminController::$currentIndex.'&configure='.$this->name.
                '&updateItem&id_item='.$item->id.'&conf='.(Tools::getIsset('id_item') ? '4' : '3').
                '&token='.Tools::getAdminTokenLite('AdminModules')
            );
        }
        Tools::redirectAdmin(
            AdminController::$currentIndex.'&configure='.$this->name.
            '&conf='.(Tools::getIsset('id_item') ? '4' : '3').
            '&token='.Tools::getAdminTokenLite('AdminModules')
        );
    }

    /**
     * Save item images.
     *
     * @see CWCarousel::saveItem()
     */
    protected function saveItemImages(Item $item): bool
    {
        $type  = Tools::strtolower(substr(strrchr($_FILES['image']['name'], '.'), 1));
        $name  = str_replace(strrchr($_FILES['image']['name'], '.'), '', $_FILES['image']['name']);
        $infos = getimagesize($_FILES['image']['tmp_name']);

        if ($error = ImageManager::validateUpload($_FILES['image'])) {
            return ['error' => $error];
        }

        move_uploaded_file($_FILES['image']['tmp_name'], $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS'));

        if (!ImageManager::resize($temp_name, $this->getUploadPath($name.'.jpg'))) {
            return false;
        }
        foreach (Item::IMAGE_SIZES as $size => $width) {
            if (!ImageManager::resize(
                $this->getUploadPath($name.'.jpg'),
                $this->getUploadPath($name."_$size.jpg"),
                $width,
                $infos[1] * $width / $infos[0],
                'jpg',
                true
            )) {
                return false;
            }
        }

        unlink($temp_name);

        $item->image  = $name.'.jpg';
        $item->width  = $infos[0];
        $item->height = $infos[1];

        return true;
    }

    /**
     * Save item sliders.
     *
     * @see CWCarousel::saveItem()
     */
    protected function saveItemSliders(Item $item, array $sliders): bool
    {
        $old = $item->id ? array_column(Slider::getSlidersByItem($item->id), 'id_slider') : [];
        foreach (array_diff($old, $sliders) as $id_slider) {
            if (!$item->removeSlider($id_slider)) {
                return false;
            }
        }
        foreach (array_diff($sliders, $old) as $id_slider) {
            if (!$item->addSlider($id_slider)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Toggle item active value.
     *
     * @see CWCarousel::getContent()
     */
    protected function activeItem(): string
    {
        if (!(new Item(Tools::getValue('id_item')))->toggleStatus()) {
            if (Tools::getValue('ajax')) {
                die('{"text": "'.$this->l('Unable to toggle slide status.').'"}');
            }

            return $this->displayError($this->l('Unable to toggle slide status.'));
        }
        $this->_clearCache('*');

        if (Tools::getValue('ajax')) {
            die('{"success": 1, "text": "'.$this->l('Slide status successfully updated').'"}');
        }
        Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&conf=5&token='.Tools::getAdminTokenLite('AdminModules'));
    }

    /**
     * Delete item.
     *
     * @see CWCarousel::getContent()
     */
    protected function deleteItem(): string
    {
        if (!(new Item(Tools::getValue('id_item')))->delete()) {
            return $this->displayError($this->l('Unable to delete slide.'));
        }
        $this->_clearCache('*');

        Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&conf=1&token='.Tools::getAdminTokenLite('AdminModules'));
    }

    /**
     * (AJAX) Update item position.
     *
     * @see CWCarousel::getContent()
     */
    protected function processAjaxUpdateItemPositions()
    {
        $msg = '{"error"}';
        foreach (Tools::getValue('item') as $position => $ids) {
            $ids = explode('_', $ids); // $ids = ['tr', $id_slider, $id_item, $initial_position]
            $new = $position + (Tools::getValue('page', 0) - 1) * Tools::getValue('selected_pagination');
            if ($ids[2] !== Tools::getValue('id')) {
                continue;
            }
            if ($item = new Item($ids[2])) {
                $msg = $item->updatePosition($ids[1], Tools::getValue('way'), $ids[3], $new)
                     ? '{"success": "'.sprintf($this->l('Position of slide %1$d sucessfully updated to %2$d.'), $ids[2], $new).'"}'
                     : '{"hasError": true, "errors": "'.sprintf($this->l('Can not update slide %1$d to position %2$d.'), $ids[2], $new).'"}';
            } else {
                $msg = '{"hasError": true, "errors": "'.sprintf($this->l('Slide %d can not be loaded.'), $ids[2]).'"}';
            }
        }
        die($msg);
    }

    /**
     * Remove locations.
     *
     * @see CWCarousel::hookActionObject<Object>DeleteAfter()
     */
    protected function removeLocations(int $id_type, int $id_hook): bool
    {
        return Db::getInstance()->delete(Location::$definition['table'], "id_type = $id_type AND id_hook = $id_hook");
    }

    /**
     * Display slider.
     */
    protected function displaySlider(int $id_type, int $id_hook): string
    {
        $template_name = 'slider.tpl';
        $id_cache = $this->getCacheId().'|'.$id_type.'-'.$id_hook;
        if ($this->isCached($template_name, $id_cache)) {
            return $this->display(__FILE__, 'slider.tpl', $this->getCacheId().'|'.$id_type.'-'.$id_hook);
        }
        if (!$slider = Slider::getSliderByLocation($id_type, $id_hook)) {
            return '';
        }

        $slider['transition'] = static::TRANSITIONS[$slider['id_transition']];
        $slider['items'] = Item::getItemsBySlider($slider['id_slider'], true);
        foreach ($slider['items'] as &$item) {
            $item['image']  = $this->getUploadUrl($item['image']);
            $item['srcset'] = implode(',', array_map(function ($width, $size) use ($item) {
                return substr_replace($item['image'], "_$size", strrpos($item['image'], '.'), 0).' '.$width.'w';
            }, Item::IMAGE_SIZES, array_keys(Item::IMAGE_SIZES)));
        }
        $this->setTemplateVars(['slider' => $slider]);

        return $this->display(__FILE__, $template_name, $id_cache);
    }

    /**
     * Get options of sliders.
     *
     * @see CWCarousel::renderItemForm()
     */
    protected function getSlidersOptions(): array
    {
        return array_map(function ($slider) {
            return ['id_slider' => $slider['id_slider'], 'name' => $slider['name']];
        }, Slider::getSliders());
    }

    /**
     * Get upload path.
     */
    protected function getUploadPath(string $filename = ''): string
    {
        return _PS_IMG_DIR_."$this->name/$filename";
    }

    /**
     * Get upload URL.
     */
    protected function getUploadUrl(string $filename = ''): string
    {
        return $this->context->link->protocol_content.Tools::getMediaServer($this->name)."/img/$this->name/$filename";
    }

    /**
     * Set template variables.
     */
    protected function setTemplateVars(array $vars): Smarty_Internal_Data
    {
        return $this->smarty->assign($vars);
    }

    /**
     * Transform hexadecimal value into RGB value.
     *
     * @see CWCarousel::hookDisplayHeader()
     */
    protected static function hex2rgb(string $hex): string
    {
        $hex = str_replace('#', '', $hex);

        if (3 === strlen($hex)) {
            $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return implode(',', [$r, $g, $b]);
    }
}
