/**
 * @file
 * Promotes the cookiesjsr settings layer to the browser's top layer via the
 * Popover API, detected by watching for `.cookiesjsr-layer-wrapper`
 * (cookiesjsr exposes no hook for this).
 *
 * Uses `popover="manual"` so Svelte's own overlay/close-button handlers stay
 * the only way to close the layer, and marks the rest of the page `inert`
 * while open, since popovers are non-modal by default.
 */
((Drupal, once, drupalSettings) => {
  const WRAPPER_SELECTOR = '.cookiesjsr-layer-wrapper';
  const SETTINGS_BUTTON_SELECTOR = '.editCookieSettings';
  const ACCEPT_ALL_LINK_SELECTOR = '.cookies-fallback--link';

  /**
   * Finds the layer wrapper in a mutation's added/removed nodes.
   *
   * On an initial hash-matched load, Svelte mounts the whole
   * `.cookiesjsr--app` tree in one shot, so the wrapper is a descendant of
   * the added node rather than the added node itself; reopening later adds
   * it directly. Checking both keeps both paths working.
   *
   * @param {NodeList} nodeList
   *   A mutation's addedNodes or removedNodes.
   * @return {Element|null}
   *   The wrapper, or null.
   */
  function findWrapper(nodeList) {
    const elements = Array.from(nodeList).filter(
      (node) => node.nodeType === Node.ELEMENT_NODE,
    );
    const direct = elements.find((node) => node.matches(WRAPPER_SELECTOR));
    if (direct) {
      return direct;
    }
    return (
      elements
        .map((node) => node.querySelector(WRAPPER_SELECTOR))
        .find(Boolean) || null
    );
  }

  function promote(wrapper) {
    try {
      // Set + show back to back, so there's no paint in between.
      wrapper.setAttribute('popover', 'manual');
      wrapper.showPopover();
    } catch (error) {
      // eslint-disable-next-line no-console
      console.warn('cookies_popover: showPopover() failed.', error);
    }
  }

  /**
   * Toggles `inert` on everything outside `container`'s ancestor chain.
   *
   * @param {HTMLElement} container
   *   The #cookiesjsr mount element.
   * @param {boolean} isInert
   *   Apply or lift inertness.
   */
  function setBackgroundInert(container, isInert) {
    let node = container;
    while (node && node !== document.body) {
      const { parentNode } = node;
      const current = node;
      if (parentNode) {
        Array.from(parentNode.children).forEach((sibling) => {
          if (sibling !== current) {
            sibling.toggleAttribute('inert', isInert);
          }
        });
      }
      node = parentNode;
    }
  }

  /**
   * Finds selector matches among a mutation's nodes, direct or nested.
   *
   * @param {NodeList} nodeList
   *   A mutation's addedNodes or removedNodes.
   * @param {string} selector
   *   A CSS selector.
   * @return {Element[]}
   *   All matches.
   */
  function findAll(nodeList, selector) {
    const elements = Array.from(nodeList).filter(
      (node) => node.nodeType === Node.ELEMENT_NODE,
    );
    const direct = elements.filter((node) => node.matches(selector));
    const nested = elements.flatMap((node) =>
      Array.from(node.querySelectorAll(selector)),
    );
    return [...direct, ...nested];
  }

  /**
   * Replaces a cookies.lib.js "accept all" fallback link with a button.
   *
   * Reuses the sibling per-service `<button>`'s class for the `--{service}`
   * part; text and click behavior (dispatch `cookiesjsrSetService`) mirror
   * cookies.lib.js.
   *
   * @param {HTMLAnchorElement} link
   *   The `.cookies-fallback--link` element.
   */
  function convertAcceptAllLink(link) {
    const serviceButton = link.parentElement?.querySelector(
      '.cookies-fallback--btn',
    );
    const button = document.createElement('button');
    button.type = 'button';
    button.className = [
      serviceButton?.classList[0],
      'cookies-fallback--btn',
      'cookies-fallback--all-btn',
    ]
      .filter(Boolean)
      .join(' ');
    button.textContent = link.textContent;
    button.addEventListener('click', () => {
      document.dispatchEvent(
        new CustomEvent('cookiesjsrSetService', { detail: { all: true } }),
      );
    });
    link.replaceWith(button);
  }

  function handleMutation(container, mutation) {
    const addedWrapper = findWrapper(mutation.addedNodes);
    if (addedWrapper) {
      promote(addedWrapper);
      setBackgroundInert(container, true);
    }
    if (findWrapper(mutation.removedNodes)) {
      setBackgroundInert(container, false);
    }
  }

  Drupal.behaviors.cookiesPopover = {
    attach(context) {
      once('cookiesPopover', '#cookiesjsr', context).forEach((container) => {
        // Feature detection: unsupported browsers get zero behavior change.
        if (typeof container.showPopover !== 'function') {
          return;
        }
        const observer = new MutationObserver((mutations) => {
          mutations.forEach((mutation) => handleMutation(container, mutation));
        });
        // subtree: true — the wrapper mounts deep inside #cookiesjsr.
        observer.observe(container, { childList: true, subtree: true });

        // On a hash-matched load, App.svelte opens the layer synchronously
        // during its initial mount, which finishes before
        // Drupal.attachBehaviors() (DOMContentLoaded) gets here — so it can
        // already be in the DOM, unpromoted, before observe() above runs.
        const existingWrapper = container.querySelector(WRAPPER_SELECTOR);
        if (existingWrapper) {
          promote(existingWrapper);
          setBackgroundInert(container, true);
        }
      });
    },
  };

  /**
   * Turns every cookies.lib.js "accept all" fallback link into a button.
   *
   * Watches the whole page, not just `once()` at attach: other modules build
   * these overlays from their own behaviors, in no guaranteed order.
   */
  Drupal.behaviors.cookiesFallbackAcceptAllButton = {
    attach(context) {
      once('cookiesFallbackAcceptAllButton', 'html', context).forEach(() => {
        document
          .querySelectorAll(ACCEPT_ALL_LINK_SELECTOR)
          .forEach(convertAcceptAllLink);
        const observer = new MutationObserver((mutations) => {
          mutations.forEach((mutation) => {
            findAll(mutation.addedNodes, ACCEPT_ALL_LINK_SELECTOR).forEach(
              convertAcceptAllLink,
            );
          });
        });
        observer.observe(document.body, { childList: true, subtree: true });
      });
    },
  };

  /**
   * Opens the settings layer from any `.editCookieSettings` element.
   *
   * cookiesjsr only reacts to `location.hash` matching `openSettingsHash`
   * (via `hashchange`, or a one-time link-click binding at load) — a button
   * is neither, so this drives the hash directly instead.
   *
   * Shared by every way of placing such a button: the settings-button
   * block's template, and any menu link on the `<button>` route with this
   * class added via Menu Link Attributes. Matches `open_settings_hash`
   * (minus `#`) so the relationship is obvious.
   */
  Drupal.behaviors.cookiesPopoverSettingsButton = {
    attach(context) {
      once(
        'cookiesPopoverSettingsButton',
        SETTINGS_BUTTON_SELECTOR,
        context,
      ).forEach((button) => {
        button.addEventListener('click', () => {
          const hash =
            drupalSettings?.cookies?.cookiesjsr?.config?.interface
              ?.openSettingsHash;
          if (!hash) {
            return;
          }
          if (window.location.hash === hash) {
            // Already on the target hash (e.g. reopening after a previous
            // close): setting the same hash again would not fire
            // `hashchange`, so dispatch one manually.
            window.dispatchEvent(new HashChangeEvent('hashchange'));
          } else {
            window.location.hash = hash;
          }
        });
      });
    },
  };
})(Drupal, once, drupalSettings);
