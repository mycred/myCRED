(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-buy-form', {
        title: __('Buy Form', 'mycred'),
        category: 'mycred',
        attributes: {
            button: {
                type: 'string',
                default: 'Buy Now'
            },
            gateway: {
                type: 'string'
            },
            ctype: {
                type: 'string'
            },
            amount: {
                type: 'string'
            },
            gift_to: {
                type: 'string'
            },
            gift_by: {
                type: 'string'
            },
            inline: {
                type: 'bool',
                default: false
            }

        },
        edit: function (props) {
            var button = props.attributes.button;
            var gateway = props.attributes.title;
            var ctype = props.attributes.ctype;
            var amount = props.attributes.amount;
            var gift_to = props.attributes.gift_to;
            var gift_by = props.attributes.gift_by;

            var inline = props.attributes.inline;

            var options = [];
            function setGateway(value) {
                props.setAttributes({gateway: value});
            }

            function setButton(value) {
                props.setAttributes({button: value});
            }

            function setType(value) {
                props.setAttributes({ctype: value});
            }
            function setAmount(value) {
                props.setAttributes({amount: value});
            }
            function setGiftTo(value) {
                props.setAttributes({gift_to: value});
            }
            function setGiftBy(value) {
                props.setAttributes({gift_by: value});
            }
            function setInline(value) {
                props.setAttributes({inline: value});
            }

            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });
            return el('div', {}, [
                el( 'p', {}, __('Buy Form Shortcode', 'mycred') ),
                el( InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('Button Label', 'mycred'),
                            help: __('The label for the form submit button.', 'mycred'),
                            value: button,
                            onChange: setButton
                        }),
                        el(TextControl, {
                            label: __('Gateway', 'mycred'),
                            help: __('Enter the gateway ID to enforce the use of a specific gateway or leave empty to let users choose.', 'mycred'),
                            value: gateway,
                            onChange: setGateway,
                        }),
                        el(SelectControl, {
                            label: __('Point Type', 'mycred'),
                            help: __('The point type you want to show.', 'mycred'),
                            value: ctype,
                            onChange: setType,
                            options
                        }),
                        el(TextControl, {
                            label: __('Amount', 'mycred'),
                            help: __('This can either be a set amount for users to buy, a comma separated list of amounts that users can choose from or left empty in which case the user decides how much they want to buy..', 'mycred'),
                            value: amount,
                            onChange: setAmount
                        }),
                        el(TextControl, {
                            label: __('Gift to', 'mycred'),
                            help: __('By default, the current user will receive the purchased amount. Use "author" to gift purchases to the post author or a specific users ID. Leave empty if not used!', 'mycred'),
                            value: gift_to,
                            onChange: setGiftTo
                        }),
                        el(TextControl, {
                            label: __('Gift By', 'mycred'),
                            value: gift_by,
                            onChange: setGiftBy
                        }),
                        el(ToggleControl, {
                            label: __('Inline', 'mycred'),
                            help: __('Controls if the form should be inline (1) or not (0). Requires themes using the Bootstrap framework.', 'mycred'),
                            checked: inline,
                            onChange: setInline
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