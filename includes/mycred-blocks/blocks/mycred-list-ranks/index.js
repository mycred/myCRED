(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-list-ranks', {
        title: __('List Ranks', 'mycred'),
        category: 'mycred',
        attributes: {
            order: {
                type: 'string'
            },
            ctype: {
                type: 'string'
            },
            wrap: {
                type: 'string',
                default: 'div'
            }
        },
        edit: function (props) {
            var order = props.attributes.order;
            var ctype = props.attributes.ctype;
            var wrap = props.attributes.wrap;

            var options = [];

            function setOrder(value) {
                props.setAttributes({order: value});
            }
            function setPtType(value) {
                props.setAttributes({ctype: value});
            }
            function setWrap(value) {
                props.setAttributes({wrap: value});
            }
            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });

            return el('div', {}, [
                el('p', {}, __('List Ranks Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(SelectControl, {
                            label: __('Order', 'mycred'),
                            help: __('Rank listing order', 'mycred'),
                            value: order,
                            onChange: setOrder,
                            options: [
                                {label: 'Descending', value: 'DESC'},
                                {label: 'Ascending', value: 'ASC'}
                            ]
                        }),
                        el(SelectControl, {
                            label: __('Point Type', 'mycred'),
                            help: __('The point type you want to show', 'mycred'),
                            value: ctype,
                            onChange: setPtType,
                            options
                        }),
                        el(TextControl, {
                            label: __('Wrapper', 'mycred'),
                            help: __('The HTML element to use as the main wrapper around this shortcodes results', 'mycred'),
                            value: wrap,
                            onChange: setWrap
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