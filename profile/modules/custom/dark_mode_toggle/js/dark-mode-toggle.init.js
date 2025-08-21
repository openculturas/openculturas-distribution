/**
 * @file
 * Use a previously stored value or OS preference to set initial dark mode.
 *
 * This should be loaded in the header to avoid FOUC and uses the previously
 * stored value of the local storage. When not set, the OS preference is used to
 * set the initial dark mode.
 */

(() => {
  const darkMode = localStorage.getItem('dark-mode');
  const isDarkMode =
    darkMode === 'dark' ||
    (!darkMode && window.matchMedia('(prefers-color-scheme: dark)').matches);

  // Use the 'dark' class to set the initial dark mode.
  document.documentElement.classList.toggle('dark', isDarkMode);
  if (isDarkMode && drupalSettings?.gin?.darkmode_class) {
    document.documentElement.classList.add(drupalSettings?.gin?.darkmode_class);
  }
  // The 'data-dark-mode-source' attribute is used to indicate the source of the
  // dark mode. It can be 'user' when the user explicitly chooses the dark mode,
  // or 'system' when the system preference is used.
  document.documentElement.setAttribute(
    'data-dark-mode-source',
    darkMode ? 'user' : 'system',
  );
})();
