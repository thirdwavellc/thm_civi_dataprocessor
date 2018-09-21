<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

namespace Civi\DataProcessor\Source;

use Civi\DataProcessor\DataFlow\SqlDataFlow\SimpleWhereClause;
use Civi\DataProcessor\DataFlow\SqlTableDataFlow;
use Civi\DataProcessor\DataSpecification\DataSpecification;
use Civi\DataProcessor\DataSpecification\FieldSpecification;

use CRM_Dataprocessor_ExtensionUtil as E;

class RelationshipSource extends AbstractCivicrmEntitySource {

  /**
   * Returns the entity name
   *
   * @return String
   */
  protected function getEntity() {
    return 'Relationship';
  }

  /**
   * Returns the table name of this entity
   *
   * @return String
   */
  protected function getTable() {
    return 'civicrm_relationship';
  }

  /**
   * @return \Civi\DataProcessor\DataSpecification\DataSpecification
   * @throws \Exception
   */
  public function getAvailableFilterFields() {
    if (!$this->availableFilterFields) {
      $this->availableFilterFields = new DataSpecification();

      $alias = $this->getSourceName(). '_relationship_type_id';
      $options = array();
      $relationship_types = civicrm_api3('RelationshipType', 'get', array('options' => array('limit' => 0)));
      foreach($relationship_types['values'] as $rel_type) {
        $options[$rel_type['id']] = $rel_type['label_a_b'];
      }
      $fieldSpec = new FieldSpecification('relationship_type_id', 'Integer', E::ts('Relationship type'), $options, $alias);
      $this->availableFilterFields->addFieldSpecification($fieldSpec->name, $fieldSpec);

      $this->loadFields($this->availableFilterFields, array('relationship_type_id'));
      $this->loadCustomGroupsAndFields($this->availableFilterFields, true);
    }
    return $this->availableFilterFields;
  }

}