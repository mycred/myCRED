(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var panelBody = wp.components.PanelBody;

    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-badges', {
        title: __('Badges', 'mycred'),
        category: 'mycred',
        attributes: {
            width: {
                type: 'string',
                default: 'MYCRED_BADGE_WIDTH'
            },
            height: {
                type: 'string',
                default: 'MYCRED_BADGE_HEIGHT'
            }
        },
        edit: function (props) {
            var width = props.attributes.width;
            var height = props.attributes.height;

            function setWidth(value) {
                props.setAttributes({width: value});
            }

            function setHeight(value) {
                props.setAttributes({height: value});
            }


            return el('div', {}, [
                el('p', {}, __('Badges Shortcode', 'mycred') ),
                el(InspectorControls, null,
                     el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('Width', 'mycred'),
                            help: __('The badge image width to use.', 'mycred'),
                            value: width,
                            onChange: setWidth

                        }),
                        el(TextControl, {
                            label: __('Height', 'mycred'),
                            help: __('The badge image height to use.', 'mycred'),
                            value: height,
                            onChange: setHeight
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