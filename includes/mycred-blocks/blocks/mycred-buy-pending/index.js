(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var panelBody = wp.components.PanelBody;

    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-buy-pending', {
        title: __('Buy Pending', 'mycred'),
        category: 'mycred',
        attributes: {
            ctype : {
                type: 'string',
            },
            pay_now : {
                type: 'string',
                default: 'Pay Now'
            },
            cancel : {
                type: 'string',
                default: 'Cancel'
            }
        },
        edit: function (props) {
            var ctype = props.attributes.ctype;
            var pay_now = props.attributes.pay_now;
            var cancel = props.attributes.cancel;

            function setTypes(value) {
                props.setAttributes({ctype: value});
            }

            function setPayNow(value) {
                props.setAttributes({pay_now: value});
            }

            function setCancel(value) {
                props.setAttributes({cancel: value});
            }


            return el('div', {}, [
                el('p', {}, __('Buy Pending Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('Point Type', 'mycred'),
                            help: __('Set to empty if you sell multiple point types and want to show all of them in one table. Should not be used if you only sell one point type.', 'mycred'),
                            value: ctype,
                            onChange: setTypes

                        }),
                        el(TextControl, {
                            label: __('Pay Now label', 'mycred'),
                            help: __('The label to use for the "Pay Now" action.', 'mycred'),
                            value: pay_now,
                            onChange: setPayNow

                        }),
                        el(TextControl, {
                            label: __('Cancel label', 'mycred'),
                            help: __('The label to use for the "Cancel" action.', 'mycred'),
                            value: cancel,
                            onChange: setCancel

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