<?php

namespace FluentCampaign\App\Services;

use FluentCrm\Framework\Support\Arr;

class MetaFormBuilder
{
    private $fields = [];

    public function addField($field)
    {
        $this->fields[] = $field;
        return $this;
    }

    public function renderFields()
    {
        $fields = $this->fields;
        if(!$fields) {
            return '';
        }
        echo '<table class="form-table"><tbody>';
        foreach ($fields as $field) {
            if($field['type'] == 'select') {
                $this->renderSelect($field);
            }
        }
        echo '</tbody></table>';
    }

    public function renderSelect($field)
    {
        $fieldId = sanitize_key($field['name']);
        $label = '<label for="'.$fieldId.'">'.Arr::get($field, 'label', '').'</label>';
        $optionValues = (array) Arr::get($field, 'value');

        $selects = '';

        foreach (Arr::get($field, 'options', []) as $option) {
            $isSelected = in_array($option['key'], $optionValues);
            $selects .= '<option '.selected( true, $isSelected, false ).' value="'.$option['key'].'">'.$option['title'].'</option>';
        }

        $dataAtts = (array) Arr::get($field, 'data_attributes', []);

        $atts = [
            'name' => $field['name'],
            'class'=> 'fcrm_select '.Arr::get($field, 'class'),
            'id' => $fieldId
        ];

        if(Arr::get($field, 'multi')) {
            $atts['multiple'] = 'multiple';
        }

        $atts = array_unique(array_merge($dataAtts, $atts));

        $attributes = '';
        foreach ($atts as $attKey => $attValue) {
            $attributes .= esc_html($attKey).'="'.esc_html($attValue).'" ';
        }

        $input = '<select '.$attributes.'>'.$selects.'</select>';

        if($description = Arr::get($field, 'desc')) {
            $input .= '<p class="description">'.esc_html($description).'</p>';
        }
        $this->renderFieldBase($label, $input);
    }

    public function renderFieldBase($label, $input)
    {
        ?>
        <tr>
            <th scope="row"><?php echo $label; ?></th>
            <td><?php echo $input; ?></td>
        </tr>
        <?php
    }

