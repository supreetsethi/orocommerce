define(function(require) {
    'use strict';

    var GridSidebarComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var tools = require('oroui/js/tools');
    var widgetManager = require('oroui/js/widget-manager');
    var BaseComponent = require('oroui/js/app/components/base/component');

    GridSidebarComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            sidebarAlias: '',
            widgetAlias: '',
            widgetContainer: '',
            widgetRoute: 'oro_datagrid_widget',
            widgetRouteParameters: {
                gridName: ''
            },
            stateShortKeys: {
                currentPage: 'i',
                pageSize: 'p',
                sorters: 's',
                filters: 'f',
                gridView: 'v',
                urlParams: 'g'
            },
            gridParam: 'grid'
        },

        /**
         * @property {Object}
         */
        listen: {
            'grid_load:complete mediator': 'onGridLoadComplete'
        },

        /**
         * @property {Object}
         */
        $container: {},

        /**
         * @property {Object}
         */
        gridCollection: {},

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$container = options._sourceElement;
            this.$widgetContainer = $(options.widgetContainer);

            mediator.on('grid-sidebar:change:' + this.options.sidebarAlias, this.onSidebarChange, this);

            this.$container.find('.control-minimize').click(_.bind(this.minimize, this));
            this.$container.find('.control-maximize').click(_.bind(this.maximize, this));

            this._maximizeOrMaximize(null);
        },

        /**
         * @param {Object} collection
         */
        onGridLoadComplete: function(collection) {
            if (collection.inputName === this.options.widgetRouteParameters.gridName) {
                this.gridCollection = collection;

                var self = this;
                widgetManager.getWidgetInstanceByAlias(
                    this.options.widgetAlias,
                    function() {
                        self._patchGridCollectionUrl(self._getQueryParamsFromUrl(location.search));
                    }
                );
            }
        },

        /**
         * @param {Object} data
         */
        onSidebarChange: function(data) {
            var params = _.extend(this._getQueryParamsFromUrl(location.search), data.params);
            var widgetParams = _.extend(this.options.widgetRouteParameters, params);
            var self = this;

            this._pushState(params);

            this._patchGridCollectionUrl(params);

            widgetManager.getWidgetInstanceByAlias(
                this.options.widgetAlias,
                function(widget) {
                    widget.setUrl(routing.generate(self.options.widgetRoute, widgetParams));

                    if (data.widgetReload) {
                        widget.render();
                    } else {
                        mediator.trigger('datagrid:doRefresh:' + widgetParams.gridName);
                    }
                }
            );
        },

        /**
         * @param {Object} params
         * @private
         */
        _patchGridCollectionUrl: function(params) {
            var collection = this.gridCollection;
            if (!_.isUndefined(collection)) {
                var url = collection.url;
                var newParams = _.extend(
                    this._getQueryParamsFromUrl(url),
                    _.omit(params, this.options.gridParam)
                );
                if (url.indexOf('?') !== -1) {
                    url = url.substring(0, url.indexOf('?'));
                }
                if (!_.isEmpty(newParams)) {
                    collection.url = url + '?' + this._urlParamsToString(newParams);
                }
            }
        },

        /**
         * @private
         * @param {Object} params
         */
        _pushState: function(params) {
            var paramsString = this._urlParamsToString(_.omit(params, 'saveState'));
            if (paramsString.length) {
                paramsString = '?' + paramsString;
            }

            history.pushState({}, document.title, location.pathname + paramsString + location.hash);
        },

        minimize: function() {
            this._maximizeOrMaximize('off');
        },

        maximize: function() {
            this._maximizeOrMaximize('on');
        },

        /**
         * @private
         * @param {string} state
         */
        _maximizeOrMaximize: function(state) {
            var params = this._getQueryParamsFromUrl(location.search);

            if (state === null) {
                state = params.sidebar || 'on';
            }

            if (state === 'on') {
                this.$container.addClass('grid-sidebar-maximized').removeClass('grid-sidebar-minimized');
                this.$widgetContainer.addClass('grid-sidebar-maximized').removeClass('grid-sidebar-minimized');

                delete params.sidebar;
            } else {
                this.$container.addClass('grid-sidebar-minimized').removeClass('grid-sidebar-maximized');
                this.$widgetContainer.addClass('grid-sidebar-minimized').removeClass('grid-sidebar-maximized');

                params.sidebar = state;
            }

            this._pushState(params);
        },

        /**
         * @param {String} url
         * @return {Object}
         * @private
         */
        _getQueryParamsFromUrl: function(url) {
            if (_.isUndefined(url)) {
                return {};
            }

            if (url.indexOf('?') === -1) {
                return {};
            }

            var query = url.substring(url.indexOf('?') + 1, url.length);
            if (!query.length) {
                return {};
            }

            return this.decodeStateData(query);
        },

        /**
         * @param {Object} params
         * @return {String}
         * @private
         */
        _urlParamsToString: function(params) {
            return $.param(params);
        },

        /**
         * Decode state object from string, operation is invert for encodeStateData.
         *
         * @static
         * @param {string} stateString
         * @return {Object}
         *
         * @see orodatagrid/js/pageable-collection
         */
        decodeStateData: function(stateString) {
            var data = tools.unpackFromQueryString(stateString);
            data = tools.invertKeys(data, _.invert(this.options.stateShortKeys));
            return data;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('grid-sidebar:change:' + this.options.sidebarAlias);

            delete this.gridCollection;

            GridSidebarComponent.__super__.dispose.call(this);
        }
    });

    return GridSidebarComponent;
});
