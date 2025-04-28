import Tribute from "tributejs";

function createTribute({
                           fieldName,
                           triggerWith,
                           pluck,
                           menuShowMinLength,
                           menuItemLimit,
                           lookupKey,
                           loadingItemString,
                           noResultsString,
                           valuesFunction,
                           triggerConfigs = null,
                           prefix = '',
                           suffix = '',
                           titleField = 'title',
                           hintField = null
                       }) {
    const targetElement = document.getElementById(fieldName);

    // Ensure menuShowMinLength is properly parsed as an integer with a default of 0
    const minLength = menuShowMinLength !== undefined && menuShowMinLength !== null ?
        parseInt(menuShowMinLength) : 0;

    // Ensure menuItemLimit is properly parsed as an integer with a default
    const itemLimit = menuItemLimit !== undefined && menuItemLimit !== null ?
        parseInt(menuItemLimit) : 10;

    // Helper function to search across all object properties
    function searchInObject(item, searchText) {
        if (!searchText) return true;

        const searchLower = searchText.toLowerCase();

        // Search in all string properties of the object
        for (const key in item) {
            if (typeof item[key] === 'string') {
                const valueToSearch = item[key].toLowerCase();

                if (valueToSearch.includes(searchLower)) {
                    return true;
                }
            }
        }

        return false;
    }

    // Helper function to get display value based on lookupKey or default
    function getDisplayValue(item, currentLookupKey) {
        // If lookupKey is specified and exists in the item, use it
        if (currentLookupKey && item[currentLookupKey]) {
            return item[currentLookupKey];
        }

        // Otherwise, try common fields in order
        if (item.value) return item.value;
        if (item.username) return item.username;
        if (item.name) return item.name;
        if (item.key) return item.key;

        // If none of the above, use the first string property
        for (const key in item) {
            if (typeof item[key] === 'string') {
                return item[key];
            }
        }

        // Last resort
        return "Unknown";
    }

    // Helper function to get title value
    function getTitleValue(item, field) {
        if (field && item[field]) {
            return item[field];
        }

        if (item.title) return item.title;
        if (item.name) return item.name;
        if (item.label) return item.label;

        return getDisplayValue(item, null);
    }

    // Helper function to get hint value
    function getHintValue(item, field, currentPrefix, currentSuffix, lookupKey) {
        if (field && item[field]) {
            return `${currentPrefix}${item[field]}${currentSuffix}`;
        }

        const displayValue = getDisplayValue(item, lookupKey);
        return `${currentPrefix}${displayValue}${currentSuffix}`;
    }

    // If triggerWith is a string (single trigger)
    if (!Array.isArray(triggerWith)) {
        const currentPrefix = prefix || '';
        const currentSuffix = suffix || '';

        const tribute = new Tribute({
            trigger: triggerWith,
            values: function (text, cb) {
                valuesFunction(text, function (items) {
                    // Filter items by searching across all properties
                    const filteredItems = items.filter(item => searchInObject(item, text));

                    cb(filteredItems);
                });
            },
            menuShowMinLength: minLength,
            menuItemLimit: itemLimit,
            loadingItemTemplate: `<div class="loading-item">${loadingItemString}</div>`,
            lookup: function (item, mentionText) {
                return getDisplayValue(item, lookupKey);
            },
            menuContainer: document.body,
            menuItemTemplate: function (item) {
                const title = getTitleValue(item.original, titleField);
                const hint = getHintValue(item.original, hintField, currentPrefix, currentSuffix, lookupKey);

                return `
                    <div class='mention-item'>
                        ${item.original.avatar ? `<img class="mention-item__avatar" src="${item.original.avatar}" alt="${title}"/>` : ''}
                        <div class='mention-item__info'>
                            <div class='mention-item__info-title'>${title}</div>
                            <div class='mention-item__info-hint'>${hint}</div>
                        </div>
                    </div>
                `;
            },
            selectTemplate: function (item) {
                if (typeof item === "undefined") return null;

                const displayValue = getDisplayValue(item.original, lookupKey);

                if (item.original.url !== null) {
                    return `<a href="${item.original.url}(--${item.original[pluck]}--)" data-trix-attribute="bold">${currentPrefix}${displayValue}${currentSuffix}</a>`;
                } else {
                    return `${currentPrefix}${displayValue}${currentSuffix}`;
                }
            },
            noMatchTemplate: function () {
                return `<span class="no-match">${noResultsString}</span>`;
            }
        });

        tribute.attach(targetElement);

        setupEventListeners(targetElement, tribute);

        return tribute;
    }

    // If triggerWith is an array (multiple triggers)
    const collections = [];

    for (let i = 0; i < triggerWith.length; i++) {
        const trigger = triggerWith[i];

        // Check if we have specific config for this trigger
        let currentLookupKey = lookupKey;
        let currentPrefix = prefix || '';
        let currentSuffix = suffix || '';
        let currentTitleField = titleField;
        let currentHintField = hintField;
        let currentConfig = null;

        if (triggerConfigs && triggerConfigs[trigger]) {
            currentConfig = triggerConfigs[trigger];

            if (currentConfig.lookupKey) {
                currentLookupKey = currentConfig.lookupKey;
            }

            if (currentConfig.prefix !== undefined) {
                currentPrefix = currentConfig.prefix;
            }

            if (currentConfig.suffix !== undefined) {
                currentSuffix = currentConfig.suffix;
            }

            // Support both camelCase and snake_case
            if (currentConfig.titleField) {
                currentTitleField = currentConfig.titleField;
            } else if (currentConfig.title_field) {
                currentTitleField = currentConfig.title_field;
            }

            // Support both camelCase and snake_case
            if (currentConfig.hintField) {
                currentHintField = currentConfig.hintField;
            } else if (currentConfig.hint_field) {
                currentHintField = currentConfig.hint_field;
            }
        }

        collections.push({
            trigger: trigger,
            values: function (text, cb) {
                // If we have a specific filter function for this trigger, use it
                if (currentConfig && typeof currentConfig.filter === 'function') {
                    return currentConfig.filter(text, cb, valuesFunction);
                }

                // Otherwise use the default filter
                valuesFunction(text, function (items) {
                    // Filter items by searching across all properties
                    const filteredItems = items.filter(item => searchInObject(item, text));

                    cb(filteredItems);
                });
            },
            lookup: function (item, mentionText) {
                return getDisplayValue(item, currentLookupKey);
            },
            menuShowMinLength: minLength,
            menuItemLimit: itemLimit,
            loadingItemTemplate: `<div class="loading-item">${loadingItemString}</div>`,
            menuContainer: document.body,
            menuItemTemplate: function (item) {
                const title = getTitleValue(item.original, currentTitleField);
                const hint = getHintValue(item.original, currentHintField, currentPrefix, currentSuffix, currentLookupKey);

                return `
                    <div class='mention-item'>
                        ${item.original.avatar ? `<img class="mention-item__avatar" src="${item.original.avatar}" alt="${title}"/>` : ''}
                        <div class='mention-item__info'>
                            <div class='mention-item__info-title'>${title}</div>
                            <div class='mention-item__info-hint'>${hint}</div>
                        </div>
                    </div>
                `;
            },
            selectTemplate: function (item) {
                if (typeof item === "undefined") return null;

                const displayValue = getDisplayValue(item.original, currentLookupKey);

                if (item.original.url !== null) {
                    return `<a href="${item.original.url}(--${item.original[pluck]}--)" data-trix-attribute="bold">${currentPrefix}${displayValue}${currentSuffix}</a>`;
                } else {
                    return `${currentPrefix}${displayValue}${currentSuffix}`;
                }
            },
            noMatchTemplate: function () {
                return `<span class="no-match">${noResultsString}</span>`;
            }
        });
    }

    const tribute = new Tribute({
        collection: collections
    });

    tribute.attach(targetElement);

    setupEventListeners(targetElement, tribute);

    return tribute;
}

