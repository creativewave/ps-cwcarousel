{if $slider.items}
<div id="cw-slider-{$slider.id_slider}" class="{if 1 < count($slider.items)}owl-carousel owl-theme owl_images_slider owl-navigation-lr {if 1 == $slider.arrow}owl-navigation-square {elseif 2 == $slider.arrow}owl-navigation-circle {elseif 3 == $slider.arrow}owl-navigation-rectangle {/if}{/if}mar_b2"{if 2 > count($slider.items)} style="position:relative;"{/if}>
{foreach $slider.items as $item}
    {if $item.url}<a href="{$item.url|escape:'html'}" title="{$item.title|escape:'htmlall':'UTF-8'}" {if $item.new_window}target="_blank"{/if} {if $item.new_window}rel="noopener"{/if}>
    {else}<div>{/if}
        <img src="{$item.image}" alt="{$item.title|escape:'htmlall':'UTF-8'}" width="{$item.width}" height="{$item.height}" srcset="{$item.srcset}" sizes="(max-width: 1920px) 100vw, 1920px" class="lazyOwl" data-src="{$item.image}"/>
        {if $item.caption}
        <div class="owl-caption" style="{if 1 == $item.text_align}align-items:flex-start;{elseif 2 == $item.text_align}align-items:center;{elseif 3 == $item.text_align}align-items:flex-end;{/if}{if 2 == $item.text_position}justify-content:center;{elseif 3 == $item.text_position}justify-content:flex-end;{/if}">
            {$item.caption}
        </div>
        {/if}
    {if $item.url}</a>
    {else}</div>{/if}
{/foreach}
</div>
{if 1 < count($slider.items)}
<script>
    {literal}
    (function ($) {

        $("#cw-slider-{/literal}{$slider.id_slider}{literal}").owlCarousel({
            {/literal}
                singleItem:      true,
                lazyLoad:        true,
                stopOnHover:     true,
                slideSpeed:      {$slider.transition_duration|default:200},
                autoHeight:      {if $slider.resize}true{else}false{/if},
                pagination:      {if $slider.navigation}true{else}false{/if},
                navigation:      {if $slider.arrow}true{else}false{/if},
                rewindNav:       {if $slider.loop}true{else}false{/if},
                transitionStyle: '{$slider.transition}',
                afterInit:       initProgressBar,
                afterMove:       resetProgressBar,
                startDragging:   pauseOnDragging,
            {literal}
        })

        var time = {/literal}{$slider.duration|default:5000}{literal},
            slider,
            progressBar,
            progressBarWrapper,
            isPause,
            tick,
            percentTime

        function initProgressBar(instance) {
            slider             = instance
            progressBar        = $('<div>', {class: 'owl_bar'})
            progressBarWrapper = $('<div>', {class: 'owl_progressBar'})
            progressBar.append(progressBar).appendTo(slider)
            startProgressBar()
        }
        function startProgressBar() {
            percentTime = 0
            isPause = false
            tick = setInterval(interval, 10)
        };
        function interval() {
            if (false === isPause) {
                percentTime += 1000 / time
                progressBar.css({width: percentTime + '%'})
                if (100 <= percentTime) {
                    slider.trigger('owl.next')
                }
            }
        }
        function resetProgressBar() {
            clearTimeout(tick)
            startProgressBar()
        }
        function pauseOnDragging() {
            isPause = true
        }

        slider.on('mouseover', function() {
            isPause = true
        })
        slider.on('mouseout', function() {
            isPause = false
        })

    })(jQuery)
    {/literal}
</script>
{/if}
{/if}
