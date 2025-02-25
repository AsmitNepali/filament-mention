import Tribute from "tributejs";

function generateMenuItemTemplate(item, lookupKey) {
    return `
        <div class='mention-item'>
            ${item.original.avatar ? `<img class="mention-item__avatar" src="${item.original.avatar}" alt="${item.original[lookupKey]}"/>` : ''}
            <div class='mention-item__info'>
                <div class='mention-item__info-name'>${item.original.name}</div>
                <div class='mention-item__info-email'>@${item.original[lookupKey]}</div>
            </div>
        </div>
    `;
}

function generateSelectTemplate(item, pluck, lookupKey) {
    if (typeof item === "undefined") return null;
    return `<a href="${item.original.url}(--${item.original[pluck]}--)" data-trix-attribute="bold">@${item.original[lookupKey]}</a>`;
}

function createTribute({ fieldName, triggerWith, pluck, menuShowMinLength, menuItemLimit, lookupKey, valuesFunction }) {
    const targetElement = document.getElementById(fieldName);
    const tribute = new Tribute({
        trigger: triggerWith,
        values: valuesFunction,
        menuShowMinLength: menuShowMinLength,
        menuItemLimit: menuItemLimit,
        loadingItemTemplate: `<div class="loading-item">Loading...</div>`,
        lookup: lookupKey,
        menuContainer: document.body,
        menuItemTemplate: (item) => generateMenuItemTemplate(item, lookupKey),
        selectTemplate: (item) => generateSelectTemplate(item, pluck, lookupKey),
        noMatchTemplate: () => `<span class="no-match">No results found</span>`
    });
    tribute.attach(targetElement);

    targetElement.addEventListener("tribute-active-true", () => tribute.menu.classList.add('tribute-active'));
    targetElement.addEventListener("tribute-active-false", () => tribute.menu.classList.remove('tribute-active'));

    targetElement.addEventListener("keydown", (event) => {
        if (!tribute.isActive) return;

        const activeItem = tribute.menu.querySelector(".highlight");
        if (!activeItem) return;

        const menu = tribute.menu;

        if (event.key === "ArrowDown") {
            // Scroll down to the next item
            const nextItem = activeItem.nextElementSibling;
            if (nextItem) {
                nextItem.scrollIntoView({ behavior: "smooth", block: "nearest" });
            }
        } else if (event.key === "ArrowUp") {
            // Scroll up to the previous item
            const prevItem = activeItem.previousElementSibling;
            if (prevItem) {
                prevItem.scrollIntoView({ behavior: "smooth", block: "nearest" });
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
                valuesFunction: (text, cb) => {
                  const items = mentionableItems.filter(user =>
                        user[lookupKey].toLowerCase().includes(text.toLowerCase())
                    );
                    cb(items);
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
                menuItemLimit,
                lookupKey,
                valuesFunction: (text, cb) => {
                    alpine.getMentionableItems(text).then(items => {
                        cb(items);
                    });
                }
            });
        }
    };
}
