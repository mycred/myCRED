(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;
    var TextareaControl = wp.components.TextareaControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-my-balance', {
        title: __('My Balance', 'mycred'),
        category: 'mycred',
        attributes: {
            user_id: {
                type: 'string'
            },
            title: {
                type: 'string'
            },
            title_el: {
                type: 'string',
                default: 'h1'
            },
            balance_el: {
                type: 'string',
                default: 'div'
            },
            wrapper: {
                type: 'bool',
                default: true
            },
            formatted: {
                type: 'bool',
                default: true
            },
            type: {
                type: 'string'
            },
            content: {
                type: 'string'
            }
        },
        edit: function (props) {
            var user_id = props.attributes.user_id;
            var title = props.attributes.title;
            var title_el = props.attributes.title_el;
            var balance_el = props.attributes.balance_el;

            var wrapper = props.attributes.wrapper;
            var formatted = props.attributes.formatted;
            var type = props.attributes.type;
            var content = props.attributes.content;

            var options = [];

            function setUserId(value) {
                props.setAttributes({user_id: value});
            }
            function setTitle(value) {
                props.setAttributes({title: value});
            }
            function setTitleEl(value) {
                props.setAttributes({title_el: value});
            }
            function setBalanceEl(value) {
                props.setAttributes({balance_el: value});
            }
            function setWrapper(value) {
                props.setAttributes({wrapper: value});
            }
            function setFormatted(value) {
                props.setAttributes({formatted: value});
            }
            function setType(value) {
                props.setAttributes({type: value});
            }
            function setContent(value) {
                props.setAttributes({content: value});
            }

            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });
            return el('div', {}, [
                el('p', {}, __('My Balance Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('User ID', 'mycred'),
                            help: __('The users balance you want to show. Use "current" to show the user who is viewing the shortcode. Can not be empty.', 'mycred'),
                            value: user_id,
                            onChange: setUserId
                        }),
                        el(TextControl, {
                            label: __('Title', 'mycred'),
                            help: __('Optional title to add before the balance.', 'mycred'),
                            value: title,
                            onChange: setTitle
                        }),
                        el(TextControl, {
                            label: __('Title Element', 'mycred'),
                            help: __('The HTML element to wrap around the title. Leave empty if not used.', 'mycred'),
                            value: title_el,
                            onChange: setTitleEl
                        }),
                        el(TextControl, {
                            label: __('Balance Element', 'mycred'),
                            help: __('The HTML element to wrap around the balance. Leave empty to hide wrapping element', 'mycred'),
                            value: balance_el,
                            onChange: setBalanceEl
                        }),
                        el(ToggleControl, {
                            label: __('Use Wrappers', 'mycred'),
                            help: __('Select if you want to wrap the balance for styling or just return the balance amount.', 'mycred'),
                            checked: wrapper,
                            onChange: setWrapper
                        }),
                        el(ToggleControl, {
                            label: __('Format Balance', 'mycred'),
                            help: __('Select if you want to format the balance amount with prefix / suffix or show just the amount. (Requires 1.7)', 'mycred'),
                            checked: formatted,
                            onChange: setFormatted
                        }),
                        el(SelectControl, {
                            label: __('Point Type', 'mycred'),
                            help: __('The point type you want to show.', 'mycred'),
                            value: type,
                            onChange: setType,
                            options
                        }),
                        el(TextareaControl, {
                            label: __('Visitors Message', 'mycred'),
                            help: __('Optional message to show when the shortcode is viewed by a visitor that is not logged in.', 'mycred'),
                            value: content,
                            onChange: setContent
                        }),
                    )
                )
            ]);
        },
        save: function (props) {
            return null;
        }
    });
})(window.wp);