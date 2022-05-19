/**
 * @file
 * Example JS file
 *
 */

//   /**
//    * Example behavior (no jQuery).
//    *
//    * @see https://www.drupal.org/docs/drupal-apis/javascript-api/javascript-api-overview#s-drupalbehaviors
//    */
//   Drupal.behaviors.myBehavior = {
//     attach: function (context, settings) {
//       //Using once() to apply the myCustomBehaviour effect when you want to run just one function.
//       once('myCustomBehavior', 'input.myCustomBehavior', context).forEach(function (element) {
//         element.classList.add('processed');
//       });
//     }
//   };

// /**
//  * Example behavior (oldschool).
//  *
//  * @see https://www.drupal.org/node/2269515/revisions/12246254/view
//  */
// (function ($, Drupal) {
//
// Drupal.behaviors.myBehavior = {
//   attach: function (context, settings) {
//     // Using once() to apply the myCustomBehaviour effect when you want to run just one function.
//     $('input.myCustomBehavior', context).once('myCustomBehavior').addClass('processed');
//
//     // Using once() with more complexity.
//     $('input.myCustom', context).once('mySecondBehavior').each(function () {
//       if ($(this).visible()) {
//         $(this).css('background', 'green');
//       }
//       else {
//         $(this).css('background', 'yellow').show();
//       }
//     });
//   }
// };
// } (jQuery, Drupal));
