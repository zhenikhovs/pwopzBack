$('.trumbowyg-text-editor').trumbowyg({
    lang: 'ru',
    resetCss: true,
    removeformatPasted: true,
    autogrow: false,
    btns: [
        ['viewHTML'],
        ['h2', 'h3', 'h4'],
        ['strong', 'em', 'underline'],
        ['link'],
        ['unorderedList', 'orderedList'],
        ['removeformat']
    ],
    plugins: [],
    minimalLinks: true,
    tagsToRemove: ['span', 'link', 'script', 'div', 'style']
});