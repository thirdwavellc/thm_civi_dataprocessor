<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\DataProcessor\DataSpecification;

class DataSpecification {

  /**
   * @var <FieldSpecification> array
   */
  protected $fields = array();

  public function __construct($fields=array()) {
    foreach($fields as $field) {
      $this->addFieldSpecification($field->name, $field);
    }
  }

  /**
   * Add a field specification
   *
   * @param String $name
   *   The identifier for this field
   * @param \Civi\DataProcessor\DataSpecification\FieldSpecification $field
   * @return \Civi\DataProcessor\DataSpecification\DataSpecification
   * @throws \Civi\DataProcessor\DataSpecification\FieldExistsException
   */
  public function addFieldSpecification($name, FieldSpecification $field) {
    if (isset($this->fields[$name])) {
      throw new FieldExistsException($name);
    }
    $this->fields[$name] = $field;
    return $this;
  }

  /**
   * @return \Civi\DataProcessor\DataSpecification\FieldSpecification[]
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * @param string
   * @return \Civi\DataProcessor\DataSpecification\FieldSpecification
   */
  public function getFieldSpecificationByName($name) {
    return $this->fields[$name];
  }

  /**
   * Returns whether a field exists
   *
   * @param $name
   * @return bool
   */
  public function doesFieldExist($name) {
    if (isset($this->fields[$name])) {
      return true;
    }
    return false;
  }

  /**
   * Merge with another dataspecification.
   *
   * @param \Civi\DataProcessor\DataSpecification\DataSpecification $dataSpecification
   * @param string $prefix
   * @return \Civi\DataProcessor\DataSpecification\DataSpecification
   * @throws \Civi\DataProcessor\DataSpecification\FieldExistsException
   */
  public function merge(DataSpecification $dataSpecification, $prefix='') {
    foreach($dataSpecification->getFields() as $field) {
      $f = clone $field;
      $f->name = $prefix.$field->name;
      $this->addFieldSpecification($f->name, $f);
    }
    return $this;
  }

}