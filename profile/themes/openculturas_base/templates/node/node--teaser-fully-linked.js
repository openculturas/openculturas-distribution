(function (Drupal, once) {
    /**
     * Extend clickable area in teasers: full teaser
     * Conditions: wrapper article.teaser-fully-linked containing a link inside h3.teaser
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.teaserFullyLinkedBehavior = {
        attach: function (context, settings) {
        // Use context to filter the DOM to only the elements of interest,
        // and use once() to guarantee that our callback function processes
        // any given element one time at most, regardless of how many times
        // the behaviour itself is called (it is not sufficient in general
        // to assume an element will only ever appear in a single context).
        once('teaserFullyLinkedBehavior', '.teaser-fully-linked', context).forEach(teaser => {
                let down;
                teaser.addEventListener('mousedown', (event) => {
                    down = Date.now();
                });
                teaser.addEventListener('mouseup', (event) => {
                    if (
                        event.target &&
                        (Date.now() - down) < 200 &&
                        event.button === 0 &&
                        Array.from(event.target.parentNode.classList).includes('contextual') === false
                    ) {
                        teaser.querySelector('h3.teaser a').click();
                    }
                });
            });
        }
    };
    /**
     * Extend clickable area in teasers: teaser images
     * Conditions: wrapper article.teaser-image-linked containing a link inside h3.teaser
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.teaserImageLinkedBehavior = {
        attach: function (context, settings) {
        // Use context to filter the DOM to only the elements of interest,
        // and use once() to guarantee that our callback function processes
        // any given element one time at most, regardless of how many times
        // the behaviour itself is called (it is not sufficient in general
        // to assume an element will only ever appear in a single context).
        once('teaserImageLinkedBehavior', 'article.teaser-image-linked', context).forEach(teaser => {
            const teaserImg = teaser.querySelector('.field--name-field-mood-image');
            const teaserHeadlineLink = teaser.querySelector('h3.teaser a');
            if (teaserImg && teaserHeadlineLink) {
                teaserImg.addEventListener('click', (event) => {
                    teaserHeadlineLink.click();
                });
            }
        });
        }
    };
} (Drupal, once));
