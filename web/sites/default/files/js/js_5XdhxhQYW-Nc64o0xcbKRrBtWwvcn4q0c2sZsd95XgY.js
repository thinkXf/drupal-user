/* @license GPL-2.0-or-later https://www.drupal.org/licensing/faq */
(function($,Drupal,once){'use strict';Drupal.behaviors.webformSubmitTrigger={attach(context){$(once('webform-trigger-submit','[data-webform-trigger-submit]')).on('change',function(){var submit=$(this).attr('data-webform-trigger-submit');$(submit).trigger('mousedown');});}};})(jQuery,Drupal,once);;
