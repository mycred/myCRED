(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;
    registerBlockType('mycred-gb-blocks/mycred-history', {
        title: __('History', 'mycred'),
        category: 'mycred',
        attributes: {
            user_id: {
                type: 'string'
            },
            number: {
                type: 'integer',
                default: '10'
            },
            time: {
                type: 'string'
            },
            ref: {
                type: 'string'
            },
            order: {
                type: 'string'
            },
            show_user: {
                type: 'bool',
                default: false
            },
            show_nav: {
                type: 'bool',
                default: true
            },
            login: {
                type: 'string'
            },
            type: {
                type: 'string'
            },
            pagination: {
                type: 'integer',
                default: '10'
            },
            inlinenav: {
                type: 'bool',
                default: false
            }
        },
        edit: function (props) {
            var user_id = props.attributes.user_id;
            var number = props.attributes.number;
            var time = props.attributes.time;
            var ref = props.attributes.ref;
            var order = props.attributes.order;
            var show_user = props.attributes.show_user;
            var show_nav = props.attributes.show_nav;
            var login = props.attributes.login;
            var type = props.attributes.type;
            var pagination = props.attributes.pagination;
            var inlinenav = props.attributes.inlinenav;

            var options = [];

            function setUserId(value) {
                props.setAttributes({user_id: value});
            }
            function setNumber(value) {
                props.setAttributes({number: value});
            }
            function setTime(value) {
                props.setAttributes({time: value});
            }
            function setRef(value) {
                props.setAttributes({ref: value});
            }
            function setOrder(value) {
                props.setAttributes({order: value});
            }
            function setShowUser(value) {
                props.setAttributes({show_user: value});
            }
            function setShowNav(value) {
                props.setAttributes({show_nav: value});
            }
            function setLogin(value) {
                props.setAttributes({login: value});
            }
            function setType(value) {
                props.setAttributes({type: value});
            }
            function setPag(value) {
                props.setAttributes({pagination: value});
            }
            function setInlineNav(value) {
                props.setAttributes({inlinenav: value});
            }
            Object.keys(mycred_types).forEach(function (key) {
                options.push({
                    label: mycred_types[key],
                    value: key
                });
            });
            return el('div', {}, [
                el('p', {}, __('History Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('User ID', 'mycred'),
                            help: __('Option to show a specific users history. Use "current" to show the current users history or leave empty to show everyones history.', 'mycred'),
                            value: user_id,
                            onChange: setUserId

                        }),
                        el(TextControl, {
                            label: __('Number of Entries', 'mycred'),
                            help: __('The number of entries to show per page. Defaults to 10.', 'mycred'),
                            value: number,
                            onChange: setNumber

                        }),
                        el(SelectControl, {
                            label: __('Time', 'mycred'),
                            help: __('Option to return entries for specific time period.', 'mycred'),
                            value: time,
                            onChange: setTime,
                            options: [
                                {label: 'Show All', value: ''},
                                {label: 'Today', value: 'today'},
                                {label: 'Yesterday', value: 'yesterday'},
                                {label: 'This Week', value: 'thisweek'},
                                {label: 'This Month', value: 'thismonth'}
                            ]
                        }),
                        el(TextControl, {
                            label: __('Reference', 'mycred'),
                            help: __('Option to only show log entries for the specified reference. Can be a single reference or a comma separated list of references (without any empty spaces).', 'mycred'),
                            value: ref,
                            onChange: setRef

                        }),
                        el(SelectControl, {
                            label: __('Order', 'mycred'),
                            help: __('Order of the log entries. Either ASC for ascending or DESC for descending.', 'mycred'),
                            value: order,
                            onChange: setOrder,
                            options: [
                                {label: 'Descending', value: 'DESC'},
                                {label: 'Ascending', value: 'ASC'}
                            ]
                        }),
                        el(ToggleControl, {
                            label: __('Show User Column?', 'mycred'),
                            checked: show_user,
                            onChange: setShowUser

                        }),
                        el(ToggleControl, {
                            label: __('Show Navigation?', 'mycred'),
                            checked: show_nav,
                            onChange: setShowNav
                        }),
                        el(TextControl, {
                            label: __('Login Message', 'mycred'),
                            help: __('Message to show a logged out user trying to view this shortcode. Leave empty if you want visitors to see the log as well.', 'mycred'),
                            value: login,
                            onChange: setLogin

                        }),
                        el(SelectControl, {
                            label: __('Point Type', 'mycred'),
                            help: __('The point type requirement. This controls not just balance requirements but also rank requirement and the reference count requirement above.', 'mycred'),
                            value: type,
                            onChange: setType,
                            options
                        }),
                        el(TextControl, {
                            label: __('Pagination', 'mycred'),
                            help: __('The number of pagination links to show. Ignored if you set to hide navigation above. (Requires 1.7)', 'mycred'),
                            value: pagination,
                            onChange: setPag
                        }),
                        el(ToggleControl, {
                            label: __('Inline Navigation?', 'mycred'),
                            help: __('If your theme is rendering the navigation vertically instead of horizontally, make sure you select "Yes" here.', 'mycred'),
                            checked: inlinenav,
                            onChange: setInlineNav
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