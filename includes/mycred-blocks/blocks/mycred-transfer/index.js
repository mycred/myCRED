(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var ToggleControl = wp.components.ToggleControl;
    var TextareaControl = wp.components.TextareaControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-transfers', {
        title: __('Transfer', 'mycred'),
        category: 'mycred',
        attributes: {
            button: {
                type: 'string'
            },
            pay_to: {
                type: 'string'
            },
            show_balance: {
                type: 'bool',
                default: false
            },
            show_limit: {
                type: 'bool',
                default: false
            },
            types: {
                type: 'string'
            },
            excluded: {
                type: 'string'
            },
            amount: {
                type: 'integer'
            },
            placeholder: {
                type: 'string'
            },
            ref: {
                type: 'string',
                default: 'transfer'
            },
            recipient_label: {
                type: 'string',
                default: 'Recipient'
            },
            amount_label: {
                type: 'string',
                default: 'Amount'
            },
            balance_label: {
                type: 'string',
                default: 'Balance'
            }
        },
        edit: function (props) {
            var button = props.attributes.button;
            var pay_to = props.attributes.pay_to;
            var placeholder = props.attributes.placeholder;
            var show_balance = props.attributes.show_balance;
            var balance_label = props.attributes.balance_label;
            var show_limit = props.attributes.show_limit;
            var ref = props.attributes.ref;
            var amount = props.attributes.amount;
            var types = props.attributes.types;
            var excluded = props.attributes.excluded;
            var recipient_label = props.attributes.recipient_label;
            var amount_label = props.attributes.amount_label;

            function setButton(value) {
                props.setAttributes({button: value});
            }
            function setAmount(value) {
                props.setAttributes({amount: value});
            }
            function setCtype(value) {
                props.setAttributes({types: value});
            }

            function setPayTo(value) {
                props.setAttributes({pay_to: value});
            }
            function setRef(value) {
                props.setAttributes({ref: value});
            }

            function setShowLimit(value) {
                props.setAttributes({show_limit: value});
            }

            function setShowBalance(value) {
                props.setAttributes({show_balance: value});
            }

            function setExcluded(value) {
                props.setAttributes({excluded: value});
            }
            function setRecLabel(value) {
                props.setAttributes({recipient_label: value});
            }

            function setAmountLabel(value) {
                props.setAttributes({amount_label: value});
            }

            function setBalanceLabel(value) {
                props.setAttributes({balance_labal: value});
            }

            function setPlaceholder(value) {
                props.setAttributes({placeholder: value});
            }

            return el('div', {}, [
                el('p', {}, __('Transfer Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('Button Label', 'mycred'),
                            help: __('The submit transfer button label. Leave empty to use the default label you set in your settings.', 'mycred'),
                            value: button,
                            onChange: setButton
                        }),
                        el(TextControl, {
                            label: __('Recipient', 'mycred'),
                            help: __('Option to pre-select a specific user to transfer points to. If left empty, the user must nominate the recipient.', 'mycred'),
                            value: pay_to,
                            onChange: setPayTo
                        }),
                        el(ToggleControl, {
                            label: __('Show Balance', 'mycred'),
                            help: __('Option to include the current users balance', 'mycred'),
                            checked: show_balance,
                            onChange: setShowBalance
                        }),
                        el(ToggleControl, {
                            label: __('Show Limit', 'mycred'),
                            help: __('Option to include the current users remaining transfer limit', 'mycred'),
                            checked: show_limit,
                            onChange: setShowLimit
                        }),
                        el(TextareaControl, {
                            label: __('Point Types', 'mycred'),
                            help: __('A comma separated list of point type keys that users can transfer. You can also just use one specific key to lock transfers to this point type only. Otherwise leave this field empty to use the default point type.', 'mycred'),
                            value: types,
                            onChange: setCtype,
                            rows: 2
                        }),
                        el(TextControl, {
                            label: __('Excluded message', 'mycred'),
                            help: __('Message to show when the user attempting to make a transfer has been set to be "Excluded" from using myCRED.', 'mycred'),
                            value: excluded,
                            onChange: setExcluded
                        }),
                        el(TextControl, {
                            label: __('Amount', 'mycred'),
                            help: __('Amount of points for clicking on this link. Use zero to give the amount you set in your "Points for clicking on links" hook settings.', 'mycred'),
                            value: amount,
                            onChange: setAmount
                        }),
                        el(TextControl, {
                            label: __('Placeholder', 'mycred'),
                            help: __('Optional text to show in the recipient field via the placeholder attribute.', 'mycred'),
                            value: placeholder,
                            onChange: setPlaceholder
                        }),
                        el(TextControl, {
                            label: __('Reference', 'mycred'),
                            help: __('By default, transfers are logged with "transfer" as reference. You can change this to a unique lowercase word like "donation" or "charity_fund_raiser" to separate transfers made with this shortcode from others.', 'mycred'),
                            value: ref,
                            onChange: setRef
                        }),
                        el(TextControl, {
                            label: __('Recipient Label', 'mycred'),
                            help: __('Option to change the label shown above the recipient field.', 'mycred'),
                            value: recipient_label,
                            onChange: setRecLabel
                        }),
                        el(TextControl, {
                            label: __('Amount Label', 'mycred'),
                            help: __('Option to change the label shown above the amount field.', 'mycred'),
                            value: amount_label,
                            onChange: setAmountLabel
                        }),
                        el(TextControl, {
                            label: __('Balance Label', 'mycred'),
                            help: __('Option to change the label shown above the point type selection. Only used if more then one point type is setup to be transferable.', 'mycred'),
                            value: balance_label,
                            onChange: setBalanceLabel
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