function setupEventListeners(targetElement, tribute) {
    targetElement.addEventListener("tribute-active-true", function () {
        tribute.menu.classList.add('tribute-active');
    });

    targetElement.addEventListener("tribute-active-false", function () {
        tribute.menu.classList.remove('tribute-active');
    });

    targetElement.addEventListener("keydown", function (event) {
        if (!tribute.isActive) return;

        const activeItem = tribute.menu.querySelector(".highlight");
        if (!activeItem) return;

        if (event.key === "ArrowDown") {
            // Scroll down to the next item
            const nextItem = activeItem.nextElementSibling;
            if (nextItem) {
                nextItem.scrollIntoView({behavior: "smooth", block: "nearest"});
            }
        } else if (event.key === "ArrowUp") {
            // Scroll up to the previous item
            const prevItem = activeItem.previousElementSibling;
            if (prevItem) {
                prevItem.scrollIntoView({behavior: "smooth", block: "nearest"});
            }
        }
    });
}

export function mention({
                            fieldName,
                            mentionableItems,
                            triggerWith,
                            pluck,
                            menuShowMinLength,
                            menuItemLimit,
                            lookupKey,
                            loadingItemString,
                            noResultsString,
                            triggerConfigs = null,
                            prefix = '',
                            suffix = '',
                            titleField = 'title',
                            hintField = null
                        }) {
    return {
        fieldName,
        pluck,
        menuShowMinLength,
        lookupKey,
        menuItemLimit,
        init() {
            createTribute({
                fieldName: this.fieldName,
                triggerWith,
                pluck,
                menuShowMinLength,
                lookupKey,
                menuItemLimit,
                loadingItemString,
                noResultsString,
                triggerConfigs,
                prefix,
                suffix,
                titleField,
                hintField,
                valuesFunction: function (text, cb) {
                    cb(mentionableItems);
                }
            });
        }
    };
}

export function fetchMention({
                                 fieldName,
                                 triggerWith,
                                 pluck,
                                 menuShowMinLength,
                                 menuItemLimit,
                                 lookupKey,
                                 loadingItemString,
                                 noResultsString,
                                 triggerConfigs = null,
                                 prefix = '',
                                 suffix = '',
                                 titleField = 'title',
                                 hintField = null
                             }) {
    return {
        fieldName,
        pluck,
        menuShowMinLength,
        menuItemLimit,
        lookupKey,
        init() {
            const alpine = this.$wire;
            createTribute({
                fieldName: this.fieldName,
                triggerWith,
                pluck,
                menuShowMinLength,
                lookupKey,
                menuItemLimit,
                loadingItemString,
                noResultsString,
                triggerConfigs,
                prefix,
                suffix,
                titleField,
                hintField,
                valuesFunction: function (text, cb) {
                    alpine.getMentionableItems(text).then(function (items) {
                        cb(items);
                    });
                }
            });
        }
    };
}
