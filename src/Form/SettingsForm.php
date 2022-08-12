<?php

namespace Drupal\home_redirect_lang\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Homepage Redirection Language settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'home_redirect_lang_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'home_redirect_lang.browser_fallback',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $browser_config = $this->config('home_redirect_lang.browser_fallback');

    // Submitted form values should be nested.
    $form['#tree'] = TRUE;

    $form['browser_fallback'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Browser Fallback'),
    ];

    $form['browser_fallback']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Browser Fallback redirection.'),
      '#default_value' => !empty($browser_config->get('enable_browser_fallback')),
      '#description' => $this->t('Enable this feature in order to redirect a client upon its first visit. Offering their native content as default. This feature rely on the Browser Preferred language Header (Accept-Language).'),
    ];

    $form['browser_fallback']['enable_referer_bypass'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent redirecting when Referer is given.'),
      '#default_value' => !empty($browser_config->get('enable_referer_bypass')),
      '#description' => $this->t('When a person visit your homepage from another website (REFERER), you can hope the other website has referred the language URL of your website. Therefore you may want to disable redirection when a REFERER header is given to avoid unattended redirection.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('home_redirect_lang.browser_fallback')
      ->set('enable_referer_bypass', $form_state->getValue('browser_fallback')['enable_referer_bypass'])
      ->set('enable_browser_fallback', $form_state->getValue('browser_fallback')['enable'])
      ->save();
  }

}