    public function initMultiSelect($selector = '', $placeholder = 'Select')
    {
        wp_enqueue_style('choices-css', FLUENTCRM_PLUGIN_URL . 'assets/libs/choices/choices.min.css', [], '10.0.0');
        wp_enqueue_script('choices-js', FLUENTCRM_PLUGIN_URL . 'assets/libs/choices/choices.min.js', ['jquery'], '10.0.0', true);

        /*
         * The old multiple-select widget was replaced with Choices.js because large tag lists
         * could render/scroll poorly in metaboxes. This guard keeps our shared Choices CSS
         * from being printed more than once when multiple fields call initMultiSelect().
         */
        static $choicesInlineStyleBooted = false;

        if (!$choicesInlineStyleBooted) {
            $choicesInlineStyleBooted = true;

            /*
             * These footer styles patch the generated Choices markup for WordPress admin metaboxes:
             * full-width layout, selected chip styling, high dropdown z-index, and a bounded
             * scroll area for large option lists.
             */
            add_action('admin_footer', function () {
                ?>
                <style>
                    /*
                     * Keep the Choices wrapper aligned with the original full-width select field
                     * and allow the dropdown to escape the wrapper when opened.
                     */
                    .choices {
                        position: relative !important;
                        overflow: visible !important;
                        width: 100%;
                        margin-bottom: 0;
                    }

                    /* Preserve a compact WordPress-admin field height while using Choices markup. */
                    .choices__inner {
                        background-color: #fff;
                        min-height: 36px;
                    }

                    /* Make selected tag chips visually clear and consistent with WP admin blue. */
                    .choices[data-type*='select-multiple'] .choices__list--multiple .choices__item {
                        background-color: #007cba !important;
                        border-color: #007cba !important;
                        color: #fff !important;
                    }

                    /* Keep selected chips in a full-width area instead of shrinking around content. */
                    .choices__list--multiple {
                        display: block;
                        width: 100%;
                    }

                    /* Use a darker blue for highlighted selected chips. */
                    .choices[data-type*='select-multiple'] .choices__list--multiple .choices__item.is-highlighted {
                        background-color: #006ba1 !important;
                        border-color: #006ba1 !important;
                        color: #fff !important;
                    }

                    /* Keep the remove-button separator visible against the blue selected chip. */
                    .choices[data-type*='select-multiple'] .choices__list--multiple .choices__button {
                        border-left-color: rgba(255, 255, 255, 0.45) !important;
                    }

                    /*
                     * Force the Choices search input to use the available width via CSS.
                     * This replaces the earlier fragile override of Choices' private input.setWidth().
                     */
                    .choices[data-type*='select-multiple'] .choices__input,
                    .choices[data-type*='select-multiple'] .choices__input--cloned {
                        display: block;
                        min-width: 100% !important;
                        width: 100% !important;
                        max-width: 100% !important;
                    }

                    /* Keep dropdowns above WordPress metabox/editor UI. */
                    .choices__list.choices__list--dropdown,
                    .choices__list[aria-expanded] {
                        z-index: 100000 !important;
                    }

                    /* Prevent 100+ tags from stretching the page; options scroll inside the dropdown. */
                    .choices__list--dropdown .choices__list,
                    .choices__list[aria-expanded] .choices__list {
                        max-height: 360px;
                        overflow-y: auto;
                        overscroll-behavior: contain;
                    }
                </style>
                <?php
            });
        }

        /*
         * Print the heavy Choices bootstrap once per page.
         * Per-selector calls below only register selector/placeholder config, which avoids
         * repeating the same large inline script when several metabox fields use this helper.
         */
        static $choicesBootstrapBooted = false;

        if (!$choicesBootstrapBooted) {
            $choicesBootstrapBooted = true;

            add_action('admin_footer', function () {
                ?>
                <script>
                    (function ($) {
                        if (window.fcRegisterChoicesMultiSelect) {
                            return;
                        }

                        /*
                         * Find target selects for both initial page load and dynamic admin UI.
                         * Without contextNodes this can do the first selector lookup; with contextNodes
                         * it searches only inside newly added DOM instead of rescanning the whole page.
                         */
                        const findChoicesMultiSelects = function (selector, contextNodes) {
                            if (!contextNodes || !contextNodes.length) {
                                return $(selector);
                            }

                            let matches = $();

                            contextNodes.forEach(function (node) {
                                if (!node || (node.nodeType !== 1 && node.nodeType !== 11)) {
                                    return;
                                }

                                const $node = $(node);

                                if (node.nodeType === 1) {
                                    matches = matches.add($node.filter(selector));
                                }

                                matches = matches.add($node.find(selector));
                            });

                            return matches;
                        };

                        /*
                         * Initialize Choices.js while leaving the original select name/value intact.
                         * That keeps existing metabox save handlers and submitted field names unchanged.
                         */
                        const initChoicesMultiSelect = function (selector, placeholder, contextNodes) {
                            findChoicesMultiSelects(selector, contextNodes).each(function () {
                                const select = this;

                                /*
                                 * The same select can be seen by the initial load and by later DOM mutations.
                                 * Marking it prevents duplicate Choices instances on one element.
                                 */
                                if (select.dataset.fcChoicesInitialized === 'yes') {
                                    return;
                                }

                                select.dataset.fcChoicesInitialized = 'yes';

                                new Choices(select, {
                                    removeItemButton: true,
                                    searchEnabled: true,
                                    // Limit rendered search results so each keystroke does not create a large DOM list.
                                    searchResultLimit: 10,
                                    // Limit the initial dropdown render; users can still find later tags through search.
                                    renderChoiceLimit: 100,
                                    // Preserve the PHP-provided option order instead of sorting again in the browser.
                                    shouldSort: false,
                                    /*
                                     * Auto positioning currently breaks the metabox select/options layout.
                                     * Keep bottom positioning until the auto-flip CSS can be verified safely.
                                     */
                                    position: 'bottom',
                                    placeholder: true,
                                    placeholderValue: placeholder,
                                    itemSelectText: '',
                                    noResultsText: '<?php echo esc_js(__('No matching options found', 'fluentcampaign-pro')); ?>',
                                    noChoicesText: '<?php echo esc_js(__('No options available', 'fluentcampaign-pro')); ?>',
                                    allowHTML: false,
                                    resetScrollPosition: true
                                });
                            });
                        };

                        /*
                         * Expose the shared helpers/config registry so tiny per-selector footer snippets
                         * can register fields without reprinting this whole bootstrap.
                         */
                        window.fcFindChoicesMultiSelects = findChoicesMultiSelects;
                        window.fcInitChoicesMultiSelect = initChoicesMultiSelect;
                        window.fcChoicesMultiSelectConfigs = window.fcChoicesMultiSelectConfigs || [];

                        /*
                         * Support selects that are inserted after page load, such as EDD price rows or
                         * integration panels rendered by AJAX.
                         */
                        const setupChoicesMultiSelectObserver = function () {
                            if (window.fcChoicesMultiSelectObserver || !window.MutationObserver) {
                                return;
                            }

                            let queuedAddedNodes = [];
                            let queuedAddedNodesTimer = null;

                            /*
                             * The observer watches document.body for broad integration compatibility, but
                             * unrelated admin DOM additions are ignored before they reach the init queue.
                             */
                            const nodeContainsRegisteredSelector = function (node) {
                                const $node = $(node);

                                return window.fcChoicesMultiSelectConfigs.some(function (config) {
                                    return config.selector && (
                                        (node.nodeType === 1 && $node.is(config.selector)) ||
                                        $node.find(config.selector).length
                                    );
                                });
                            };

                            /*
                             * Queue only element/document-fragment nodes that can contain registered selects,
                             * and avoid queueing the same added node more than once.
                             */
                            const queueAddedNode = function (node) {
                                if (!node || (node.nodeType !== 1 && node.nodeType !== 11) || queuedAddedNodes.indexOf(node) !== -1) {
                                    return false;
                                }

                                if (!nodeContainsRegisteredSelector(node)) {
                                    return false;
                                }

                                queuedAddedNodes.push(node);
                                return true;
                            };

                            /*
                             * Re-initialize only inside queued added-node contexts.
                             * This avoids the previous full-document selector rescan on every mutation.
                             */
                            const flushQueuedAddedNodes = function () {
                                queuedAddedNodesTimer = null;

                                if (!queuedAddedNodes.length) {
                                    return;
                                }

                                const contextNodes = queuedAddedNodes.slice();
                                queuedAddedNodes = [];

                                window.fcChoicesMultiSelectConfigs.forEach(function (config) {
                                    initChoicesMultiSelect(config.selector, config.placeholder, contextNodes);
                                });
                            };

                            /*
                             * Debounce rapid admin DOM changes so many mutations collapse into one init pass.
                             */
                            const scheduleQueuedAddedNodesFlush = function () {
                                if (queuedAddedNodesTimer) {
                                    return;
                                }

                                queuedAddedNodesTimer = window.setTimeout(flushQueuedAddedNodes, 100);
                            };

                            /*
                             * Watch dynamic inserts for the page lifetime, but only relevant nodes are queued.
                             */
                            window.fcChoicesMultiSelectObserver = new MutationObserver(function (mutations) {
                                let hasQueuedNodes = false;

                                mutations.forEach(function (mutation) {
                                    if (!mutation.addedNodes || !mutation.addedNodes.length) {
                                        return;
                                    }

                                    Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                                        hasQueuedNodes = queueAddedNode(node) || hasQueuedNodes;
                                    });
                                });

                                if (hasQueuedNodes) {
                                    scheduleQueuedAddedNodesFlush();
                                }
                            });

                            window.fcChoicesMultiSelectObserver.observe(document.body, {
                                childList: true,
                                subtree: true
                            });
                        };

                        let registeredConfigsInitQueued = false;

                        /*
                         * Coalesce multiple selector registrations into a single jQuery-ready init pass.
                         */
                        const initRegisteredConfigs = function () {
                            registeredConfigsInitQueued = false;

                            window.fcChoicesMultiSelectConfigs.forEach(function (config) {
                                initChoicesMultiSelect(config.selector, config.placeholder);
                            });

                            setupChoicesMultiSelectObserver();
                        };

                        const scheduleRegisteredConfigsInit = function () {
                            if (registeredConfigsInitQueued) {
                                return;
                            }

                            registeredConfigsInitQueued = true;
                            $(initRegisteredConfigs);
                        };

                        /*
                         * Lightweight API called by each initMultiSelect() invocation.
                         * It stores selector/placeholder config once and schedules initialization.
                         */
                        window.fcRegisterChoicesMultiSelect = function (selector, placeholder) {
                            const configExists = window.fcChoicesMultiSelectConfigs.some(function (config) {
                                return config.selector === selector;
                            });

                            if (!configExists) {
                                window.fcChoicesMultiSelectConfigs.push({
                                    selector: selector,
                                    placeholder: placeholder
                                });
                            }

                            scheduleRegisteredConfigsInit();
                        };
                    })(jQuery);
                </script>
                <?php
            });
        }

        if ($selector) {
            /*
             * Keep per-selector output small.
             * The shared bootstrap above owns initialization; this only registers the selector
             * and placeholder needed by the current metabox/integration.
             */
            add_action('admin_footer', function () use ($selector, $placeholder) {
                ?>
                <script>
                    window.fcRegisterChoicesMultiSelect('<?php echo esc_js($selector); ?>', '<?php echo esc_js($placeholder); ?>');
                </script>
                <?php
            });
        }
    }
}
