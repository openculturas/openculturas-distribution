To find out more about how to contribute to OpenCulturas, please also read <mark>[CONTRIBUTING.md]()</mark>

OpenCulturas is built as a truly free and open source software solution, standards-compliant, and free of 3rd-party dependencies that might conflict with those standards:


## Development principles

## Privacy by default

Avoid CDN/3rd-party calls (we advocate/endorse the strict European privacy laws), hence locally hosted fonts + OpenStreetMap

## Accessibility by default

* Everything is accessible and operatable by keyboard and assistive technologies
* Use given color variables (CSS custom properties) in good contrast combinations, they will automatically work in dark mode, too
* Respect the difference between links (leading to a different page) and buttons. The latter must be used to trigger functions. When not possible, add ARIA attributes and use a `.button` CSS class.
* Use generic HTML elements over Javascript enhancements.
* Use given unit variables, for text as well as for layout dimensions (respecting/inheriting user's pre-set font size)

## Site-builder friendly

* (Re-)use given solutions for similar tasks
  * Only introduce a new module to build new functionality
  * Re-use fields whenever this makes sense
  * Build view modes, form modes, widgets the same way as the given ones (user experience is based on principles the users have already learned about a given platform)
* When introducing a field, give helpful information in the description. If the label is self-explaining, do not add a description.
* Avoid template-overrides wherever possible, prefer UI settings over code

## English first

* Build english first and make any text string translatable (even when it's visually hidden)
* Comment your code

## Freedom of choice: avoid dependencies

This is especially important when a 3rd-party dependency does not conform to our development principles.

* Avoid technology dependencies beyond Drupal
* Introduce 3rd-party library dependencies as optional when possible (unless you are willing to maintain the updates, too)

## Contributions welcome

When you follow these principles there's a high chance that what you developed qualifies for integration in the OpenCulturas software.
When reviewing contributions, our focus is on _generic_ solutions that all or most of the OpenCulturas site owners can potentially benefit from
(just like you are benefiting from solutions that others have made available for you).
Please check out [CONTRIBUTING.md](CONTRIBUTING.md).
