(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var el = wp.element.createElement;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var panelBody = wp.components.PanelBody;
    var __ = wp.i18n.__;

    registerBlockType('mycred-gb-blocks/mycred-users-of-rank', {
        title: __('Users of rank', 'mycred'),
        category: 'mycred',
        attributes: {
            rank_id: {
                type: 'string'
            },
            login: {
                type: 'string'
            },
            number: {
                type: 'string',
                default: '10'
            },
            wrap: {
                type: 'string',
                default: '1'
            },
            col: {
                type: 'string',
                default: '1'
            },
            nothing: {
                type: 'string',
                default: 'No users found with this rank'
            },
            order: {
                type: 'string'
            },
            ctype: {
                type: 'string'
            }
        },
        edit: function (props) {
            var rank_id = props.attributes.rank_id;
            var login = props.attributes.login;
            var number = props.attributes.number;
            var wrap = props.attributes.wrap;
            var col = props.attributes.col;
            var nothing = props.attributes.nothing;
            var order = props.attributes.order;
            var ctype = props.attributes.ctype;

            var options = [];

            function setRankId(value) {
                props.setAttributes({rank_id: value});
            }
            function setLogin(value) {
                props.setAttributes({login: value});
            }
            function setNumber(value) {
                props.setAttributes({number: value});
            }

            function setWrap(value) {
                props.setAttributes({wrap: value});
            }
            function setCol(value) {
                props.setAttributes({col: value});
            }
            function setNothing(value) {
                props.setAttributes({nothing: value});
            }
            function setOrder(value) {
                props.setAttributes({order: value});
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
                el('p', {}, __('Users of rank Shortcode', 'mycred') ),
                el(InspectorControls, null,
                    el( panelBody, { title: 'Form Settings', initialOpen: true },
                        el(TextControl, {
                            label: __('Rank ID', 'mycred'),
                            help: __('The rank to list users for', 'mycred'),
                            value: rank_id,
                            onChange: setRankId
                        }),
                        el(TextControl, {
                            label: __('Login Message', 'mycred'),
                            help: __('Message to show for logged out users. This shortcode will not return anything if this is left empty.', 'mycred'),
                            value: login,
                            onChange: setLogin
                        }),
                        el(TextControl, {
                            label: __('Number of Users', 'mycred'),
                            help: __('The number of users to return. Use -1 to return all users of this rank.', 'mycred'),
                            value: number,
                            onChange: setNumber
                        }),
                        el(TextControl, {
                            label: __('Wrapper Element', 'mycred'),
                            help: __('Option to wrap each row in a specific type of HTML element. Defaults to "div" but you can also use "table" to render a table. Can not be empty!', 'mycred'),
                            value: wrap,
                            onChange: setWrap
                        }),
                        el(TextControl, {
                            label: __('Table Columns', 'mycred'),
                            help: __('If you are using a table as the wrapper, you can set the number of columns you want to use. Unless you have customized the rendering of this shortcode, this should remain at 1.', 'mycred'),
                            value: col,
                            onChange: setCol
                        }),
                        el(TextControl, {
                            label: __('No Results', 'mycred'),
                            help: __('Message to show when the given rank has no users', 'mycred'),
                            value: nothing,
                            onChange: setNothing
                        }),
                        el(SelectControl, {
                            label: __('Order', 'mycred'),
                            help: __('Users are sorted according to their balance. Here you can select which order you want to show them. Lowest to highest or highest to lowest.', 'mycred'),
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
                    )
                )
            ]);
        },
        save: function (props) {
            return null;
        }
    });
})(window.wp);