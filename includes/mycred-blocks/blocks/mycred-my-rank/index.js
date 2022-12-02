(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var ToggleControl = wp.components.ToggleControl;
    var SelectControl = wp.components.SelectControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-my-rank', {
        title: __('My Rank', 'mycred'),
        category: 'mycred',
        attributes: {
            user_id: {
                type: 'string'
            },
            show_title: {
                type: 'bool',
                default: true
            },
            show_logo: {
                type: 'bool'
            },
            logo_size: {
                type: 'string',
                default: 'post-thumbnail'
            },
            first: {
                type: 'string',
                default: 'logo'
            },
            ctype: {
                type: 'string'
            }
        },
        edit: function (props) {
            var user_id = props.attributes.user_id;
            var show_title = props.attributes.show_title;
            var show_logo = props.attributes.show_logo;
            var logo_size = props.attributes.logo_size;
            var first = props.attributes.first;
            var ctype = props.attributes.ctype;

            var options = [];
            function setUserId(value) {
                props.setAttributes({user_id: value});
            }
            function setShowTitle(value) {
                props.setAttributes({show_title: value});
            }

            function setShowLogo(value) {
                props.setAttributes({show_logo: value});
            }
            function setLogoSize(value) {
                props.setAttributes({logo_size: value});
            }
            function setFirst(value) {
                props.setAttributes({first: value});
            }

            function setPtType(value) {
                props.setAttributes({ctype: value});
            }

            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });

            return el('div', {}, [
                el('p', {}, __('My Rank Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('User ID', 'mycred'),
                            help: __('Optional ID of a specific user. If you want to show the rank of the user viewing this shortcode, leave this field empty.', 'mycred'),
                            value: user_id,
                            onChange: setUserId

                        }),
                        el(ToggleControl, {
                            label: __('Show Title', 'mycred'),
                            help: __('Option to show the rank title. Defaults to yes', 'mycred'),
                            checked: show_title,
                            onChange: setShowTitle
                        }),
                        el(ToggleControl, {
                            label: __('Show Logo', 'mycred'),
                            help: __('Option to show the rank logo. Defaults to no.', 'mycred'),
                            checked: show_logo,
                            onChange: setShowLogo
                        }),
                        el(TextControl, {
                            label: __('Logo Size', 'mycred'),
                            help: __('Registered image size or size in pixels e.g. 100x100', 'mycred'),
                            value: logo_size,
                            onChange: setLogoSize
                        }),
                        el(SelectControl, {
                            label: __('Order', 'mycred'),
                            help: __('Select what you want to show first. This is ignored if you have selected to only show one detail', 'mycred'),
                            value: first,
                            onChange: setFirst,
                            options: [
                                {label: 'Logo then Title', value: 'logo'},
                                {label: 'Title then Logo', value: 'title'}
                            ]
                        }),
                        el(SelectControl, {
                            label: __('Point Type', 'mycred'),
                            help: __('The point type you want to show', 'mycred'),
                            value: ctype,
                            onChange: setPtType,
                            options
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