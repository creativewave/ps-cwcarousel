# Sliders

## About

Sliders is a Prestashop module to manage and display sliders. It focuses on performances and currently support:

- [x] same images on multiple sliders
- [x] `srcset` attribute
- [ ] preloading
- [ ] lazy loading

This module is currently used in production websites with Prestashop 1.6 and PHP 7+, but you may need to tweak some CSS and/or JS for your needs. The best way to make changes and still get updates is to create your own git branch and rebase/merge/cherry-pick new versions or specific commits.

## Installation

This module is best used with Composer managing your Prestashop project globally. This method follows best practices for managing external dependencies of a PHP project.

Create or edit `composer.json` in the Prestashop root directory:

```json
"repositories": [
  {
    "type": "git",
    "url": "https://github.com/creativewave/ps-cwcarousel"
  },
  {
    "type": "git",
    "url": "https://github.com/creativewave/ps-objectmodel-extension"
  }
],
"require": {
  "creativewave/ps-cwmedia": "^1"
},

```

Then run `composer update`.

## Todo

* Improvement: use admin module controllers.
* Improvement: remove CSS/JS dependencies.
* Improvement: preload first image and lazy load the others.
* Improvement: use CSS variables instead of inlining CSS.
* Unit tests
