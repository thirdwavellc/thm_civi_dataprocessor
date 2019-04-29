<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

use CRM_Dataprocessor_ExtensionUtil as E;

abstract class CRM_Dataprocessor_Form_Output_AbstractUIOutputForm extends CRM_Core_Form_Search {

  /**
   * @var \Civi\DataProcessor\ProcessorType\AbstractProcessorType;
   */
  protected $dataProcessor;

  /**
   * @var int
   */
  protected $dataProcessorId;

  /**
   * @var array
   */
  protected $dataProcessorBAO;

  /**
   * @var \CRM_Dataprocessor_BAO_Output
   */
  protected $dataProcessorOutput;

  /**
   * Return the data processor ID
   *
   * @return String
   */
  abstract protected function getDataProcessorName();

  /**
   * Returns the name of the output for this search
   *
   * @return string
   */
  abstract protected function getOutputName();

  /**
   * Checks whether the output has a valid configuration
   *
   * @return bool
   */
  abstract protected function isConfigurationValid();

  public function preProcess() {
    parent::preProcess();
    $this->loadDataProcessor();
  }

  /**
   * Retrieve the data processor and the output configuration
   *
   * @throws \Exception
   */
  protected function loadDataProcessor() {
    if (!$this->dataProcessorId) {
      $dataProcessorName = $this->getDataProcessorName();
      $sql = "
        SELECT civicrm_data_processor.id as data_processor_id,  civicrm_data_processor_output.id AS output_id
        FROM civicrm_data_processor 
        INNER JOIN civicrm_data_processor_output ON civicrm_data_processor.id = civicrm_data_processor_output.data_processor_id
        WHERE is_active = 1 AND civicrm_data_processor.name = %1 AND civicrm_data_processor_output.type = %2
      ";
      $params[1] = [$dataProcessorName, 'String'];
      $params[2] = [$this->getOutputName(), 'String'];
      $dao = CRM_Dataprocessor_BAO_DataProcessor::executeQuery($sql, $params, TRUE, 'CRM_Dataprocessor_BAO_DataProcessor');
      if (!$dao->fetch()) {
        throw new \Exception('Could not find Data Processor "' . $dataProcessorName.'"');
      }
      $this->dataProcessor = CRM_Dataprocessor_BAO_DataProcessor::getDataProcessorById($dao->data_processor_id);
      $this->dataProcessorId = $dao->data_processor_id;

      $dataProcessorBAO = CRM_Dataprocessor_BAO_DataProcessor::getValues(array('id' => $this->dataProcessorId));
      $this->dataProcessorBAO = $dataProcessorBAO[$this->dataProcessorId];

      $output = CRM_Dataprocessor_BAO_Output::getValues(['id' => $dao->output_id]);
      $this->dataProcessorOutput = $output[$dao->output_id];
      $this->assign('output', $this->dataProcessorOutput);

      if (!$this->isConfigurationValid()) {
        throw new \Exception('Invalid configuration found of the data processor "' . $dataProcessorName . '"');
      }
    }
  }

  /**
   * Returns whether the search has required filters.
   *
   * @return bool
   */
  protected function hasRequiredFilters() {
    if ($this->dataProcessor->getFilterHandlers()) {
      foreach ($this->dataProcessor->getFilterHandlers() as $filter) {
        if ($filter->isRequired()) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * Validate the filters
   *
   * @return array
   */
  protected function validateFilters() {
    $errors = array();
    if ($this->dataProcessor->getFilterHandlers()) {
      foreach ($this->dataProcessor->getFilterHandlers() as $filter) {
        $errors = array_merge($errors, $filter->validateSubmittedFilterParams($this->_formValues));
      }
    }
    return $errors;
  }

  /**
   * Apply the filters to the database processor
   *
   * @throws \Exception
   */
  public static function applyFilters(\Civi\DataProcessor\ProcessorType\AbstractProcessorType $dataProcessor, $submittedValues) {
    if ($dataProcessor->getFilterHandlers()) {
      foreach ($dataProcessor->getFilterHandlers() as $filter) {
       $filter->applyFilterFromSubmittedFilterParams($submittedValues);
      }
    }
  }

  /**
   * Build the criteria form
   */
  protected function buildCriteriaForm() {
    $filterElements = array();
    if ($this->dataProcessor->getFilterHandlers()) {
      foreach ($this->dataProcessor->getFilterHandlers() as $filterHandler) {
        $fieldSpec = $filterHandler->getFieldSpecification();
        if (!$fieldSpec) {
          continue;
        }
        $filterElements[$fieldSpec->alias]['filter'] = $filterHandler->addToFilterForm($this);
        $filterElements[$fieldSpec->alias]['template'] = $filterHandler->getTemplateFileName();
      }
      $this->assign('filters', $filterElements);
    }
  }

}