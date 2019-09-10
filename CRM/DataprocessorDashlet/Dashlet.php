<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

use \CRM_Dataprocessor_ExtensionUtil as E;

class CRM_DataprocessorDashlet_Dashlet implements Civi\DataProcessor\Output\OutputInterface {

  /**
   * Returns true when this output has additional configuration
   *
   * @return bool
   */
  public function hasConfiguration() {
    return true;
  }

  /**
   * When this output type has additional configuration you can add
   * the fields on the form with this function.
   *
   * @param \CRM_Core_Form $form
   * @param array $output
   */
  public function buildConfigurationForm(\CRM_Core_Form $form, $output = []) {
    $form->add('text', 'title', E::ts('Title'), true);
    $form->add('select','permission', E::ts('Permission'), \CRM_Core_Permission::basicPermissions(), true, array(
      'style' => 'min-width:250px',
      'class' => 'crm-select2 huge',
      'placeholder' => E::ts('- select -'),
    ));
    $form->add('text', 'default_limit', E::ts('Default Limit'));
    $form->add('wysiwyg', 'help_text', E::ts('Help text for this search'), array('rows' => 6, 'cols' => 80));

    $defaults = array();
    if ($output) {
      if (isset($output['permission'])) {
        $defaults['permission'] = $output['permission'];
      }
      if (isset($output['configuration']) && is_array($output['configuration'])) {
        if (isset($output['configuration']['title'])) {
          $defaults['title'] = $output['configuration']['title'];
        }
        if (isset($output['configuration']['default_limit'])) {
          $defaults['default_limit'] = $output['configuration']['default_limit'];
        }
        if (isset($output['configuration']['help_text'])) {
          $defaults['help_text'] = $output['configuration']['help_text'];
        }
      }
    }
    if (!isset($defaults['permission'])) {
      $defaults['permission'] = 'access CiviCRM';
    }
    if (empty($defaults['title'])) {
      $defaults['title'] = civicrm_api3('DataProcessor', 'getvalue', array('id' => $output['data_processor_id'], 'return' => 'title'));
    }
    if (empty($defaults['default_limit'])) {
      $defaults['default_limit'] = 10;
    }
    $form->setDefaults($defaults);
  }

  /**
   * When this output type has configuration specify the template file name
   * for the configuration form.
   *
   * @return false|string
   */
  public function getConfigurationTemplateFileName() {
    return "CRM/DataprocessorDashlet/Form/OutputConfiguration/Dashlet.tpl";
  }

  /**
   * Process the submitted values and create a configuration array
   *
   * @param $submittedValues
   * @param array $output
   *
   * @return array $output
   * @throws \Exception
   */
  public function processConfiguration($submittedValues, &$output) {
    $dataProcessor = civicrm_api3('DataProcessor', 'getsingle', array('id' => $output['data_processor_id']));
    $dashletName = 'dataprocessor_'.$dataProcessor['name'];
    $dashletUrl = \CRM_Utils_System::url('civicrm/dataprocessor/page/dashlet', array('data_processor' => $dataProcessor['name']));
    $fullScreenUrl = \CRM_Utils_System::url('civicrm/dataprocessor/page/dashlet', array('data_processor' => $dataProcessor['name'], 'context' => 'dashletFullscreen'));
    $dashletParams['url'] = $dashletUrl;
    $dashletParams['fullscreen_url'] = $fullScreenUrl;
    $dashletParams['name'] = $dashletName;
    $dashletParams['label'] = $submittedValues['title'];
    $dashletParams['permission'] = $submittedValues['permission'];
    $dashletParams['is_active'] = 1;
    $dashletParams['cache_minutes'] = 60;

    try {
      $id = civicrm_api3('Dashboard', 'getvalue', ['name' => $dashletName, 'return' => 'id']);
      if ($id) {
        $dashletParams['id'] = $id;
      }
    } catch (\Exception $e) {
      // Do nothing
    }

    civicrm_api3('Dashboard', 'create', $dashletParams);

    $output['permission'] = $submittedValues['permission'];
    $configuration['title'] = $submittedValues['title'];
    $configuration['default_limit'] = $submittedValues['default_limit'];
    $configuration['help_text'] = $submittedValues['help_text'];
    return $configuration;
  }

  /**
   * This function is called prior to removing an output
   *
   * @param array $output
   * @return void
   * @throws \Exception
   */
  public function deleteOutput($output) {
    $dataProcessor = civicrm_api3('DataProcessor', 'getsingle', array('id' => $output['data_processor_id']));
    $dashletName = 'dataprocessor_'.$dataProcessor['name'];
    $dashlets = civicrm_api3('Dashboard', 'get', [
      'name' => $dashletName,
      'options' => ['limit' => 0]
    ]);
    foreach ($dashlets['values'] as $dashlet) {
      try {
        civicrm_api3('Dashlet', 'delete', ['id' => $dashlet['id']]);
      } catch (\Exception $e) {
        // Do nothing
      }
    }
  }


}
