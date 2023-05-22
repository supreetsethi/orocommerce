import FilteredProductVariantsPlugin from 'oroshoppinglist/js/datagrid/plugins/filtered-product-variants-plugin';
import ShoppingListRefreshPlugin from 'oroshoppinglist/js/datagrid/plugins/shopping-list-refresh-plugin';
import quantityHelper from 'oroproduct/js/app/quantity-helper';
import ShoppingListRow from 'oroshoppinglist/js/datagrid/row/shopping-list-row';
import {isHighlight, isError, messageModel} from './utils';

export const flattenData = data => {
    return data.reduce((flatData, rawData) => {
        const {subData, ...item} = rawData;
        const itemClassName = [];

        if (isHighlight(item)) {
            itemClassName.push('highlight');
        }

        if (isError(item)) {
            itemClassName.push('highlight-error');
        }

        if (!subData) {
            itemClassName.push('single-row');
            item.row_class_name = itemClassName.join(' ');
            flatData.push(item);
            item._hasVariants = false;
            item._isVariant = false;

            if (isError(item) || isHighlight(item)) {
                flatData.push(messageModel(item, 'item'));
            }
        } else {
            let filteredOutVariants = 0;
            const precisions = [];
            let lastFiltered = item;

            itemClassName.push('group-row');

            if (subData.length) {
                itemClassName.push('group-row-has-children');
            }

            if (item.isConfigurable) {
                itemClassName.push('group-row-configurable');
            }

            item.row_class_name = itemClassName.join(' ');
            item.ids = [];
            item._hasVariants = item.isConfigurable || false;
            item._isVariant = false;

            flatData.push(item);

            const flatSubData = subData.reduce((subDataCollection, subItem, index) => {
                const className = ['sub-row'];

                if (subItem.units && subItem.units[item.unit]) {
                    precisions.push(subItem.units[item.unit].precision);
                }

                if (isHighlight(subItem)) {
                    className.push('highlight');
                }

                if (isHighlight(item)) {
                    className.push('parent-row-has-highlight');
                }

                if (isError(subItem)) {
                    className.push('highlight-error');
                }

                if (isError(item)) {
                    className.push('parent-row-has-highlight-error');
                }

                if (subData.length - 1 === index) {
                    className.push('sub-row-last');
                }

                if (subItem.filteredOut) {
                    filteredOutVariants++;
                    className.push('hide');
                } else {
                    lastFiltered = subItem;
                }

                item.ids.push(subItem.id);
                subItem._isVariant = item._hasVariants || false;
                subItem._groupId = item.productId;
                subItem.row_class_name = className.join(' ');
                subItem.row_attributes = {
                    'data-product-group': subItem._groupId
                };

                subDataCollection.push(subItem);

                if ((isError(subItem) && subItem.sku) || isHighlight(subItem)) {
                    subDataCollection.push(messageModel(subItem, 'item'));
                }

                return subDataCollection;
            }, []);

            item.precision = precisions.length
                ? Math.max.apply(null, precisions)
                : quantityHelper.getDefaultMaxFractionDigits();

            if (filteredOutVariants) {
                lastFiltered.filteredOutData = {
                    count: filteredOutVariants,
                    group: {
                        name: item.name,
                        id: item.productId
                    }
                };

                lastFiltered.row_class_name += ' filtered-out';
            }

            if (isError(item) || isHighlight(item)) {
                const itemMessageModel = messageModel(item, 'item');
                const itemMessageModelClasses = itemMessageModel.row_class_name.split(' ')
                    .filter(className => !['group-row', 'group-row-has-children'].includes(className));

                itemMessageModelClasses.push('sub-row');

                itemMessageModel.row_class_name = itemMessageModelClasses.join(' ');

                const prevSubItem = flatData.at(-1);

                if (prevSubItem.isMessage) {
                    prevSubItem.row_class_name = prevSubItem.row_class_name.split(' ')
                        .filter(className => !['sub-row-last'].includes(className)).join(' ');
                }

                flatData.push(itemMessageModel);
            }

            flatData.push(...flatSubData);
        }

        return flatData;
    }, []);
};

const shoppingListFlatDataBuilder = {
    processDatagridOptions(deferred, options) {
        const {
            parseResponseModels,
            parseResponseOptions
        } = options.metadata.options;

        Object.assign(options.metadata.options, {
            parseResponseModels: resp => {
                if (parseResponseModels) {
                    resp = parseResponseModels(resp);
                }
                return 'data' in resp ? flattenData(resp.data) : resp;
            },
            parseResponseOptions: (resp = {}) => {
                if (parseResponseOptions) {
                    resp = parseResponseOptions(resp);
                }
                const {options = {}} = resp;
                return {
                    reset: false,
                    uniqueOnly: true,
                    wait: false,
                    ...options
                };
            }
        });

        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }
        options.metadata.plugins.push(FilteredProductVariantsPlugin, ShoppingListRefreshPlugin);

        options.data.data = flattenData(options.data.data);

        options.themeOptions = {
            ...options.themeOptions,
            rowView: ShoppingListRow
        };

        return deferred.resolve();
    },

    /**
     * Init() function is required
     */
    init(deferred, options) {
        options.gridPromise.done(grid => {
            grid.collection.on('beforeRemove', (modelToRemove, collection, options) => {
                if (modelToRemove.get('_isVariant')) {
                    options.recountTotalRecords = false;
                }
            });
        });

        return deferred.resolve();
    }
};

export default shoppingListFlatDataBuilder;
