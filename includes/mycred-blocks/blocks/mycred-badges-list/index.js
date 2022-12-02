(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var panelBody = wp.components.PanelBody;

    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-badges-list', {
        title: __('Badges List', 'mycred'),
        category: 'mycred',
        attributes: {
            achievement_tabs : {
                type: 'string',
                default: 1
            }
        },
        edit: function (props) {
            var achievement_tabs = props.attributes.achievement_tabs;

            function setAchievement_tabs(value) {
                props.setAttributes({achievement_tabs: value});
            }


            return el('div', {}, [
                el('p', {}, __('Badges List Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('Achievement Tabs', 'mycred'),
                            help: __(' Use (1) to enable or (0) to disable the achievement tabs.', 'mycred'),
                            value: achievement_tabs,
                            onChange: setAchievement_tabs

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