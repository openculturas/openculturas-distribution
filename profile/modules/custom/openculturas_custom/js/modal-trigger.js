/**
 * @file
 * Shows a block_content entity's content in a popover, triggered by a delay
 * or by scrolling its anchor into view, with a per-entity dismiss cooldown.
 *
 * The cookie consent UI always has priority over the popover.
 */
((Drupal, once, drupalSettings) => {
  const STORAGE_PREFIX = 'oc-modal-trigger:';
  const COOKIE_BANNER_CONTAINER_SELECTOR = '#cookiesjsr';
  // The consent banner and the settings layer of cookiesjsr.
  const COOKIE_CONSENT_UI_SELECTOR =
    '.cookiesjsr-banner--active, .cookiesjsr-layer-wrapper';
  // 100 years.
  const PERMANENT_DISMISS_MS = 100 * 365 * 24 * 60 * 60 * 1000;

  /**
   * Reads a dismissal record from localStorage.
   *
   * @param {string} dismissKey
   *   The popover's dismiss key.
   * @return {{until: number}|null}
   *   The parsed record, or null if absent/invalid/unavailable.
   */
  function readDismissal(dismissKey) {
    try {
      const raw = window.localStorage.getItem(STORAGE_PREFIX + dismissKey);
      if (!raw) {
        return null;
      }
      const parsed = JSON.parse(raw);
      return typeof parsed?.until === 'number' ? parsed : null;
    } catch (error) {
      return null;
    }
  }

  /**
   * Writes a dismissal record to localStorage.
   *
   * @param {string} dismissKey
   *   The popover's dismiss key.
   * @param {number} until
   *   Epoch milliseconds until the popover may show again.
   */
  function writeDismissal(dismissKey, until) {
    try {
      window.localStorage.setItem(
        STORAGE_PREFIX + dismissKey,
        JSON.stringify({ until }),
      );
    } catch (error) {
      // Without storage, for example in private mode, the popover simply
      // opens again on the next page view.
    }
  }

  /**
   * Whether a cookie with the given name exists.
   *
   * @param {string} name
   *   The cookie name.
   * @return {boolean}
   *   True if the cookie exists.
   */
  function hasCookie(name) {
    return document.cookie
      .split(';')
      .some((cookie) => cookie.trim().startsWith(`${name}=`));
  }

  /**
   * Whether the cookie consent banner or settings layer is showing.
   *
   * @return {boolean}
   *   True if the cookie consent UI is present.
   */
  function isCookieBannerVisible() {
    return document.querySelector(COOKIE_CONSENT_UI_SELECTOR) !== null;
  }

  /**
   * Shows a popover, unless the cookie consent UI is showing.
   *
   * cookiesjsr mounts its banner asynchronously, so it can also appear after
   * the popover opened. `watchCookieBanner()` handles that case.
   *
   * @param {HTMLElement} popover
   *   The popover element.
   */
  function openPopover(popover) {
    if (isCookieBannerVisible()) {
      return;
    }
    try {
      popover.showPopover();
    } catch (error) {
      // eslint-disable-next-line no-console
      console.warn('modal-trigger: showPopover() failed.', error);
    }
  }

  /**
   * Watches `#cookiesjsr` for the cookie consent UI appearing or disappearing.
   *
   * cookiesjsr has no events for this. When the UI appears, it closes an
   * open popover and flags it, so the `toggle` handler writes no cooldown.
   * Then `onSuppress` re-arms the trigger. When the UI disappears, `onHide`
   * runs the trigger again.
   *
   * A BigPipe placeholder can add the container after this behavior
   * attached. The body is then watched until the container exists.
   *
   * @param {HTMLElement} popover
   *   The popover element to guard.
   * @param {{onHide?: Function, onSuppress?: Function}} [handlers]
   *   Optional callbacks for the cookie UI disappearing and for a popover
   *   it force-closed.
   */
  function watchCookieBanner(popover, { onHide, onSuppress } = {}) {
    const observeContainer = (container) => {
      let visible = false;
      const update = () => {
        const isVisible = isCookieBannerVisible();
        if (isVisible === visible) {
          return;
        }
        visible = isVisible;
        if (isVisible) {
          if (popover.matches(':popover-open')) {
            popover.dataset.modalTriggerSuppressedByBanner = 'true';
            popover.hidePopover();
            onSuppress?.();
          }
          return;
        }
        onHide?.();
      };
      new MutationObserver(update).observe(container, {
        childList: true,
        subtree: true,
      });
      update();
    };

    const container = document.querySelector(COOKIE_BANNER_CONTAINER_SELECTOR);
    if (container) {
      observeContainer(container);
      return;
    }
    const bodyObserver = new MutationObserver(() => {
      const addedContainer = document.querySelector(
        COOKIE_BANNER_CONTAINER_SELECTOR,
      );
      if (addedContainer) {
        bodyObserver.disconnect();
        observeContainer(addedContainer);
      }
    });
    bodyObserver.observe(document.body, { childList: true, subtree: true });
  }

  // Time after the cookie UI closes before a scroll-mode popover opens, so
  // the visitor can reorient first.
  const SCROLL_REORIENT_BUFFER_MS = 1000;

  /**
   * Attaches the configured trigger (delay or scroll) to an anchor.
   *
   * A trigger that the cookie UI blocks or interrupts runs again after the
   * cookie UI closes, so the visitor does not lose the popover.
   *
   * - 'delay' mode starts the full delay again: the delay is a dwell time.
   * - 'scroll' mode opens when the anchor is in view and the cookie UI has
   *   been closed for `SCROLL_REORIENT_BUFFER_MS`. While consent is missing,
   *   it also waits until the page has scrolled past the scroll limit of
   *   cookiesjsr (`getScrollFloor()`). Otherwise the popover could open just
   *   before the banner appears on scroll.
   *
   * @param {HTMLElement} anchor
   *   The in-flow sentinel element.
   * @param {HTMLElement} popover
   *   The popover element it controls.
   * @return {{onHide: Function, onSuppress: Function}|undefined}
   *   Handlers for `watchCookieBanner()`, or undefined for an unsupported
   *   mode.
   */
  function attachTrigger(anchor, popover) {
    const { modalTriggerMode: mode, modalTriggerDelay: delay } = anchor.dataset;

    if (mode === 'delay') {
      const delaySeconds = Math.max(0, Number(delay) || 0);
      let opened = false;
      const fire = () => {
        window.setTimeout(() => {
          if (opened || isCookieBannerVisible()) {
            return;
          }
          opened = true;
          openPopover(popover);
        }, delaySeconds * 1000);
      };
      fire();
      return {
        onHide: () => {
          if (!opened) {
            fire();
          }
        },
        onSuppress: () => {
          opened = false;
        },
      };
    }

    if (mode !== 'scroll' || typeof IntersectionObserver !== 'function') {
      return undefined;
    }

    const cookiesConfig = drupalSettings?.cookies?.cookiesjsr?.config;
    const cookieScrollLimit = cookiesConfig?.library?.scrollLimit;
    const consentCookieName = cookiesConfig?.cookie?.name || 'cookiesjsr';

    /**
     * The scroll position the popover waits for before it opens.
     *
     * cookiesjsr shows its banner on scroll only while consent is missing,
     * and caps its scroll limit at the scrollable height of the page.
     *
     * @return {number}
     *   The scroll position in pixels.
     */
    function getScrollFloor() {
      if (
        typeof cookieScrollLimit !== 'number' ||
        hasCookie(consentCookieName)
      ) {
        return 0;
      }
      const scrollableHeight =
        document.documentElement.scrollHeight -
        document.documentElement.clientHeight;
      return Math.max(0, Math.min(cookieScrollLimit + 1, scrollableHeight));
    }

    let isAnchorVisible = false;
    let cookieClearedAt = isCookieBannerVisible() ? null : 0;
    let opened = false;
    let intersectionObserver;

    function tryOpen() {
      if (opened || !isAnchorVisible || window.scrollY < getScrollFloor()) {
        return;
      }
      if (isCookieBannerVisible() || cookieClearedAt === null) {
        return;
      }
      const elapsed = Date.now() - cookieClearedAt;
      if (elapsed < SCROLL_REORIENT_BUFFER_MS) {
        window.setTimeout(tryOpen, SCROLL_REORIENT_BUFFER_MS - elapsed);
        return;
      }
      opened = true;
      openPopover(popover);
      intersectionObserver.disconnect();
      window.removeEventListener('scroll', tryOpen);
    }

    intersectionObserver = new IntersectionObserver((entries) => {
      isAnchorVisible = entries.some((entry) => entry.isIntersecting);
      if (isAnchorVisible) {
        tryOpen();
      }
    });
    intersectionObserver.observe(anchor);
    window.addEventListener('scroll', tryOpen, { passive: true });

    return {
      onHide: () => {
        cookieClearedAt = Date.now();
        window.setTimeout(tryOpen, SCROLL_REORIENT_BUFFER_MS);
      },
      onSuppress: () => {
        opened = false;
        cookieClearedAt = null;
        intersectionObserver.observe(anchor);
        window.addEventListener('scroll', tryOpen, { passive: true });
      },
    };
  }

  Drupal.behaviors.modalTrigger = {
    attach(context) {
      once('modalTrigger', '[data-modal-trigger-popover]', context).forEach(
        (anchor) => {
          const popover = document.getElementById(
            anchor.dataset.modalTriggerPopover,
          );
          if (!popover || typeof popover.showPopover !== 'function') {
            return;
          }

          const {
            modalTriggerDismissKey: dismissKey,
            modalTriggerCooldownDays: cooldownDays,
          } = anchor.dataset;

          const dismissal = readDismissal(dismissKey);
          if (dismissal && dismissal.until > Date.now()) {
            return;
          }

          watchCookieBanner(popover, attachTrigger(anchor, popover));

          const dontShowAgainButton = popover.querySelector(
            '[data-modal-trigger-dont-show-again]',
          );
          if (dontShowAgainButton) {
            dontShowAgainButton.addEventListener('click', () => {
              popover.dataset.dismissPermanently = 'true';
              popover.hidePopover();
            });
          }

          // popover="manual" has no light dismiss, so a click on the cookie
          // banner never counts as a dismissal. Escape must still close it.
          document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && popover.matches(':popover-open')) {
              popover.hidePopover();
            }
          });

          popover.addEventListener('toggle', (event) => {
            if (event.newState !== 'closed') {
              return;
            }
            if (popover.dataset.modalTriggerSuppressedByBanner === 'true') {
              // Closed by the cookie UI, not by the visitor.
              delete popover.dataset.modalTriggerSuppressedByBanner;
              return;
            }
            if (popover.dataset.dismissPermanently === 'true') {
              writeDismissal(dismissKey, Date.now() + PERMANENT_DISMISS_MS);
              return;
            }
            const days = Number(cooldownDays) || 0;
            if (days > 0) {
              writeDismissal(
                dismissKey,
                Date.now() + days * 24 * 60 * 60 * 1000,
              );
            }
          });
        },
      );
    },
  };
})(Drupal, once, drupalSettings);
