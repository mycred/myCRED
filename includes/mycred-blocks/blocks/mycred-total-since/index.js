(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var ToggleControl = wp.components.ToggleControl;
    var SelectControl = wp.components.SelectControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-total-since', {
        title: __('Total Since', 'mycred'),
        category: 'mycred',
        attributes: {
            from: {
                type: 'string'
            },
            until: {
                type: 'string'
            },
            type: {
                type: 'string'
            },
            ref: {
                type: 'string'
            },
            user_id: {
                type: 'string'
            },
            formatted: {
                type: 'bool',
                default: true
            }
        },
        edit: function (props) {
            var from = props.attributes.from;
            var until = props.attributes.until;
            var type = props.attributes.type;
            var ref = props.attributes.ref;
            var user_id = props.attributes.user_id;
            var formatted = props.attributes.formatted;
            var options = [];

            function setFrom(value) {
                props.setAttributes({from: value});
            }
            function setUntil(value) {
                props.setAttributes({until: value});
            }
            function setPtType(value) {
                props.setAttributes({type: value});
            }
            function setRef(value) {
                props.setAttributes({ref: value});
            }
            function setUserId(value) {
                props.setAttributes({user_id: value});
            }
            function setFormatted(value) {
                props.setAttributes({type: value});
            }

            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });

            return el('div', {}, [
                el('p', {}, __('Total Since Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('From', 'mycred'),
                            help: __('Option to set from when we should start adding up points. Accepts: "today" for start of today, a UNIX timestamp or a well formatted date. See PHPs strtotime for further information on available options.', 'mycred'),
                            value: from,
                            onChange: setFrom

                        }),
                        el(TextControl, {
                            label: __('Until', 'mycred'),
                            help: __('Option to set what time to stop counting. Accepts: "now" for now, a UNIX timestamp or a well formatted date. See PHPs strtotime for further information on available options.', 'mycred'),
                            value: until,
                            onChange: setUntil

                        }),
                        el(SelectControl, {
                            label: __('Point Type', 'mycred'),
                            help: __('The point type to add up', 'mycred'),
                            value: type,
                            onChange: setPtType,
                            options
                        }),
                        el(TextControl, {
                            label: __('Reference', 'mycred'),
                            help: __('Option to filter results based on a reference.', 'mycred'),
                            value: ref,
                            onChange: setRef
                        }),
                        el(TextControl, {
                            label: __('User ID', 'mycred'),
                            help: __('Option to add up points for a specific user. Use "current" for the current user viewing the shortcode or leave empty to add up points for everyone. Must be used in combination with "Reference" and/or "Reference ID" above.', 'mycred'),
                            value: user_id,
                            onChange: setUserId
                        }),
                        el(ToggleControl, {
                            label: __('Formatted', 'mycred'),
                            help: __('Option to show results formatted with prefix / suffix (1) or in plain format (0)', 'mycred'),
                            checked: formatted,
                            onChange: setFormatted
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