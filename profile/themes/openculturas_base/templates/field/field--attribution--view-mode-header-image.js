(function (Drupal, once) {

    /**
     * Opening / Closing logic for attribution bubble.
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.pageAttributionBubble = { // Define Drupal Behaviour
      attributionBubbleToggleSelector: 'button.attribution--bubble--toggle', // Define Selector for Toggle Button
      attributionBubbleDialogSelector: 'dialog.attribution--bubble--dialog', // Define Selector for Dialog
  
      // Drupal base function, runs when the behaviour is attached
      attach: function (context, settings) {
  
        // Attach a click event listener to all toggle buttons once
        once('init-attribution-bubble', this.attributionBubbleToggleSelector, context).forEach((attributionBubbleToggle) => {
          attributionBubbleToggle.addEventListener('click', (event) => {
            this.attributionBubbleToggle(event);
          })
        });
  
        // Attach a click event listener to the body once
        once('init-attribution-bubble-backdrop', 'body', context).forEach((body) => {
          body.addEventListener('click', (event) => {
            this.attributionBubbleBackdrop(event);
          })
  
          // Add an event listener to the document
          document.addEventListener('keydown', (event) => {
            this.attributionBubbleKeypress(event);
          })
        });
  
      },
  
      // Open or close the dialog, runs when clicking on the toggle button
      attributionBubbleToggle: function (event) {
        // Define the wrapping div element around button and dialog
        const attributionBubbleElement = event.target.parentNode;
        // Define the toggle button element within the wrapping div
        const attributionBubbleToggleElement = attributionBubbleElement.querySelector(this.attributionBubbleToggleSelector);
        // Define the dialog element within the wrapping div
        const attributionBubbleDialogElement = attributionBubbleElement.querySelector(this.attributionBubbleDialogSelector);
  
        // Abort if the event or any of the elements was not found
        if(!event || !attributionBubbleElement || !attributionBubbleToggleElement || !attributionBubbleDialogElement) {
          return;
        }
  
        // Check if dialog is open
        if(attributionBubbleDialogElement.open) {
          // Dialog is Open => close the dialog and remove the active attribute on the button
          delete attributionBubbleToggleElement.dataset.active;
          attributionBubbleDialogElement.close();
        } else {
          // Dialog is Closed => open the dialog and add the active attribute on the button
          attributionBubbleToggleElement.dataset.active = '';
          attributionBubbleDialogElement.show();
        }
  
      },
  
      attributionBubbleBackdrop: function (event) {
        // Abort if no event was found
        if(!event) {
          return;
        }
        // Check whether event target (click) is neither in a button or dialog element
        const isBackdrop = !(event.target.closest(this.attributionBubbleToggleSelector)) && !(event.target.closest(this.attributionBubbleDialogSelector));
        if(isBackdrop) {
          // Event target (click) is neither in a button or dialog element => cleanup dialogs
          this.attributionBubbleCleanup();
        }
      },

        // Abort if no event was found or the event does not contain a keypress
        // Check whether the event key pressed is escape key
        // if the escape key is pressed, call the cleanup function
      attributionBubbleKeypress: function (event) {
        if (!event || !event.key) {
            return;
        }
        const keyName = event.key;
        if(keyName === 'Escape') {
            this.attributionBubbleCleanup();
        }
      },
  
      // Close all open dialogs, runs when clicking anywhere but the buttons or the dialogs
      attributionBubbleCleanup: function () {
        // Close all open dialogs
        document.querySelectorAll(this.attributionBubbleDialogSelector + '[open]').forEach((attributionBubbleDialogElement) => {
          attributionBubbleDialogElement.close();
        });
  
        // Remove all active attributes on the buttons
        document.querySelectorAll(this.attributionBubbleToggleSelector + '[data-active]').forEach((attributionBubbleToggleElement) => {
          delete attributionBubbleToggleElement.dataset.active;
        });
      }
    };
  
  } (Drupal, once));